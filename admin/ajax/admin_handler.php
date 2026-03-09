<?php
session_start();
require_once __DIR__ . '/../../includes/auth.php';
cekLogin('admin');
require_once __DIR__ . '/../../config/database.php';
header('Content-Type: application/json');

$action = $_REQUEST['action'] ?? '';

function nextAdminId($conn) {
    $res = $conn->query("SELECT id FROM admin ORDER BY CAST(SUBSTR(id,2) AS UNSIGNED) DESC LIMIT 1");
    if ($res && $res->num_rows > 0) {
        $last = $res->fetch_assoc()['id'];
        $num  = (int)substr($last, 1) + 1;
    } else {
        $num = 1;
    }
    return 'A' . $num;
}

// ===================== GET_ALL =====================
if ($action === 'get_all') {
    $page    = max(1, (int)($_GET['page'] ?? 1));
    $perPage = $_GET['per_page'] ?? '10';

    $total = $conn->query("SELECT COUNT(*) AS c FROM admin")->fetch_assoc()['c'];

    if ($perPage === 'all') {
        $result = $conn->query("SELECT id, email, nama, created_at FROM admin ORDER BY CAST(SUBSTR(id,2) AS UNSIGNED)");
        $data = [];
        while ($row = $result->fetch_assoc()) $data[] = $row;
        echo json_encode(['status' => 'success', 'data' => $data, 'total' => $total, 'per_page' => 'all', 'page' => 1]);
    } else {
        $perPage = (int)$perPage;
        $offset  = ($page - 1) * $perPage;
        $stmt = $conn->prepare("SELECT id, email, nama, created_at FROM admin ORDER BY CAST(SUBSTR(id,2) AS UNSIGNED) LIMIT ? OFFSET ?");
        $stmt->bind_param("ii", $perPage, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = [];
        while ($row = $result->fetch_assoc()) $data[] = $row;
        echo json_encode(['status' => 'success', 'data' => $data, 'total' => $total, 'per_page' => $perPage, 'page' => $page]);
    }
    exit;
}

// ===================== ADD =====================
if ($action === 'add') {
    $email = trim($_POST['email'] ?? '');
    $nama  = trim($_POST['nama'] ?? '');
    $pw    = $_POST['password'] ?? '';

    if (empty($email) || empty($nama) || empty($pw)) {
        echo json_encode(['status' => 'error', 'message' => 'Semua field wajib diisi']); exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['status' => 'error', 'message' => 'Format email tidak valid']); exit;
    }
    if (strlen($pw) < 6) {
        echo json_encode(['status' => 'error', 'message' => 'Password minimal 6 karakter']); exit;
    }

    // Check duplicate email
    $chk = $conn->prepare("SELECT id FROM admin WHERE email = ?");
    $chk->bind_param("s", $email);
    $chk->execute();
    if ($chk->get_result()->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Email sudah terdaftar']); exit;
    }

    $id   = nextAdminId($conn);
    $hash = password_hash($pw, PASSWORD_BCRYPT);

    $stmt = $conn->prepare("INSERT INTO admin (id, email, nama, password) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $id, $email, $nama, $hash);
    if ($stmt->execute()) echo json_encode(['status' => 'success', 'message' => 'Admin berhasil ditambahkan']);
    else echo json_encode(['status' => 'error', 'message' => 'Gagal menambahkan admin']);
    exit;
}

// ===================== EDIT =====================
if ($action === 'edit') {
    $id    = trim($_POST['id'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $nama  = trim($_POST['nama'] ?? '');
    $pw    = $_POST['password'] ?? '';

    if (empty($id) || empty($email) || empty($nama)) {
        echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap']); exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['status' => 'error', 'message' => 'Format email tidak valid']); exit;
    }

    // Check duplicate email (exclude self)
    $chk = $conn->prepare("SELECT id FROM admin WHERE email = ? AND id != ?");
    $chk->bind_param("ss", $email, $id);
    $chk->execute();
    if ($chk->get_result()->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Email sudah digunakan admin lain']); exit;
    }

    if (!empty($pw)) {
        if (strlen($pw) < 6) { echo json_encode(['status' => 'error', 'message' => 'Password minimal 6 karakter']); exit; }
        $hash = password_hash($pw, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("UPDATE admin SET email=?, nama=?, password=? WHERE id=?");
        $stmt->bind_param("ssss", $email, $nama, $hash, $id);
    } else {
        $stmt = $conn->prepare("UPDATE admin SET email=?, nama=? WHERE id=?");
        $stmt->bind_param("sss", $email, $nama, $id);
    }

    if ($stmt->execute()) echo json_encode(['status' => 'success', 'message' => 'Admin berhasil diupdate']);
    else echo json_encode(['status' => 'error', 'message' => 'Gagal mengupdate admin']);
    exit;
}

// ===================== DELETE =====================
if ($action === 'delete') {
    $id = trim($_POST['id'] ?? '');
    if (empty($id)) { echo json_encode(['status' => 'error', 'message' => 'ID tidak valid']); exit; }
    if ($id === $_SESSION['user_id']) {
        echo json_encode(['status' => 'error', 'message' => 'Tidak dapat menghapus akun sendiri']); exit;
    }
    $stmt = $conn->prepare("DELETE FROM admin WHERE id = ?");
    $stmt->bind_param("s", $id);
    if ($stmt->execute()) echo json_encode(['status' => 'success', 'message' => 'Admin berhasil dihapus']);
    else echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus admin']);
    exit;
}

// ===================== DELETE_MULTIPLE =====================
if ($action === 'delete_multiple') {
    $ids = $_POST['ids'] ?? [];
    if (empty($ids)) { echo json_encode(['status' => 'error', 'message' => 'Pilih minimal satu admin']); exit; }

    // Remove own session ID
    $ids = array_filter($ids, fn($id) => trim($id) !== $_SESSION['user_id']);
    $ids = array_values($ids);

    if (empty($ids)) {
        echo json_encode(['status' => 'error', 'message' => 'Tidak ada admin yang dapat dihapus (akun sendiri tidak bisa dihapus)']); exit;
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('s', count($ids));
    $stmt  = $conn->prepare("DELETE FROM admin WHERE id IN ($placeholders)");
    $stmt->bind_param($types, ...$ids);
    if ($stmt->execute()) echo json_encode(['status' => 'success', 'message' => count($ids) . ' admin berhasil dihapus']);
    else echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus admin']);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Action tidak dikenali']);
