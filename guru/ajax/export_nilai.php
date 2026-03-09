<?php
session_start();
date_default_timezone_set('Asia/Jakarta');
require_once __DIR__ . '/../../includes/auth.php';
cekLogin('guru');
require_once __DIR__ . '/../../config/database.php';

$ruangId = (int)($_GET['ruang_id'] ?? 0);
$guru_id = (int)$_SESSION['user_id'];

if (!$ruangId) {
    http_response_code(400);
    die('Parameter ruang_id diperlukan');
}

// Verify ownership
$verif = $conn->prepare("SELECT id, nama_ruang FROM ruang_ujian WHERE id = ? AND guru_id = ?");
$verif->bind_param("ii", $ruangId, $guru_id);
$verif->execute();
$ruang = $verif->get_result()->fetch_assoc();
if (!$ruang) {
    http_response_code(403);
    die('Akses ditolak');
}

$kelasFilter = isset($_GET['kelas']) ? trim($_GET['kelas']) : '';

$stmt = $conn->prepare("
    SELECT s.nama, k.nama_kelas, us.jumlah_benar, us.jumlah_salah, us.nilai, us.status
    FROM ujian_siswa us
    JOIN siswa s ON us.siswa_id = s.id
    LEFT JOIN kelas k ON s.kelas_id = k.id
    WHERE us.ruang_ujian_id = ?
    " . ($kelasFilter ? "AND k.id = ?" : "") . "
    ORDER BY s.nama
");
if ($kelasFilter) {
    $stmt->bind_param("is", $ruangId, $kelasFilter);
} else {
    $stmt->bind_param("i", $ruangId);
}
$stmt->execute();
$data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$namaFile = 'Nilai_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $ruang['nama_ruang']) . '.xls';

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="' . $namaFile . '"');
header('Cache-Control: max-age=0');
?>
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head>
<body>
<table border="1">
    <tr>
        <td colspan="6" style="font-size:14pt;font-weight:bold;text-align:center">
            Nilai Ujian <?= htmlspecialchars($ruang['nama_ruang']) ?>
        </td>
    </tr>
    <tr>
        <th style="background:#4472C4;color:white;font-weight:bold">No</th>
        <th style="background:#4472C4;color:white;font-weight:bold">Nama Siswa</th>
        <th style="background:#4472C4;color:white;font-weight:bold">Kelas</th>
        <th style="background:#4472C4;color:white;font-weight:bold">Jumlah Benar</th>
        <th style="background:#4472C4;color:white;font-weight:bold">Jumlah Salah</th>
        <th style="background:#4472C4;color:white;font-weight:bold">Nilai</th>
    </tr>
    <?php foreach ($data as $i => $row): ?>
    <tr>
        <td><?= $i + 1 ?></td>
        <td><?= htmlspecialchars($row['nama']) ?></td>
        <td><?= htmlspecialchars($row['nama_kelas'] ?? '-') ?></td>
        <td><?= $row['jumlah_benar'] ?? '-' ?></td>
        <td><?= $row['jumlah_salah'] ?? '-' ?></td>
        <td><?= $row['status'] === 'selesai' ? number_format((float)$row['nilai'], 2) : '-' ?></td>
    </tr>
    <?php endforeach; ?>
</table>
</body>
</html>
