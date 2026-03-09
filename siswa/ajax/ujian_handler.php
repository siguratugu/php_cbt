<?php
session_start();
date_default_timezone_set('Asia/Jakarta');
require_once __DIR__ . '/../../includes/auth.php';
cekLogin('siswa');
require_once __DIR__ . '/../../config/database.php';
header('Content-Type: application/json');

$action   = $_REQUEST['action'] ?? '';
$siswa_id = (int)$_SESSION['user_id'];
$kelas_id = $_SESSION['kelas_id'] ?? '';

// List available exam rooms for this student
if ($action === 'get_ruang_list') {
    $page    = max(1, (int)($_GET['page'] ?? 1));
    $perPage = $_GET['per_page'] ?? '10';

    $countStmt = $conn->prepare(
        "SELECT COUNT(*) as c FROM ruang_ujian ru
         JOIN ruang_ujian_kelas ruk ON ru.id = ruk.ruang_ujian_id
         WHERE ruk.kelas_id = ? AND NOW() BETWEEN ru.tanggal_mulai AND ru.tanggal_selesai"
    );
    $countStmt->bind_param("s", $kelas_id);
    $countStmt->execute();
    $total = $countStmt->get_result()->fetch_assoc()['c'];

    $baseQuery = "SELECT ru.id, ru.nama_ruang, ru.token, m.nama_mapel,
        COALESCE(us.status, 'belum') as status,
        COALESCE(us.jumlah_benar, 0) as jumlah_benar,
        COALESCE(us.jumlah_salah, 0) as jumlah_salah,
        COALESCE(us.nilai, 0) as nilai
        FROM ruang_ujian ru
        JOIN ruang_ujian_kelas ruk ON ru.id = ruk.ruang_ujian_id
        JOIN bank_soal bs ON ru.bank_soal_id = bs.id
        LEFT JOIN mapel m ON bs.mapel_id = m.id
        LEFT JOIN ujian_siswa us ON ru.id = us.ruang_ujian_id AND us.siswa_id = ?
        WHERE ruk.kelas_id = ? AND NOW() BETWEEN ru.tanggal_mulai AND ru.tanggal_selesai
        ORDER BY ru.id DESC";

    if ($perPage === 'all') {
        $stmt = $conn->prepare($baseQuery);
        $stmt->bind_param("is", $siswa_id, $kelas_id);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['status' => 'success', 'data' => $data, 'total' => $total, 'per_page' => 'all', 'page' => 1]);
    } else {
        $perPage = (int)$perPage;
        $offset  = ($page - 1) * $perPage;
        $stmt = $conn->prepare($baseQuery . " LIMIT ? OFFSET ?");
        $stmt->bind_param("isii", $siswa_id, $kelas_id, $perPage, $offset);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['status' => 'success', 'data' => $data, 'total' => $total, 'per_page' => $perPage, 'page' => $page]);
    }
    exit;
}

// Verify token before starting exam
if ($action === 'verify_token') {
    $token    = strtoupper(trim($_POST['token'] ?? ''));
    $ruang_id = (int)($_POST['ruang_id'] ?? 0);

    if (empty($token) || !$ruang_id) {
        echo json_encode(['status' => 'error', 'message' => 'Token dan ruang ujian wajib diisi']);
        exit;
    }

    $stmt = $conn->prepare(
        "SELECT ru.id FROM ruang_ujian ru
         JOIN ruang_ujian_kelas ruk ON ru.id = ruk.ruang_ujian_id
         WHERE ru.id = ? AND ru.token = ? AND ruk.kelas_id = ?
         AND NOW() BETWEEN ru.tanggal_mulai AND ru.tanggal_selesai"
    );
    $stmt->bind_param("iss", $ruang_id, $token, $kelas_id);
    $stmt->execute();
    $ruang = $stmt->get_result()->fetch_assoc();

    if (!$ruang) {
        echo json_encode(['status' => 'error', 'message' => 'Token tidak valid atau ujian tidak tersedia']);
        exit;
    }

    echo json_encode(['status' => 'success', 'message' => 'Token valid']);
    exit;
}

// Start or continue exam
if ($action === 'mulai') {
    $ruang_id = (int)($_POST['ruang_id'] ?? 0);
    if (!$ruang_id) { echo json_encode(['status' => 'error', 'message' => 'Ruang ujian tidak valid']); exit; }

    // Verify access
    $stmt = $conn->prepare(
        "SELECT ru.*, bs.waktu_mengerjakan, bs.id as bs_id
         FROM ruang_ujian ru
         JOIN ruang_ujian_kelas ruk ON ru.id = ruk.ruang_ujian_id
         JOIN bank_soal bs ON ru.bank_soal_id = bs.id
         WHERE ru.id = ? AND ruk.kelas_id = ? AND NOW() BETWEEN ru.tanggal_mulai AND ru.tanggal_selesai"
    );
    $stmt->bind_param("is", $ruang_id, $kelas_id);
    $stmt->execute();
    $ruang = $stmt->get_result()->fetch_assoc();
    if (!$ruang) { echo json_encode(['status' => 'error', 'message' => 'Akses ujian tidak valid']); exit; }

    // Check if already started
    $checkStmt = $conn->prepare("SELECT * FROM ujian_siswa WHERE ruang_ujian_id = ? AND siswa_id = ?");
    $checkStmt->bind_param("ii", $ruang_id, $siswa_id);
    $checkStmt->execute();
    $existingUjian = $checkStmt->get_result()->fetch_assoc();

    if ($existingUjian) {
        if ($existingUjian['status'] === 'selesai') {
            echo json_encode(['status' => 'error', 'message' => 'Ujian sudah selesai dikerjakan']);
            exit;
        }
        // Already in progress, return existing
        echo json_encode(['status' => 'success', 'ujian_id' => $existingUjian['id'], 'message' => 'Lanjutkan ujian']);
        exit;
    }

    // Get soal list
    $soalStmt = $conn->prepare("SELECT id FROM soal WHERE bank_soal_id = ? ORDER BY nomor_soal");
    $soalStmt->bind_param("i", $ruang['bs_id']);
    $soalStmt->execute();
    $soalList = $soalStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $soalIds = array_column($soalList, 'id');

    $acakSoalOrder = null;
    $acakJawabanOrder = null;

    if ($ruang['acak_soal'] && !empty($soalIds)) {
        shuffle($soalIds);
        $acakSoalOrder = implode(',', $soalIds);
    }
    if ($ruang['acak_jawaban'] && !empty($soalIds)) {
        $jawabanOrders = [];
        foreach ($soalIds as $sid) {
            $opsi = ['a', 'b', 'c', 'd', 'e'];
            shuffle($opsi);
            $jawabanOrders[$sid] = implode('', $opsi);
        }
        $acakJawabanOrder = json_encode($jawabanOrders);
    }

    $insStmt = $conn->prepare(
        "INSERT INTO ujian_siswa (ruang_ujian_id, siswa_id, status, waktu_mulai, acak_soal_order, acak_jawaban_order)
         VALUES (?, ?, 'sedang', NOW(), ?, ?)"
    );
    $insStmt->bind_param("iiss", $ruang_id, $siswa_id, $acakSoalOrder, $acakJawabanOrder);
    if (!$insStmt->execute()) {
        echo json_encode(['status' => 'error', 'message' => 'Gagal memulai ujian']);
        exit;
    }
    $ujian_id = $conn->insert_id;
    echo json_encode(['status' => 'success', 'ujian_id' => $ujian_id, 'message' => 'Ujian dimulai']);
    exit;
}

// Get exam data (soal list, timer, etc.)
if ($action === 'get_ujian_data') {
    $ruang_id = (int)($_GET['ruang_id'] ?? 0);
    if (!$ruang_id) { echo json_encode(['status' => 'error', 'message' => 'Ruang ujian tidak valid']); exit; }

    // Get ujian_siswa record
    $stmt = $conn->prepare(
        "SELECT us.*, ru.waktu_hentikan, ru.acak_soal, ru.acak_jawaban, ru.nama_ruang,
         bs.waktu_mengerjakan, bs.id as bank_soal_id, bs.nama_soal
         FROM ujian_siswa us
         JOIN ruang_ujian ru ON us.ruang_ujian_id = ru.id
         JOIN bank_soal bs ON ru.bank_soal_id = bs.id
         WHERE us.ruang_ujian_id = ? AND us.siswa_id = ?"
    );
    $stmt->bind_param("ii", $ruang_id, $siswa_id);
    $stmt->execute();
    $ujian = $stmt->get_result()->fetch_assoc();

    if (!$ujian) { echo json_encode(['status' => 'error', 'message' => 'Data ujian tidak ditemukan']); exit; }
    if ($ujian['status'] === 'selesai') { echo json_encode(['status' => 'selesai', 'data' => $ujian]); exit; }

    // Calculate remaining time
    $waktuMengerjakan = ($ujian['waktu_mengerjakan'] + ($ujian['waktu_tambahan'] ?? 0)) * 60; // seconds
    $elapsed = time() - strtotime($ujian['waktu_mulai']);
    $sisaWaktu = max(0, $waktuMengerjakan - $elapsed);

    // Get soal in correct order
    $soalOrder = $ujian['acak_soal_order'] ? explode(',', $ujian['acak_soal_order']) : null;

    $soalStmt = $conn->prepare(
        "SELECT s.id, s.nomor_soal, s.jenis_soal, s.pertanyaan,
         s.opsi_a, s.opsi_b, s.opsi_c, s.opsi_d, s.opsi_e,
         s.kunci_jawaban, s.pasangan_kiri, s.pasangan_kanan, s.pasangan_jawaban, s.jawaban_bs,
         js.jawaban, js.is_ragu
         FROM soal s
         LEFT JOIN jawaban_siswa js ON s.id = js.soal_id AND js.ujian_siswa_id = ?
         WHERE s.bank_soal_id = ?
         ORDER BY s.nomor_soal"
    );
    $soalStmt->bind_param("ii", $ujian['id'], $ujian['bank_soal_id']);
    $soalStmt->execute();
    $soalList = $soalStmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Reorder if acak
    if ($soalOrder && !empty($soalList)) {
        $soalById = [];
        foreach ($soalList as $s) { $soalById[$s['id']] = $s; }
        $ordered = [];
        foreach ($soalOrder as $sid) {
            if (isset($soalById[$sid])) $ordered[] = $soalById[$sid];
        }
        $soalList = $ordered;
    }

    // Apply jawaban order if acak
    $acakJawabanOrder = $ujian['acak_jawaban_order'] ? json_decode($ujian['acak_jawaban_order'], true) : null;
    if ($acakJawabanOrder) {
        foreach ($soalList as &$s) {
            if (isset($acakJawabanOrder[$s['id']]) && $s['jenis_soal'] === 'pg') {
                $s['acak_jawaban_order'] = $acakJawabanOrder[$s['id']];
            }
        }
        unset($s);
    }

    // Remove kunci_jawaban from response (security)
    foreach ($soalList as &$s) { unset($s['kunci_jawaban']); }
    unset($s);

    echo json_encode([
        'status'     => 'success',
        'ujian_id'   => $ujian['id'],
        'nama_ruang' => $ujian['nama_ruang'],
        'nama_soal'  => $ujian['nama_soal'],
        'sisa_waktu' => $sisaWaktu,
        'waktu_hentikan' => $ujian['waktu_hentikan'],
        'waktu_mulai' => $ujian['waktu_mulai'],
        'soal_list'  => $soalList,
        'status_ujian' => $ujian['status']
    ]);
    exit;
}

// Ping to keep session alive
if ($action === 'ping') {
    $ujian_id = (int)($_POST['ujian_id'] ?? 0);
    // Just verify it's a valid ongoing exam
    $stmt = $conn->prepare("SELECT id FROM ujian_siswa WHERE id = ? AND siswa_id = ? AND status = 'sedang'");
    $stmt->bind_param("ii", $ujian_id, $siswa_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Ujian tidak valid']);
    }
    exit;
}

// Finish exam
if ($action === 'selesai') {
    $ujian_id = (int)($_POST['ujian_id'] ?? 0);
    $ruang_id = (int)($_POST['ruang_id'] ?? 0);

    $stmt = $conn->prepare("SELECT us.*, ru.waktu_hentikan, bs.bobot_pg, bs.bobot_esai, bs.bobot_menjodohkan, bs.bobot_benar_salah, bs.id as bs_id FROM ujian_siswa us JOIN ruang_ujian ru ON us.ruang_ujian_id=ru.id JOIN bank_soal bs ON ru.bank_soal_id=bs.id WHERE us.id=? AND us.siswa_id=? AND us.status='sedang'");
    $stmt->bind_param("ii", $ujian_id, $siswa_id);
    $stmt->execute();
    $ujian = $stmt->get_result()->fetch_assoc();
    if (!$ujian) { echo json_encode(['status' => 'error', 'message' => 'Ujian tidak valid']); exit; }

    // Count answers
    $soalStmt = $conn->prepare("SELECT s.id, s.jenis_soal, s.kunci_jawaban, s.pasangan_jawaban, s.jawaban_bs, js.jawaban FROM soal s LEFT JOIN jawaban_siswa js ON s.id=js.soal_id AND js.ujian_siswa_id=? WHERE s.bank_soal_id=?");
    $soalStmt->bind_param("ii", $ujian_id, $ujian['bs_id']);
    $soalStmt->execute();
    $soalList = $soalStmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $jumlah_benar = 0; $jumlah_salah = 0;
    $pg_benar = 0; $pg_total = 0;
    $esai_benar = 0; $esai_total = 0;
    $menj_benar = 0; $menj_total = 0;
    $bs_benar = 0; $bs_total = 0;

    foreach ($soalList as $soal) {
        $jawaban = $soal['jawaban'] ?? '';
        $benar = false;
        if ($soal['jenis_soal'] === 'pg') {
            $pg_total++;
            if (!empty($jawaban) && strtolower($jawaban) === strtolower($soal['kunci_jawaban'])) {
                $benar = true; $pg_benar++;
            }
        } elseif ($soal['jenis_soal'] === 'esai') {
            $esai_total++;
            // Essay answers are marked correct if non-empty (manual review not supported)
            if (!empty($jawaban)) { $benar = true; $esai_benar++; }
        } elseif ($soal['jenis_soal'] === 'menjodohkan') {
            $menj_total++;
            if (!empty($jawaban) && $jawaban === $soal['pasangan_jawaban']) {
                $benar = true; $menj_benar++;
            }
        } elseif ($soal['jenis_soal'] === 'benar_salah') {
            $bs_total++;
            if (!empty($jawaban) && $jawaban === $soal['jawaban_bs']) {
                $benar = true; $bs_benar++;
            }
        }
        if ($benar) $jumlah_benar++;
        elseif (!empty($jawaban)) $jumlah_salah++;

        // Update is_benar in jawaban_siswa
        if (!empty($jawaban)) {
            $updJs = $conn->prepare("UPDATE jawaban_siswa SET is_benar=? WHERE ujian_siswa_id=? AND soal_id=?");
            $isBenar = $benar ? 1 : 0;
            $updJs->bind_param("iii", $isBenar, $ujian_id, $soal['id']);
            $updJs->execute();
        }
    }

    // Calculate nilai
    $nilai = 0;
    $totalSoal = count($soalList);
    if ($totalSoal > 0) {
        $bobot_pg    = (float)$ujian['bobot_pg'];
        $bobot_esai  = (float)$ujian['bobot_esai'];
        $bobot_menj  = (float)$ujian['bobot_menjodohkan'];
        $bobot_bs    = (float)$ujian['bobot_benar_salah'];

        if ($pg_total > 0)   $nilai += ($pg_benar / $pg_total) * $bobot_pg;
        if ($esai_total > 0) $nilai += ($esai_benar / $esai_total) * $bobot_esai;
        if ($menj_total > 0) $nilai += ($menj_benar / $menj_total) * $bobot_menj;
        if ($bs_total > 0)   $nilai += ($bs_benar / $bs_total) * $bobot_bs;
    }

    $updStmt = $conn->prepare("UPDATE ujian_siswa SET status='selesai', waktu_selesai=NOW(), jumlah_benar=?, jumlah_salah=?, nilai=? WHERE id=?");
    $updStmt->bind_param("iidi", $jumlah_benar, $jumlah_salah, $nilai, $ujian_id);
    $updStmt->execute();

    echo json_encode([
        'status'       => 'success',
        'jumlah_benar' => $jumlah_benar,
        'jumlah_salah' => $jumlah_salah,
        'nilai'        => round($nilai, 2),
        'message'      => 'Ujian selesai'
    ]);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Action tidak dikenali']);
