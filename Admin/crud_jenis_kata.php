<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: ../login");
    exit();
}

require '../koneksi.php';

$status = $_GET['status'] ?? '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {
    $id_jeniskata = $_POST['id_jeniskata'];

    $nama_jenis = $_POST['nama_jenis'];
    $is_edit = !empty($_POST['id_lama']);

    if ($is_edit) {
        $id_lama = $_POST['id_lama'];
        $sql = "UPDATE jenis_kata SET id_jeniskata = ?, nama_jenis = ? WHERE id_jeniskata = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $id_jeniskata, $nama_jenis, $id_lama);
    } else {
        $sql = "INSERT INTO jenis_kata (id_jeniskata, nama_jenis) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $id_jeniskata, $nama_jenis);
    }

    if ($stmt->execute()) {
        $action_log = $is_edit ? "EDIT JENIS KATA" : "TAMBAH JENIS KATA";
        $details_log = "ID: $id_jeniskata, Nama: '$nama_jenis'";
        log_activity($action_log, $details_log);
        header("Location: crud_jenis_kata?status=sukses");
    } else {
        header("Location: crud_jenis_kata?status=gagal: " . urlencode($stmt->error));
    }
    $stmt->close();
    exit();
}

if (isset($_GET['hapus'])) {
    $id_jeniskata_to_delete = $_GET['hapus'];

    $conn->begin_transaction();
    try {
        $nama_jenis_deleted = "N/A";
        $stmt_get_nama = $conn->prepare("SELECT nama_jenis FROM jenis_kata WHERE id_jeniskata = ?");
        $stmt_get_nama->bind_param("s", $id_jeniskata_to_delete);
        $stmt_get_nama->execute();
        $result_nama = $stmt_get_nama->get_result();
        if ($row_nama = $result_nama->fetch_assoc()) {
            $nama_jenis_deleted = $row_nama['nama_jenis'];
        }
        $stmt_get_nama->close();

        $kalimat_ids_to_check = [];
        $sql_get_kalimat = "SELECT DISTINCT id_kalimat FROM kata WHERE id_jeniskata = ? AND id_kalimat IS NOT NULL";
        $stmt_get_kalimat = $conn->prepare($sql_get_kalimat);
        $stmt_get_kalimat->bind_param("s", $id_jeniskata_to_delete);
        $stmt_get_kalimat->execute();
        $result_kalimat = $stmt_get_kalimat->get_result();
        while ($row = $result_kalimat->fetch_assoc()) {
            $kalimat_ids_to_check[] = $row['id_kalimat'];
        }
        $stmt_get_kalimat->close();

        $sql_update_kata = "UPDATE kata SET id_jeniskata = NULL WHERE id_jeniskata = ?";
        $stmt_update_kata = $conn->prepare($sql_update_kata);
        $stmt_update_kata->bind_param("s", $id_jeniskata_to_delete);
        $stmt_update_kata->execute();
        $stmt_update_kata->close();

        $sql_del_jk = "DELETE FROM jenis_kata WHERE id_jeniskata = ?";
        $stmt_del_jk = $conn->prepare($sql_del_jk);
        $stmt_del_jk->bind_param("s", $id_jeniskata_to_delete);
        $stmt_del_jk->execute();
        $stmt_del_jk->close();

        $sql_del_kata_asli = "DELETE FROM kata WHERE id_jeniskata = ?";
        $stmt_del_kata_asli = $conn->prepare($sql_del_kata_asli);
        $stmt_del_kata_asli->bind_param("s", $id_jeniskata_to_delete);
        $stmt_del_kata_asli->execute();
        $stmt_del_kata_asli->close();

        if (!empty($kalimat_ids_to_check)) {
            $sql_check_usage = "SELECT COUNT(*) as count FROM kata WHERE id_kalimat = ?";
            $stmt_check = $conn->prepare($sql_check_usage);
            $sql_del_kalimat = "DELETE FROM kalimat WHERE id_kalimat = ?";
            $stmt_del_kalimat = $conn->prepare($sql_del_kalimat);

            foreach ($kalimat_ids_to_check as $id_kalimat) {
                $stmt_check->bind_param("s", $id_kalimat);
                $stmt_check->execute();
                $count = $stmt_check->get_result()->fetch_assoc()['count'];

                if ($count == 0) {
                    $stmt_del_kalimat->bind_param("s", $id_kalimat);
                    $stmt_del_kalimat->execute();
                }
            }
            $stmt_check->close();
            $stmt_del_kalimat->close();
        }
        log_activity("HAPUS JENIS KATA", "ID: $id_jeniskata_to_delete, Nama: '$nama_jenis_deleted'");

        $conn->commit();
        header("Location: crud_jenis_kata?status=dihapus");
    } catch (Exception $e) {
        $conn->rollback();
        header("Location: crud_jenis_kata?status=gagaldihapus: " . urlencode($e->getMessage()));
    }
    exit();
}

$edit_data = null;
$next_id_jeniskata = 'J001';
if (isset($_GET['edit'])) {
    $id_jeniskata = $_GET['edit'];
    $sql = "SELECT id_jeniskata, nama_jenis FROM jenis_kata WHERE id_jeniskata = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $id_jeniskata);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_data = $result->fetch_assoc();
    $stmt->close();
} else {
    $result = $conn->query("SELECT id_jeniskata FROM jenis_kata ORDER BY id_jeniskata DESC LIMIT 1");
    if ($result->num_rows > 0) {
        $last_id = $result->fetch_assoc()['id_jeniskata'];
        $num = (int) substr($last_id, 1); // Ambil angka dari 'J...'
        $next_id_jeniskata = 'J' . str_pad($num + 1, 3, '0', STR_PAD_LEFT); // Format J001, J002, dst.
    }
}

$sql_list = "
    SELECT 
        jk.id_jeniskata, 
        jk.nama_jenis, 
        COUNT(k.id_kata) AS jumlah_kata 
    FROM 
        jenis_kata jk
    LEFT JOIN 
        kata k ON jk.id_jeniskata = k.id_jeniskata 
    GROUP BY 
        jk.id_jeniskata, jk.nama_jenis 
    ORDER BY 
        jk.id_jeniskata ASC
";
$list_jenis_kata = $conn->query($sql_list);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Kelola Jenis Kata</title>
    <link rel="icon" type="image/png" href="../logo-upr.png" />
    <link rel="stylesheet" href="admin.css">

</head>

<body>
    <header class="header">
        <div class="logo">
            <img src="../logo-upr.png" alt="Logo Leksikon Borneo" class="logo-icon" />
            <div class="logo-text">Admin Kamus Tunjung</div>
        </div>
        <nav>
            <a href="logout" class="btn-logout">Logout</a>
        </nav>
    </header>
    <div class="admin-container">
        <a href="admin" style="text-decoration: none;">&larr; Kembali ke Dashboard</a>
        <h1 style="color: var(--primary-color); text-align: center;">Kelola Jenis Kata</h1>

        <?php if ($status == 'sukses' || $status == 'dihapus'): ?>
            <div class="message sukses">Data berhasil diproses!</div>
        <?php elseif (strpos($status, 'gagal') !== false): ?>
            <div class="message gagal">Terjadi kesalahan: <?php echo htmlspecialchars(urldecode(substr($status, strpos($status, ':') + 1))); ?></div>
        <?php endif; ?>

        <div class="form-crud">
            <h3><?php echo $edit_data ? '✍️ Edit' : '➕ Tambah'; ?> Jenis Kata</h3>
            <form action="crud_jenis_kata" method="POST">
                <?php if ($edit_data): ?>
                    <input type="hidden" name="id_lama" value="<?php echo htmlspecialchars($edit_data['id_jeniskata']); ?>">
                <?php endif; ?>

                <label for="id_jeniskata">ID Jenis Kata</label>
                <input type="text" id="id_jeniskata" name="id_jeniskata" value="<?php echo htmlspecialchars($edit_data['id_jeniskata'] ?? $next_id_jeniskata); ?>" required readonly>

                <label for="nama_jenis">Nama Jenis Kata</label>
                <input type="text" id="nama_jenis" name="nama_jenis" placeholder="Contoh: n (nomina)" value="<?php echo htmlspecialchars($edit_data['nama_jenis'] ?? ''); ?>" required>

                <button type="submit" name="submit" class="search-button"><?php echo $edit_data ? 'Update Data' : 'Simpan Data'; ?></button>
            </form>
        </div>

        <h2>Daftar Jenis Kata</h2>
        <div class="table-responsive-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nama Jenis</th>
                        <th>Jumlah Kata</th>
                        <th class="action-cell">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($list_jenis_kata->num_rows > 0): ?>
                        <?php while ($row = $list_jenis_kata->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['id_jeniskata']); ?></td>
                                <td><?php echo htmlspecialchars($row['nama_jenis']); ?></td>
                                <td><?php echo htmlspecialchars($row['jumlah_kata']); ?></td>
                                <td class="action-cell">
                                    <a href="crud_jenis_kata?edit=<?php echo urlencode($row['id_jeniskata']); ?>" class="btn-edit">Edit</a>
                                    <a href="crud_jenis_kata?hapus=<?php echo urlencode($row['id_jeniskata']); ?>" class="btn-hapus" onclick="return confirm('PERINGATAN: Menghapus jenis kata ini akan mengatur ulang (SET NULL) semua kata yang menggunakannya. Anda yakin?')">Hapus</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align: center;">Belum ada data.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>
<?php $conn->close(); ?>