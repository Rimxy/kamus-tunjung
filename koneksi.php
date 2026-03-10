<?php

$host     = "localhost";
$username = "";
$password = "";
$database = "kamus_tunjung";
// -----------------------------------------

$conn = mysqli_connect($host, $username, $password, $database);

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");


// ==========================================================
// === FUNGSI LOGGING AKTIVITAS ===
// ==========================================================
/**
 * Mencatat aktivitas admin ke file teks.
 * File log akan disimpan di /admin/activity_log.txt
 *
 * @param string $action Deskripsi aksi (mis. "TAMBAH KATA")
 * @param string $details Detail data (mis. "ID: W01234, Kata: anum")
 */
function log_activity($action, $details = '')
{
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    $log_file = __DIR__ . '/admin/activity_log.txt';

    $timestamp = date('Y-m-d H:i:s');

    $username = $_SESSION['admin_username'] ?? 'Sistem';

    $log_entry = "[$timestamp] [$username] [$action] - $details" . PHP_EOL;

    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}
// ==========================================================
