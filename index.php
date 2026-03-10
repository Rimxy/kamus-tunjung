<?php
require 'koneksi.php';
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Kamus Dayak - Kamus Digital Dayak</title>
  <link rel="icon" type="image/png" href="logo-upr.png" />
  <link rel="shortcut icon" type="image/png" href="logo-upr.png" />
  <link rel="stylesheet" href="style.css?v=4" />
  <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
  <meta http-equiv="Pragma" content="no-cache">
  <meta http-equiv="Expires" content="0">
</head>

<body>
  <header class="header">
    <div class="logo">
      <img
        src="logo-upr.png"
        alt="Logo Leksikon Borneo"
        class="logo-icon" />
      <div class="logo-text">Kamus Bahasa Tunjung</div>
    </div>

    <nav class="nav">
      <a href="index" class="active">Kamus</a>
      <a href="seputar_halaman/seputar-halaman">Seputar Halaman</a>
    </nav>
  </header>


  <main class="main-container">
    <div style="text-align: center; margin: 2rem 0;">
      <h1>Selamat Datang di Kamus Tunjung</h1>
      <p style="font-size: 1.1rem; color: var(--secondary-color);">
        Silakan masukkan kata yang ingin Anda cari.
      </p>
    </div>
    <div class="mode-switcher">
      <button id="btn-mode-cari" class="mode-btn active">Cari Kata</button>
      <button id="btn-mode-jelajah" class="mode-btn">Jelajah Kamus</button>
    </div>

    <div id="search-mode-content">
      <div class="translation-direction">
        <button id="btn-search-id-dayak" class="direction-btn active">
          Indonesia → Bahasa Tunjung
        </button>
        <button id="btn-search-dayak-id" class="direction-btn">
          Bahasa Tunjung → Indonesia
        </button>
      </div>
      <form class="search-form" id="search-form">
        <input type="text" id="search-input" class="search-input" placeholder="Ketik kata Bahasa Indonesia..." autocomplete="off" />
        <button type="submit" class="search-button">CARI</button>
      </form>
      <div id="search-results-container">
      </div>
    </div>

    <div id="library-mode-content" style="display: none;">
      <div class="translation-direction">
        <button id="btn-id-dayak" class="direction-btn active">
          Indonesia → Bahasa Tunjung
        </button>
        <button id="btn-dayak-id" class="direction-btn">
          Bahasa Tunjung → Indonesia
        </button>
      </div>

      <div id="library-display">
        <div id="alphabet-container" class="alphabet-container">
        </div>
        <div id="word-list-container" class="word-list-container">
        </div>
        <div id="pagination-container" class="pagination-container">
        </div>
        <div id="library-results-container">
        </div>
      </div>
    </div>
  </main>

  <div class="login-icon-container">
    <a href="login" class="admin-login-icon" aria-label="Login Admin">
      <svg xmlns="http://www.w3.org/2000/svg" height="28" width="28" viewBox="0 0 24 24" fill="currentColor">
        <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z" />
      </svg>
      <span class="tooltip-text">Login Admin</span>
    </a>
  </div>
  <footer class="footer">
    <p>© 2025 Kamus Bahasa Tunjung</p>
  </footer>


  <script src="script.js?v=5"></script>
</body>

</html>