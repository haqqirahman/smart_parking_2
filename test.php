<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "car_park_db";

$conn = new mysqli($host, $user, $pass, $db);

$q = $conn->query("SELECT * FROM users WHERE username='admin' AND password='admin123'");

if($q->num_rows > 0){
    echo "LOGIN BERHASIL!";
} else {
    echo "LOGIN GAGAL!";
}
?>
