<?php
// Set timezone ke Asia/Jakarta (WIB)
date_default_timezone_set('Asia/Jakarta');

// Koneksi database
$host = "localhost";
$user = "root";
$pass = "";
$db   = "car_park_db";

$conn = new mysqli($host, $user, $pass, $db, 3306);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
?>
