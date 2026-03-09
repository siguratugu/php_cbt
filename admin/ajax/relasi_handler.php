<?php
session_start();
require_once __DIR__ . '/../../includes/auth.php';
cekLogin('admin');
require_once __DIR__ . '/../../config/database.php';
header('Content-Type: application/json');

$action = $_REQUEST['action'] ?? '';

// ── GET guru list with counts ────────────────────────────────────────────────
if ($action === 'get_guru_list') {
    $sql = "SELECT g.id, g.nama,
                   COUNT(DISTINCT rg.mapel_id) AS jml_mapel,
                   COUNT(DISTINCT rg.kelas_id) AS jml_kelas
            FROM guru g
            LEFT JOIN relasi_guru rg ON g.id = rg.guru_id
            GROUP BY g.id, g.nama
            ORDER BY g.nama";
    $result = $conn->query($sql);
    $data = [];
    while ($row = $result->fetch_assoc()) $data[] = $row;
    echo json_encode(['status' => 'success', 'data' => $data]);
    exit;
}

// ── GET existing relasi for one guru ────────────────────────────────────────
if ($action === 'get_relasi') {
    $guru_id = (int)($_GET['guru_id'] ?? 0);
    if ($guru_id <= 0) { echo json_encode(['status' => 'error', 'message' => 'guru_id tidak valid']); exit; }

    $stmt = $conn->prepare("SELECT DISTINCT kelas_id FROM relasi_guru WHERE guru_id = ?");
    $stmt->bind_param("i", $guru_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $kelas_ids = [];
    while ($row = $res->fetch_assoc()) $kelas_ids[] = $row['kelas_id'];

    $stmt2 = $conn->prepare("SELECT DISTINCT mapel_id FROM relasi_guru WHERE guru_id = ?");
    $stmt2->bind_param("i", $guru_id);
    $stmt2->execute();
    $res2 = $stmt2->get_result();
    $mapel_ids = [];
    while ($row = $res2->fetch_assoc()) $mapel_ids[] = $row['mapel_id'];

    echo json_encode(['status' => 'success', 'kelas_ids' => $kelas_ids, 'mapel_ids' => $mapel_ids]);
    exit;
}

// ── GET all kelas list ───────────────────────────────────────────────────────
if ($action === 'get_kelas') {
    $result = $conn->query("SELECT id, nama_kelas FROM kelas ORDER BY CAST(SUBSTR(id,2) AS UNSIGNED)");
    $data = [];
    while ($row = $result->fetch_assoc()) $data[] = $row;
    echo json_encode(['status' => 'success', 'data' => $data]);
    exit;
}

// ── GET all mapel list ───────────────────────────────────────────────────────
if ($action === 'get_mapel') {
    $result = $conn->query("SELECT id, nama_mapel FROM mapel ORDER BY CAST(SUBSTR(id,2) AS UNSIGNED)");
    $data = [];
    while ($row = $result->fetch_assoc()) $data[] = $row;
    echo json_encode(['status' => 'success', 'data' => $data]);
    exit;
}

// ── SAVE relasi ──────────────────────────────────────────────────────────────
if ($action === 'save_relasi') {
    $guru_id   = (int)($_POST['guru_id'] ?? 0);
    $kelas_ids = $_POST['kelas_ids'] ?? [];
    $mapel_ids = $_POST['mapel_ids'] ?? [];

    if ($guru_id <= 0) { echo json_encode(['status' => 'error', 'message' => 'guru_id tidak valid']); exit; }

    // Delete all existing relasi for this guru
    $del = $conn->prepare("DELETE FROM relasi_guru WHERE guru_id = ?");
    $del->bind_param("i", $guru_id);
    $del->execute();

    // Insert all kelas × mapel combinations
    if (!empty($kelas_ids) && !empty($mapel_ids)) {
        $ins = $conn->prepare("INSERT INTO relasi_guru (guru_id, kelas_id, mapel_id) VALUES (?, ?, ?)");
        foreach ($kelas_ids as $kelas_id) {
            foreach ($mapel_ids as $mapel_id) {
                $kelas_id = trim($kelas_id);
                $mapel_id = trim($mapel_id);
                if (empty($kelas_id) || empty($mapel_id)) continue;
                $ins->bind_param("iss", $guru_id, $kelas_id, $mapel_id);
                $ins->execute();
            }
        }
    }

    echo json_encode(['status' => 'success', 'message' => 'Relasi guru berhasil disimpan']);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Action tidak dikenali']);
