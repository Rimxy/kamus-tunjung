<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Seputar Halaman - Kamus Tunjung</title>
    <link rel="icon" type="image/png" href="../logo-upr.png" />
    <link rel="shortcut icon" type="image/png" href="../logo-upr.png" />
    <link rel="stylesheet" href="../style.css?v=3" />
</head>

<body>
    <header class="header">
        <div class="logo">
            <img
                src="../logo-upr.png"
                alt="Logo Leksikon Borneo"
                class="logo-icon" />
            <div class="logo-text">Kamus Bahasa Tunjung</div>
        </div>

        <nav class="nav">
            <a href="../index">Kamus</a>
            <a href="seputar-halaman" class="active">Seputar Halaman</a>
        </nav>
    </header>

    <main class="main-container" style="text-align: left; max-width: 960px;">
        <h2 style="font-family: 'Merriweather', serif; color: var(--primary-color);">Seputar Laman</h2>

        <h3 style="color: var(--secondary-color); margin-top: 2rem; padding-bottom: 5px; border-bottom: 1px solid #ddd;">Pilihan</h3>
        <div class="sitemap-grid">
            <a href="tentang-kami" class="sitemap-btn">ℹ️ Tentang Kami</a>
            <a href="kontak" class="sitemap-btn">✉️ Kontak</a>
            <a href="penyusun" class="sitemap-btn">👥 Penyusun</a>
            <a href="kata-pengantar" class="sitemap-btn">📖 Kata Pengantar dan Prakata</a>
            <a href="#" class="sitemap-btn">❓ Bantuan</a>
            <a href="#" class="sitemap-btn">✨ Fitur</a>
            <a href="#" class="sitemap-btn">📊 Statistik</a>
            <a href="#" class="sitemap-btn">❤️ Apresiasi Masyarakat</a>
            <a href="#" class="sitemap-btn">⚖️ Hukum</a>
            <a href="petunjuk-pemakaian" class="sitemap-btn">📚 Petunjuk Pemakaian</a>
            <a href="#" class="sitemap-btn">🛠️ Petunjuk Teknis</a>
            <a href="#" class="sitemap-btn">▶️ Video Panduan</a>
        </div>
    </main>

    <div class="login-icon-container">
        <a href="../login" class="admin-login-icon" aria-label="Login Admin">
            <svg xmlns="http://www.w3.org/2000/svg" height="28" width="28" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z" />
            </svg>
            <span class="tooltip-text">Login Admin</span>
        </a>
    </div>

    <footer class="footer">
        <p>© 2025 Kamus Bahasa Tunjung</p>
    </footer>

    <script>
        // Tombol kembali ke Halaman Cari (Mode Default)
        document.getElementById('btn-kembali-cari')?.addEventListener('click', function(e) {
            e.preventDefault();
            // Simpan 'mode' di localStorage agar index.php tahu
            localStorage.setItem('defaultMode', 'cari');
            window.location.href = 'index';
        });

        // Tombol kembali ke Halaman Jelajah
        document.getElementById('btn-kembali-jelajah')?.addEventListener('click', function(e) {
            e.preventDefault();
            // Simpan 'mode' di localStorage agar index.php tahu
            localStorage.setItem('defaultMode', 'jelajah');
            window.location.href = 'index';
        });
    </script>
</body>

</html>