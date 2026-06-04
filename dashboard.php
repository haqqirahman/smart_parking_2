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
   1. RINGKASAN DATA UTAMA DARI DATABASE
   ===================================================== */
// Total slot dari tabel parking_slots
$total_slots = 0;
$res_total   = mysqli_query($conn, "SELECT COUNT(*) AS c FROM parking_slots");
if ($res_total) {
    $row_total   = mysqli_fetch_assoc($res_total);
    $total_slots = (int)$row_total['c'];
}

// Jumlah occupied & free dari tabel parking_status
$occupied = $free = 0;

$res_occ = mysqli_query($conn, "SELECT COUNT(*) AS c FROM parking_status WHERE status = 'Occupied'");
if ($res_occ) {
    $row_occ = mysqli_fetch_assoc($res_occ);
    $occupied = (int)$row_occ['c'];
}

$res_free = mysqli_query($conn, "SELECT COUNT(*) AS c FROM parking_status WHERE status = 'Free'");
if ($res_free) {
    $row_free = mysqli_fetch_assoc($res_free);
    $free = (int)$row_free['c'];
}

// Perubahan status hari ini dari parking_history
$changes_today = 0;
$sql_changes = "
    SELECT COUNT(*) AS c 
    FROM parking_history 
    WHERE DATE(event_time) = CURDATE()
";
$res_changes = mysqli_query($conn, $sql_changes);
if ($res_changes) {
    $row_changes   = mysqli_fetch_assoc($res_changes);
    $changes_today = (int)$row_changes['c'];
}

/* =====================================================
   2. PANGGIL PYTHON analyze_parking.py UNTUK DATA ANALITIK
   ===================================================== */
$project_root       = "C:\\xampp\\htdocs\\WEB\\smart_parking_2\\";
$python_interpreter = $project_root . "env310\\Scripts\\python.exe";
$analytics_script   = $project_root . "analyze_parking.py";

// nilai default (kalau Python gagal)
$analytics = [
    "usage_per_hour" => array_fill(0, 24, 0),
    "avg_duration"   => ["slots" => [], "avg_minutes" => []],
    "occupancy_pie"  => ["occupied" => 0, "free" => 0, "other" => 0],
    "heatmap"        => []
];
$analytics_error = null;

if (file_exists($python_interpreter) && file_exists($analytics_script)) {
    $cmd = 'cd /d ' . escapeshellarg($project_root)
         . ' && ' . escapeshellarg($python_interpreter)
         . ' '  . escapeshellarg($analytics_script) . ' 2>&1';

    $output = shell_exec($cmd);

    $decoded = json_decode($output, true);
    if (is_array($decoded) && empty($decoded['error'])) {
        $analytics = $decoded;
    } else {
        $analytics_error = $output; // kalau mau, bisa disimpan ke log
    }
} else {
    $analytics_error = "Python atau analyze_parking.py tidak ditemukan.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard | Smart Parking</title>
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

        .card-stat { border: none; border-radius: 16px; box-shadow: 0 4px 12px rgba(15, 23, 42, 0.08); }
        .card-stat .icon-wrapper { width: 48px; height: 48px; border-radius: 14px; display: flex; align-items: center; justify-content: center; }

        .section-title { font-weight: 600; font-size: 18px; }

        .analytics-card { border-radius: 18px; box-shadow: 0 4px 16px rgba(15, 23, 42, 0.06); border: none; }
        .analytics-card h6 { font-weight: 600; }
        .no-data-label { color: #9ca3af; font-size: 14px; margin: 60px 0; text-align: center; }
    </style>

    <script>
        // kirim data analitik ke JavaScript
        const parkingAnalytics = <?php echo json_encode($analytics, JSON_UNESCAPED_UNICODE); ?>;
    </script>
</head>
<body>
    <!-- Sidebar -->
    <div id="sidebar">
        <div class="logo"><i class="fa-solid fa-car"></i><span>SmartPark</span></div>
        <ul>
            <li class="mt-2 active"><a href="dashboard.php"><i class="fa-solid fa-gauge"></i><span> Dashboard</span></a></li>
            <li class="mt-2"><a href="parking_marker.php"><i class="fa-solid fa-location-dot"></i><span> Parking Marker</span></a></li>
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
            <h5 class="mb-0 fw-bold">Dashboard</h5>
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

            <!-- Ringkasan atas -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="card card-stat">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1">Total Slot</p>
                                <h4 class="fw-bold mb-0"><?php echo $total_slots; ?></h4>
                            </div>
                            <div class="icon-wrapper bg-primary bg-opacity-10 text-primary">
                                <i class="fa-solid fa-square-parking"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card card-stat">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1">Occupied</p>
                                <h4 class="fw-bold mb-0"><?php echo $occupied; ?></h4>
                            </div>
                            <div class="icon-wrapper bg-danger bg-opacity-10 text-danger">
                                <i class="fa-solid fa-car-side"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card card-stat">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1">Free</p>
                                <h4 class="fw-bold mb-0"><?php echo $free; ?></h4>
                            </div>
                            <div class="icon-wrapper bg-success bg-opacity-10 text-success">
                                <i class="fa-solid fa-square-check"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card card-stat">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1">Perubahan Hari Ini</p>
                                <h4 class="fw-bold mb-0"><?php echo $changes_today; ?></h4>
                            </div>
                            <div class="icon-wrapper bg-warning bg-opacity-10 text-warning">
                                <i class="fa-solid fa-arrows-rotate"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Parking Analytics -->
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 class="section-title">
                    <i class="fa-solid fa-chart-column me-2"></i>Parking Analytics
                </h5>
                <small class="text-muted">Generated from <strong>parking_history</strong> data</small>
            </div>

            <div class="row">
                <!-- 1) Jumlah perubahan status per jam -->
                <div class="col-lg-6 mb-4">
                    <div class="card analytics-card">
                        <div class="card-body">
                            <h6 class="mb-1">Jumlah Perubahan Status Parkir per Jam</h6>
                            <p id="no-data-usage" class="no-data-label mb-0">No Data Available</p>
                            <canvas id="chartUsagePerHour" style="display:none; height:260px;"></canvas>
                        </div>
                        <div class="card-footer bg-white border-0 small text-muted">
                            <i class="fa-solid fa-book me-1"></i>Jumlah Perubahan Status Parkir per Jam
                        </div>
                    </div>
                </div>

                <!-- 2) Rata-rata durasi per slot -->
                <div class="col-lg-6 mb-4">
                    <div class="card analytics-card">
                        <div class="card-body">
                            <h6 class="mb-1">Rata-rata Durasi Parkir per Slot (menit)</h6>
                            <p id="no-data-duration" class="no-data-label mb-0">No Data Available</p>
                            <canvas id="chartAvgDuration" style="display:none; height:260px;"></canvas>
                        </div>
                        <div class="card-footer bg-white border-0 small text-muted">
                            <i class="fa-regular fa-clock me-1"></i>Rata-Rata Durasi Parkir per Slot
                        </div>
                    </div>
                </div>

                <!-- 3) Tingkat keterisian saat ini -->
                <div class="col-lg-6 mb-4">
                    <div class="card analytics-card">
                        <div class="card-body">
                            <h6 class="mb-1">Tingkat Keterisian Parkir Saat Ini</h6>
                            <p id="no-data-pie" class="no-data-label mb-0">No Data Available</p>
                            <canvas id="chartOccupancyPie" style="display:none; height:260px;"></canvas>
                        </div>
                        <div class="card-footer bg-white border-0 small text-muted">
                            <i class="fa-solid fa-car-rear me-1"></i>Tingkat Keterisian Parkir Saat Ini
                        </div>
                    </div>
                </div>

                <!-- 4) Heatmap waktu sibuk -->
                <div class="col-lg-6 mb-4">
                    <div class="card analytics-card">
                        <div class="card-body">
                            <h6 class="mb-1">Heatmap Kepadatan Parkir (Occupied Events)</h6>
                            <p id="no-data-heatmap" class="no-data-label mb-0">No Data Available</p>
                            <canvas id="chartHeatmap" style="display:none; height:260px;"></canvas>
                        </div>
                        <div class="card-footer bg-white border-0 small text-muted">
                            <i class="fa-regular fa-calendar-days me-1"></i>Heatmap Waktu Sibuk (Hari × Jam)
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($analytics_error): ?>
                <div class="alert alert-warning mt-2">
                    <strong>Info:</strong> Analytics Python error (tidak wajib diperlihatkan ke user akhir):<br>
                    <code><?php echo htmlspecialchars($analytics_error); ?></code>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

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

        function sumArray(arr) {
            if (!Array.isArray(arr)) return 0;
            return arr.reduce((a, b) => a + (b || 0), 0);
        }

        // 1) Bar: jumlah perubahan status per jam
        (function() {
            const data = parkingAnalytics.usage_per_hour || [];
            if (sumArray(data) === 0) return;

            document.getElementById('no-data-usage').style.display = 'none';
            const canvas = document.getElementById('chartUsagePerHour');
            canvas.style.display = 'block';

            const hours = [...Array(24).keys()].map(h => h.toString().padStart(2,'0') + ':00');

            new Chart(canvas.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: hours,
                    datasets: [{
                        label: 'Perubahan Status',
                        data: data
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        x: { title: { display: true, text: 'Jam' } },
                        y: { title: { display: true, text: 'Jumlah Perubahan' }, beginAtZero: true }
                    }
                }
            });
        })();

        // 2) Bar: rata-rata durasi per slot
        (function() {
            const avg = parkingAnalytics.avg_duration || {};
            const slots = avg.slots || [];
            const mins  = avg.avg_minutes || [];
            if (!slots.length) return;

            document.getElementById('no-data-duration').style.display = 'none';
            const canvas = document.getElementById('chartAvgDuration');
            canvas.style.display = 'block';

            new Chart(canvas.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: slots.map(s => 'Slot ' + s),
                    datasets: [{
                        label: 'Rata-rata Durasi (menit)',
                        data: mins
                    }]
                },
                options: {
                    indexAxis: 'y',
                    scales: {
                        x: { beginAtZero: true, title: { display: true, text: 'Menit' } }
                    }
                }
            });
        })();

        // 3) Pie: tingkat keterisian
        (function() {
            const pie = parkingAnalytics.occupancy_pie || {};
            const occ = pie.occupied || 0;
            const free = pie.free || 0;
            const other = pie.other || 0;
            const total = occ + free + other;
            if (!total) return;

            document.getElementById('no-data-pie').style.display = 'none';
            const canvas = document.getElementById('chartOccupancyPie');
            canvas.style.display = 'block';

            new Chart(canvas.getContext('2d'), {
                type: 'pie',
                data: {
                    labels: ['Occupied', 'Free', 'Other'],
                    datasets: [{
                        data: [occ, free, other]
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { position: 'bottom' }
                    }
                }
            });
        })();

        // 4) Heatmap sederhana: stacked bar (7 hari × 24 jam)
        (function() {
            const heat = parkingAnalytics.heatmap || [];
            if (!heat.length) return;

            const total = heat.flat().reduce((a,b) => a + (b||0), 0);
            if (!total) return;

            document.getElementById('no-data-heatmap').style.display = 'none';
            const canvas = document.getElementById('chartHeatmap');
            canvas.style.display = 'block';

            const days = ['Min','Sen','Sel','Rab','Kam','Jum','Sab'];
            const hours = [...Array(24).keys()].map(h => h.toString().padStart(2,'0'));

            const datasets = days.map((d, di) => ({
                label: d,
                data: heat[di] || [],
                stack: 'stack1'
            }));

            new Chart(canvas.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: hours,
                    datasets: datasets
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { position: 'bottom' }
                    },
                    scales: {
                        x: { stacked: true, title: { display: true, text: 'Jam' } },
                        y: { stacked: true, title: { display: true, text: 'Jumlah Occupied Events' } }
                    }
                }
            });
        })();
    </script>
</body>
</html>
