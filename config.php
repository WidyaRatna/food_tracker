<?php
// config.php - File konfigurasi untuk aplikasi Calorie Counter

// Pengaturan koneksi database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'web_login');

// Membuat koneksi ke database
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Set zona waktu ke WIB (Waktu Indonesia Barat)
date_default_timezone_set('Asia/Jakarta');
$conn->query("SET time_zone = '+07:00'");

// Opsional: Pengaturan lain jika diperlukan
define('APP_NAME', 'Calorie Counter');
define('BASE_URL', 'http://localhost/your_project_folder/'); // Ganti dengan URL proyek Anda

?>