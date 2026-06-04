import sys
import json
from pathlib import Path
from ultralytics import YOLO
import cv2  # untuk menyimpan gambar hasil plot

# ==============================
# CEK ARGUMEN
# ==============================
if len(sys.argv) < 3:
    print(json.dumps({"error": "butuh path_model dan path_gambar"}))
    sys.exit(1)

model_path = Path(sys.argv[1]).resolve()
image_path = Path(sys.argv[2]).resolve()

# ==============================
# LOAD MODEL
# ==============================
model = YOLO(str(model_path))

# ==============================
# PREDIKSI (TANPA SAVE OTOMATIS)
# ==============================
results = model.predict(
    source=str(image_path),
    conf=0.25,
    save=False,          # kita simpan manual
    verbose=False
)

r = results[0]
names = model.names
empty_count = 0
occupied_count = 0

# Hitung jumlah kelas (empty/occupied)
if r.boxes is not None and r.boxes.cls is not None:
    for c in r.boxes.cls:
        label = names[int(c)]
        label_low = label.lower()
        if label_low.startswith("empty"):
            empty_count += 1
        elif label_low.startswith("occupied"):
            occupied_count += 1

# ==============================
# GAMBAR BOUNDING BOX TANPA LABEL + SAVE MANUAL
# ==============================
base_dir = Path(__file__).resolve().parent
out_dir = base_dir / "runs_web" / "predict"
out_dir.mkdir(parents=True, exist_ok=True)

# HILANGKAN LABEL DAN CONFIDENCE
annotated_img = r.plot(labels=False, conf=False)

# Simpan ke folder predict
out_path = out_dir / image_path.name
cv2.imwrite(str(out_path), annotated_img)

# path relatif untuk ditampilkan di <img src="...">
saved_rel = out_path.relative_to(base_dir)

output = {
    "empty": int(empty_count),
    "occupied": int(occupied_count),
    "image_out": str(saved_rel).replace("\\", "/")
}

# BARIS TERAKHIR = JSON
print(json.dumps(output))
