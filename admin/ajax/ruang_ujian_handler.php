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

    $total = $conn->query("SELECT COUNT(*) AS c FROM ruang_ujian")->fetch_assoc()['c'];

    $baseQuery = "
        SELECT ru.*, 
               COALESCE(g.nama, 'Admin') AS guru_nama,
               bs.nama_soal,
               GROUP_CONCAT(k.nama_kelas ORDER BY k.nama_kelas SEPARATOR ',') AS kelas_list
        FROM ruang_ujian ru
        LEFT JOIN guru g ON ru.guru_id = g.id
        LEFT JOIN bank_soal bs ON ru.bank_soal_id = bs.id
        LEFT JOIN ruang_ujian_kelas ruk ON ru.id = ruk.ruang_ujian_id
        LEFT JOIN kelas k ON ruk.kelas_id = k.id
        GROUP BY ru.id
        ORDER BY ru.id DESC
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

    $stmt = $conn->prepare("SELECT * FROM ruang_ujian WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();
    if (!$data) { echo json_encode(['status' => 'error', 'message' => 'Data tidak ditemukan']); exit; }

    $kelasStmt = $conn->prepare("SELECT kelas_id FROM ruang_ujian_kelas WHERE ruang_ujian_id = ?");
    $kelasStmt->bind_param("i", $id);
    $kelasStmt->execute();
    $kelasResult = $kelasStmt->get_result();
    $kelasIds = [];
    while ($kr = $kelasResult->fetch_assoc()) $kelasIds[] = $kr['kelas_id'];

    echo json_encode(['status' => 'success', 'data' => $data, 'kelas_ids' => $kelasIds]);
    exit;
}

// ===================== GET_GURU_LIST =====================
if ($action === 'get_guru_list') {
    $result = $conn->query("SELECT id, nama FROM guru ORDER BY nama");
    $data = [];
    while ($row = $result->fetch_assoc()) $data[] = $row;
    echo json_encode(['status' => 'success', 'data' => $data]);
    exit;
}

// ===================== GET_BANK_SOAL_BY_GURU =====================
if ($action === 'get_bank_soal_by_guru') {
    $guruId = $_GET['guru_id'] ?? '';
    if (empty($guruId)) { echo json_encode(['status' => 'error', 'message' => 'Guru ID diperlukan']); exit; }

    if ($guruId === 'admin') {
        $result = $conn->query("SELECT id, nama_soal FROM bank_soal WHERE admin_id IS NOT NULL ORDER BY nama_soal");
    } else {
        $stmt = $conn->prepare("SELECT id, nama_soal FROM bank_soal WHERE guru_id = ? ORDER BY nama_soal");
        $stmt->bind_param("i", $guruId);
        $stmt->execute();
        $result = $stmt->get_result();
    }
    $data = [];
    while ($row = $result->fetch_assoc()) $data[] = $row;
    echo json_encode(['status' => 'success', 'data' => $data]);
    exit;
}

// ===================== GET_KELAS_LIST =====================
if ($action === 'get_kelas_list') {
    $result = $conn->query("SELECT id, nama_kelas FROM kelas ORDER BY CAST(SUBSTR(id,2) AS UNSIGNED)");
    $data = [];
    while ($row = $result->fetch_assoc()) $data[] = $row;
    echo json_encode(['status' => 'success', 'data' => $data]);
    exit;
}

// ===================== GENERATE_TOKEN =====================
if ($action === 'generate_token') {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $maxAttempts = 20;
    for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
        $token = '';
        for ($i = 0; $i < 6; $i++) $token .= $chars[random_int(0, strlen($chars) - 1)];
        $chk = $conn->prepare("SELECT id FROM ruang_ujian WHERE token = ?");
        $chk->bind_param("s", $token);
        $chk->execute();
        if ($chk->get_result()->num_rows === 0) {
            echo json_encode(['status' => 'success', 'token' => $token]);
            exit;
        }
    }
    echo json_encode(['status' => 'error', 'message' => 'Gagal generate token unik']);
    exit;
}

// ===================== ADD =====================
if ($action === 'add') {
    $namaRuang    = trim($_POST['nama_ruang'] ?? '');
    $guruId       = trim($_POST['guru_id'] ?? '');
    $bankSoalId   = (int)($_POST['bank_soal_id'] ?? 0);
    $waktuHentikan= (int)($_POST['waktu_hentikan'] ?? 0);
    $batasKeluar  = (int)($_POST['batas_keluar'] ?? 3);
    $kelasIds     = $_POST['kelas_ids'] ?? [];
    $tMulai       = trim($_POST['tanggal_mulai'] ?? '');
    $tSelesai     = trim($_POST['tanggal_selesai'] ?? '');
    $acakSoal     = (int)($_POST['acak_soal'] ?? 0);
    $acakJawaban  = (int)($_POST['acak_jawaban'] ?? 0);
    $token        = strtoupper(trim($_POST['token'] ?? ''));

    if (empty($namaRuang) || empty($guruId) || !$bankSoalId || !$waktuHentikan || empty($tMulai) || empty($tSelesai) || empty($token)) {
        echo json_encode(['status' => 'error', 'message' => 'Semua field wajib diisi']); exit;
    }
    if (empty($kelasIds)) { echo json_encode(['status' => 'error', 'message' => 'Pilih minimal satu kelas']); exit; }

    // Check token uniqueness
    $chkToken = $conn->prepare("SELECT id FROM ruang_ujian WHERE token = ?");
    $chkToken->bind_param("s", $token);
    $chkToken->execute();
    if ($chkToken->get_result()->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Token sudah digunakan, generate token baru']); exit;
    }

    // Convert datetime-local to MySQL datetime
    $tMulaiDb   = date('Y-m-d H:i:s', strtotime(str_replace('T', ' ', $tMulai)));
    $tSelesaiDb = date('Y-m-d H:i:s', strtotime(str_replace('T', ' ', $tSelesai)));

    $dbGuruId  = ($guruId === 'admin') ? null : (int)$guruId;
    $dbAdminId = ($guruId === 'admin') ? $_SESSION['user_id'] : null;

    $stmt = $conn->prepare("INSERT INTO ruang_ujian (nama_ruang, token, guru_id, admin_id, bank_soal_id, waktu_hentikan, batas_keluar, tanggal_mulai, tanggal_selesai, acak_soal, acak_jawaban) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->bind_param("sssiiiiisii", $namaRuang, $token, $dbGuruId, $dbAdminId, $bankSoalId, $waktuHentikan, $batasKeluar, $tMulaiDb, $tSelesaiDb, $acakSoal, $acakJawaban);

    if (!$stmt->execute()) {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan ruang ujian: ' . $conn->error]); exit;
    }
    $ruangId = $conn->insert_id;

    // Insert kelas
    $stmtKelas = $conn->prepare("INSERT INTO ruang_ujian_kelas (ruang_ujian_id, kelas_id) VALUES (?, ?)");
    foreach ($kelasIds as $kelasId) {
        $stmtKelas->bind_param("is", $ruangId, $kelasId);
        $stmtKelas->execute();
    }

    echo json_encode(['status' => 'success', 'message' => 'Ruang ujian berhasil ditambahkan']);
    exit;
}

// ===================== EDIT =====================
if ($action === 'edit') {
    $id           = (int)($_POST['id'] ?? 0);
    $namaRuang    = trim($_POST['nama_ruang'] ?? '');
    $guruId       = trim($_POST['guru_id'] ?? '');
    $bankSoalId   = (int)($_POST['bank_soal_id'] ?? 0);
    $waktuHentikan= (int)($_POST['waktu_hentikan'] ?? 0);
    $batasKeluar  = (int)($_POST['batas_keluar'] ?? 3);
    $kelasIds     = $_POST['kelas_ids'] ?? [];
    $tMulai       = trim($_POST['tanggal_mulai'] ?? '');
    $tSelesai     = trim($_POST['tanggal_selesai'] ?? '');
    $acakSoal     = (int)($_POST['acak_soal'] ?? 0);
    $acakJawaban  = (int)($_POST['acak_jawaban'] ?? 0);
    $token        = strtoupper(trim($_POST['token'] ?? ''));

    if (!$id || empty($namaRuang) || empty($guruId) || !$bankSoalId || !$waktuHentikan || empty($tMulai) || empty($tSelesai) || empty($token)) {
        echo json_encode(['status' => 'error', 'message' => 'Semua field wajib diisi']); exit;
    }
    if (empty($kelasIds)) { echo json_encode(['status' => 'error', 'message' => 'Pilih minimal satu kelas']); exit; }

    // Check token uniqueness (exclude self)
    $chkToken = $conn->prepare("SELECT id FROM ruang_ujian WHERE token = ? AND id != ?");
    $chkToken->bind_param("si", $token, $id);
    $chkToken->execute();
    if ($chkToken->get_result()->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Token sudah digunakan ruang lain']); exit;
    }

    $tMulaiDb   = date('Y-m-d H:i:s', strtotime(str_replace('T', ' ', $tMulai)));
    $tSelesaiDb = date('Y-m-d H:i:s', strtotime(str_replace('T', ' ', $tSelesai)));

    $dbGuruId  = ($guruId === 'admin') ? null : (int)$guruId;
    $dbAdminId = ($guruId === 'admin') ? $_SESSION['user_id'] : null;

    $stmt = $conn->prepare("UPDATE ruang_ujian SET nama_ruang=?, token=?, guru_id=?, admin_id=?, bank_soal_id=?, waktu_hentikan=?, batas_keluar=?, tanggal_mulai=?, tanggal_selesai=?, acak_soal=?, acak_jawaban=? WHERE id=?");
    $stmt->bind_param("sssiiiiisiii", $namaRuang, $token, $dbGuruId, $dbAdminId, $bankSoalId, $waktuHentikan, $batasKeluar, $tMulaiDb, $tSelesaiDb, $acakSoal, $acakJawaban, $id);

    if (!$stmt->execute()) {
        echo json_encode(['status' => 'error', 'message' => 'Gagal mengupdate ruang ujian']); exit;
    }

    // Update kelas: delete old, insert new
    $delKelas = $conn->prepare("DELETE FROM ruang_ujian_kelas WHERE ruang_ujian_id = ?");
    $delKelas->bind_param("i", $id);
    $delKelas->execute();

    $stmtKelas = $conn->prepare("INSERT INTO ruang_ujian_kelas (ruang_ujian_id, kelas_id) VALUES (?, ?)");
    foreach ($kelasIds as $kelasId) {
        $stmtKelas->bind_param("is", $id, $kelasId);
        $stmtKelas->execute();
    }

    echo json_encode(['status' => 'success', 'message' => 'Ruang ujian berhasil diupdate']);
    exit;
}

// ===================== DELETE =====================
if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) { echo json_encode(['status' => 'error', 'message' => 'ID tidak valid']); exit; }
    $stmt = $conn->prepare("DELETE FROM ruang_ujian WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) echo json_encode(['status' => 'success', 'message' => 'Ruang ujian berhasil dihapus']);
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
    $stmt = $conn->prepare("DELETE FROM ruang_ujian WHERE id IN ($placeholders)");
    $stmt->bind_param($types, ...$ids);
    if ($stmt->execute()) echo json_encode(['status' => 'success', 'message' => count($ids) . ' ruang ujian berhasil dihapus']);
    else echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus']);
    exit;
}

// ===================== GET_MONITORING_DATA =====================
if ($action === 'get_monitoring_data') {
    $ruangId = (int)($_GET['ruang_id'] ?? 0);
    $kelasId = trim($_GET['kelas_id'] ?? '');

    if (!$ruangId) { echo json_encode(['status' => 'error', 'message' => 'Ruang ID diperlukan']); exit; }

    // Ensure ujian_siswa records exist for all students in assigned kelas
    $ruangInfo = $conn->query("SELECT * FROM ruang_ujian WHERE id = $ruangId")->fetch_assoc();
    if ($ruangInfo) {
        $kelasQuery = "SELECT kelas_id FROM ruang_ujian_kelas WHERE ruang_ujian_id = $ruangId";
        $kelasResult = $conn->query($kelasQuery);
        while ($kr = $kelasResult->fetch_assoc()) {
            $k = $kr['kelas_id'];
            $siswaResult = $conn->query("SELECT id FROM siswa WHERE kelas_id = '$k'");
            while ($sr = $siswaResult->fetch_assoc()) {
                $sid = $sr['id'];
                $exist = $conn->query("SELECT id FROM ujian_siswa WHERE ruang_ujian_id=$ruangId AND siswa_id=$sid");
                if ($exist->num_rows === 0) {
                    $conn->query("INSERT IGNORE INTO ujian_siswa (ruang_ujian_id, siswa_id, status) VALUES ($ruangId, $sid, 'belum')");
                }
            }
        }
    }

    $sql = "SELECT us.id AS ujian_siswa_id, s.nama, s.nisn, k.nama_kelas,
                   us.status, us.waktu_mulai, us.waktu_selesai,
                   us.jumlah_benar, us.jumlah_salah, us.nilai, us.jumlah_keluar
            FROM ujian_siswa us
            JOIN siswa s ON us.siswa_id = s.id
            LEFT JOIN kelas k ON s.kelas_id = k.id
            WHERE us.ruang_ujian_id = ?";

    if (!empty($kelasId)) {
        $sql .= " AND s.kelas_id = ?";
        $sql .= " ORDER BY k.nama_kelas, s.nama";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $ruangId, $kelasId);
    } else {
        $sql .= " ORDER BY k.nama_kelas, s.nama";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $ruangId);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    while ($row = $result->fetch_assoc()) $data[] = $row;

    echo json_encode(['status' => 'success', 'data' => $data]);
    exit;
}

// ===================== RESET_UJIAN =====================
if ($action === 'reset_ujian') {
    $ujianSiswaId = (int)($_POST['ujian_siswa_id'] ?? 0);
    if (!$ujianSiswaId) { echo json_encode(['status' => 'error', 'message' => 'ID tidak valid']); exit; }

    $delJawaban = $conn->prepare("DELETE FROM jawaban_siswa WHERE ujian_siswa_id = ?");
    $delJawaban->bind_param("i", $ujianSiswaId);
    $delJawaban->execute();

    $stmt = $conn->prepare("UPDATE ujian_siswa SET status='belum', waktu_mulai=NULL, waktu_selesai=NULL, jumlah_benar=0, jumlah_salah=0, nilai=0, jumlah_keluar=0, acak_soal_order=NULL, acak_jawaban_order=NULL WHERE id = ?");
    $stmt->bind_param("i", $ujianSiswaId);
    if ($stmt->execute()) echo json_encode(['status' => 'success', 'message' => 'Ujian siswa berhasil direset']);
    else echo json_encode(['status' => 'error', 'message' => 'Gagal reset ujian']);
    exit;
}

// ===================== TAMBAH_WAKTU =====================
if ($action === 'tambah_waktu') {
    $ujianSiswaId = (int)($_POST['ujian_siswa_id'] ?? 0);
    $menit        = (int)($_POST['menit'] ?? 0);
    if (!$ujianSiswaId || $menit < 1) { echo json_encode(['status' => 'error', 'message' => 'Data tidak valid']); exit; }

    $stmt = $conn->prepare("UPDATE ujian_siswa SET waktu_tambahan = waktu_tambahan + ? WHERE id = ?");
    $stmt->bind_param("ii", $menit, $ujianSiswaId);
    if ($stmt->execute()) echo json_encode(['status' => 'success', 'message' => "$menit menit berhasil ditambahkan"]);
    else echo json_encode(['status' => 'error', 'message' => 'Gagal menambah waktu']);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Action tidak dikenali']);
