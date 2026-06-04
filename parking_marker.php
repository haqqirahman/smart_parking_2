<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

include 'config.php';

$user     = $_SESSION['user'];
$name     = $user['name'];
$username = $user['username'];
$role     = $user['role'];

$resultData = null;
$errorMsg   = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {

    // =========================
    // 1. VALIDASI & SIMPAN FILE
    // =========================
    $uploadErrorCode = isset($_FILES['image']['error']) ? $_FILES['image']['error'] : null;

    if ($uploadErrorCode !== UPLOAD_ERR_OK) {
        $errorMsg = "Gagal mengupload gambar (kode error: " . ($uploadErrorCode ?? 'unknown') . ").";
    } else {
        $target_dir = __DIR__ . DIRECTORY_SEPARATOR . "uploads";

        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        // Nama file aman + unik
        $origName = basename($_FILES["image"]["name"]);
        $filename = time() . '_' . preg_replace('/[^A-Za-z0-9_\.-]/', '_', $origName);

        $target_file_abs = $target_dir . DIRECTORY_SEPARATOR . $filename; // path absolut (untuk Python)
        $target_file_web = "uploads/" . $filename; // path relatif (untuk <img> di HTML)

        if (!move_uploaded_file($_FILES["image"]["tmp_name"], $target_file_abs)) {
            $errorMsg = "Gagal memindahkan file upload ke folder server.";
        } else {

            // =========================
            // 2. PANGGIL PYTHON + YOLO
            // =========================

            // Path ke Python (sesuaikan)
            $pythonPath = 'C:/xampp/htdocs/WEB/smart_parking_2/env310/Scripts/python.exe';

            // Pakai path absolut untuk script, model, dan gambar
            $modelPath  = __DIR__ . DIRECTORY_SEPARATOR . 'best.pt';
            $scriptPath = __DIR__ . DIRECTORY_SEPARATOR . 'infer_yolo.py';
            $imagePath  = $target_file_abs;

            // Penting: path Python DI-QUOTE, jangan pakai escapeshellcmd
            $cmd = '"' . $pythonPath . '" ' .
                   escapeshellarg($scriptPath) . ' ' .
                   escapeshellarg($modelPath) . ' ' .
                   escapeshellarg($imagePath) . ' 2>&1';

            $output = shell_exec($cmd);

            if ($output === null) {
                $errorMsg = "Gagal menjalankan proses YOLO (shell_exec() mengembalikan null). Pastikan 'shell_exec' di-enable di php.ini.";
            } else {
                // Ambil baris terakhir (diasumsikan JSON)
                $lines    = preg_split('/\r\n|\r|\n/', trim($output));
                $lastLine = end($lines);
                $json     = json_decode($lastLine, true);

                if ($json === null) {
                    // JSON gagal, kirim debug output
                    $errorMsg = "Gagal membaca output dari YOLO.<br>Output lengkap:<pre>" . htmlspecialchars($output) . "</pre>";
                } else {
                    $resultData = $json;
                    // simpan path gambar yang di-upload untuk ditampilkan di HTML
                    $resultData['uploaded_image'] = $target_file_web;

                    // =========================
                    // 3. SIMPAN KE DATABASE
                    // =========================
                    $empty = isset($resultData['empty'])    ? (int)$resultData['empty']    : 0;
                    $occ   = isset($resultData['occupied']) ? (int)$resultData['occupied'] : 0;
                    $img   = $target_file_web;                      // path relatif, contoh: uploads/xxx.png
                    $now   = date('Y-m-d H:i:s');                   // waktu sekarang

                    $stmt = $conn->prepare("
                        INSERT INTO parking_marker_history (image_path, empty_count, occupied_count, created_at)
                        VALUES (?, ?, ?, ?)
                    ");

                    if ($stmt) {
                        $stmt->bind_param("siis", $img, $empty, $occ, $now);
                        $stmt->execute();
                        $stmt->close();
                    } else {
                        // Kalau prepare gagal, tampilkan pesan error DB
                        $errorMsg = "Gagal menyimpan ke database: " . $conn->error;
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Parking Marker | Smart Parking</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f4f5f7; overflow-x: hidden; }

        /* Sidebar */
        #sidebar { position: fixed; left: 0; top: 0; height: 100%; width: 240px; background: #1f1f1f; color: white; transition: all 0.3s; z-index: 999; overflow-y: auto; }
        #sidebar.collapsed { width: 70px; }
        #sidebar .logo { font-size: 22px; font-weight: bold; padding: 20px; text-align: center; background: #fc9d22; color: #fff; letter-spacing: 1px; }
        #sidebar ul { list-style: none; padding: 0; margin: 0; }
        #sidebar ul li { border-bottom: 1px solid rgba(255,255,255,0.07); }
        #sidebar ul li a { text-decoration: none; color: white; display: flex; align-items: center; gap: 10px; font-weight: 500; padding: 14px 20px; border-radius: 8px; transition: all 0.3s; position: relative; }
        #sidebar ul li a:hover { background: rgba(252,157,34,0.2); color: #fc9d22; transform: translateX(4px); }
        #sidebar ul li.active a { background: #fc9e228b; color: #fff; font-weight: 600; box-shadow: 0 0 8px rgba(252,157,34,0.5); }
        #sidebar ul li.active a::before { content: ""; position: absolute; left: 0; top: 0; width: 4px; height: 100%; background: #fff; border-radius: 0 4px 4px 0; }
        #sidebar.collapsed ul li a span { display: none; }
        #sidebar.collapsed .logo { font-size: 0; padding: 20px 0; }
        #sidebar.collapsed ul li a { justify-content: center; padding: 14px 0; }

        /* Topbar */
        #topbar { position: fixed; top: 0; left: 240px; height: 60px; width: calc(100% - 240px); background: white; border-bottom: 1px solid #ddd; display: flex; justify-content: space-between; align-items: center; padding: 0 20px; transition: all 0.3s; z-index: 998; }
        #topbar.collapsed { left: 70px; width: calc(100% - 70px); }
        .profile { display: flex; align-items: center; gap: 10px; }
        .profile img { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; }

        /* Main content */
        #content { margin-left: 240px; margin-top: 60px; padding: 30px; transition: all 0.3s; }
        #content.collapsed { margin-left: 70px; }

        .card-custom { border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <div id="sidebar">
        <div class="logo"><i class="fa-solid fa-car"></i><span>SmartPark</span></div>
        <ul>
            <li class="mt-2"><a href="dashboard.php"><i class="fa-solid fa-gauge"></i><span> Dashboard</span></a></li>
            <li class="active mt-2"><a href="parking_marker.php"><i class="fa-solid fa-location-dot"></i><span> Parking Marker</span></a></li>
            <li class="mt-2"><a href="parking_monitoring.php"><i class="fa-solid fa-camera"></i><span> Parking Monitoring</span></a></li>
            <li class="mt-2"><a href="history.php"><i class="fa-solid fa-chart-line"></i><span> History</span></a></li>
            <li class="mt-2"><a href="user_profile.php"><i class="fa-solid fa-gear"></i><span> Account</span></a></li>
            <li class="mt-2"><a href="logout.php" class="text-danger"><i class="fa-solid fa-right-from-bracket"></i><span> Logout</span></a></li>
        </ul>
    </div>

    <!-- Topbar -->
    <div id="topbar">
        <div class="d-flex align-items-center">
            <button id="toggle-btn" class="btn btn-light me-3"><i class="fa-solid fa-bars"></i></button>
            <h5 class="mb-0 fw-bold">Parking Marker</h5>
        </div>
        <div class="profile dropdown me-4">
            <a href="#" class="d-flex align-items-center text-dark text-decoration-none dropdown-toggle" id="profileDropdown" data-bs-toggle="dropdown">
                <img src="images/profil.png" alt="Profile">
                <span class="ms-2"><?php echo htmlspecialchars($name); ?></span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
                <li><a class="dropdown-item" href="user_profile.php"><i class="fa-solid fa-user me-2"></i> My Profile</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i> Logout</a></li>
            </ul>
        </div>
    </div>

    <!-- Main Content -->
    <div id="content">
        <div class="container-fluid">

            <div class="card card-custom mb-4">
                <div class="card-body">
                    <h4 class="mb-3">Parking Marker (Upload Gambar &amp; Prediksi YOLO)</h4>
                    <p class="text-muted mb-4">
                        Upload gambar area parkir, sistem akan menghitung jumlah slot <i>empty</i> dan <i>occupied</i> menggunakan model YOLO yang sudah di-<i>training</i>.
                    </p>

                    <!-- Form Upload -->
                    <form method="POST" enctype="multipart/form-data" class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Pilih gambar parkiran (jpg/png):</label>
                            <input type="file" name="image" accept="image/*" class="form-control" required>
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa-solid fa-upload me-1"></i> Upload &amp; <i>Predict</i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Error -->
            <?php if (!empty($errorMsg)): ?>
                <div class="alert alert-danger"><?= $errorMsg ?></div>
            <?php endif; ?>

            <!-- Hasil Prediksi -->
            <?php if (!empty($resultData)): ?>
                <div class="card card-custom">
                    <div class="card-body">
                        <h5 class="mb-3">Hasil Deteksi YOLO (<i>best.pt</i>)</h5>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <h6>Gambar Asli</h6>
                                <img src="<?= htmlspecialchars($resultData['uploaded_image']) ?>"
                                     alt="Uploaded" class="img-fluid border rounded">
                            </div>

                            <?php if (!empty($resultData['image_out'])): ?>
                            <div class="col-md-6 mb-3">
                                <h6>Gambar dengan <i>Bounding Box</i></h6>
                                <img src="<?= htmlspecialchars($resultData['image_out']) ?>"
                                     alt="Predicted" class="img-fluid border rounded">
                            </div>
                            <?php endif; ?>
                        </div>

                        <hr>

                        <!-- Slot Kosong (Empty) - Biru -->
                        <p class="mb-2">
                            <span style="
                                display:inline-block;
                                width:18px;
                                height:18px;
                                background:#0000FF;
                                border:2px solid #0000FF;
                                margin-right:8px;
                                border-radius:4px;">
                            </span>
                            Slot kosong (<i>empty</i>):
                            <strong><?= (int)($resultData['empty'] ?? 0) ?></strong>
                        </p>

                        <!-- Slot Terisi (Occupied) - Cyan -->
                        <p class="mb-0">
                            <span style="
                                display:inline-block;
                                width:18px;
                                height:18px;
                                background:#00FFFF;
                                border:2px solid #00FFFF;
                                margin-right:8px;
                                border-radius:4px;">
                            </span>
                            Slot terisi (<i>occupied</i>):
                            <strong><?= (int)($resultData['occupied'] ?? 0) ?></strong>
                        </p>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const toggleBtn = document.getElementById('toggle-btn');
        const sidebar  = document.getElementById('sidebar');
        const topbar   = document.getElementById('topbar');
        const content  = document.getElementById('content');

        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
            topbar.classList.toggle('collapsed');
            content.classList.toggle('collapsed');
        });
    </script>
</body>
</html>
