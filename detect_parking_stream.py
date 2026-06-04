# detect_parking_stream_yolo.py
# Streaming video + YOLO (best.pt) untuk hitung empty / occupied

from flask import Flask, Response
from ultralytics import YOLO
import cv2
import mysql.connector
import numpy as np
import threading
import time
import os
from datetime import datetime

app = Flask(__name__)

# ==========================================================
# 1. KONFIGURASI PATH
# ==========================================================
BASE_DIR   = os.path.dirname(os.path.abspath(__file__))
MODEL_PATH = os.path.join(BASE_DIR, "best.pt")  # pastikan nama file benar
VIDEO_DIR  = os.path.join(BASE_DIR, "uploads", "parking_videos")

# ==========================================================
# 2. KONEKSI DATABASE (sesuaikan kalau perlu)
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
model = YOLO(MODEL_PATH)
print("✅ Model loaded")

# Kita asumsikan class names di best.pt: 0 = empty, 1 = occupied
names = model.model.names  # dict: {0: 'empty', 1: 'occupied'}
print("📌 Class names:", names)

EMPTY_CLASS_NAMES = {"empty", "Empty", "EMPTY"}       # biar aman
OCC_CLASS_NAMES   = {"occupied", "Occupied", "OCC"}   # sesuaikan kalau beda

# ==========================================================
# 4. PILIH VIDEO TERBARU
# ==========================================================
if not os.path.isdir(VIDEO_DIR):
    os.makedirs(VIDEO_DIR, exist_ok=True)

video_files = [
    f for f in os.listdir(VIDEO_DIR)
    if f.lower().endswith((".mp4", ".avi", ".mov", ".mkv"))
]

if not video_files:
    raise RuntimeError(
        f"Tidak ada file video di folder: {VIDEO_DIR}. "
        "Upload dulu dari parking_monitoring.php."
    )

video_files.sort()
video_path = os.path.join(VIDEO_DIR, video_files[-1])
print("🎥 Menggunakan video:", video_path)

cap = cv2.VideoCapture(video_path)
if not cap.isOpened():
    raise RuntimeError(f"Gagal membuka video: {video_path}")

# ==========================================================
# 5. VARIABEL GLOBAL UNTUK STREAMING
# ==========================================================
frame_lock = threading.Lock()
current_frame = None
current_empty = 0
current_occ = 0
last_db_insert = 0.0      # timestamp detik, supaya tidak terlalu sering INSERT

DB_INSERT_INTERVAL = 5.0  # detik (opsional, atur sesuai kebutuhan)

# ==========================================================
# 6. FUNGSI SIMPAN KE DATABASE (OPSIONAL)
# ==========================================================
def save_to_db(empty_count: int, occ_count: int):
    """
    Simpan agregat empty/occupied ke tabel parking_video_history.
    Panggil hanya kalau koneksi DB tersedia.
    """
    global last_db_insert

    if db is None or cursor is None:
        return

    now = time.time()
    if now - last_db_insert < DB_INSERT_INTERVAL:
        # biar tidak terlalu sering INSERT (tiap frame)
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
# 7. LOOP PEMROSESAN VIDEO + YOLO
# ==========================================================
def process_video():
    global current_frame, current_empty, current_occ

    while True:
        success, img = cap.read()
        if not success:
            # kalau video habis, ulang dari awal
            cap.set(cv2.CAP_PROP_POS_FRAMES, 0)
            continue

        # Jalankan YOLO di frame ini
        results = model.predict(source=img, imgsz=640, conf=0.4, verbose=False)
        if not results:
            continue

        res = results[0]
        empty_count = 0
        occ_count = 0

        # Gambar bounding box
        for box, cls in zip(res.boxes.xyxy, res.boxes.cls):
            x1, y1, x2, y2 = map(int, box)
            cls_id = int(cls)
            cls_name = names.get(cls_id, str(cls_id))

            if cls_name in EMPTY_CLASS_NAMES:
                color = (255, 0, 0)   # biru (empty)
                empty_count += 1
                label = "empty"
            elif cls_name in OCC_CLASS_NAMES:
                color = (0, 255, 255)  # cyan (occupied)
                occ_count += 1
                label = "occupied"
            else:
                # kalau ada kelas lain, bisa dilewati
                color = (255, 255, 255)
                label = cls_name

            cv2.rectangle(img, (x1, y1), (x2, y2), color, 2)
            cv2.putText(
                img, label, (x1, max(20, y1 - 5)),
                cv2.FONT_HERSHEY_SIMPLEX, 0.6, color, 2
            )

        total_slots = empty_count + occ_count

        # Teks ringkasan di pojok
        info_text = f"Free: {empty_count} / {total_slots} | Occupied: {occ_count}"
        cv2.putText(
            img, info_text, (30, 40),
            cv2.FONT_HERSHEY_SIMPLEX, 1, (0, 255, 255), 3
        )

        # Simpan ke variabel global (untuk streaming & kartu di dashboard)
        with frame_lock:
            current_frame = img.copy()
            current_empty = empty_count
            current_occ = occ_count

        # (Opsional) simpan ke database
        save_to_db(empty_count, occ_count)

        # batasi FPS (biar server nggak terlalu berat)
        time.sleep(0.05)

# ==========================================================
# 8. FUNGSI UNTUK STREAMING FRAME VIA FLASK
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
        # multipart/x-mixed-replace stream
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

# (Opsional) endpoint JSON untuk kartu di dashboard
@app.route("/stats")
def stats():
    return {
        "empty": current_empty,
        "occupied": current_occ,
        "total": current_empty + current_occ
    }

# ==========================================================
# 9. MAIN
# ==========================================================
if __name__ == "__main__":
    # Jalankan thread pemrosesan video
    threading.Thread(target=process_video, daemon=True).start()
    print("🚀 Flask stream running at http://localhost:5001/video_feed")
    app.run(host="0.0.0.0", port=5001, debug=False)
