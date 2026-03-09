<?php
session_start();
require_once __DIR__ . '/../../includes/auth.php';
cekLogin('admin');
require_once __DIR__ . '/../../config/database.php';
header('Content-Type: application/json');

$action = $_REQUEST['action'] ?? '';

// ===================== GET_ALL =====================
if ($action === 'get_all') {
    $page    = max(1, (int)($_GET['page'] ?? 1));
    $perPage = $_GET['per_page'] ?? '10';

    $total = $conn->query("SELECT COUNT(*) AS c FROM pengumuman")->fetch_assoc()['c'];

    $baseQuery = "
        SELECT p.id, p.judul, p.isi, p.created_at,
               GROUP_CONCAT(k.nama_kelas ORDER BY k.nama_kelas SEPARATOR ', ') AS kelas_list
        FROM pengumuman p
        LEFT JOIN pengumuman_kelas pk ON p.id = pk.pengumuman_id
        LEFT JOIN kelas k ON pk.kelas_id = k.id
        GROUP BY p.id
        ORDER BY p.id DESC
    ";

    if ($perPage === 'all') {
        $result = $conn->query($baseQuery);
        $data = [];
        while ($row = $result->fetch_assoc()) $data[] = $row;
        echo json_encode(['status' => 'success', 'data' => $data, 'total' => $total, 'per_page' => 'all', 'page' => 1]);
    } else {
        $perPage = (int)$perPage;
        $offset  = ($page - 1) * $perPage;
        $stmt = $conn->prepare($baseQuery . " LIMIT ? OFFSET ?");
        $stmt->bind_param("ii", $perPage, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = [];
        while ($row = $result->fetch_assoc()) $data[] = $row;
        echo json_encode(['status' => 'success', 'data' => $data, 'total' => $total, 'per_page' => $perPage, 'page' => $page]);
    }
    exit;
}

// ===================== GET_DETAIL =====================
if ($action === 'get_detail') {
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) { echo json_encode(['status' => 'error', 'message' => 'ID tidak valid']); exit; }

    $stmt = $conn->prepare("SELECT * FROM pengumuman WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();
    if (!$data) { echo json_encode(['status' => 'error', 'message' => 'Data tidak ditemukan']); exit; }

    $kelasStmt = $conn->prepare("SELECT kelas_id FROM pengumuman_kelas WHERE pengumuman_id = ?");
    $kelasStmt->bind_param("i", $id);
    $kelasStmt->execute();
    $kelasResult = $kelasStmt->get_result();
    $kelasIds = [];
    while ($kr = $kelasResult->fetch_assoc()) $kelasIds[] = $kr['kelas_id'];

    echo json_encode(['status' => 'success', 'data' => $data, 'kelas_ids' => $kelasIds]);
    exit;
}

// ===================== ADD =====================
if ($action === 'add') {
    $judul    = trim($_POST['judul'] ?? '');
    $isi      = trim($_POST['isi'] ?? '');
    $kelasIds = $_POST['kelas_ids'] ?? [];
    $adminId  = $_SESSION['user_id'];

    if (empty($judul) || empty($isi)) {
        echo json_encode(['status' => 'error', 'message' => 'Judul dan isi wajib diisi']); exit;
    }
    if (empty($kelasIds)) {
        echo json_encode(['status' => 'error', 'message' => 'Pilih minimal satu kelas']); exit;
    }

    $stmt = $conn->prepare("INSERT INTO pengumuman (judul, isi, admin_id) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $judul, $isi, $adminId);
    if (!$stmt->execute()) {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan pengumuman']); exit;
    }
    $pengumumanId = $conn->insert_id;

    $stmtKelas = $conn->prepare("INSERT INTO pengumuman_kelas (pengumuman_id, kelas_id) VALUES (?, ?)");
    foreach ($kelasIds as $kelasId) {
        $stmtKelas->bind_param("is", $pengumumanId, $kelasId);
        $stmtKelas->execute();
    }

    echo json_encode(['status' => 'success', 'message' => 'Pengumuman berhasil ditambahkan']);
    exit;
}

// ===================== EDIT =====================
if ($action === 'edit') {
    $id       = (int)($_POST['id'] ?? 0);
    $judul    = trim($_POST['judul'] ?? '');
    $isi      = trim($_POST['isi'] ?? '');
    $kelasIds = $_POST['kelas_ids'] ?? [];

    if (!$id || empty($judul) || empty($isi)) {
        echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap']); exit;
    }
    if (empty($kelasIds)) {
        echo json_encode(['status' => 'error', 'message' => 'Pilih minimal satu kelas']); exit;
    }

    $stmt = $conn->prepare("UPDATE pengumuman SET judul=?, isi=? WHERE id=?");
    $stmt->bind_param("ssi", $judul, $isi, $id);
    if (!$stmt->execute()) {
        echo json_encode(['status' => 'error', 'message' => 'Gagal mengupdate pengumuman']); exit;
    }

    // Delete old kelas, insert new
    $delKelas = $conn->prepare("DELETE FROM pengumuman_kelas WHERE pengumuman_id = ?");
    $delKelas->bind_param("i", $id);
    $delKelas->execute();

    $stmtKelas = $conn->prepare("INSERT INTO pengumuman_kelas (pengumuman_id, kelas_id) VALUES (?, ?)");
    foreach ($kelasIds as $kelasId) {
        $stmtKelas->bind_param("is", $id, $kelasId);
        $stmtKelas->execute();
    }

    echo json_encode(['status' => 'success', 'message' => 'Pengumuman berhasil diupdate']);
    exit;
}

// ===================== DELETE =====================
if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) { echo json_encode(['status' => 'error', 'message' => 'ID tidak valid']); exit; }
    $stmt = $conn->prepare("DELETE FROM pengumuman WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) echo json_encode(['status' => 'success', 'message' => 'Pengumuman berhasil dihapus']);
    else echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus']);
    exit;
}

// ===================== DELETE_MULTIPLE =====================
if ($action === 'delete_multiple') {
    $ids = $_POST['ids'] ?? [];
    if (empty($ids)) { echo json_encode(['status' => 'error', 'message' => 'Pilih minimal satu data']); exit; }
    $ids = array_map('intval', $ids);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids));
    $stmt  = $conn->prepare("DELETE FROM pengumuman WHERE id IN ($placeholders)");
    $stmt->bind_param($types, ...$ids);
    if ($stmt->execute()) echo json_encode(['status' => 'success', 'message' => count($ids) . ' pengumuman berhasil dihapus']);
    else echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus']);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Action tidak dikenali']);
