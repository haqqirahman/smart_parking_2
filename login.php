<?php
session_start();
include "config.php";

$error = '';
$login_success = false;
$user = null;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Ambil input dari form
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Hilangkan spasi berlebih
    $username = trim($username);
    $password = trim($password);

    // === LOGIN TANPA MD5, SESUAI DATA DI DATABASE (admin / admin123) ===
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND password = ?");
    if ($stmt) {
        $stmt->bind_param("ss", $username, $password);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows == 1) {
            $user = $result->fetch_assoc();
            $_SESSION['user'] = $user;
            $login_success = true;
        } else {
            $error = "Username atau password salah!";
        }

        $stmt->close();
    } else {
        $error = "Terjadi kesalahan pada server (query gagal disiapkan).";
    }
}
?>
<!DOCTYPE html>
<html>
   <head>
      <!-- SweetAlert2 CSS & JS -->
      <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

      <!-- bootstrap css -->
      <link rel="stylesheet" type="text/css" href="css/bootstrap.min.css">
      <!-- style css -->
      <link rel="stylesheet" type="text/css" href="css/style.css">
      <!-- Responsive-->
      <link rel="stylesheet" href="css/responsive.css">
      <!-- fevicon -->
      <link rel="icon" href="images/fevicon.png" type="image/gif" />
      <!-- font css -->
      <link href="https://fonts.googleapis.com/css?family=Poppins:400,500,600,700&display=swap" rel="stylesheet">
      <!-- Scrollbar Custom CSS -->
      <link rel="stylesheet" href="css/jquery.mCustomScrollbar.min.css">
      <!-- Tweaks for older IEs-->
      <link rel="stylesheet" href="https://netdna.bootstrapcdn.com/font-awesome/4.0.3/css/font-awesome.css">
      <!-- font awesome css -->
      <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
      <title>Login Smart Parking</title>
   </head>

   <style>
    html, body {
        height: 100%;
        margin: 0;
        font-family: 'Poppins', sans-serif;
        background: #000;
    }

    .login-box {
        background: rgba(255,255,255,0.95);
        padding: 40px;
        border-radius: 15px;
        max-width: 400px;
        width: 100%;
        box-shadow: 0 15px 30px rgba(0,0,0,0.5);
    }

    .banner_img {
        background: linear-gradient(to bottom, #000, #1a1a1a);
        display: flex;
        justify-content: center;
        align-items: center;
        border-radius: 15px;
        box-shadow: 0 15px 30px rgba(0,0,0,0.5);
        height: 100%;
    }

    .banner_img img {
        max-width: 100%;
        border-radius: 15px;
    }
   </style>

   <body>
      <!-- header section strats -->
      <header class="header_section">
        <div class="container">
            <nav class="navbar navbar-expand-lg custom_nav-container ">
            </nav>
         </div>
      </header>
      <!-- end header section -->

      <!-- banner section start --> 
      <div id="home" class="banner_section layout_padding">
        <div class="container-fluid">
            <div class="row align-items-center">
                <!-- Left: Login Form -->
                <div class="col-md-6 d-flex justify-content-center">
                    <div class="login-box">
                        <h2 class="banner_taital" style="color:#fc9d22;margin-bottom:20px;text-align:center;font-weight:bold;border-bottom:5px solid #fc9d22;display:inline-block;padding-bottom:15px;">
                            Login
                        </h2>

                        <!-- Form login -->
                        <form method="POST" action="">
                            <input type="text" name="username" placeholder="Username" required
                                   style="width:100%; padding:12px; margin:10px 0; border-radius:5px; border:1px solid #ccc;">

                            <input type="password" name="password" placeholder="Password" required
                                   style="width:100%; padding:12px; margin:10px 0; border-radius:5px; border:1px solid #ccc;">

                            <button type="submit"
                                    style="width:100%; padding:12px; background:#fc9d22; border:none; border-radius:5px; color:white; font-weight:bold; cursor:pointer; margin-top:10px;">
                                Masuk
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Right: Banner Image -->
                <div class="col-md-6 d-none d-md-block">
                    <div class="banner_img">
                        <img src="images/banner-img.png" alt="Smart Parking">
                    </div>
                </div>
            </div>
        </div>
      </div>

<?php if ($login_success && $user): ?>
<script>
    Swal.fire({
        icon: 'success',
        title: 'Login Berhasil!',
        text: 'Selamat datang, <?php echo htmlspecialchars($user['name'] ?? $user['username']); ?>!',
        showConfirmButton: false,
        timer: 1500
    }).then(() => {
        window.location.href = 'dashboard.php';
    });
</script>
<?php elseif (!empty($error)): ?>
<script>
    Swal.fire({
        icon: 'error',
        title: 'Login Gagal!',
        text: '<?php echo addslashes($error); ?>',
        confirmButtonColor: '#fc9d22'
    });
</script>
<?php endif; ?>

</body>
</html>
