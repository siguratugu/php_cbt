<?php
session_start();
require_once __DIR__ . '/../../includes/auth.php';
cekLogin('admin');
require_once __DIR__ . '/../../config/database.php';
header('Content-Type: application/json');

$action = $_REQUEST['action'] ?? '';

// ── GET ALL (paginated) ──────────────────────────────────────────────────────
if ($action === 'get_all') {
    $page    = max(1, (int)($_GET['page'] ?? 1));
    $perPage = $_GET['per_page'] ?? '10';

    $total = $conn->query("SELECT COUNT(*) AS c FROM guru")->fetch_assoc()['c'];

    if ($perPage === 'all') {
        $result = $conn->query("SELECT id, nama, nik, created_at FROM guru ORDER BY nama");
        $data = [];
        while ($row = $result->fetch_assoc()) $data[] = $row;
        echo json_encode(['status' => 'success', 'data' => $data, 'total' => $total, 'per_page' => 'all', 'page' => 1]);
    } else {
        $perPage = (int)$perPage;
        $offset  = ($page - 1) * $perPage;
        $stmt = $conn->prepare("SELECT id, nama, nik, created_at FROM guru ORDER BY nama LIMIT ? OFFSET ?");
        $stmt->bind_param("ii", $perPage, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = [];
        while ($row = $result->fetch_assoc()) $data[] = $row;
        echo json_encode(['status' => 'success', 'data' => $data, 'total' => $total, 'per_page' => $perPage, 'page' => $page]);
    }
    exit;
}

// ── ADD ──────────────────────────────────────────────────────────────────────
if ($action === 'add') {
    $nama     = trim($_POST['nama']     ?? '');
    $nik      = trim($_POST['nik']      ?? '');
    $password = trim($_POST['password'] ?? '123456');

    if (empty($nama))                          { echo json_encode(['status' => 'error', 'message' => 'Nama wajib diisi']); exit; }
    if (!preg_match('/^\d{16}$/', $nik))       { echo json_encode(['status' => 'error', 'message' => 'NIK harus 16 digit angka']); exit; }
    if (empty($password))                      { $password = '123456'; }

    // Check duplicate NIK
    $chk = $conn->prepare("SELECT id FROM guru WHERE nik = ?");
    $chk->bind_param("s", $nik);
    $chk->execute();
    if ($chk->get_result()->num_rows > 0) { echo json_encode(['status' => 'error', 'message' => 'NIK sudah terdaftar']); exit; }

    $hashed = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $conn->prepare("INSERT INTO guru (nama, nik, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $nama, $nik, $hashed);
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Guru berhasil ditambahkan']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menambahkan guru']);
    }
    exit;
}

// ── EDIT ─────────────────────────────────────────────────────────────────────
if ($action === 'edit') {
    $id       = (int)($_POST['id']       ?? 0);
    $nama     = trim($_POST['nama']      ?? '');
    $nik      = trim($_POST['nik']       ?? '');
    $password = trim($_POST['password']  ?? '');

    if ($id <= 0 || empty($nama))                { echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap']); exit; }
    if (!preg_match('/^\d{16}$/', $nik))          { echo json_encode(['status' => 'error', 'message' => 'NIK harus 16 digit angka']); exit; }

    // Check duplicate NIK (exclude self)
    $chk = $conn->prepare("SELECT id FROM guru WHERE nik = ? AND id != ?");
    $chk->bind_param("si", $nik, $id);
    $chk->execute();
    if ($chk->get_result()->num_rows > 0) { echo json_encode(['status' => 'error', 'message' => 'NIK sudah digunakan guru lain']); exit; }

    if (!empty($password)) {
        $hashed = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("UPDATE guru SET nama = ?, nik = ?, password = ? WHERE id = ?");
        $stmt->bind_param("sssi", $nama, $nik, $hashed, $id);
    } else {
        $stmt = $conn->prepare("UPDATE guru SET nama = ?, nik = ? WHERE id = ?");
        $stmt->bind_param("ssi", $nama, $nik, $id);
    }

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Data guru berhasil diupdate']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal mengupdate data guru']);
    }
    exit;
}

// ── DELETE ───────────────────────────────────────────────────────────────────
if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) { echo json_encode(['status' => 'error', 'message' => 'ID tidak valid']); exit; }
    $stmt = $conn->prepare("DELETE FROM guru WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) echo json_encode(['status' => 'success', 'message' => 'Guru berhasil dihapus']);
    else echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus guru']);
    exit;
}

// ── DELETE MULTIPLE ──────────────────────────────────────────────────────────
if ($action === 'delete_multiple') {
    $ids = array_filter(array_map('intval', $_POST['ids'] ?? []));
    if (empty($ids)) { echo json_encode(['status' => 'error', 'message' => 'Pilih minimal satu guru']); exit; }
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids));
    $stmt = $conn->prepare("DELETE FROM guru WHERE id IN ($placeholders)");
    $stmt->bind_param($types, ...$ids);
    if ($stmt->execute()) echo json_encode(['status' => 'success', 'message' => count($ids) . ' guru berhasil dihapus']);
    else echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus guru']);
    exit;
}

// ── RESET PASSWORD (single) ──────────────────────────────────────────────────
if ($action === 'reset_password') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) { echo json_encode(['status' => 'error', 'message' => 'ID tidak valid']); exit; }
    $hashed = password_hash('123456', PASSWORD_BCRYPT);
    $stmt = $conn->prepare("UPDATE guru SET password = ? WHERE id = ?");
    $stmt->bind_param("si", $hashed, $id);
    if ($stmt->execute()) echo json_encode(['status' => 'success', 'message' => 'Password berhasil direset ke 123456']);
    else echo json_encode(['status' => 'error', 'message' => 'Gagal reset password']);
    exit;
}

// ── RESET PASSWORD MULTIPLE ──────────────────────────────────────────────────
if ($action === 'reset_password_multiple') {
    $ids = array_filter(array_map('intval', $_POST['ids'] ?? []));
    if (empty($ids)) { echo json_encode(['status' => 'error', 'message' => 'Pilih minimal satu guru']); exit; }
    $hashed = password_hash('123456', PASSWORD_BCRYPT);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids));
    $stmt = $conn->prepare("UPDATE guru SET password = ? WHERE id IN ($placeholders)");
    $stmt->bind_param('s' . $types, $hashed, ...$ids);
    if ($stmt->execute()) echo json_encode(['status' => 'success', 'message' => count($ids) . ' guru berhasil direset passwordnya']);
    else echo json_encode(['status' => 'error', 'message' => 'Gagal reset password']);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Action tidak dikenali']);
