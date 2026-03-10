<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
  header("Location: ../login");
  exit();
}
require '../koneksi.php';

$upload_dir = "../audio_dialek/";

if (!is_dir($upload_dir)) {
  mkdir($upload_dir, 0755, true);
}
if (!is_writable($upload_dir)) {
  die("Error: Folder '$upload_dir' tidak dapat ditulis. Ubah izin (permissions) folder.");
}

function prosesUploadDialek($conn, $id_kata_target)
{
  global $upload_dir;

  if (isset($_FILES['dialek_file']) && $_FILES['dialek_file']['error'] == UPLOAD_ERR_OK) {
    $file_tmp_path = $_FILES['dialek_file']['tmp_name'];
    $file_name_original = $_FILES['dialek_file']['name'];
    $file_size = $_FILES['dialek_file']['size'];
    $file_type = $_FILES['dialek_file']['type'];

    $allowed_mime_types = ['audio/mpeg', 'audio/wav', 'audio/x-wav', 'audio/mp3', 'audio/m4a', 'audio/ogg', 'audio/x-m4a'];
    if (!in_array($file_type, $allowed_mime_types)) {
      throw new Exception("Tipe file audio tidak valid. Harap unggah MP3, WAV, atau M4A.");
    }

    if ($file_size > 10 * 1024 * 1024) {
      throw new Exception("File audio terlalu besar. Maksimal 10MB.");
    }

    $file_extension = pathinfo($file_name_original, PATHINFO_EXTENSION);
    $new_file_name = $id_kata_target . '.' . $file_extension;
    $target_file_path = $upload_dir . $new_file_name;

    $existing_files = glob($upload_dir . $id_kata_target . ".*");
    foreach ($existing_files as $file) {
      if ($file != $target_file_path) {
        unlink($file);
      }
    }

    // 6. Pindahkan file
    if (move_uploaded_file($file_tmp_path, $target_file_path)) {
      return $new_file_name;
    } else {
      throw new Exception("Gagal memindahkan file yang diunggah.");
    }
  }

  return null;
}

function buatKalimatBaru($conn)
{
  $id_kalimat_baru = 'KAL00001';
  $result_kalimat = $conn->query("SELECT id_kalimat FROM kalimat ORDER BY id_kalimat DESC LIMIT 1");
  if ($result_kalimat->num_rows > 0) {
    $last_id = $result_kalimat->fetch_assoc()['id_kalimat'];
    $num = (int) substr($last_id, 3);
    $id_kalimat_baru = 'KAL' . str_pad($num + 1, 5, '0', STR_PAD_LEFT);
  }

  $sql_kalimat = "INSERT INTO kalimat (id_kalimat, kalimat_tunjung, kalimat_indonesia, kalimat_inggris) VALUES (?, ?, ?, ?)";
  $stmt_kalimat = $conn->prepare($sql_kalimat);
  $stmt_kalimat->bind_param(
    "ssss",
    $id_kalimat_baru,
    $_POST['kalimat_tunjung_new'],
    $_POST['kalimat_indonesia_new'],
    $_POST['kalimat_inggris_new']
  );
  $stmt_kalimat->execute();
  $stmt_kalimat->close();

  return $id_kalimat_baru;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {

  $conn->begin_transaction();
  try {
    $id_kata = $_POST['id_kata'];
    $kata_tunjung = $_POST['kata_tunjung'];
    $turunan_kata = $_POST['turunan_kata'];
    $kata_indonesia = $_POST['kata_indonesia'];
    $kata_inggris = $_POST['kata_inggris'];
    $id_jeniskata = $_POST['id_jeniskata'];

    $dialek_final_filename = $_POST['dialek_existing'] ?? null;

    $is_edit = !empty($_POST['id_lama']);
    $id_kalimat_final = null;

    if (isset($_POST['hapus_dialek']) && $_POST['hapus_dialek'] == '1') {
      if (!empty($dialek_final_filename) && file_exists($upload_dir . $dialek_final_filename)) {
        unlink($upload_dir . $dialek_final_filename);
      }
      $dialek_final_filename = null;
    } else {
      $file_baru = prosesUploadDialek($conn, $id_kata);
      if ($file_baru !== null) {
        $dialek_final_filename = $file_baru;
      }
    }

    if ($is_edit) {
      $id_lama = $_POST['id_lama'];
      $id_kalimat_original = !empty($_POST['id_kalimat']) ? $_POST['id_kalimat'] : null;
      $kalimat_baru_disediakan = !empty($_POST['kalimat_tunjung_new']);
      $id_kalimat_final = $id_kalimat_original;

      if ($kalimat_baru_disediakan) {
        if ($id_kalimat_original) {
          $sql_update_kalimat = "UPDATE kalimat SET kalimat_tunjung = ?, kalimat_indonesia = ?, kalimat_inggris = ? WHERE id_kalimat = ?";
          $stmt_uk = $conn->prepare($sql_update_kalimat);
          $stmt_uk->bind_param(
            "ssss",
            $_POST['kalimat_tunjung_new'],
            $_POST['kalimat_indonesia_new'],
            $_POST['kalimat_inggris_new'],
            $id_kalimat_original
          );
          $stmt_uk->execute();
          $stmt_uk->close();
          $id_kalimat_final = $id_kalimat_original;
        } else {
          $id_kalimat_final = buatKalimatBaru($conn);
        }
      } elseif (empty($_POST['kalimat_tunjung_new']) && $id_kalimat_original) {

        $id_kalimat_final = null;
      }

      $sql = "UPDATE kata SET id_kata = ?, kata_tunjung = ?, turunan_kata = ?, kata_indonesia = ?, kata_inggris = ?, id_jeniskata = ?, id_kalimat = ?, dialek = ? WHERE id_kata = ?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("sssssssss", $id_kata, $kata_tunjung, $turunan_kata, $kata_indonesia, $kata_inggris, $id_jeniskata, $id_kalimat_final, $dialek_final_filename, $id_lama);
    } else {
      if (!empty($_POST['kalimat_tunjung_new'])) {
        $id_kalimat_final = buatKalimatBaru($conn);
      }
      $sql = "INSERT INTO kata (id_kata, kata_tunjung, turunan_kata, kata_indonesia, kata_inggris, id_jeniskata, id_kalimat, dialek) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("ssssssss", $id_kata, $kata_tunjung, $turunan_kata, $kata_indonesia, $kata_inggris, $id_jeniskata, $id_kalimat_final, $dialek_final_filename);
    }

    if (!$stmt->execute()) {
      throw new Exception($stmt->error);
    }
    $stmt->close();

    $action_log = $is_edit ? "EDIT KATA" : "TAMBAH KATA";
    $details_log = "ID: $id_kata, Tunjung: '$kata_tunjung', Indo: '$kata_indonesia'";
    log_activity($action_log, $details_log);

    $conn->commit();
    header("Location: crud_kata?status=sukses");
  } catch (Exception $e) {
    $conn->rollback();
    header("Location: crud_kata?status=gagal: " . urlencode($e->getMessage()));
  }
  exit();
}

if (isset($_GET['hapus'])) {
  $id_kata_to_delete = $_GET['hapus'];
  $conn->begin_transaction();
  try {
    $id_kalimat_to_check = null;
    $dialek_to_delete = null;

    $stmt_get = $conn->prepare("SELECT id_kalimat, dialek FROM kata WHERE id_kata = ?");
    $stmt_get->bind_param("s", $id_kata_to_delete);
    $stmt_get->execute();
    $result = $stmt_get->get_result();

    if ($result->num_rows > 0) {
      $row = $result->fetch_assoc();
      $id_kalimat_to_check = $row['id_kalimat'];
      $dialek_to_delete = $row['dialek'];
    }
    $stmt_get->close();

    if (!empty($dialek_to_delete) && file_exists($upload_dir . $dialek_to_delete)) {
      unlink($upload_dir . $dialek_to_delete);
    }

    $stmt_del_kata = $conn->prepare("DELETE FROM kata WHERE id_kata = ?");
    $stmt_del_kata->bind_param("s", $id_kata_to_delete);
    $stmt_del_kata->execute();
    $stmt_del_kata->close();

    if ($id_kalimat_to_check) {
      $stmt_check = $conn->prepare("SELECT COUNT(*) as count FROM kata WHERE id_kalimat = ?");
      $stmt_check->bind_param("s", $id_kalimat_to_check);
      $stmt_check->execute();
      $count = $stmt_check->get_result()->fetch_assoc()['count'];
      $stmt_check->close();

      if ($count == 0) {
        $stmt_del_kalimat = $conn->prepare("DELETE FROM kalimat WHERE id_kalimat = ?");
        $stmt_del_kalimat->bind_param("s", $id_kalimat_to_check);
        $stmt_del_kalimat->execute();
        $stmt_del_kalimat->close();
      }
    }

    log_activity("HAPUS KATA", "ID: $id_kata_to_delete, Kata: '$kata_tunjung_deleted'");

    $conn->commit();
    header("Location: crud_kata?status=dihapus");
  } catch (Exception $e) {
    $conn->rollback();
    header("Location: crud_kata?status=gagaldihapus: " . $e->getMessage());
  }
  exit();
}

$opsi_jenis_kata = $conn->query("SELECT id_jeniskata, nama_jenis FROM jenis_kata ORDER BY nama_jenis");

$edit_data = null;
$next_id_kata = 'W00001';

if (isset($_GET['edit'])) {
  $id_kata = $_GET['edit'];
  $sql = "SELECT k.*, kal.kalimat_tunjung, kal.kalimat_indonesia, kal.kalimat_inggris 
            FROM kata k 
            LEFT JOIN kalimat kal ON k.id_kalimat = kal.id_kalimat 
            WHERE k.id_kata = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("s", $id_kata);
  $stmt->execute();
  $result = $stmt->get_result();
  $edit_data = $result->fetch_assoc();
  $stmt->close();
} else {
  $result_kata = $conn->query("SELECT id_kata FROM kata ORDER BY id_kata DESC LIMIT 1");
  if ($result_kata->num_rows > 0) {
    $last_id = $result_kata->fetch_assoc()['id_kata'];
    $num = (int) substr($last_id, 1);
    $next_id_kata = 'W' . str_pad($num + 1, 5, '0', STR_PAD_LEFT);
  }
}

$rows_per_page = 15;
$page = (int)($_GET['page'] ?? 1);
if ($page < 1) {
  $page = 1;
}
$offset = ($page - 1) * $rows_per_page;
$result_total = $conn->query("SELECT COUNT(*) as total FROM kata");
$total_rows = $result_total->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $rows_per_page);
if ($page > $total_pages && $total_pages > 0) {
  $page = $total_pages;
  $offset = ($page - 1) * $rows_per_page;
}
$list_kata_sql = "
    SELECT k.*, jk.nama_jenis AS jeniskata, kal.kalimat_indonesia, kal.kalimat_tunjung, kal.kalimat_inggris
    FROM kata k
    LEFT JOIN jenis_kata jk ON k.id_jeniskata = jk.id_jeniskata
    LEFT JOIN kalimat kal ON k.id_kalimat = kal.id_kalimat
    ORDER BY k.id_kata ASC
    LIMIT ? OFFSET ?
";
$stmt_list = $conn->prepare($list_kata_sql);
$stmt_list->bind_param("ii", $rows_per_page, $offset);
$stmt_list->execute();
$list_kata = $stmt_list->get_result();

$status = $_GET['status'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <title>Kelola Kata</title>
  <link rel="icon" type="image/png" href="../logo-upr.png" />
  <link rel="stylesheet" href="admin.css">
</head>

<body>
  <header class="header">
    <div class="logo">
      <img src="../logo-upr.png" alt="Logo Leksikon Borneo" class="logo-icon" />
      <div class="logo-text">Admin Kamus Tunjung</div>
    </div>
  </header>
  <div class="admin-container">
    <a href="admin" style="text-decoration: none;">&larr; Kembali ke Dashboard</a>
    <h1 style="color: var(--primary-color); text-align: center;">Kelola Kata Kamus</h1>

    <?php if ($status == 'sukses' || $status == 'dihapus'): ?>
      <div class="message sukses">Data berhasil diproses!</div>
    <?php elseif (strpos($status, 'gagal') !== false): ?>
      <div class="message gagal">Terjadi kesalahan: <?php echo htmlspecialchars(urldecode(substr($status, strpos($status, ':') + 1))); ?></div>
    <?php endif; ?>

    <div class="form-crud">
      <h3><?php echo $edit_data ? '✍️ Edit' : '➕ Tambah'; ?> Kata</h3>
      <form action="crud_kata" method="POST" enctype="multipart/form-data">
        <?php if ($edit_data): ?>
          <input type="hidden" name="id_lama" value="<?php echo htmlspecialchars($edit_data['id_kata']); ?>">
          <input type="hidden" name="id_kalimat" value="<?php echo htmlspecialchars($edit_data['id_kalimat']); ?>">
          <input type="hidden" name="dialek_existing" value="<?php echo htmlspecialchars($edit_data['dialek'] ?? ''); ?>">
        <?php endif; ?>

        <label for="id_kata">ID Kata (Otomatis)</label>
        <input type="text" id="id_kata" name="id_kata" value="<?php echo htmlspecialchars($edit_data['id_kata'] ?? $next_id_kata); ?>" required readonly>

        <label for="kata_tunjung">Kata Bahasa Tunjung</label>
        <input type="text" id="kata_tunjung" name="kata_tunjung" value="<?php echo htmlspecialchars($edit_data['kata_tunjung'] ?? ''); ?>" required>

        <label for="turunan_kata">Turunan Kata</label>
        <input type="text" id="turunan_kata" name="turunan_kata" value="<?php echo htmlspecialchars($edit_data['turunan_kata'] ?? ''); ?>">

        <label for="kata_indonesia">Kata Bahasa Indonesia</label>
        <input type="text" id="kata_indonesia" name="kata_indonesia" value="<?php echo htmlspecialchars($edit_data['kata_indonesia'] ?? ''); ?>">

        <label for="kata_inggris">Kata Bahasa Inggris</label>
        <input type="text" id="kata_inggris" name="kata_inggris" value="<?php echo htmlspecialchars($edit_data['kata_inggris'] ?? ''); ?>">

        <label for="id_jeniskata">Jenis Kata</label>
        <select id="id_jeniskata" name="id_jeniskata" required>
          <option value="">-- Pilih Jenis Kata --</option>
          <?php mysqli_data_seek($opsi_jenis_kata, 0);
          while ($jk = $opsi_jenis_kata->fetch_assoc()): ?>
            <option value="<?php echo $jk['id_jeniskata']; ?>" <?php echo (isset($edit_data) && $edit_data['id_jeniskata'] == $jk['id_jeniskata']) ? 'selected' : ''; ?> required>
              <?php echo htmlspecialchars($jk['nama_jenis']); ?>
            </option>
          <?php endwhile; ?>
        </select>

        <label for="dialek_file">File Audio Dialek (MP3, WAV, M4A)</label>

        <?php if (!empty($edit_data['dialek'])): ?>
          <div class="audio-player-container-small" style="margin-bottom: 10px;">
            <p style="margin: 0 0 5px 0;">Audio saat ini:</p>
            <audio controls src="<?php echo $upload_dir . htmlspecialchars($edit_data['dialek']); ?>">
              Browser Anda tidak mendukung audio.
            </audio>
            <div style="margin-top: 5px;">
              <input type="checkbox" name="hapus_dialek" id="hapus_dialek" value="1">
              <label for="hapus_dialek" style="display: inline; font-weight: normal;">Hapus audio ini</label>
            </div>
          </div>
          <label for="dialek_file" style="font-weight: normal; font-style: italic;">Unggah file baru di bawah ini untuk mengganti:</label>
        <?php endif; ?>

        <input type="file" id="dialek_file" name="dialek_file" accept="audio/mpeg,audio/wav,audio/m4a,audio/mp3,audio/ogg">

        <hr style="margin: 2rem 0;">
        <h3>➕ Contoh Kalimat</h3>

        <label for="kalimat_tunjung_new">Contoh Kalimat Bahasa Tunjung</label>
        <textarea id="kalimat_tunjung_new" name="kalimat_tunjung_new" rows="2"><?php echo htmlspecialchars($edit_data['kalimat_tunjung'] ?? ''); ?></textarea>

        <label for="kalimat_indonesia_new">Contoh Kalimat Bahasa Indonesia</label>
        <textarea id="kalimat_indonesia_new" name="kalimat_indonesia_new" rows="2"><?php echo htmlspecialchars($edit_data['kalimat_indonesia'] ?? ''); ?></textarea>

        <label for="kalimat_inggris_new">Contoh Kalimat Bahasa Inggris</label>
        <textarea id="kalimat_inggris_new" name="kalimat_inggris_new" rows="2"><?php echo htmlspecialchars($edit_data['kalimat_inggris'] ?? ''); ?></textarea>

        <button type="submit" name="submit" class="search-button"><?php echo $edit_data ? 'Update' : 'Simpan'; ?></button>
      </form>
    </div>


    <h2>Daftar Kata</h2>
    <div class="search-container" style="margin-bottom: 1.5rem;">
      <input type="text" id="live-search-input" placeholder="Ketik untuk mencari kata..." style="width: 100%; padding: 0.75rem; border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box;">
    </div>

    <div class="table-responsive-wrapper">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Kata Tunjung</th>
            <th>Turunan Kata</th>
            <th>Kata Indonesia</th>
            <th>Kata Inggris</th>
            <th>Jenis Kata</th>
            <th>Dialek (Audio)</th>
            <th>Kalimat Tunjung</th>
            <th>Kalimat Indonesia</th>
            <th>Kalimat Inggris</th>
            <th class="action-cell">Aksi</th>
          </tr>
        </thead>
        <tbody id="kata-results-body">
          <?php if ($list_kata->num_rows > 0): ?>
            <?php
            while ($row = $list_kata->fetch_assoc()): ?>
              <tr>
                <td><?php echo htmlspecialchars($row['id_kata']); ?></td>
                <td><?php echo htmlspecialchars($row['kata_tunjung']); ?></td>
                <td><?php echo htmlspecialchars($row['turunan_kata'] ?? 'N/A'); ?></td>
                <td><?php echo htmlspecialchars($row['kata_indonesia']); ?></td>
                <td><?php echo htmlspecialchars($row['kata_inggris']); ?></td>
                <td><?php echo htmlspecialchars($row['jeniskata']); ?></td>

                <td>
                  <?php if (!empty($row['dialek'])): ?>
                    <div class="audio-player-container-small">
                      <audio controls src="<?php echo $upload_dir . htmlspecialchars($row['dialek']); ?>">
                        Browser Anda tidak mendukung audio. (<?php echo htmlspecialchars($row['dialek']); ?>)
                      </audio>
                    </div>
                  <?php else: ?>
                    N/A
                  <?php endif; ?>
                </td>

                <td><?php echo htmlspecialchars($row['kalimat_tunjung'] ?? 'N/A'); ?></td>
                <td><?php echo htmlspecialchars($row['kalimat_indonesia'] ?? 'N/A'); ?></td>
                <td><?php echo htmlspecialchars($row['kalimat_inggris'] ?? 'N/A'); ?></td>
                <td class="action-cell"> <a href="crud_kata?edit=<?php echo urlencode($row['id_kata']); ?>" class="btn-edit">Edit</a>
                  <a href="crud_kata?hapus=<?php echo urlencode($row['id_kata']); ?>" class="btn-hapus" onclick="return confirm('Anda yakin ingin menghapus kata ini? INI JUGA AKAN MENGHAPUS FILE AUDIO TERKAIT.')">Hapus</a>
                </td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="11" style="text-align: center;">Belum ada data kata.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <div class="pagination-container" id="pagination-controls">
      <span class="pagination-info">
        Halaman <?php echo $page; ?> dari <?php echo $total_pages; ?> (Total <?php echo $total_rows; ?> kata)
      </span>
      <?php if ($total_pages > 1): ?>
        <div>
          <a href="?page=1" class="pagination-btn" <?php if ($page <= 1) {
                                                      echo 'style="pointer-events: none; opacity: 0.5;"';
                                                    } ?>>First</a>
          <a href="<?php if ($page <= 1) {
                      echo '#';
                    } else {
                      echo "?page=" . ($page - 1);
                    } ?>" class="pagination-btn" <?php if ($page <= 1) {
                                                    echo 'style="pointer-events: none; opacity: 0.5;"';
                                                  } ?>>Prev</a>
          <?php
          $window = 2;
          for ($i = max(1, $page - $window); $i <= min($total_pages, $page + $window); $i++):
          ?>
            <a href="?page=<?php echo $i; ?>" class="pagination-btn page-number <?php echo ($i == $page) ? 'active' : ''; ?>"><?php echo $i; ?></a>
          <?php endfor; ?>
          <a href="<?php if ($page >= $total_pages) {
                      echo '#';
                    } else {
                      echo "?page=" . ($page + 1);
                    } ?>" class="pagination-btn" <?php if ($page >= $total_pages) {
                                                    echo 'style="pointer-events: none; opacity: 0.5;"';
                                                  } ?>>Next</a>
          <a href="?page=<?php echo $total_pages; ?>" class="pagination-btn" <?php if ($page >= $total_pages) {
                                                                                echo 'style="pointer-events: none; opacity: 0.5;"';
                                                                              } ?>>Last</a>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const searchInput = document.getElementById('live-search-input');
      const resultsBody = document.getElementById('kata-results-body');
      const paginationControls = document.getElementById('pagination-controls');
      const originalPaginatedHtml = resultsBody.innerHTML;
      let lastQuery = '';

      function escapeHTML(str) {
        if (str === null || str === undefined) return '';
        const p = document.createElement('p');
        p.textContent = str;
        return p.innerHTML;
      }
      const minChar = 3;
      searchInput.addEventListener('input', function() {
        const query = this.value.trim();
        if (query.length >= minChar) {
          lastQuery = query;
          if (paginationControls) {
            paginationControls.style.display = 'none';
          }
          fetch(`live_search_kata?query=${encodeURIComponent(query)}`)
            .then(response => {
              if (!response.ok) {
                throw new Error('Network response was not ok');
              }
              return response.json();
            })
            .then(data => {
              resultsBody.innerHTML = '';
              if (data.length > 0) {
                data.forEach(row => {
                  const tr = document.createElement('tr');

                  let dialekHTML = 'N/A';
                  if (row.dialek) {
                    dialekHTML = `
                      <div class="audio-player-container-small">
                        <audio controls src="../audio_dialek/${escapeHTML(row.dialek)}"></audio>
                      </div>
                    `;
                  }

                  tr.innerHTML = `
                    <td>${escapeHTML(row.id_kata)}</td>
                    <td>${escapeHTML(row.kata_tunjung)}</td>
                    <td>${escapeHTML(row.turunan_kata) || 'N/A'}</td>
                    <td>${escapeHTML(row.kata_indonesia)}</td>
                    <td>${escapeHTML(row.kata_inggris)}</td>
                    <td>${escapeHTML(row.jeniskata) || 'N/A'}</td>
                    <td>${dialekHTML}</td> <!-- Kolom Dialek Diperbarui -->
                    <td>${escapeHTML(row.kalimat_tunjung) || 'N/A'}</td>
                    <td>${escapeHTML(row.kalimat_indonesia) || 'N/A'}</td> 
                    <td>${escapeHTML(row.kalimat_inggris) || 'N/A'}</td>
                    <td class="action-cell">
                        <a href="crud_kata?edit=${encodeURIComponent(row.id_kata)}" class="btn-edit">Edit</a>
                        <a href="crud_kata?hapus=${encodeURIComponent(row.id_kata)}" class="btn-hapus" onclick="return confirm('Anda yakin ingin menghapus kata ini?')">Hapus</a>
                    </td>
                  `;
                  resultsBody.appendChild(tr);
                });
              } else {
                resultsBody.innerHTML = `<tr><td colspan="11" style="text-align: center;">Tidak ada hasil untuk pencarian "${escapeHTML(query)}".</td></tr>`;
              }
            })
            .catch(error => {
              console.error('Error fetching data:', error);
              resultsBody.innerHTML = `<tr><td colspan="11" style="text-align: center;">Terjadi kesalahan saat memuat data.</td></tr>`;
            });
        } else if (query.length === 0 && lastQuery.length > 0) {
          lastQuery = '';
          if (paginationControls) {
            paginationControls.style.display = 'flex';
          }
          resultsBody.innerHTML = originalPaginatedHtml;
        } else if (query.length > 0 && query.length < minChar) {
          lastQuery = query;
          if (paginationControls) {
            paginationControls.style.display = 'none';
          }
          resultsBody.innerHTML = `<tr><td colspan="11" style="text-align: center;">Ketik ${minChar} karakter atau lebih untuk mencari...</td></tr>`;
        }
      });
    });
  </script>
</body>

</html>
<?php
$stmt_list->close();
$conn->close();
?>