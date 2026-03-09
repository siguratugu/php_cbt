<?php
session_start();
require_once __DIR__ . '/../../includes/auth.php';
cekLogin('admin');
require_once __DIR__ . '/../../config/database.php';
header('Content-Type: application/json');

$action = $_REQUEST['action'] ?? '';

/* =====================================================================
   GET ALL  –  paginated list with guru + mapel join
   ===================================================================== */
if ($action === 'get_all') {
    $page    = max(1, (int)($_GET['page'] ?? 1));
    $perPage = $_GET['per_page'] ?? '10';

    $totalRes = $conn->query("SELECT COUNT(*) AS c FROM bank_soal");
    $total    = $totalRes ? (int)$totalRes->fetch_assoc()['c'] : 0;

    $baseSql = "SELECT bs.*, g.nama AS nama_guru, m.nama_mapel
                FROM bank_soal bs
                LEFT JOIN guru  g ON bs.guru_id  = g.id
                LEFT JOIN mapel m ON bs.mapel_id = m.id
                ORDER BY bs.created_at DESC";

    if ($perPage === 'all') {
        $result = $conn->query($baseSql);
        $data   = [];
        while ($row = $result->fetch_assoc()) $data[] = $row;
        echo json_encode(['status' => 'success', 'data' => $data, 'total' => $total, 'per_page' => 'all', 'page' => 1]);
    } else {
        $perPage = (int)$perPage;
        $offset  = ($page - 1) * $perPage;
        $stmt    = $conn->prepare($baseSql . " LIMIT ? OFFSET ?");
        $stmt->bind_param("ii", $perPage, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        $data   = [];
        while ($row = $result->fetch_assoc()) $data[] = $row;
        echo json_encode(['status' => 'success', 'data' => $data, 'total' => $total, 'per_page' => $perPage, 'page' => $page]);
    }
    exit;
}

/* =====================================================================
   GET SINGLE  –  one bank_soal row by id
   ===================================================================== */
if ($action === 'get_single') {
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) { echo json_encode(['status' => 'error', 'message' => 'ID tidak valid']); exit; }

    $stmt = $conn->prepare("SELECT * FROM bank_soal WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!$row) { echo json_encode(['status' => 'error', 'message' => 'Data tidak ditemukan']); exit; }

    echo json_encode(['status' => 'success', 'data' => $row]);
    exit;
}

/* =====================================================================
   GET GURU LIST
   ===================================================================== */
if ($action === 'get_guru_list') {
    $result = $conn->query("SELECT id, nama FROM guru ORDER BY nama ASC");
    $data   = [];
    while ($row = $result->fetch_assoc()) $data[] = $row;
    echo json_encode(['status' => 'success', 'data' => $data]);
    exit;
}

/* =====================================================================
   GET MAPEL LIST
   ===================================================================== */
if ($action === 'get_mapel_list') {
    $result = $conn->query("SELECT id, nama_mapel FROM mapel ORDER BY nama_mapel ASC");
    $data   = [];
    while ($row = $result->fetch_assoc()) $data[] = $row;
    echo json_encode(['status' => 'success', 'data' => $data]);
    exit;
}

/* =====================================================================
   GET BY GURU  –  filter bank_soal by guru_id (or admin)
   ===================================================================== */
if ($action === 'get_by_guru') {
    $guru_id = $_GET['guru_id'] ?? '';

    $baseSql = "SELECT bs.*, g.nama AS nama_guru, m.nama_mapel
                FROM bank_soal bs
                LEFT JOIN guru  g ON bs.guru_id  = g.id
                LEFT JOIN mapel m ON bs.mapel_id = m.id";

    if ($guru_id === 'admin') {
        $stmt = $conn->prepare($baseSql . " WHERE bs.admin_id IS NOT NULL ORDER BY bs.created_at DESC");
        $stmt->execute();
    } else {
        $gid  = (int)$guru_id;
        $stmt = $conn->prepare($baseSql . " WHERE bs.guru_id = ? ORDER BY bs.created_at DESC");
        $stmt->bind_param("i", $gid);
        $stmt->execute();
    }

    $result = $stmt->get_result();
    $data   = [];
    while ($row = $result->fetch_assoc()) $data[] = $row;
    echo json_encode(['status' => 'success', 'data' => $data]);
    exit;
}

/* =====================================================================
   ADD  –  insert new bank_soal
   ===================================================================== */
if ($action === 'add') {
    $guru_id   = trim($_POST['guru_id'] ?? '');
    $nama_soal = trim($_POST['nama_soal'] ?? '');
    $mapel_id  = trim($_POST['mapel_id'] ?? '') !== '' ? trim($_POST['mapel_id']) : null;
    $waktu     = (int)($_POST['waktu_mengerjakan'] ?? 0);
    $bobot_pg  = (float)($_POST['bobot_pg'] ?? 0);
    $bobot_e   = (float)($_POST['bobot_esai'] ?? 0);
    $bobot_mj  = (float)($_POST['bobot_menjodohkan'] ?? 0);
    $bobot_bs  = (float)($_POST['bobot_benar_salah'] ?? 0);

    if (empty($nama_soal)) {
        echo json_encode(['status' => 'error', 'message' => 'Nama soal wajib diisi']);
        exit;
    }
    if ($waktu <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Waktu mengerjakan tidak valid']);
        exit;
    }
    $total = round($bobot_pg + $bobot_e + $bobot_mj + $bobot_bs, 2);
    if ($total != 100) {
        echo json_encode(['status' => 'error', 'message' => "Total bobot harus 100%, saat ini {$total}%"]);
        exit;
    }

    if ($guru_id === 'admin') {
        $admin_id = $_SESSION['user_id'];
        $stmt = $conn->prepare(
            "INSERT INTO bank_soal (admin_id, nama_soal, mapel_id, waktu_mengerjakan, bobot_pg, bobot_esai, bobot_menjodohkan, bobot_benar_salah)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("sssidddd", $admin_id, $nama_soal, $mapel_id, $waktu, $bobot_pg, $bobot_e, $bobot_mj, $bobot_bs);
    } else {
        $gid  = (int)$guru_id;
        $stmt = $conn->prepare(
            "INSERT INTO bank_soal (guru_id, nama_soal, mapel_id, waktu_mengerjakan, bobot_pg, bobot_esai, bobot_menjodohkan, bobot_benar_salah)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("issidddd", $gid, $nama_soal, $mapel_id, $waktu, $bobot_pg, $bobot_e, $bobot_mj, $bobot_bs);
    }

    if ($stmt->execute()) {
        $new_id = $conn->insert_id;
        echo json_encode(['status' => 'success', 'id' => $new_id, 'message' => 'Bank soal berhasil ditambahkan']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan bank soal: ' . $conn->error]);
    }
    exit;
}

/* =====================================================================
   EDIT  –  update existing bank_soal
   ===================================================================== */
if ($action === 'edit') {
    $id        = (int)($_POST['id'] ?? 0);
    $guru_id   = trim($_POST['guru_id'] ?? '');
    $nama_soal = trim($_POST['nama_soal'] ?? '');
    $mapel_id  = trim($_POST['mapel_id'] ?? '') !== '' ? trim($_POST['mapel_id']) : null;
    $waktu     = (int)($_POST['waktu_mengerjakan'] ?? 0);
    $bobot_pg  = (float)($_POST['bobot_pg'] ?? 0);
    $bobot_e   = (float)($_POST['bobot_esai'] ?? 0);
    $bobot_mj  = (float)($_POST['bobot_menjodohkan'] ?? 0);
    $bobot_bs  = (float)($_POST['bobot_benar_salah'] ?? 0);

    if (!$id || empty($nama_soal)) {
        echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap']);
        exit;
    }
    if ($waktu <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Waktu mengerjakan tidak valid']);
        exit;
    }
    $total = round($bobot_pg + $bobot_e + $bobot_mj + $bobot_bs, 2);
    if ($total != 100) {
        echo json_encode(['status' => 'error', 'message' => "Total bobot harus 100%, saat ini {$total}%"]);
        exit;
    }

    if ($guru_id === 'admin') {
        $admin_id = $_SESSION['user_id'];
        $stmt = $conn->prepare(
            "UPDATE bank_soal
             SET admin_id=?, guru_id=NULL, nama_soal=?, mapel_id=?, waktu_mengerjakan=?,
                 bobot_pg=?, bobot_esai=?, bobot_menjodohkan=?, bobot_benar_salah=?
             WHERE id=?"
        );
        $stmt->bind_param("sssiddddi", $admin_id, $nama_soal, $mapel_id, $waktu, $bobot_pg, $bobot_e, $bobot_mj, $bobot_bs, $id);
    } else {
        $gid = (int)$guru_id;
        $stmt = $conn->prepare(
            "UPDATE bank_soal
             SET guru_id=?, admin_id=NULL, nama_soal=?, mapel_id=?, waktu_mengerjakan=?,
                 bobot_pg=?, bobot_esai=?, bobot_menjodohkan=?, bobot_benar_salah=?
             WHERE id=?"
        );
        $stmt->bind_param("issiddddi", $gid, $nama_soal, $mapel_id, $waktu, $bobot_pg, $bobot_e, $bobot_mj, $bobot_bs, $id);
    }

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Bank soal berhasil diupdate']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal mengupdate bank soal: ' . $conn->error]);
    }
    exit;
}

/* =====================================================================
   DELETE  –  single
   ===================================================================== */
if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) { echo json_encode(['status' => 'error', 'message' => 'ID tidak valid']); exit; }

    $stmt = $conn->prepare("DELETE FROM bank_soal WHERE id = ?");
    $stmt->bind_param("i", $id);
    echo $stmt->execute()
        ? json_encode(['status' => 'success', 'message' => 'Bank soal berhasil dihapus'])
        : json_encode(['status' => 'error', 'message' => 'Gagal menghapus bank soal']);
    exit;
}

/* =====================================================================
   DELETE MULTIPLE
   ===================================================================== */
if ($action === 'delete_multiple') {
    $ids = $_POST['ids'] ?? [];
    if (empty($ids)) {
        echo json_encode(['status' => 'error', 'message' => 'Pilih minimal satu bank soal']);
        exit;
    }
    $ids          = array_map('intval', $ids);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types        = str_repeat('i', count($ids));
    $stmt         = $conn->prepare("DELETE FROM bank_soal WHERE id IN ($placeholders)");
    $stmt->bind_param($types, ...$ids);

    echo $stmt->execute()
        ? json_encode(['status' => 'success', 'message' => count($ids) . ' bank soal berhasil dihapus'])
        : json_encode(['status' => 'error', 'message' => 'Gagal menghapus bank soal']);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Action tidak dikenali']);
