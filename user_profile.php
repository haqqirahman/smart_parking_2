<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user = $_SESSION['user'];
$user_id = $user['id'];
$name = $user['name'];
$username = $user['username'];
$role = $user['role'];

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['update_profile'])) {
        $name_new = trim($_POST['name']);
        $username_new = trim($_POST['username']);

        $stmt = $conn->prepare("UPDATE users SET name=?, username=? WHERE id=?");
        $stmt->bind_param("ssi", $name_new, $username_new, $user_id);
        $stmt->execute();

        $_SESSION['user']['name'] = $name_new;
        $_SESSION['user']['username'] = $username_new;

        $success = "Profile updated successfully!";
        $name = $name_new;
        $username = $username_new;
    }

    if (isset($_POST['change_password'])) {
        $current = md5($_POST['current_password']);
        $new = md5($_POST['new_password']);
        $confirm = md5($_POST['confirm_password']);

        if ($new !== $confirm) {
            $error = "New password and confirmation do not match!";
        } else {
            $check = $conn->query("SELECT * FROM users WHERE id='$user_id' AND password='$current'");
            if ($check->num_rows > 0) {
                $conn->query("UPDATE users SET password='$new' WHERE id='$user_id'");
                $success = "Password changed successfully!";
            } else {
                $error = "Current password is incorrect!";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>User Profile - SmartPark</title>
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
    position: fixed; left:0; top:0; height:100%; width:240px;
    background:#1f1f1f; color:white; transition:0.3s; z-index:999; overflow-y:auto;
}
#sidebar.collapsed { width:70px; }
#sidebar .logo { font-size:22px; font-weight:bold; padding:20px; text-align:center; background:#fc9d22; color:#fff; letter-spacing:1px; }
#sidebar ul { list-style:none; padding:0; margin:0; }
#sidebar ul li { border-bottom:1px solid rgba(255,255,255,0.07); }
#sidebar ul li a { text-decoration:none; color:white; display:flex; align-items:center; gap:10px; font-weight:500; padding:14px 20px; border-radius:8px; transition:0.3s; position:relative; }
#sidebar ul li a:hover { background:rgba(252,157,34,0.2); color:#fc9d22; transform:translateX(4px); }
#sidebar ul li.active a { background:#fc9e228b; color:#fff; font-weight:600; box-shadow:0 0 8px rgba(252,157,34,0.5); }
#sidebar ul li.active a::before { content:""; position:absolute; left:0; top:0; width:4px; height:100%; background:#fff; border-radius:0 4px 4px 0; }
#sidebar.collapsed ul li a span { display:none; }
#sidebar.collapsed .logo { font-size:0; padding:20px 0; }
#sidebar.collapsed ul li a { justify-content:center; padding:14px 0; }

/* Topbar */
#topbar { position:fixed; top:0; left:240px; height:60px; width:calc(100% - 240px); background:white; border-bottom:1px solid #ddd; display:flex; justify-content:space-between; align-items:center; padding:0 20px; transition:0.3s; z-index:998; }
#topbar.collapsed { left:70px; width:calc(100% - 70px); }
.profile { display:flex; align-items:center; gap:10px; }
.profile img { width:40px; height:40px; border-radius:50%; object-fit:cover; }

/* Main content */
#content { margin-left:240px; margin-top:60px; padding:30px; transition:0.3s; }
#content.collapsed { margin-left:70px; }

/* Form card */
.profile-card { background:#fff; padding:30px; border-radius:10px; box-shadow:0 3px 15px rgba(0,0,0,0.1); max-width:100%; margin:auto; }
.btn-orange { background-color:#fc9d22; color:white; border:none; transition:0.2s; }
.btn-orange:hover { background-color:#e4891e; }
</style>
</head>
<body>

<!-- Sidebar -->
<div id="sidebar">
    <div class="logo">
        <i class="fa-solid fa-car"></i> <span>SmartPark</span>
    </div>
    <ul>
        <li class="mt-2"><a href="dashboard.php"><i class="fa-solid fa-gauge"></i><span> Dashboard</span></a></li>
        <li class="mt-2"><a href="parking_marker.php"><i class="fa-solid fa-location-dot"></i><span> Parking Marker</span></a></li>
        <li class="mt-2"><a href="parking_monitoring.php"><i class="fa-solid fa-camera"></i><span> Parking Monitoring</span></a></li>
        <li class="mt-2"><a href="history.php"><i class="fa-solid fa-chart-line"></i><span> History</span></a></li>
        <li class="active mt-2"><a href="user_profile.php"><i class="fa-solid fa-gear"></i><span> Account</span></a></li>
        <li class="mt-2"><a href="logout.php" class="text-danger"><i class="fa-solid fa-right-from-bracket"></i><span> Logout</span></a></li>
    </ul>
</div>

<!-- Topbar -->
<div id="topbar">
  <div class="d-flex align-items-center">
    <button id="toggle-btn" class="btn btn-light me-3">
        <i class="fa-solid fa-bars"></i>
    </button>
    <h5 class="mb-0 fw-bold">Profile</h5>
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

<!-- Main content -->
<div id="content">
  <div class="container-fluid">
  <div class="card shadow-sm p-4">
    <?php if(isset($success)) echo "<div class='alert alert-success'>$success</div>"; ?>
    <?php if(isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>

    <form method="POST">
        <div class="card-header bg-white d-flex justify-content-between align-items-center mb-3">
          <h5 class="mb-0 fw-bold"><i class="fa-solid fa-user-pen  me-2"></i>Edit Profile</h5>
        </div>
        <div class="mb-3">
            <label class="form-label">Full Name</label>
            <input type="text" name="name" value="<?php echo htmlspecialchars($name); ?>" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="text" name="username" value="<?php echo htmlspecialchars($username); ?>" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Role</label>
            <input type="text" class="form-control" value="<?php echo htmlspecialchars($role); ?>" readonly>
        </div>
        <button type="submit" name="update_profile" class="btn btn-orange w-100"><i class="fa-solid fa-save me-2"></i>Save Changes</button>
    </form>


    <form method="POST">
        <div class="card-header bg-white d-flex justify-content-between align-items-center mb-3 mt-4">
          <h5 class="mb-0 fw-bold"><i class="fa-solid fa-key  me-2"></i>Change Password</h5>
        </div>
        <div class="mb-3">
            <label class="form-label">Current Password</label>
            <input type="password" name="current_password" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">New Password</label>
            <input type="password" name="new_password" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Confirm New Password</label>
            <input type="password" name="confirm_password" class="form-control" required>
        </div>
        <button type="submit" name="change_password" class="btn btn-outline-dark w-100"><i class="fa-solid fa-lock me-2"></i>Update Password</button>
    </form>
  </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
const toggleBtn = document.getElementById('toggle-btn');
const sidebar = document.getElementById('sidebar');
const topbar = document.getElementById('topbar');
const content = document.getElementById('content');

toggleBtn.addEventListener('click', () => {
    sidebar.classList.toggle('collapsed');
    topbar.classList.toggle('collapsed');
    content.classList.toggle('collapsed');
});
</script>
</body>
</html>
