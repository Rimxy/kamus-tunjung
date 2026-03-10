<?php

require '../koneksi.php';

header('Content-Type: application/json');

$search_query = $_GET['query'] ?? '';

$sql = "
    SELECT 
        k.id_kata, k.kata_tunjung, k.turunan_kata, k.kata_indonesia, k.kata_inggris, 
        k.dialek, 
        jk.nama_jenis AS jeniskata, 
        kal.kalimat_tunjung, kal.kalimat_indonesia, kal.kalimat_inggris
    FROM kata k
    LEFT JOIN jenis_kata jk ON k.id_jeniskata = jk.id_jeniskata
    LEFT JOIN kalimat kal ON k.id_kalimat = kal.id_kalimat
";

$params = [];
$types = "";

if (!empty($search_query)) {
    $sql .= " WHERE LOWER(k.kata_tunjung) LIKE ? 
                OR LOWER(k.kata_indonesia) LIKE ? 
                OR LOWER(k.kata_inggris) LIKE ?
                OR LOWER(k.turunan_kata) LIKE ?
                OR LOWER(k.dialek) LIKE ?";

    $search_term = "%" . strtolower($search_query) . "%";

    $params = [$search_term, $search_term, $search_term, $search_term, $search_term];
    $types = "sssss";
}

$sql .= " ORDER BY k.id_kata ASC";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

$stmt->close();
$conn->close();

echo json_encode($data);
