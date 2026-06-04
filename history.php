<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

include 'config.php';

$user = $_SESSION['user'];
$name = $user['name'];
$username = $user['username'];
$role = $user['role'];

// Filter date range
$start_date = $_GET['start_date'] ?? '';
$end_date   = $_GET['end_date'] ?? '';

// Pagination setup
$limit  = 10; // record per page
$page   = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Build WHERE clause (pakai created_at)
$where = "";
if (!empty($start_date) && !empty($end_date)) {
    $where = "WHERE created_at >= '$start_date 00:00:00' AND created_at <= '$end_date 23:59:59'";
} elseif (!empty($start_date)) {
    $where = "WHERE created_at >= '$start_date 00:00:00'";
} elseif (!empty($end_date)) {
    $where = "WHERE created_at <= '$end_date 23:59:59'";
}

// Query total records untuk pagination
$sql_count  = "SELECT COUNT(*) AS total FROM parking_marker_history $where";
$res_count  = mysqli_query($conn, $sql_count);
$total_rows = mysqli_fetch_assoc($res_count)['total'] ?? 0;
$total_pages = max(1, ceil($total_rows / $limit));

// Fetch data with limit & offset
$sql = "SELECT id, image_path, empty_count, occupied_count, created_at
        FROM parking_marker_history
        $where
        ORDER BY created_at DESC
        LIMIT $limit OFFSET $offset";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>History | Smart Parking</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <style>
    body {
      font-family: 'Poppins', sans-serif;
      background-color: #f4f5f7;
      overflow-x: hidden;
    }
    /* Sidebar */
    #sidebar {
      position: fixed;
      left: 0;
      top: 0;
      height: 100%;
      width: 240px;
      background: #1f1f1f;
      color: white;
      transition: all 0.3s;
      z-index: 999;
      overflow-y: auto;
    }
    #sidebar.collapsed { width: 70px; }
    #sidebar .logo {
      font-size: 22px;
      font-weight: bold;
      padding: 20px;
      text-align: center;
      background: #fc9d22;
      color: #fff;
      letter-spacing: 1px;
    }
    #sidebar ul { list-style: none; padding: 0; margin: 0; }
    #sidebar ul li { border-bottom: 1px solid rgba(255,255,255,0.07); }
    #sidebar ul li a {
      text-decoration: none;
      color: white;
      display: flex;
      align-items: center;
      gap: 10px;
      font-weight: 500;
      padding: 14px 20px;
      border-radius: 8px;
      transition: all 0.3s;
      position: relative;
    }
    #sidebar ul li a:hover {
      background: rgba(252,157,34,0.2);
      color: #fc9d22;
      transform: translateX(4px);
    }
    #sidebar ul li.active a {
      background: #fc9e228b;
      color: #fff;
      font-weight: 600;
      box-shadow: 0 0 8px rgba(252,157,34,0.5);
    }
    #sidebar ul li.active a::before {
      content: "";
      position: absolute;
      left: 0;
      top: 0;
      width: 4px;
      height: 100%;
      background: #fff;
      border-radius: 0 4px 4px 0;
    }
    #sidebar.collapsed ul li a span { display: none; }
    #sidebar.collapsed .logo { font-size: 0; padding: 20px 0; }
    #sidebar.collapsed ul li a { justify-content: center; padding: 14px 0; }

    /* Topbar */
    #topbar {
      position: fixed;
      top: 0;
      left: 240px;
      height: 60px;
      width: calc(100% - 240px);
      background: white;
      border-bottom: 1px solid #ddd;
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 0 20px;
      transition: all 0.3s;
      z-index: 998;
    }
    #topbar.collapsed { left: 70px; width: calc(100% - 70px); }
    .profile { display: flex; align-items: center; gap: 10px; }
    .profile img { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; }

    /* Main content */
    #content {
      margin-left: 240px;
      margin-top: 60px;
      padding: 30px;
      transition: all 0.3s;
    }
    #content.collapsed { margin-left: 70px; }

    table th { background: #fc9d22; color: white; }
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
      <li class="active mt-2"><a href="history.php"><i class="fa-solid fa-chart-line"></i><span> History</span></a></li>
      <li class="mt-2"><a href="user_profile.php"><i class="fa-solid fa-gear"></i><span> Account</span></a></li>
      <li class="mt-2"><a href="logout.php" class="text-danger"><i class="fa-solid fa-right-from-bracket"></i><span> Logout</span></a></li>
    </ul>
  </div>

  <!-- Topbar -->
  <div id="topbar">
    <div class="d-flex align-items-center">
      <button id="toggle-btn" class="btn btn-light me-3"><i class="fa-solid fa-bars"></i></button>
      <h5 class="mb-0 fw-bold">History (Parking Marker)</h5>
    </div>
    <div class="profile dropdown me-4">
      <a href="#" class="d-flex align-items-center text-dark text-decoration-none dropdown-toggle"
         id="profileDropdown" data-bs-toggle="dropdown" aria-expanded="false">
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
      <div class="card shadow-sm p-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
          <h5 class="mb-0 fw-bold">
            <i class="fa-solid fa-chart-line me-2"></i> Parking Marker History
          </h5>
        </div>
        <div class="card-body">
          <!-- Filter -->
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
              <a href="history.php" class="btn btn-secondary">
                <i class="fa-solid fa-rotate-left me-1"></i> Reset
              </a>
              <!-- kalau mau export PDF khusus marker, bisa ganti filenya -->
              <!-- <a href="export_marker_history_pdf.php?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" 
                 class="btn btn-danger text-white ms-2"><i class="fa-solid fa-file-pdf me-1"></i> Export PDF</a> -->
            </div>
          </form>

          <!-- Table -->
          <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle">
              <thead>
                <tr class="text-center">
                  <th>No</th>
                  <th>Preview Image</th>
                  <th>Empty Slots</th>
                  <th>Occupied Slots</th>
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
                        <?php if (!empty($row['image_path'])): ?>
                          <img src="<?php echo htmlspecialchars($row['image_path']); ?>"
                               alt="img" style="max-width:120px; max-height:80px;" class="img-thumbnail">
                        <?php else: ?>
                          -
                        <?php endif; ?>
                      </td>
                      <td class="text-center">
                        <span class="badge bg-primary">
                          <?php echo (int)$row['empty_count']; ?> empty
                        </span>
                      </td>
                      <td class="text-center">
                        <span class="badge bg-info text-dark">
                          <?php echo (int)$row['occupied_count']; ?> occupied
                        </span>
                      </td>
                      <td class="text-center">
                        <?php echo date('d M Y, H:i', strtotime($row['created_at'])); ?>
                      </td>
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

  <!-- Scripts -->
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
