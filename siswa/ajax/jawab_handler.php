<?php
session_start();
date_default_timezone_set('Asia/Jakarta');
require_once __DIR__ . '/../../includes/auth.php';
cekLogin('siswa');
require_once __DIR__ . '/../../config/database.php';
header('Content-Type: application/json');

$action   = $_REQUEST['action'] ?? '';
$siswa_id = (int)$_SESSION['user_id'];

// Save answer
if ($action === 'simpan_jawaban') {
    $ujian_id = (int)($_POST['ujian_id'] ?? 0);
    $soal_id  = (int)($_POST['soal_id'] ?? 0);
    $jawaban  = trim($_POST['jawaban'] ?? '');

    if (!$ujian_id || !$soal_id) {
        echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap']);
        exit;
    }

    // Verify ownership
    $verif = $conn->prepare("SELECT us.id FROM ujian_siswa us WHERE us.id = ? AND us.siswa_id = ? AND us.status = 'sedang'");
    $verif->bind_param("ii", $ujian_id, $siswa_id);
    $verif->execute();
    if ($verif->get_result()->num_rows === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Akses tidak valid']);
        exit;
    }

    // Upsert answer
    $stmt = $conn->prepare(
        "INSERT INTO jawaban_siswa (ujian_siswa_id, soal_id, jawaban)
         VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE jawaban = VALUES(jawaban), answered_at = NOW()"
    );
    $stmt->bind_param("iis", $ujian_id, $soal_id, $jawaban);
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Jawaban disimpan']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan jawaban']);
    }
    exit;
}

// Toggle ragu-ragu
if ($action === 'set_ragu') {
    $ujian_id = (int)($_POST['ujian_id'] ?? 0);
    $soal_id  = (int)($_POST['soal_id'] ?? 0);
    $is_ragu  = (int)($_POST['is_ragu'] ?? 0);

    if (!$ujian_id || !$soal_id) {
        echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap']);
        exit;
    }

    $verif = $conn->prepare("SELECT id FROM ujian_siswa WHERE id = ? AND siswa_id = ? AND status = 'sedang'");
    $verif->bind_param("ii", $ujian_id, $siswa_id);
    $verif->execute();
    if ($verif->get_result()->num_rows === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Akses tidak valid']);
        exit;
    }

    $stmt = $conn->prepare(
        "INSERT INTO jawaban_siswa (ujian_siswa_id, soal_id, is_ragu)
         VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE is_ragu = VALUES(is_ragu)"
    );
    $stmt->bind_param("iii", $ujian_id, $soal_id, $is_ragu);
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'is_ragu' => $is_ragu]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal update status ragu']);
    }
    exit;
}

// Get status all questions (for number grid coloring)
if ($action === 'get_status') {
    $ujian_id = (int)($_GET['ujian_id'] ?? 0);

    $verif = $conn->prepare("SELECT id FROM ujian_siswa WHERE id = ? AND siswa_id = ?");
    $verif->bind_param("ii", $ujian_id, $siswa_id);
    $verif->execute();
    if ($verif->get_result()->num_rows === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Akses tidak valid']);
        exit;
    }

    $stmt = $conn->prepare(
        "SELECT soal_id, jawaban, is_ragu FROM jawaban_siswa WHERE ujian_siswa_id = ?"
    );
    $stmt->bind_param("i", $ujian_id);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $statusMap = [];
    foreach ($rows as $row) {
        $s = 'belum';
        if (!empty($row['jawaban'])) $s = 'dijawab';
        if ($row['is_ragu']) $s = 'ragu';
        $statusMap[$row['soal_id']] = $s;
    }

    echo json_encode(['status' => 'success', 'data' => $statusMap]);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Action tidak dikenali']);
