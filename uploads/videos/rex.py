import cv2
# from ultralytics import YOLO

# model=YOLO("best.pt")
camera=cv2.VideoCapture(0)

while 1:
    ret,gambar=camera.read()
    
    # hasil=model(gambar)
    # names = hasil.names  # dict: id -> nama kelas
    # classes = hasil.boxes.cls.tolist() if hasil.boxes is not None else []
    # print(hasil)
    # print(names,classes)
    
    cv2.imshow(" bebas",gambar)
    if cv2.waitKey(1) & 0xFF==ord("q"):
        break
cv2.destroyAllWindows()
camera.release()