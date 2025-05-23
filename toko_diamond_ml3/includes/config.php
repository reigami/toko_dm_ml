<?php
session_start();

// Koneksi database
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'toko_diamond_ml';

$conn = new mysqli($host, $user, $password, $database);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
} else {
    echo "";
}

// Fungsi dasar
function sanitize_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}
?>