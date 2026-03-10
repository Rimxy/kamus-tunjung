<?php
session_start();
// Cek apakah admin sudah login
if (!isset($_SESSION['admin_logged_in'])) {
  header("Location: login?error=Silakan login terlebih dahulu");
  exit();
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Admin - Kamus Tunjung</title>
  <link rel="icon" type="image/png" href="../logo-upr.png" />
  <link rel="stylesheet" href="admin.css">
</head>

<body>
  <header class="header">
    <div class="logo">
      <div class="logo">
        <img
          src="../logo-upr.png"
          alt="Logo Leksikon Borneo"
          class="logo-icon" />
        <div class="logo-text">Admin Kamus Tunjung</div>
      </div>
    </div>
  </header>
  <div class="admin-container">
    <div class="admin-header">
      <h1>Selamat Datang, <?php echo htmlspecialchars($_SESSION['admin_nama']); ?>!</h1>
      <a href="logout" class="btn-logout">Logout</a>
    </div>

    <h2>Panel Kontrol</h2>
    <p>Silakan pilih data yang ingin Anda kelola:</p>

    <div class="admin-nav">
      <a href="crud_kata">Kelola Kata dan Kalimat</a>
      <a href="crud_jenis_kata">Kelola Jenis Kata</a>
    </div>
  </div>
</body>

</html>