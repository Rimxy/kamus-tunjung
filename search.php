<?php
require 'koneksi.php';

$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$direction = isset($_GET['direction']) ? $_GET['direction'] : 'indonesia_ke_dayak';

if (empty($keyword)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Kata kunci tidak boleh kosong.']);
    exit();
}

$sql = "";
$baseQuery = "SELECT 
                k.kata_tunjung, 
                k.kata_indonesia, 
                k.kata_inggris, 
                k.turunan_kata, 
                j.nama_jenis AS jeniskata, 
                kal.kalimat_tunjung, 
                kal.kalimat_indonesia AS kalimat_indo, 
                kal.kalimat_inggris AS kalimat_inggris
              FROM kata k
              LEFT JOIN jenis_kata j ON k.id_jeniskata = j.id_jeniskata
              LEFT JOIN kalimat kal ON k.id_kalimat = kal.id_kalimat";

if ($direction === 'indonesia_ke_dayak') {
    $sql = $baseQuery . " 
        WHERE (k.kata_indonesia REGEXP ? OR k.kata_indonesia = ?)
        ORDER BY
          CASE
            -- Prioritas 1: Kecocokan persis (misal: 'air' atau 'geraham (hewan)')
            WHEN k.kata_indonesia = ? THEN 1
            
            -- Prioritas 2: Kecocokan sinonim (misal: 'mau, suka, ingin' ATAU 'air; sungai')
            WHEN (k.kata_indonesia LIKE '%,%' OR k.kata_indonesia LIKE '%;%') THEN 2
            
            -- Prioritas 3: Dimulai dengan kata itu (misal: 'suka menyalak')
            WHEN k.kata_indonesia LIKE ? THEN 3
            
            -- Prioritas 4: Kecocokan lain (misal: 'tidak mau')
            ELSE 4
          END ASC,
          
          -- Jika prioritas sama, utamakan yang LEBIH PENDEK
          LENGTH(k.kata_indonesia) ASC 
    ";

    $stmt = $conn->prepare($sql);

    $regexp_keyword = '[[:<:]]' . $conn->real_escape_string($keyword) . '[[:>:]]';
    $exact_keyword = $keyword;
    $like_keyword = $conn->real_escape_string($keyword) . '%';
    $stmt->bind_param("ssss", $regexp_keyword, $exact_keyword, $exact_keyword, $like_keyword);
} else {
    $sql = $baseQuery . " WHERE k.kata_tunjung = ? OR k.turunan_kata = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $keyword, $keyword);
}

$stmt->execute();
$result = $stmt->get_result();

$response = [];
if ($result->num_rows > 0) {

    $data_list = [];

    while ($data = $result->fetch_assoc()) {
        $data_list[] = [
            'kata_tunjung' => $data['kata_tunjung'],
            'kata_indonesia' => $data['kata_indonesia'],
            'kata_inggris' => $data['kata_inggris'],
            'turunan_kata' => $data['turunan_kata'],
            'jenis_kata' => $data['jeniskata'],
            'kalimat_tunjung' => $data['kalimat_tunjung'],
            'kalimat_indo' => $data['kalimat_indo'],
            'kalimat_inggris' => $data['kalimat_inggris']
        ];
    }

    $response = [
        'success' => true,
        'data' => $data_list
    ];
} else {
    $response = ['success' => false, 'message' => "Kata '$keyword' tidak ditemukan."];
}

header('Content-Type: application/json');
echo json_encode($response);

$stmt->close();
$conn->close();
