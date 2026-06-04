<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

include 'config.php';

$user = $_SESSION['user'];
$name = $user['name'];

// Filter date range
$start_date = $_GET['start_date'] ?? '';
$end_date   = $_GET['end_date'] ?? '';

// Pagination
$limit  = 10;
$page   = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// WHERE clause
$where = "";
if (!empty($start_date) && !empty($end_date)) {
    $where = "WHERE created_at >= '{$start_date} 00:00:00' AND created_at <= '{$end_date} 23:59:59'";
} elseif (!empty($start_date)) {
    $where = "WHERE created_at >= '{$start_date} 00:00:00'";
} elseif (!empty($end_date)) {
    $where = "WHERE created_at <= '{$end_date} 23:59:59'";
}

// total rows
$sql_count  = "SELECT COUNT(*) AS total FROM parking_marker_history $where";
$res_count  = mysqli_query($conn, $sql_count);
$total_rows = mysqli_fetch_assoc($res_count)['total'];
$total_pages = max(1, ceil($total_rows / $limit));

// ambil data
$sql = "
    SELECT id, image_path, empty_count, occupied_count, created_at
    FROM parking_marker_history
    $where
    ORDER BY created_at DESC
    LIMIT $limit OFFSET $offset
";
$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Parking Marker History | SmartPark</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <style>
    body { font-family: 'Poppins', sans-serif; background-color: #f4f5f7; overflow-x: hidden; }
    #sidebar { position: fixed; left:0; top:0; height:100%; width:240px; background:#1f1f1f; color:white; transition:.3s; z-index:999; overflow-y:auto; }
    #sidebar.collapsed { width:70px; }
    #sidebar .logo { font-size:22px; font-weight:bold; padding:20px; text-align:center; background:#fc9d22; color:#fff; letter-spacing:1px; }
    #sidebar ul { list-style:none; padding:0; margin:0; }
    #sidebar ul li { border-bottom:1px solid rgba(255,255,255,0.07); }
    #sidebar ul li a { text-decoration:none; color:white; display:flex; align-items:center; gap:10px; font-weight:500; padding:14px 20px; border-radius:8px; transition:.3s; position:relative; }
    #sidebar ul li a:hover { background:rgba(252,157,34,0.2); color:#fc9d22; transform:translateX(4px); }
    #sidebar ul li.active a { background:#fc9e228b; color:#fff; font-weight:600; box-shadow:0 0 8px rgba(252,157,34,0.5); }
    #sidebar ul li.active a::before { content:""; position:absolute; left:0; top:0; width:4px; height:100%; background:#fff; border-radius:0 4px 4px 0; }
    #sidebar.collapsed ul li a span { display:none; }
    #sidebar.collapsed .logo { font-size:0; padding:20px 0; }
    #sidebar.collapsed ul li a { justify-content:center; padding:14px 0; }

    #topbar { position:fixed; top:0; left:240px; height:60px; width:calc(100% - 240px); background:white; border-bottom:1px solid #ddd; display:flex; justify-content:space-between; align-items:center; padding:0 20px; transition:.3s; z-index:998; }
    #topbar.collapsed { left:70px; width:calc(100% - 70px); }
    .profile img { width:40px; height:40px; border-radius:50%; object-fit:cover; }

    #content { margin-left:240px; margin-top:60px; padding:30px; transition:.3s; }
    #content.collapsed { margin-left:70px; }

    table th { background:#fc9d22; color:white; }
    .img-thumb { max-width:120px; max-height:80px; object-fit:cover; border-radius:6px; border:1px solid #ddd; }
  </style>
</head>
<body>

  <!-- Sidebar -->
  <div id="sidebar">
    <div class="logo"><i class="fa-solid fa-car"></i> <span>SmartPark</span></div>
    <ul>
      <li class="mt-2"><a href="dashboard.php"><i class="fa-solid fa-gauge"></i><span> Dashboard</span></a></li>
      <li class="mt-2"><a href="parking_marker.php"><i class="fa-solid fa-location-dot"></i><span> Parking Marker</span></a></li>
      <li class="mt-2"><a href="parking_monitoring.php"><i class="fa-solid fa-camera"></i><span> Parking Monitoring</span></a></li>
      <li class="active mt-2"><a href="history_marker.php"><i class="fa-solid fa-chart-line"></i><span> History</span></a></li>
      <li class="mt-2"><a href="user_profile.php"><i class="fa-solid fa-gear"></i><span> Account</span></a></li>
      <li class="mt-2"><a href="logout.php" class="text-danger"><i class="fa-solid fa-right-from-bracket"></i><span> Logout</span></a></li>
    </ul>
  </div>

  <!-- Topbar -->
  <div id="topbar">
    <div class="d-flex align-items-center">
      <button id="toggle-btn" class="btn btn-light me-3"><i class="fa-solid fa-bars"></i></button>
      <h5 class="mb-0 fw-bold">Parking Marker History</h5>
    </div>
    <div class="profile dropdown me-4">
      <a href="#" class="d-flex align-items-center text-dark text-decoration-none dropdown-toggle"
         id="profileDropdown" data-bs-toggle="dropdown">
        <img src="images/profil.png" alt="Profile">
        <span class="ms-2"><?php echo htmlspecialchars($name); ?></span>
      </a>
      <ul class="dropdown-menu dropdown-menu-end">
        <li><a class="dropdown-item" href="user_profile.php"><i class="fa-solid fa-user me-2"></i> My Profile</a></li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item text-danger" href="logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i> Logout</a></li>
      </ul>
    </div>
  </div>

  <!-- Main Content -->
  <div id="content">
    <div class="container-fluid">
      <div class="card shadow-sm p-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
          <h5 class="mb-0 fw-bold"><i class="fa-solid fa-chart-line me-2"></i> Parking Marker History</h5>
        </div>
        <div class="card-body">

          <!-- Filter tanggal -->
          <form method="get" class="row g-3 mb-4">
            <div class="col-md-4">
              <label for="start_date" class="form-label">Start Date</label>
              <input type="date" id="start_date" name="start_date"
                     value="<?php echo htmlspecialchars($start_date); ?>" class="form-control">
            </div>
            <div class="col-md-4">
              <label for="end_date" class="form-label">End Date</label>
              <input type="date" id="end_date" name="end_date"
                     value="<?php echo htmlspecialchars($end_date); ?>" class="form-control">
            </div>
            <div class="col-md-4 d-flex align-items-end">
              <button type="submit" class="btn btn-warning text-white me-2">
                <i class="fa-solid fa-filter me-1"></i> Filter
              </button>
              <a href="history_marker.php" class="btn btn-secondary">
                <i class="fa-solid fa-rotate-left me-1"></i> Reset
              </a>
            </div>
          </form>

          <!-- Tabel -->
          <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle">
              <thead>
                <tr class="text-center">
                  <th>No</th>
                  <th>Image</th>
                  <th>Empty</th>
                  <th>Occupied</th>
                  <th>Date & Time</th>
                </tr>
              </thead>
              <tbody>
                <?php if (mysqli_num_rows($result) > 0): ?>
                  <?php $no = $offset + 1; ?>
                  <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                      <td class="text-center"><?php echo $no++; ?></td>
                      <td class="text-center">
                        <a href="<?php echo htmlspecialchars($row['image_path']); ?>" target="_blank">
                          <img src="<?php echo htmlspecialchars($row['image_path']); ?>"
                               class="img-thumb" alt="Parking">
                        </a>
                      </td>
                      <td class="text-center"><span class="badge bg-success"><?php echo (int)$row['empty_count']; ?></span></td>
                      <td class="text-center"><span class="badge bg-danger"><?php echo (int)$row['occupied_count']; ?></span></td>
                      <td class="text-center"><?php echo date('d M Y, H:i', strtotime($row['created_at'])); ?></td>
                    </tr>
                  <?php endwhile; ?>
                <?php else: ?>
                  <tr><td colspan="5" class="text-center text-muted">No records found.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <div class="mt-3">
            <nav>
              <ul class="pagination justify-content-center">
                <?php
                  $start_page = max(1, $page - 5);
                  $end_page   = min($total_pages, $start_page + 9);

                  if ($page > 1) {
                    echo '<li class="page-item"><a class="page-link" href="?page='.($page-1).'&start_date='.$start_date.'&end_date='.$end_date.'">Previous</a></li>';
                  }
                  for ($i = $start_page; $i <= $end_page; $i++) {
                    $active = $i == $page ? 'active' : '';
                    echo '<li class="page-item '.$active.'"><a class="page-link" href="?page='.$i.'&start_date='.$start_date.'&end_date='.$end_date.'">'.$i.'</a></li>';
                  }
                  if ($page < $total_pages) {
                    echo '<li class="page-item"><a class="page-link" href="?page='.($page+1).'&start_date='.$start_date.'&end_date='.$end_date.'">Next</a></li>';
                  }
                ?>
              </ul>
            </nav>
          </div>

        </div>
      </div>
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