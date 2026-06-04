from flask import Flask, render_template, Response
# from ultralytics import YOLO
import cv2

app = Flask(__name__)

# ====== LOAD MODEL YOLOV8 ======
# Ganti "best.pt" dengan path model kamu
MODEL_PATH = "best.pt"
# model = YOLO(MODEL_PATH)

# ====== KAMERA ======
# 0 = webcam laptop, kalau pakai USB cam bisa coba 1,2,...
# Kalau pakai IP camera/ CCTV bisa pakai URL RTSP / HTTP di sini
cap = cv2.VideoCapture("carPark.mp4")


def gen_frames():
    """Generator untuk stream video dengan hasil deteksi."""
    while True:
        success, frame = cap.read()
        if not success:
            break

        # Jalankan deteksi YOLO langsung ke frame (BGR)
        # results = model(frame, conf=0.25)[0]

        # Ambil info kelas untuk hitung slot parkir
        # names = results.names  # dict: id -> nama kelas
        # classes = results.boxes.cls.tolist() if results.boxes is not None else []
        names=["empty","occupied"]
        classes=[0,1,1]
        total_slot = len(classes)
        kosong = 0
        terisi = 0

        for c in classes:
            nama_kelas = names[int(c)].lower()
            if nama_kelas == 'empty':
                kosong += 1
            elif nama_kelas == 'occupied':
                terisi += 1

        # Gambar bounding box dari YOLO
        # annotated = results.plot()  # sudah ada box + label (BGR)

        # Tambah teks informasi slot di atas gambar
        # info_text = f"Total: {total_slot} | Kosong: {kosong} | Terisi: {terisi}"
        # cv2.putText(
        #     annotated,
        #     info_text,
        #     (10, 30),
        #     cv2.FONT_HERSHEY_SIMPLEX,
        #     0.9,
        #     (0, 255, 0),
        #     2
        # )

        # Encode ke JPEG untuk dikirim sebagai stream
        ret, buffer = cv2.imencode('.jpg', frame)
        frame_bytes = buffer.tobytes()

        # Format multipart/x-mixed-replace (MJPEG)
        yield (
            b'--frame\r\n'
            b'Content-Type: image/jpeg\r\n\r\n' + frame_bytes + b'\r\n'
        )


@app.route('/')
def index():
    # Halaman utama
    return render_template('index.html')


@app.route('/video_feed')
def video_feed():
    # Endpoint stream video
    return Response(
        gen_frames(),
        mimetype='multipart/x-mixed-replace; boundary=frame'
    )


if __name__ == '__main__':
    try:
        app.run(host='0.0.0.0', port=5000, debug=True)
    finally:
        # Pastikan kamera dilepas saat server dimatikan
        cap.release()
        cv2.destroyAllWindows()
