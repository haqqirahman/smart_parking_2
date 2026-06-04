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

/* =====================================================
   1. KONFIGURASI PROJECT & PYTHON
   ===================================================== */
// SESUAIKAN dengan folder project kamu
$project_root       = "C:\\xampp\\htdocs\\WEB\\smart_parking_2\\";
$python_interpreter = $project_root . "env310\\Scripts\\python.exe";
// perhatikan: nama file Python = detect_parking_stream_yolo.py (di root project)
$python_script      = $project_root . "detect_parking_stream_yolo.py";

/* =====================================================
   2. COBA NYALAKAN PYTHON STREAM DI BACKGROUND
      (Kalau sudah jalan, yang baru akan gagal bind port dan mati sendiri)
   ===================================================== */
if (file_exists($python_interpreter) && file_exists($python_script)) {
    // start "" "C:\...\python.exe" "C:\...\detect_parking_stream_yolo.py"
    $cmd = 'start "" ' . escapeshellarg($python_interpreter) . ' ' . escapeshellarg($python_script) . ' > NUL 2>&1';
    pclose(popen($cmd, "r"));
}

/* =====================================================
   3. PROSES UPLOAD VIDEO
   ===================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['video'])) {
    $allowedTypes = ['video/mp4', 'video/avi', 'video/mov', 'video/mkv'];
    $fileType     = $_FILES['video']['type'] ?? '';

    if (!in_array($fileType, $allowedTypes)) {
        echo "<script>alert('❌ Hanya boleh file video!');window.location='parking_monitoring.php';</script>";
        exit;
    }

    $target_dir = "uploads/parking_videos/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    // Hapus file lama (opsional, biar folder selalu 1 file terbaru)
    foreach (glob($target_dir . "*") as $oldFile) {
        @unlink($oldFile);
    }

    // Biar nama file aman dan unik
    $origName   = basename($_FILES["video"]["name"]);
    $safeName   = preg_replace('/[^A-Za-z0-9_\.-]/', '_', $origName);
    $video_path = $target_dir . time() . "_" . $safeName;

    if (move_uploaded_file($_FILES["video"]["tmp_name"], $video_path)) {

        // (OPSIONAL) Paksa panggil lagi Python, tapi sebenarnya tidak wajib
        // karena script Python kita sudah auto baca video terbaru.
        if (file_exists($python_interpreter) && file_exists($python_script)) {
            $cmd = 'start "" ' . escapeshellarg($python_interpreter) . ' ' . escapeshellarg($python_script) . ' > NUL 2>&1';
            pclose(popen($cmd, "r"));
        }

        echo "<script>alert('🚗 Video diupload! Stream akan pakai video terbaru.');window.location='parking_monitoring.php';</script>";
        exit;
    } else {
        echo "<script>alert('❌ Gagal upload video!');window.location='parking_monitoring.php';</script>";
        exit;
    }
}

/* =====================================================
   4. AMBIL DATA SLOT & STATUS REAL-TIME (DARI TABEL)
   ===================================================== */
$query = "
    SELECT 
        s.slot_number, 
        s.x, 
        s.y, 
        s.width, 
        s.height, 
        COALESCE(p.status, 'Free') AS status 
    FROM parking_slots s
    LEFT JOIN parking_status p ON s.slot_number = p.slot_number
    ORDER BY s.slot_number ASC
";
$result = mysqli_query($conn, $query);
$slots  = [];

if ($result) {
    $slots = mysqli_fetch_all($result, MYSQLI_ASSOC);
}

/* Hitung total occupied dan free dari tabel parking_status */
$sql_occ = "SELECT COUNT(*) AS c FROM parking_status WHERE status = 'Occupied'";
$sql_free = "SELECT COUNT(*) AS c FROM parking_status WHERE status = 'Free'";

$res_occ = mysqli_query($conn, $sql_occ);
$row_occ = $res_occ ? mysqli_fetch_assoc($res_occ) : ['c' => 0];
$video_occupied = (int)$row_occ['c'];

$res_free = mysqli_query($conn, $sql_free);
$row_free = $res_free ? mysqli_fetch_assoc($res_free) : ['c' => 0];
$video_free = (int)$row_free['c'];

$video_total = $video_occupied + $video_free;

/* =====================================================
   5. AMBIL HASIL PREDICT TERBARU DARI parking_marker.php
      (TABEL parking_marker_history)
   ===================================================== */
$latest_marker = null;

$sql_marker = "
    SELECT image_path, empty_count, occupied_count, created_at
    FROM parking_marker_history
    ORDER BY created_at DESC
    LIMIT 1
";
$res_marker = mysqli_query($conn, $sql_marker);
if ($res_marker && mysqli_num_rows($res_marker) > 0) {
    $latest_marker = mysqli_fetch_assoc($res_marker);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Parking Monitoring | Smart Parking</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Auto refresh untuk update layout/status -->
    <meta http-equiv="refresh" content="5">

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

        .parking-area { position: relative; width: 100%; max-width: 100%; margin: 0 auto; background: #fefeff; border-radius: 12px; height: 700px; border: 2px solid #d1d5db; box-shadow: 0 0.5px 1px rgba(0, 0, 0, 0.1); }
        .slot { position: absolute; border: 3px solid; border-radius: 6px; text-align: center; font-weight: bold; font-size: 14px; color: white; padding-top: 4px; box-sizing: border-box; transition: transform 0.2s, box-shadow 0.2s; }
        .slot:hover { transform: scale(1.05); box-shadow: 0 4px 10px rgba(0,0,0,0.3); }
        .occupied { border-color: red; background-color: rgba(255, 0, 0, 0.5); }
        .free { border-color: lime; background-color: rgba(0, 255, 0, 0.5); }

        iframe { border: none; }

        .nav-tabs .nav-link {
            color: #555;
            font-weight: 500;
            padding: 10px 25px;
            border: none;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
            border-radius: 8px 8px 0 0;
        }
        .nav-tabs .nav-link:hover {
            color: #fc9d22;
        }
        .nav-tabs .nav-link.active {
            color: #fc9d22;
            border-bottom: 3px solid #fc9d22;
            background-color: #fff;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div id="sidebar">
        <div class="logo"><i class="fa-solid fa-car"></i><span>SmartPark</span></div>
        <ul>
            <li class="mt-2"><a href="dashboard.php"><i class="fa-solid fa-gauge"></i><span> Dashboard</span></a></li>
            <li class="mt-2"><a href="parking_marker.php"><i class="fa-solid fa-location-dot"></i><span> Parking Marker</span></a></li>
            <li class="active mt-2"><a href="parking_monitoring.php"><i class="fa-solid fa-camera"></i><span> Parking Monitoring</span></a></li>
            <li class="mt-2"><a href="history.php"><i class="fa-solid fa-chart-line"></i><span> History</span></a></li>
            <li class="mt-2"><a href="user_profile.php"><i class="fa-solid fa-gear"></i><span> Account</span></a></li>
            <li class="mt-2"><a href="logout.php" class="text-danger"><i class="fa-solid fa-right-from-bracket"></i><span> Logout</span></a></li>
        </ul>
    </div>

    <!-- Topbar -->
    <div id="topbar">
        <div class="d-flex align-items-center">
            <button id="toggle-btn" class="btn btn-light me-3"><i class="fa-solid fa-bars"></i></button>
            <h5 class="mb-0 fw-bold">Parking Monitoring</h5>
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

            <!-- Tabs Nav -->
            <ul class="nav nav-tabs mb-4" id="parkingTabs" role="tablist" style="border-bottom: 3px solid #c6c6c6ff; padding-bottom: 10px;">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="video-tab" data-bs-toggle="tab" data-bs-target="#video" type="button" role="tab">
                        📹 Video
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="layout-tab" data-bs-toggle="tab" data-bs-target="#layout" type="button" role="tab">
                        🅿️ Layout & Marker Snapshot
                    </button>
                </li>
            </ul>

            <div class="tab-content">
                <!-- VIDEO TAB -->
                <div class="tab-pane fade show active" id="video" role="tabpanel">
                    <!-- Ringkasan status real-time dari video -->
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div class="card shadow-sm">
                                <div class="card-body text-center">
                                    <i class="fa-solid fa-square-parking fa-2x text-primary mb-2"></i>
                                    <h6 class="mb-0">Total Slot Terdeteksi (video)</h6>
                                    <h3 class="fw-bold mb-0"><?php echo $video_total; ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card shadow-sm">
                                <div class="card-body text-center">
                                    <i class="fa-solid fa-car-side fa-2x text-danger mb-2"></i>
                                    <h6 class="mb-0">Occupied (video)</h6>
                                    <h3 class="fw-bold mb-0"><?php echo $video_occupied; ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card shadow-sm">
                                <div class="card-body text-center">
                                    <i class="fa-solid fa-square-check fa-2x text-success mb-2"></i>
                                    <h6 class="mb-0">Free (video)</h6>
                                    <h3 class="fw-bold mb-0"><?php echo $video_free; ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Upload video -->
                    <div class="card mb-4 shadow-sm">
                        <div class="card-body">
                            <h6 class="fw-bold mb-2"><i class="fa-solid fa-upload me-1"></i> Upload Video untuk Diproses</h6>
                            <form method="POST" enctype="multipart/form-data" class="row g-2">
                                <div class="col-md-8">
                                    <input type="file" name="video" accept="video/*" class="form-control" required>
                                </div>
                                <div class="col-md-4 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary w-100">
                                        Mulai Monitoring dari Video
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Iframe streaming -->
                    <div class="card p-4 shadow-sm d-flex justify-content-center align-items-center" style="height:800px;">
                        <iframe src="http://localhost:5001/video_feed" height="740px" width="90%"></iframe>
                    </div>
                </div>

                <!-- LAYOUT + HASIL PREDICT GAMBAR DARI parking_marker.php -->
                <div class="tab-pane fade" id="layout" role="tabpanel">
                    <div class="row">
                        <div class="col-lg-7 mb-3">
                            <div class="card shadow-sm">
                                <div class="card-header bg-white">
                                    <h6 class="mb-0 fw-bold"><i class="fa-solid fa-map me-1"></i> Layout Slot Parkir (Real-time)</h6>
                                </div>
                                <div class="card-body">
                                    <div class="parking-area">
                                        <?php foreach ($slots as $slot): ?>
                                            <div
                                                class="slot <?php echo strtolower($slot['status']); ?>"

                                                style="
                                                    left: <?php echo (int)$slot['x']; ?>px;
                                                    top: <?php echo (int)$slot['y']; ?>px;
                                                    width: <?php echo (int)$slot['width']; ?>px;
                                                    height: <?php echo (int)$slot['height']; ?>px;
                                                "
                                            >
                                                <?php echo 'P-' . str_pad($slot['slot_number'], 2, '0', STR_PAD_LEFT); ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-5 mb-3">
                            <div class="card shadow-sm">
                                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0 fw-bold">
                                        <i class="fa-solid fa-image me-1"></i> Snapshot Terbaru dari Parking Marker
                                    </h6>
                                    <?php if ($latest_marker): ?>
                                        <small class="text-muted">
                                            <?php
                                            echo 'Updated: ' . date('d M Y H:i', strtotime($latest_marker['created_at']));
                                            ?>
                                        </small>
                                    <?php endif; ?>
                                </div>
                                <div class="card-body">
                                    <?php if ($latest_marker): ?>
                                        <div class="mb-3 text-center">
                                            <img src="<?php echo htmlspecialchars($latest_marker['image_path']); ?>"
                                                 alt="Latest Marker Image"
                                                 class="img-fluid rounded border">
                                        </div>
                                        <p class="mb-1">
                                            Slot kosong (<i>empty</i>): 
                                            <strong><?php echo (int)$latest_marker['empty_count']; ?></strong>
                                        </p>
                                        <p class="mb-0">
                                            Slot terisi (<i>occupied</i>): 
                                            <strong><?php echo (int)$latest_marker['occupied_count']; ?></strong>
                                        </p>
                                    <?php else: ?>
                                        <p class="text-muted mb-0">
                                            Belum ada data dari <i>parking_marker.php</i>.
                                            Silakan upload gambar di menu <strong>Parking Marker</strong>.
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div> <!-- /row -->
                </div>
            </div> <!-- /tab-content -->
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
