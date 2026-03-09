<?php
session_start();
require_once __DIR__ . '/../../includes/auth.php';
cekLogin('admin');
require_once __DIR__ . '/../../config/database.php';
header('Content-Type: application/json');

$action = $_REQUEST['action'] ?? '';

function nextMapelId($conn) {
    $res = $conn->query("SELECT id FROM mapel ORDER BY CAST(SUBSTR(id,2) AS UNSIGNED) DESC LIMIT 1");
    if ($res && $res->num_rows > 0) {
        $last = $res->fetch_assoc()['id'];
        $num  = (int)substr($last, 1) + 1;
    } else {
        $num = 1;
    }
    return 'M' . $num;
}

if ($action === 'get_all') {
    $page    = max(1, (int)($_GET['page'] ?? 1));
    $perPage = $_GET['per_page'] ?? '10';
    $total   = $conn->query("SELECT COUNT(*) as c FROM mapel")->fetch_assoc()['c'];

    if ($perPage === 'all') {
        $result = $conn->query("SELECT * FROM mapel ORDER BY CAST(SUBSTR(id,2) AS UNSIGNED)");
        $data   = [];
        while ($row = $result->fetch_assoc()) $data[] = $row;
        echo json_encode(['status' => 'success', 'data' => $data, 'total' => $total, 'per_page' => 'all', 'page' => 1]);
    } else {
        $perPage = (int)$perPage;
        $offset  = ($page - 1) * $perPage;
        $stmt    = $conn->prepare("SELECT * FROM mapel ORDER BY CAST(SUBSTR(id,2) AS UNSIGNED) LIMIT ? OFFSET ?");
        $stmt->bind_param("ii", $perPage, $offset);
        $stmt->execute();
        $result  = $stmt->get_result();
        $data    = [];
        while ($row = $result->fetch_assoc()) $data[] = $row;
        echo json_encode(['status' => 'success', 'data' => $data, 'total' => $total, 'per_page' => $perPage, 'page' => $page]);
    }
    exit;
}

if ($action === 'add') {
    $names  = $_POST['nama_mapel'] ?? [];
    if (!is_array($names) || count($names) === 0) { echo json_encode(['status' => 'error', 'message' => 'Nama mapel wajib diisi']); exit; }

    $added = 0; $errors = [];
    foreach ($names as $nama) {
        $nama = trim($nama);
        if (empty($nama)) continue;
        $id = nextMapelId($conn);
        $chk = $conn->prepare("SELECT id FROM mapel WHERE nama_mapel = ?");
        $chk->bind_param("s", $nama); $chk->execute();
        if ($chk->get_result()->num_rows > 0) { $errors[] = "$nama sudah ada"; continue; }
        $stmt = $conn->prepare("INSERT INTO mapel (id, nama_mapel) VALUES (?, ?)");
        $stmt->bind_param("ss", $id, $nama);
        if ($stmt->execute()) $added++;
        else $errors[] = "Gagal menambahkan $nama";
    }

    if ($added > 0) echo json_encode(['status' => 'success', 'message' => "$added mapel berhasil ditambahkan" . (!empty($errors) ? '. Sebagian gagal: ' . implode(', ', $errors) : '')]);
    else echo json_encode(['status' => 'error', 'message' => implode(', ', $errors) ?: 'Tidak ada mapel yang ditambahkan']);
    exit;
}

if ($action === 'edit') {
    $id   = trim($_POST['id'] ?? '');
    $nama = trim($_POST['nama_mapel'] ?? '');
    if (empty($id) || empty($nama)) { echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap']); exit; }
    $stmt = $conn->prepare("UPDATE mapel SET nama_mapel = ? WHERE id = ?");
    $stmt->bind_param("ss", $nama, $id);
    echo $stmt->execute() ? json_encode(['status' => 'success', 'message' => 'Mapel berhasil diupdate']) : json_encode(['status' => 'error', 'message' => 'Gagal mengupdate mapel']);
    exit;
}

if ($action === 'delete') {
    $id = trim($_POST['id'] ?? '');
    if (empty($id)) { echo json_encode(['status' => 'error', 'message' => 'ID tidak valid']); exit; }
    $stmt = $conn->prepare("DELETE FROM mapel WHERE id = ?");
    $stmt->bind_param("s", $id);
    echo $stmt->execute() ? json_encode(['status' => 'success', 'message' => 'Mapel berhasil dihapus']) : json_encode(['status' => 'error', 'message' => 'Gagal menghapus mapel']);
    exit;
}

if ($action === 'delete_multiple') {
    $ids = $_POST['ids'] ?? [];
    if (empty($ids)) { echo json_encode(['status' => 'error', 'message' => 'Pilih minimal satu mapel']); exit; }
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types        = str_repeat('s', count($ids));
    $stmt         = $conn->prepare("DELETE FROM mapel WHERE id IN ($placeholders)");
    $stmt->bind_param($types, ...$ids);
    echo $stmt->execute() ? json_encode(['status' => 'success', 'message' => count($ids) . ' mapel berhasil dihapus']) : json_encode(['status' => 'error', 'message' => 'Gagal menghapus mapel']);
    exit;
}

if ($action === 'get_list') {
    $result = $conn->query("SELECT id, nama_mapel FROM mapel ORDER BY CAST(SUBSTR(id,2) AS UNSIGNED)");
    $data   = [];
    while ($row = $result->fetch_assoc()) $data[] = $row;
    echo json_encode(['status' => 'success', 'data' => $data]);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Action tidak dikenali']);
