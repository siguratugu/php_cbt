<?php
session_start();
require_once __DIR__ . '/../../includes/auth.php';
cekLogin('admin');
require_once __DIR__ . '/../../config/database.php';
header('Content-Type: application/json');

$action = $_REQUEST['action'] ?? '';

function nextKelasId($conn) {
    $res = $conn->query("SELECT id FROM kelas ORDER BY CAST(SUBSTR(id,2) AS UNSIGNED) DESC LIMIT 1");
    if ($res && $res->num_rows > 0) {
        $last = $res->fetch_assoc()['id'];
        $num = (int)substr($last, 1) + 1;
    } else {
        $num = 1;
    }
    return 'K' . $num;
}

if ($action === 'get_all') {
    $page    = max(1, (int)($_GET['page'] ?? 1));
    $perPage = $_GET['per_page'] ?? '10';

    $total = $conn->query("SELECT COUNT(*) as c FROM kelas")->fetch_assoc()['c'];

    if ($perPage === 'all') {
        $result = $conn->query("SELECT * FROM kelas ORDER BY CAST(SUBSTR(id,2) AS UNSIGNED)");
        $data = [];
        while ($row = $result->fetch_assoc()) $data[] = $row;
        echo json_encode(['status' => 'success', 'data' => $data, 'total' => $total, 'per_page' => 'all', 'page' => 1]);
    } else {
        $perPage = (int)$perPage;
        $offset = ($page - 1) * $perPage;
        $stmt = $conn->prepare("SELECT * FROM kelas ORDER BY CAST(SUBSTR(id,2) AS UNSIGNED) LIMIT ? OFFSET ?");
        $stmt->bind_param("ii", $perPage, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = [];
        while ($row = $result->fetch_assoc()) $data[] = $row;
        echo json_encode(['status' => 'success', 'data' => $data, 'total' => $total, 'per_page' => $perPage, 'page' => $page]);
    }
    exit;
}

if ($action === 'add') {
    $names = $_POST['nama_kelas'] ?? [];
    if (!is_array($names) || count($names) === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Nama kelas wajib diisi']);
        exit;
    }

    $added = 0;
    $errors = [];
    foreach ($names as $nama) {
        $nama = trim($nama);
        if (empty($nama)) continue;
        $id = nextKelasId($conn);

        // Check duplicate name
        $chk = $conn->prepare("SELECT id FROM kelas WHERE nama_kelas = ?");
        $chk->bind_param("s", $nama);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            $errors[] = "$nama sudah ada";
            continue;
        }

        $stmt = $conn->prepare("INSERT INTO kelas (id, nama_kelas) VALUES (?, ?)");
        $stmt->bind_param("ss", $id, $nama);
        if ($stmt->execute()) $added++;
        else $errors[] = "Gagal menambahkan $nama";
    }

    if ($added > 0) {
        $msg = "$added kelas berhasil ditambahkan";
        if (!empty($errors)) $msg .= '. Sebagian gagal: ' . implode(', ', $errors);
        echo json_encode(['status' => 'success', 'message' => $msg]);
    } else {
        echo json_encode(['status' => 'error', 'message' => implode(', ', $errors) ?: 'Tidak ada kelas yang ditambahkan']);
    }
    exit;
}

if ($action === 'edit') {
    $id   = trim($_POST['id'] ?? '');
    $nama = trim($_POST['nama_kelas'] ?? '');
    if (empty($id) || empty($nama)) {
        echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap']);
        exit;
    }
    $stmt = $conn->prepare("UPDATE kelas SET nama_kelas = ? WHERE id = ?");
    $stmt->bind_param("ss", $nama, $id);
    if ($stmt->execute()) echo json_encode(['status' => 'success', 'message' => 'Kelas berhasil diupdate']);
    else echo json_encode(['status' => 'error', 'message' => 'Gagal mengupdate kelas']);
    exit;
}

if ($action === 'delete') {
    $id = trim($_POST['id'] ?? '');
    if (empty($id)) { echo json_encode(['status' => 'error', 'message' => 'ID tidak valid']); exit; }
    $stmt = $conn->prepare("DELETE FROM kelas WHERE id = ?");
    $stmt->bind_param("s", $id);
    if ($stmt->execute()) echo json_encode(['status' => 'success', 'message' => 'Kelas berhasil dihapus']);
    else echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus kelas']);
    exit;
}

if ($action === 'delete_multiple') {
    $ids = $_POST['ids'] ?? [];
    if (empty($ids)) { echo json_encode(['status' => 'error', 'message' => 'Pilih minimal satu kelas']); exit; }
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('s', count($ids));
    $stmt = $conn->prepare("DELETE FROM kelas WHERE id IN ($placeholders)");
    $stmt->bind_param($types, ...$ids);
    if ($stmt->execute()) echo json_encode(['status' => 'success', 'message' => count($ids) . ' kelas berhasil dihapus']);
    else echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus kelas']);
    exit;
}

if ($action === 'get_list') {
    $result = $conn->query("SELECT id, nama_kelas FROM kelas ORDER BY CAST(SUBSTR(id,2) AS UNSIGNED)");
    $data = [];
    while ($row = $result->fetch_assoc()) $data[] = $row;
    echo json_encode(['status' => 'success', 'data' => $data]);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Action tidak dikenali']);
