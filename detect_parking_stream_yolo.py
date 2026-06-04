# detect_parking_stream_yolo.py
# Streaming video + YOLO (best.pt) untuk hitung empty / occupied

from flask import Flask, Response
from ultralytics import YOLO
import cv2
import mysql.connector
import numpy as np
import threading
import time
from datetime import datetime
from pathlib import Path

app = Flask(__name__)

# ==========================================================
# 1. KONFIGURASI PATH
# ==========================================================
BASE_DIR   = Path(__file__).resolve().parent
MODEL_PATH = BASE_DIR / "best.pt"
VIDEO_DIR  = BASE_DIR / "uploads" / "parking_videos"

# ==========================================================
# 2. KONEKSI DATABASE
# ==========================================================
try:
    db = mysql.connector.connect(
        host="localhost",
        user="root",
        password="",
        database="car_park_db"
    )
    cursor = db.cursor(dictionary=True)
    print("✅ Terhubung ke database car_park_db")
except Exception as e:
    print("❌ Gagal konek DB:", e)
    db = None
    cursor = None

# ==========================================================
# 3. LOAD MODEL YOLO
# ==========================================================
print("🔄 Loading YOLO model dari:", MODEL_PATH)
if not MODEL_PATH.exists():
    raise RuntimeError(f"Model YOLO tidak ditemukan di: {MODEL_PATH}")

model = YOLO(str(MODEL_PATH))
print("✅ Model loaded")

# Asumsi: class 0 = empty, 1 = occupied
names = model.model.names
print("📌 Class names:", names)

EMPTY_CLASS_NAMES = {"empty", "Empty", "EMPTY"}
OCC_CLASS_NAMES   = {"occupied", "Occupied", "OCC"}

# ID class yang dianggap occupied (diprioritaskan)
OCC_CLASS_IDS = {cid for cid, name in names.items() if name in OCC_CLASS_NAMES}

# ==========================================================
# 4. FUNGSI CARI VIDEO TERBARU
# ==========================================================
def get_latest_video():
    VIDEO_DIR.mkdir(parents=True, exist_ok=True)
    files = [
        f for f in VIDEO_DIR.iterdir()
        if f.suffix.lower() in [".mp4", ".avi", ".mov", ".mkv"]
    ]
    if not files:
        return None
    files_sorted = sorted(files, key=lambda p: p.stat().st_mtime)
    return str(files_sorted[-1])

current_video_path = None
cap = None

# ==========================================================
# 5. VARIABEL GLOBAL
# ==========================================================
frame_lock = threading.Lock()
current_frame = None
current_empty = 0
current_occ = 0
last_db_insert = 0.0

DB_INSERT_INTERVAL = 5.0  # detik

# tracking sederhana supaya box stabil
tracks = []          # list of dict: {box, cls_id, ttl}
TRACK_TTL = 5        # frame; makin besar makin stabil tapi lambat adaptasi

# ==========================================================
# 6. SIMPAN DB
# ==========================================================
def save_to_db(empty_count: int, occ_count: int):
    global last_db_insert
    if db is None or cursor is None:
        return

    now = time.time()
    if now - last_db_insert < DB_INSERT_INTERVAL:
        return

    try:
        sql = """
            INSERT INTO parking_video_history (empty_count, occupied_count, created_at)
            VALUES (%s, %s, %s)
        """
        cursor.execute(sql, (empty_count, occ_count, datetime.now()))
        db.commit()
        last_db_insert = now
        print(f"💾 DB log -> empty={empty_count}, occ={occ_count}")
    except Exception as e:
        print("⚠️ Gagal insert parking_video_history:", e)

# ==========================================================
# 7. HELPER: IOU, ROI, DUPLICATE & TRACKING
# ==========================================================
def box_iou(b1, b2):
    # b1,b2: [x1,y1,x2,y2]
    xA = max(b1[0], b2[0])
    yA = max(b1[1], b2[1])
    xB = min(b1[2], b2[2])
    yB = min(b1[3], b2[3])

    inter_w = max(0, xB - xA)
    inter_h = max(0, yB - yA)
    inter = inter_w * inter_h
    if inter <= 0:
        return 0.0

    area1 = (b1[2] - b1[0]) * (b1[3] - b1[1])
    area2 = (b2[2] - b2[0]) * (b2[3] - b2[1])
    return inter / float(area1 + area2 - inter)


# === ROI BARU: HANYA AREA PARKIR, DILEBARKAN KIRI/TENGAH/KANAN + CADANGAN ===
def is_in_parking_roi(cx, cy, w_img, h_img):
    """
    ROI pakai rasio 0..1 dari lebar & tinggi gambar.
    Dibuat agak lebar supaya slot kiri tidak terpotong tapi
    tetap membatasi ke area parkir saja.
    """
    x = cx / w_img
    y = cy / h_img

    # LEFT COLUMN — dilebarkan sampai pinggir kiri
    if 0.00 <= x <= 0.27 and 0.10 <= y <= 0.95:
        return True

    # MIDDLE COLUMN — sedikit lebar
    if 0.28 <= x <= 0.62 and 0.10 <= y <= 0.95:
        return True

    # RIGHT COLUMN — sampai hampir pinggir kanan
    if 0.63 <= x <= 0.99 and 0.10 <= y <= 0.95:
        return True

    # Cadangan: jika masih dalam area tengah gambar, tetap izinkan
    # (anti miss untuk slot yang kepotong tipis)
    if 0.05 < x < 0.95 and 0.08 < y < 0.98:
        return True

    return False


# === DUPLICATE FILTER: LEMBUT, HANYA BOX YANG BENAR-BENAR SAMA ===
def is_duplicate_box(b, ref):
    """
    Duplikat kalau:
    - IoU cukup tinggi (> 0.65), DAN
    - pusat X & Y sangat dekat (beda < 0.25 ukuran box).
    Jadi slot tetangga tidak digabung.
    """
    iou = box_iou(b, ref)
    if iou <= 0.65:
        return False

    cx1 = (b[0] + b[2]) / 2.0
    cy1 = (b[1] + b[3]) / 2.0
    cx2 = (ref[0] + ref[2]) / 2.0
    cy2 = (ref[1] + ref[3]) / 2.0

    dx = abs(cx1 - cx2)
    dy = abs(cy1 - cy2)

    w  = ref[2] - ref[0]
    h  = ref[3] - ref[1]

    return (dx < 0.25 * w) and (dy < 0.25 * h)


def update_tracks(dets_boxes, dets_cls):
    """
    Tracking sangat sederhana: match ke track lama pakai IoU,
    kalau hilang 1–2 frame tetap ditampilkan (TTL).
    """
    global tracks

    new_tracks = []

    # kurangi TTL semua track lama
    for tr in tracks:
        tr["ttl"] -= 1
        if tr["ttl"] > 0:
            new_tracks.append(tr)
    tracks = new_tracks

    # match deteksi baru ke track yang ada
    for box, cid in zip(dets_boxes, dets_cls):
        best_iou = 0.0
        best_idx = -1
        for i, tr in enumerate(tracks):
            iou = box_iou(box, tr["box"])
            if iou > best_iou:
                best_iou = iou
                best_idx = i

        if best_iou > 0.5:
            # update track lama
            tracks[best_idx]["box"] = box
            tracks[best_idx]["cls_id"] = cid
            tracks[best_idx]["ttl"] = TRACK_TTL
        else:
            # buat track baru
            tracks.append({
                "box": box,
                "cls_id": cid,
                "ttl": TRACK_TTL
            })


# ==========================================================
# 8. LOOP VIDEO + YOLO
# ==========================================================
def process_video():
    global current_frame, current_empty, current_occ
    global cap, current_video_path, tracks

    while True:
        latest = get_latest_video()
        if latest is None:
            img = 255 * np.ones((480, 640, 3), dtype="uint8")
            cv2.putText(
                img, "No video in uploads/parking_videos",
                (30, 240),
                cv2.FONT_HERSHEY_SIMPLEX, 0.8,
                (0, 0, 255), 2
            )
            with frame_lock:
                current_frame = img
            time.sleep(1.0)
            continue

        if latest != current_video_path:
            print(f"🎥 Switch video ke: {latest}")
            current_video_path = latest

            if cap is not None:
                cap.release()
                cap = None

            cap = cv2.VideoCapture(current_video_path)
            if not cap.isOpened():
                print("❌ Gagal membuka video:", current_video_path)
                time.sleep(1.0)
                continue

        success, img = cap.read()
        if not success:
            print("ℹ️ Video selesai, cek video baru...")
            time.sleep(0.5)
            continue

        h, w = img.shape[:2]

        # ========= YOLO PREDICT =========
        results = model.predict(
            source=img,
            imgsz=960,
            conf=0.30,      # agak ketat biar box lebih rapi
            iou=0.70,
            classes=[0, 1],
            agnostic_nms=True,
            max_det=250,
            verbose=False
        )
        if not results:
            continue

        res = results[0]

        # Ambil boxes, cls, conf ke numpy
        boxes_xyxy = res.boxes.xyxy.cpu().numpy()
        cls_ids    = res.boxes.cls.cpu().numpy().astype(int)
        confs      = res.boxes.conf.cpu().numpy()

        if boxes_xyxy.size == 0:
            with frame_lock:
                current_frame = img.copy()
                current_empty = 0
                current_occ   = 0
            continue

        # ========= FILTER ROI: hanya area parkir =========
        centers_x = (boxes_xyxy[:, 0] + boxes_xyxy[:, 2]) / 2.0
        centers_y = (boxes_xyxy[:, 1] + boxes_xyxy[:, 3]) / 2.0

        roi_keep = np.array([
            is_in_parking_roi(cx, cy, w, h)
            for cx, cy in zip(centers_x, centers_y)
        ])

        boxes_xyxy = boxes_xyxy[roi_keep]
        cls_ids    = cls_ids[roi_keep]
        confs      = confs[roi_keep]

        if boxes_xyxy.size == 0:
            with frame_lock:
                current_frame = img.copy()
                current_empty = 0
                current_occ   = 0
            continue

        # ========= BUANG BOX YANG TERLALU KECIL / TERLALU TINGGI =========
        widths  = boxes_xyxy[:, 2] - boxes_xyxy[:, 0]
        heights = boxes_xyxy[:, 3] - boxes_xyxy[:, 1]
        areas   = widths * heights

        min_area = 0.00045 * w * h      # buang noise kecil
        keep_area = areas > min_area

        # aspect ratio filter: buang box sangat tinggi (jalur penuh)
        aspect = heights / (widths + 1e-6)
        med_h = np.median(heights)
        keep_shape = (aspect < 3.5) & (heights < 1.7 * med_h)

        keep_mask = keep_area & keep_shape

        boxes_xyxy = boxes_xyxy[keep_mask]
        cls_ids    = cls_ids[keep_mask]
        confs      = confs[keep_mask]

        if boxes_xyxy.size == 0:
            with frame_lock:
                current_frame = img.copy()
                current_empty = 0
                current_occ   = 0
            continue

        # ========= FILTER DUPLIKAT DI FRAME INI =========
        bonus_occ = np.array([0.18 if cid in OCC_CLASS_IDS else 0.0 for cid in cls_ids])
        priority  = confs + bonus_occ

        order = np.argsort(-priority)     # urut dari skor tertinggi
        keep_indices = []

        for idx in order:
            b = boxes_xyxy[idx]
            dup = False
            for kept in keep_indices:
                if is_duplicate_box(b, boxes_xyxy[kept]):
                    dup = True
                    break
            if not dup:
                keep_indices.append(idx)

        det_boxes = boxes_xyxy[keep_indices]
        det_cls   = cls_ids[keep_indices]

        # ========= UPDATE TRACKING (SUPAYA STABIL) =========
        update_tracks(det_boxes, det_cls)

        # gunakan track aktif untuk digambar
        draw_boxes = []
        draw_cls   = []
        for tr in tracks:
            if tr["ttl"] > 0:
                draw_boxes.append(tr["box"])
                draw_cls.append(tr["cls_id"])

        draw_boxes = np.array(draw_boxes)
        draw_cls   = np.array(draw_cls, dtype=int)

        # hitung jumlah dari track
        empty_count = 0
        occ_count   = 0

        for box, cid in zip(draw_boxes, draw_cls):
            x1, y1, x2, y2 = box.astype(int)
            cls_name = names.get(int(cid), str(int(cid)))

            if cls_name in EMPTY_CLASS_NAMES:
                color = (255, 0, 0)
                empty_count += 1
                label = "empty"
            elif cls_name in OCC_CLASS_NAMES:
                color = (0, 255, 255)
                occ_count += 1
                label = "occupied"
            else:
                color = (255, 255, 255)
                label = cls_name

            cv2.rectangle(img, (x1, y1), (x2, y2), color, 2)
            cv2.putText(
                img, label, (x1, max(20, y1 - 5)),
                cv2.FONT_HERSHEY_SIMPLEX, 0.6, color, 2
            )

        total_slots = empty_count + occ_count
        info_text = f"Free: {empty_count} / {total_slots} | Occupied: {occ_count}"
        cv2.putText(
            img, info_text, (30, 40),
            cv2.FONT_HERSHEY_SIMPLEX, 1, (0, 255, 255), 3
        )

        with frame_lock:
            current_frame = img.copy()
            current_empty = empty_count
            current_occ   = occ_count

        save_to_db(empty_count, occ_count)
        time.sleep(0.05)

# ==========================================================
# 9. STREAMING FLASK
# ==========================================================
def generate_frames():
    global current_frame
    while True:
        if current_frame is None:
            time.sleep(0.01)
            continue

        with frame_lock:
            ret, buffer = cv2.imencode(".jpg", current_frame)
        if not ret:
            continue

        frame = buffer.tobytes()
        yield (
            b"--frame\r\n"
            b"Content-Type: image/jpeg\r\n\r\n" +
            frame + b"\r\n"
        )

@app.route("/video_feed")
def video_feed():
    return Response(
        generate_frames(),
        mimetype="multipart/x-mixed-replace; boundary=frame"
    )

@app.route("/stats")
def stats():
    return {
        "empty": current_empty,
        "occupied": current_occ,
        "total": current_empty + current_occ
    }

# ==========================================================
# 10. MAIN
# ==========================================================
if __name__ == "__main__":
    threading.Thread(target=process_video, daemon=True).start()
    print("🚀 Flask stream running at http://localhost:5001/video_feed")
    app.run(host="0.0.0.0", port=5001, debug=False)
