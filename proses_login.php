<?php
session_start();
require 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

function catat_log($pesan)
{
    $file_log = 'login_log.txt';
    $waktu = date('d-m-Y H:i:s');
    $isi_log = "[$waktu] " . $pesan . "\n";
    file_put_contents($file_log, $isi_log, FILE_APPEND);
}

// Ambil data dari form
$username = $_POST['username'];
$password = $_POST['password'];

if (empty($username) || empty($password)) {
    header("Location: login?error=Username dan Password tidak boleh kosong");
    exit();
}

// Cari user di database
$sql = "SELECT * FROM admin WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $admin = $result->fetch_assoc();
    if (password_verify($password, $admin['password'])) {
        catat_log("Login BERHASIL - User: '$username'");
        session_regenerate_id(true);

        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $admin['username'];
        $_SESSION['admin_nama'] = $admin['nama'];
        header("Location: Admin/admin");
        exit();
    } else {
        catat_log("Login GAGAL - Password salah untuk user: '$username'");
        header("Location: login?error=Username atau Password salah");
        exit();
    }
} else {
    catat_log("Login GAGAL - User tidak ditemukan: '$username'");
    header("Location: login?error=Username atau Password salah");
    exit();
}

$stmt->close();
$conn->close();
