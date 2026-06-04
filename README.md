# Smart Parking 2

Sistem monitoring dan deteksi slot parkir berbasis YOLO, PHP, dan MySQL untuk memantau ketersediaan lahan parkir secara real-time.

## Fitur

- Monitoring slot parkir real-time
- Deteksi kendaraan menggunakan YOLO
- Dashboard monitoring
- Riwayat parkir
- Export laporan PDF
- Manajemen data pengguna
- Streaming deteksi parkir

## Teknologi

- PHP
- MySQL
- Python
- YOLO
- XAMPP
- PHPMailer

## Instalasi

### 1. Clone Repository

```bash
git clone <repository-url>
```

### 2. Import Database

Import file:

```text
car_park_db.sql
```

ke MySQL melalui phpMyAdmin.

### 3. Install Dependency PHP

```bash
composer install
```

### 4. Aktifkan XAMPP

- Apache
- MySQL

### 5. Jalankan Aplikasi

Buka browser:

```text
http://localhost/WEB/smart_parking_2
```

## Struktur Folder

```text
assets/
css/
js/
images/
uploads/
vendor/
PHPMailer/
```
## File Penting

- index.php
- dashboard.php
- history.php
- parking_monitoring.php
- parking_marker.php
- detect_parking_stream.py
- detect_parking_stream_yolo.py
- analyze_parking.py

## Author

Fathur
