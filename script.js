document.addEventListener("DOMContentLoaded", () => {
  const defaultMode = localStorage.getItem("defaultMode");
  if (defaultMode) {
    alihkanMode(defaultMode);
    localStorage.removeItem("defaultMode");
  }

  const btnModeCari = document.getElementById("btn-mode-cari");
  const btnModeJelajah = document.getElementById("btn-mode-jelajah");
  const searchModeContent = document.getElementById("search-mode-content");
  const libraryModeContent = document.getElementById("library-mode-content");
  const btnSearchIdToDayak = document.getElementById("btn-search-id-dayak");
  const btnSearchDayakToId = document.getElementById("btn-search-dayak-id");
  const searchForm = document.getElementById("search-form");
  const searchInput = document.getElementById("search-input");
  const searchResultsContainer = document.getElementById(
    "search-results-container",
  );
  const btnLibIdToDayak = document.getElementById("btn-id-dayak");
  const btnLibDayakToId = document.getElementById("btn-dayak-id");
  const alphabetContainer = document.getElementById("alphabet-container");
  const wordListContainer = document.getElementById("word-list-container");
  const paginationContainer = document.getElementById("pagination-container");
  const libraryResultsContainer = document.getElementById(
    "library-results-container",
  );

  // === STATE APLIKASI ===
  let arahCari = "indonesia_ke_dayak";
  let arahJelajah = "indonesia_ke_dayak";
  let abjadSudahDimuat = false;
  let hurufAktif = "";
  let halamanAktif = 1;

  // === FUNGSI UTAMA ===

  function alihkanMode(mode) {
    if (mode === "cari") {
      searchModeContent.style.display = "block";
      libraryModeContent.style.display = "none";
      btnModeCari.classList.add("active");
      btnModeJelajah.classList.remove("active");
    } else {
      // mode 'jelajah'
      searchModeContent.style.display = "none";
      libraryModeContent.style.display = "block";
      btnModeCari.classList.remove("active");
      btnModeJelajah.classList.add("active");

      if (!abjadSudahDimuat) {
        tampilkanAbjad();
        abjadSudahDimuat = true;
      }
    }
  }

  function gantiArahCari(arah) {
    arahCari = arah;
    searchInput.value = "";
    searchResultsContainer.innerHTML = "";
    if (arah === "indonesia_ke_dayak") {
      btnSearchIdToDayak.classList.add("active");
      btnSearchDayakToId.classList.remove("active");
      searchInput.placeholder = "Ketik kata Bahasa Indonesia...";
    } else {
      btnSearchDayakToId.classList.add("active");
      btnSearchIdToDayak.classList.remove("active");
      searchInput.placeholder = "Ketik kata Bahasa Tunjung...";
    }
  }

  function gantiArahJelajah(arah) {
    arahJelajah = arah;
    wordListContainer.innerHTML = "";
    libraryResultsContainer.innerHTML = "";
    paginationContainer.innerHTML = "";

    if (arah === "indonesia_ke_dayak") {
      btnLibIdToDayak.classList.add("active");
      btnLibDayakToId.classList.remove("active");
    } else {
      // (arah === "dayak_ke_indonesia")
      btnLibIdToDayak.classList.remove("active");
      btnLibDayakToId.classList.add("active");
    }

    tampilkanAbjad();
  }

  // --- FUNGSI ASINKRON (FETCH DATA) ---

  async function tampilkanAbjad() {
    alphabetContainer.innerHTML = "<p>Memuat abjad...</p>";
    try {
      const url = `library.php?action=get_alphabets&direction=${arahJelajah}&_cache=${new Date().getTime()}`;
      const response = await fetch(url);
      const result = await response.json();

      alphabetContainer.innerHTML = ""; // Bersihkan dulu
      if (result.success && result.data.length > 0) {
        result.data.forEach((huruf) => {
          const linkHuruf = document.createElement("a");
          linkHuruf.href = "#";
          linkHuruf.textContent = huruf;
          linkHuruf.className = "alphabet-link";
          linkHuruf.addEventListener("click", (e) => {
            e.preventDefault();
            document
              .querySelectorAll(".alphabet-link.active")
              .forEach((el) => el.classList.remove("active"));
            linkHuruf.classList.add("active");
            tampilkanKata(huruf, 1); // Selalu mulai dari halaman 1
          });
          alphabetContainer.appendChild(linkHuruf);
        });
      } else {
        alphabetContainer.innerHTML =
          "<p>Tidak ada data abjad untuk arah ini.</p>";
      }
    } catch (error) {
      alphabetContainer.innerHTML = `<div class="no-result">Gagal memuat abjad.</div>`;
    }
  }

  async function tampilkanKata(huruf, page) {
    wordListContainer.innerHTML = "<p>Memuat kata...</p>";
    libraryResultsContainer.innerHTML = "";
    paginationContainer.innerHTML = ""; // Bersihkan pagination

    // Simpan state saat ini
    hurufAktif = huruf;
    halamanAktif = page;

    try {
      const url = `library.php?action=get_words&direction=${arahJelajah}&letter=${huruf}&page=${page}&_cache=${new Date().getTime()}`;
      const response = await fetch(url);
      const result = await response.json();

      wordListContainer.innerHTML = ""; // Bersihkan dulu
      if (result.success && result.data.words.length > 0) {
        // 1. Tampilkan daftar kata (untuk halaman ini)
        result.data.words.forEach((kata) => {
          const itemKata = document.createElement("a");
          itemKata.href = "#";
          // Pastikan kata tidak null/undefined sebelum di-render
          const kataTampil = kata || "";
          itemKata.textContent =
            kataTampil.charAt(0).toUpperCase() + kataTampil.slice(1);
          itemKata.className = "word-item";
          itemKata.addEventListener("click", (e) => {
            e.preventDefault();
            document
              .querySelectorAll(".word-item.active")
              .forEach((el) => el.classList.remove("active"));
            itemKata.classList.add("active");

            cariDanTampilkan(kata, libraryResultsContainer, arahJelajah);
          });
          wordListContainer.appendChild(itemKata);
        });

        // 2. Buat Tombol Pagination
        const totalPages = result.data.totalPages;
        const currentPage = result.data.currentPage;

        if (totalPages > 1) {
          // Tombol "Sebelumnya"
          const btnPrev = document.createElement("button");
          btnPrev.innerHTML = "&laquo; Sebelumnya";
          btnPrev.className = "pagination-btn";
          if (currentPage === 1) {
            btnPrev.disabled = true;
          }
          btnPrev.addEventListener("click", () => {
            tampilkanKata(hurufAktif, halamanAktif - 1);
          });

          // Info Halaman
          const pageInfo = document.createElement("span");
          pageInfo.className = "pagination-info";
          pageInfo.textContent = `Halaman ${currentPage} dari ${totalPages}`;

          // Tombol "Berikutnya"
          const btnNext = document.createElement("button");
          btnNext.innerHTML = "Berikutnya &raquo;";
          btnNext.className = "pagination-btn";
          if (currentPage === totalPages) {
            btnNext.disabled = true;
          }
          btnNext.addEventListener("click", () => {
            tampilkanKata(hurufAktif, halamanAktif + 1);
          });

          paginationContainer.appendChild(btnPrev);
          paginationContainer.appendChild(pageInfo);
          paginationContainer.appendChild(btnNext);
        }
      } else {
        wordListContainer.innerHTML = `<p>Tidak ada kata yang diawali huruf '${huruf}'.</p>`;
      }
    } catch (error) {
      wordListContainer.innerHTML = `<div class="no-result">Gagal memuat kata.</div>`;
    }
  }

  /**
   * ==========================================================
   * FUNGSI PENCARIAN (PERBAIKAN LOGIKA INDO -> TUNJUNG)
   * ==========================================================
   */
  // GANTI SELURUH FUNGSI INI DI script.js ANDA
  async function cariDanTampilkan(kata, container, direction) {
    container.innerHTML = "<p>Mencari...</p>";
    try {
      const url = `search.php?keyword=${encodeURIComponent(
        kata,
      )}&direction=${direction}&_cache=${new Date().getTime()}`;
      const response = await fetch(url);
      if (!response.ok) throw new Error("Respons server bermasalah.");

      const result = await response.json();

      if (container === libraryResultsContainer) {
        container.scrollIntoView({ behavior: "smooth", block: "start" });
      }

      container.innerHTML = "";

      if (
        result.success &&
        Array.isArray(result.data) &&
        result.data.length > 0
      ) {
        let allCardsHTML = "";

        // Tentukan kata dasar (hasil pertama)
        const mainData = result.data[0];
        let mainCardHTML = "";
        let derivativesHTML = "";

        // === KODE BARU UNTUK IKON SVG ===
        const speakerSVG = `<svg class="speaker-svg" xmlns="http://www.w3.org/2000/svg" height="24" width="24" viewBox="0 0 24 24" fill="currentColor"><path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02zM14 3.23v2.06c2.89.86 5 3.54 5 6.71s-2.11 5.85-5 6.71v2.06c4.01-.91 7-4.49 7-8.77s-2.99-7.86-7-8.77z"/></svg>`;
        // =================================

        // Tentukan Tampilan Kartu Utama berdasarkan arah pencarian
        let kataJudul, labelJudul, kataJudulLang;
        let terjemahanValue, terjemahanLang, terjemahanLabel;
        let blokKataDasar = "";

        if (direction === "indonesia_ke_dayak") {
          // ==============================
          // Arah: INDONESIA -> TUNJUNG
          // ==============================
          kataJudul = mainData.kata_indonesia;
          labelJudul = "Bahasa Indonesia";
          kataJudulLang = "id-ID";

          terjemahanLang = "id-ID";
          terjemahanLabel = "Bahasa Tunjung";

          if (mainData.turunan_kata) {
            terjemahanValue = mainData.turunan_kata;
            blokKataDasar = `
            <div class="translation-block" style="margin-top: 1rem; background-color: #f7f7f7; padding: 0.5rem 1rem; border-radius: 4px;">
                <span class="label">KATA DASAR (B. Tunjung)</span>
                <span class="text" style="font-size: 1.5rem;">${mainData.kata_tunjung}</span>
                <span class="speaker-icon" onclick="ucapkan('${mainData.kata_tunjung}', 'id-ID')">${speakerSVG}</span>
            </div>
          `;
          } else {
            terjemahanValue = mainData.kata_tunjung;
          }
          // ===================================
        } else {
          // ==============================
          // Arah: TUNJUNG -> INDONESIA
          // ==============================
          terjemahanValue = mainData.kata_indonesia;
          terjemahanLang = "id-ID";
          terjemahanLabel = "Bahasa Indonesia";

          if (
            mainData.turunan_kata &&
            mainData.turunan_kata.toLowerCase() === kata.toLowerCase()
          ) {
            kataJudul = mainData.turunan_kata;
            labelJudul = "Turunan B. Tunjung";
            kataJudulLang = "id-ID";

            blokKataDasar = `
            <div class="translation-block" style="margin-top: 1rem; background-color: #f7f7f7; padding: 0.5rem 1rem; border-radius: 4px;">
                <span class="label">KATA DASAR (B. Tunjung)</span>
                <span class="text" style="font-size: 1.5rem;">${mainData.kata_tunjung}</span>
                <span class="speaker-icon" onclick="ucapkan('${mainData.kata_tunjung}', 'id-ID')">${speakerSVG}</span>
            </div>
          `;
          } else {
            kataJudul = mainData.kata_tunjung;
            labelJudul = "Kata Dasar B. Tunjung";
            kataJudulLang = "id-ID";
          }

          if (result.data.length > 1) {
            derivativesHTML = `
            <details class="derivatives-dropdown">
                <summary>Lihat ${
                  result.data.length - 1
                } turunan kata lainnya</summary>
                <div class="derivatives-list">
          `;
            result.data.slice(1).forEach((turunanData) => {
              derivativesHTML += `
                <div class="derivative-item">
                    <span class="derivative-word">${
                      turunanData.turunan_kata || "N/A"
                    } 
                      <span class="speaker-icon" onclick="ucapkan('${
                        turunanData.turunan_kata || ""
                      }', 'id-ID')">${speakerSVG}</span>
                    </span>
                    <span class="derivative-pos">(${
                      turunanData.jenis_kata || "N/A"
                    })</span>
                    <p class="derivative-translation"><strong>Arti:</strong> ${
                      turunanData.kata_indonesia || "-"
                    }</p>
                    <p class="derivative-example"><em>"${
                      turunanData.kalimat_tunjung || "-"
                    }"</em>
                      <span class="speaker-icon" onclick="ucapkan('${
                        turunanData.kalimat_tunjung || ""
                      }', 'id-ID')">${speakerSVG}</span>
                    </p>
                    <p class="derivative-meaning">(Artinya: ${
                      turunanData.kalimat_indo || "-"
                    })</p>
                    <p class="derivative-meaning" style="margin-top: 0.25rem;">(Meaning: ${
                      turunanData.kalimat_inggris || "-"
                    }
                      <span class="speaker-icon" onclick="ucapkan('${
                        turunanData.kalimat_inggris || ""
                      }', 'en-US')">${speakerSVG}</span>
                    </p>
                </div>
            `;
            });
            derivativesHTML += `</div></details>`;
          }
        }

        let jenisKataHTML = "";
        if (mainData.jenis_kata) {
          jenisKataHTML = `
          <div class="translation-block" style="margin-top: 1rem;">
              <span class="label">JENIS KATA</span>
              <span class="text" style="font-size: 1.3rem; font-style: italic; color: #555;">${mainData.jenis_kata}</span>
          </div>
          `;
        }

        mainCardHTML = `
          <div class="result-card">
              <h3>${labelJudul}: <span>${kataJudul || "N/A"}</span>
                  <span class="speaker-icon" onclick="ucapkan('${
                    kataJudul || ""
                  }', '${kataJudulLang}')">${speakerSVG}</span>
              </h3><hr>
              
              ${blokKataDasar}

              <div class="translation-block">
                  <span class="label">TERJEMAHAN (${terjemahanLabel})</span>
                  <span class="text">${terjemahanValue || "-"}</span>
                  <span class="speaker-icon" onclick="ucapkan('${
                    terjemahanValue || ""
                  }', '${terjemahanLang}')">${speakerSVG}</span>
              </div>
              
              ${jenisKataHTML}

              <div class="translation-block" style="margin-top: 1.5rem;">
                  <span class="label">TERJEMAHAN (Bahasa Inggris)</span>
                  <span class="text" style="font-size: 1.5rem;">${
                    mainData.kata_inggris || "-"
                  }</span>
                  <span class="speaker-icon" onclick="ucapkan('${
                    mainData.kata_inggris || ""
                  }', 'en-US')">${speakerSVG}</span>
              </div>

              <div class="example-block" style="margin-top: 1.5rem;">
                  <span class="label">CONTOH PENGGUNAAN KALIMAT (Bahasa Tunjung)</span>
                  <p class="sentence">"${mainData.kalimat_tunjung || "-"}"
                      <span class="speaker-icon" onclick="ucapkan('${
                        mainData.kalimat_tunjung || ""
                      }', 'id-ID')">${speakerSVG}</span>
                  </p>
                  <p class="meaning">(Artinya: ${
                    mainData.kalimat_indo || "-"
                  })</p>
                  <p class="meaning" style="margin-top: 0.5rem;">(Meaning: ${
                    mainData.kalimat_inggris || "-"
                  }
                      <span class="speaker-icon" onclick="ucapkan('${
                        mainData.kalimat_inggris || ""
                      }', 'en-US')">${speakerSVG}</span>
                  </p>
              </div>
              
              ${derivativesHTML}
          </div>
      `;
        container.innerHTML = mainCardHTML;
      } else {
        container.innerHTML = `<div class="no-result">Maaf, kata "${kata}" tidak ditemukan dalam kamus.</div>`;
      }
    } catch (error) {
      container.innerHTML = `<div class="no-result">Terjadi kesalahan: ${error.message}</div>`;
    }
  }

  // === EVENT LISTENERS ===
  btnModeCari.addEventListener("click", () => alihkanMode("cari"));
  btnModeJelajah.addEventListener("click", () => alihkanMode("jelajah"));

  btnSearchIdToDayak.addEventListener("click", () =>
    gantiArahCari("indonesia_ke_dayak"),
  );
  btnSearchDayakToId.addEventListener("click", () =>
    gantiArahCari("dayak_ke_indonesia"),
  );
  searchForm.addEventListener("submit", (event) => {
    event.preventDefault();
    const kataKunci = searchInput.value.trim();
    if (kataKunci) {
      cariDanTampilkan(kataKunci, searchResultsContainer, arahCari);
    }
  });

  btnLibIdToDayak.addEventListener("click", () =>
    gantiArahJelajah("indonesia_ke_dayak"),
  );
  btnLibDayakToId.addEventListener("click", () =>
    gantiArahJelajah("dayak_ke_indonesia"),
  );
});

// Fungsi suara (Global)
function ucapkan(teks, lang = "id-ID") {
  if ("speechSynthesis" in window) {
    window.speechSynthesis.cancel();
    const ucapan = new SpeechSynthesisUtterance(teks);
    ucapan.lang = lang;
    window.speechSynthesis.speak(ucapan);
  } else {
    alert("Maaf, browser Anda tidak mendukung fitur suara.");
  }
}
