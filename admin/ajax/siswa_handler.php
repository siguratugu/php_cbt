<?php
session_start();
require_once __DIR__ . '/../../includes/auth.php';
cekLogin('admin');
require_once __DIR__ . '/../../config/database.php';
header('Content-Type: application/json');

$action = $_REQUEST['action'] ?? '';

// ── GET ALL (paginated, with kelas name) ─────────────────────────────────────
if ($action === 'get_all') {
    $page    = max(1, (int)($_GET['page'] ?? 1));
    $perPage = $_GET['per_page'] ?? '10';

    $total = $conn->query("SELECT COUNT(*) AS c FROM siswa")->fetch_assoc()['c'];

    $select = "SELECT s.id, s.nama, s.nisn, s.kelas_id, k.nama_kelas, s.created_at
               FROM siswa s
               LEFT JOIN kelas k ON s.kelas_id = k.id
               ORDER BY s.nama";

    if ($perPage === 'all') {
        $result = $conn->query($select);
        $data = [];
        while ($row = $result->fetch_assoc()) $data[] = $row;
        echo json_encode(['status' => 'success', 'data' => $data, 'total' => $total, 'per_page' => 'all', 'page' => 1]);
    } else {
        $perPage = (int)$perPage;
        $offset  = ($page - 1) * $perPage;
        $stmt = $conn->prepare($select . " LIMIT ? OFFSET ?");
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
    $nisn     = trim($_POST['nisn']     ?? '');
    $kelas_id = trim($_POST['kelas_id'] ?? '');
    $password = trim($_POST['password'] ?? '123456');

    if (empty($nama))                         { echo json_encode(['status' => 'error', 'message' => 'Nama wajib diisi']); exit; }
    if (!preg_match('/^\d{10}$/', $nisn))     { echo json_encode(['status' => 'error', 'message' => 'NISN harus 10 digit angka']); exit; }
    if (empty($kelas_id))                     { echo json_encode(['status' => 'error', 'message' => 'Kelas wajib dipilih']); exit; }
    if (empty($password))                     { $password = '123456'; }

    // Check duplicate NISN
    $chk = $conn->prepare("SELECT id FROM siswa WHERE nisn = ?");
    $chk->bind_param("s", $nisn);
    $chk->execute();
    if ($chk->get_result()->num_rows > 0) { echo json_encode(['status' => 'error', 'message' => 'NISN sudah terdaftar']); exit; }

    $hashed = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $conn->prepare("INSERT INTO siswa (nama, nisn, kelas_id, password) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $nama, $nisn, $kelas_id, $hashed);
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Siswa berhasil ditambahkan']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menambahkan siswa']);
    }
    exit;
}

// ── EDIT ─────────────────────────────────────────────────────────────────────
if ($action === 'edit') {
    $id       = (int)($_POST['id']       ?? 0);
    $nama     = trim($_POST['nama']      ?? '');
    $nisn     = trim($_POST['nisn']      ?? '');
    $kelas_id = trim($_POST['kelas_id']  ?? '');
    $password = trim($_POST['password']  ?? '');

    if ($id <= 0 || empty($nama) || empty($kelas_id)) { echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap']); exit; }
    if (!preg_match('/^\d{10}$/', $nisn))              { echo json_encode(['status' => 'error', 'message' => 'NISN harus 10 digit angka']); exit; }

    // Check duplicate NISN (exclude self)
    $chk = $conn->prepare("SELECT id FROM siswa WHERE nisn = ? AND id != ?");
    $chk->bind_param("si", $nisn, $id);
    $chk->execute();
    if ($chk->get_result()->num_rows > 0) { echo json_encode(['status' => 'error', 'message' => 'NISN sudah digunakan siswa lain']); exit; }

    if (!empty($password)) {
        $hashed = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("UPDATE siswa SET nama = ?, nisn = ?, kelas_id = ?, password = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $nama, $nisn, $kelas_id, $hashed, $id);
    } else {
        $stmt = $conn->prepare("UPDATE siswa SET nama = ?, nisn = ?, kelas_id = ? WHERE id = ?");
        $stmt->bind_param("sssi", $nama, $nisn, $kelas_id, $id);
    }

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Data siswa berhasil diupdate']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal mengupdate data siswa']);
    }
    exit;
}

// ── DELETE ───────────────────────────────────────────────────────────────────
if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) { echo json_encode(['status' => 'error', 'message' => 'ID tidak valid']); exit; }
    $stmt = $conn->prepare("DELETE FROM siswa WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) echo json_encode(['status' => 'success', 'message' => 'Siswa berhasil dihapus']);
    else echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus siswa']);
    exit;
}

// ── DELETE MULTIPLE ──────────────────────────────────────────────────────────
if ($action === 'delete_multiple') {
    $ids = array_filter(array_map('intval', $_POST['ids'] ?? []));
    if (empty($ids)) { echo json_encode(['status' => 'error', 'message' => 'Pilih minimal satu siswa']); exit; }
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids));
    $stmt = $conn->prepare("DELETE FROM siswa WHERE id IN ($placeholders)");
    $stmt->bind_param($types, ...$ids);
    if ($stmt->execute()) echo json_encode(['status' => 'success', 'message' => count($ids) . ' siswa berhasil dihapus']);
    else echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus siswa']);
    exit;
}

// ── RESET PASSWORD (single) ──────────────────────────────────────────────────
if ($action === 'reset_password') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) { echo json_encode(['status' => 'error', 'message' => 'ID tidak valid']); exit; }
    $hashed = password_hash('123456', PASSWORD_BCRYPT);
    $stmt = $conn->prepare("UPDATE siswa SET password = ? WHERE id = ?");
    $stmt->bind_param("si", $hashed, $id);
    if ($stmt->execute()) echo json_encode(['status' => 'success', 'message' => 'Password berhasil direset ke 123456']);
    else echo json_encode(['status' => 'error', 'message' => 'Gagal reset password']);
    exit;
}

// ── RESET PASSWORD MULTIPLE ──────────────────────────────────────────────────
if ($action === 'reset_password_multiple') {
    $ids = array_filter(array_map('intval', $_POST['ids'] ?? []));
    if (empty($ids)) { echo json_encode(['status' => 'error', 'message' => 'Pilih minimal satu siswa']); exit; }
    $hashed = password_hash('123456', PASSWORD_BCRYPT);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids));
    $stmt = $conn->prepare("UPDATE siswa SET password = ? WHERE id IN ($placeholders)");
    $stmt->bind_param('s' . $types, $hashed, ...$ids);
    if ($stmt->execute()) echo json_encode(['status' => 'success', 'message' => count($ids) . ' siswa berhasil direset passwordnya']);
    else echo json_encode(['status' => 'error', 'message' => 'Gagal reset password']);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Action tidak dikenali']);
