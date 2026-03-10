<?php
require 'koneksi.php';

header('Content-Type: application/json');

$action = isset($_GET['action']) ? $_GET['action'] : '';
$direction = isset($_GET['direction']) ? $_GET['direction'] : 'indonesia_ke_dayak';

$response = [];

try {
    switch ($action) {
        case 'get_alphabets':
            $column = ($direction === 'indonesia_ke_dayak') ? 'kata_indonesia' : 'kata_tunjung';
            $sql = "SELECT DISTINCT UPPER(LEFT($column, 1)) AS alphabet 
                    FROM kata 
                    WHERE $column IS NOT NULL AND $column != '' 
                      AND UPPER(LEFT($column, 1)) BETWEEN 'A' AND 'Z' 
                    ORDER BY alphabet ASC";
            $result = $conn->query($sql);

            $alphabets = [];
            while ($row = $result->fetch_assoc()) {
                $alphabets[] = $row['alphabet'];
            }
            $response = ['success' => true, 'data' => $alphabets];
            break;
        case 'get_words':
            $letter = isset($_GET['letter']) ? $_GET['letter'] : '';
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = 20;

            if (empty($letter)) {
                throw new Exception("Parameter 'letter' tidak boleh kosong.");
            }
            if ($page < 1) {
                $page = 1;
            }

            $column = ($direction === 'indonesia_ke_dayak') ? 'kata_indonesia' : 'kata_tunjung';
            $search_letter = $letter . '%';

            // 1. Hitung total kata UNIK untuk pagination
            $sql_count = "SELECT COUNT(DISTINCT $column) as total FROM kata WHERE $column LIKE ?";
            $stmt_count = $conn->prepare($sql_count);
            $stmt_count->bind_param("s", $search_letter);
            $stmt_count->execute();
            $result_count = $stmt_count->get_result();
            $total_words = $result_count->fetch_assoc()['total'];
            $total_pages = ceil($total_words / $limit);
            $stmt_count->close();

            // 2. Hitung offset
            $offset = ($page - 1) * $limit;

            // 3. Ambil kata UNIK untuk halaman saat ini
            $sql = "SELECT DISTINCT $column AS word FROM kata WHERE $column LIKE ? ORDER BY $column ASC LIMIT ? OFFSET ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sii", $search_letter, $limit, $offset);
            $stmt->execute();
            $result = $stmt->get_result();

            $words = [];
            while ($row = $result->fetch_assoc()) {
                $words[] = $row['word'];
            }
            $stmt->close();

            // 4. Kembalikan data dalam format baru
            $response = [
                'success' => true,
                'data' => [
                    'words' => $words,
                    'totalPages' => $total_pages,
                    'currentPage' => $page
                ]
            ];
            break;

        default:
            throw new Exception("Aksi tidak valid.");
    }
} catch (Exception $e) {
    $response = ['success' => false, 'message' => $e->getMessage()];
}

// Mengirimkan hasil dalam format JSON
echo json_encode($response);

$conn->close();
