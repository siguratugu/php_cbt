<?php
session_start();
require_once __DIR__ . '/../../includes/auth.php';
cekLogin('admin');
require_once __DIR__ . '/../../config/database.php';
header('Content-Type: application/json');

$action = $_REQUEST['action'] ?? '';

/* =====================================================================
   HELPER  –  update jumlah_soal counter on bank_soal
   ===================================================================== */
function updateJumlahSoal($conn, $bank_soal_id) {
    $stmt = $conn->prepare(
        "UPDATE bank_soal SET jumlah_soal = (SELECT COUNT(*) FROM soal WHERE bank_soal_id = ?) WHERE id = ?"
    );
    $stmt->bind_param("ii", $bank_soal_id, $bank_soal_id);
    $stmt->execute();
}

/* =====================================================================
   GET SOAL  –  all soal for a bank_soal_id
   ===================================================================== */
if ($action === 'get_soal') {
    $bank_soal_id = (int)($_GET['bank_soal_id'] ?? 0);
    if (!$bank_soal_id) {
        echo json_encode(['status' => 'error', 'message' => 'bank_soal_id tidak valid']);
        exit;
    }

    $stmt = $conn->prepare(
        "SELECT * FROM soal WHERE bank_soal_id = ? ORDER BY nomor_soal ASC"
    );
    $stmt->bind_param("i", $bank_soal_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data   = [];
    while ($row = $result->fetch_assoc()) $data[] = $row;

    echo json_encode(['status' => 'success', 'data' => $data]);
    exit;
}

/* =====================================================================
   GET SINGLE SOAL
   ===================================================================== */
if ($action === 'get_single') {
    $soal_id = (int)($_GET['soal_id'] ?? 0);
    if (!$soal_id) {
        echo json_encode(['status' => 'error', 'message' => 'soal_id tidak valid']);
        exit;
    }
    $stmt = $conn->prepare("SELECT * FROM soal WHERE id = ?");
    $stmt->bind_param("i", $soal_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!$row) {
        echo json_encode(['status' => 'error', 'message' => 'Soal tidak ditemukan']);
        exit;
    }
    echo json_encode(['status' => 'success', 'data' => $row]);
    exit;
}

/* =====================================================================
   GET COUNT
   ===================================================================== */
if ($action === 'get_count') {
    $bank_soal_id = (int)($_GET['bank_soal_id'] ?? 0);
    if (!$bank_soal_id) {
        echo json_encode(['status' => 'error', 'message' => 'bank_soal_id tidak valid']);
        exit;
    }
    $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM soal WHERE bank_soal_id = ?");
    $stmt->bind_param("i", $bank_soal_id);
    $stmt->execute();
    $count = (int)$stmt->get_result()->fetch_assoc()['c'];
    echo json_encode(['status' => 'success', 'count' => $count]);
    exit;
}

/* =====================================================================
   SAVE SOAL  –  upsert by bank_soal_id + nomor_soal
   ===================================================================== */
if ($action === 'save_soal') {
    $bank_soal_id = (int)($_POST['bank_soal_id'] ?? 0);
    $nomor_soal   = (int)($_POST['nomor_soal']   ?? 0);
    $jenis_soal   = trim($_POST['jenis_soal']    ?? '');
    $pertanyaan   = trim($_POST['pertanyaan']    ?? '');

    if (!$bank_soal_id || !$nomor_soal || empty($jenis_soal) || empty($pertanyaan)) {
        echo json_encode(['status' => 'error', 'message' => 'Data soal tidak lengkap']);
        exit;
    }

    $allowed_jenis = ['pg', 'esai', 'menjodohkan', 'benar_salah'];
    if (!in_array($jenis_soal, $allowed_jenis)) {
        echo json_encode(['status' => 'error', 'message' => 'Jenis soal tidak valid']);
        exit;
    }

    // Collect type-specific fields; set NULL for unused fields
    $opsi_a = $opsi_b = $opsi_c = $opsi_d = $opsi_e = null;
    $kunci_jawaban = null;
    $pasangan_kiri = $pasangan_kanan = $pasangan_jawaban = null;
    $jawaban_bs    = null;

    if ($jenis_soal === 'pg') {
        $opsi_a         = trim($_POST['opsi_a'] ?? '') ?: null;
        $opsi_b         = trim($_POST['opsi_b'] ?? '') ?: null;
        $opsi_c         = trim($_POST['opsi_c'] ?? '') ?: null;
        $opsi_d         = trim($_POST['opsi_d'] ?? '') ?: null;
        $opsi_e         = trim($_POST['opsi_e'] ?? '') ?: null;
        $kunci_jawaban  = trim($_POST['kunci_jawaban'] ?? '') ?: null;

    } elseif ($jenis_soal === 'esai') {
        $kunci_jawaban  = trim($_POST['kunci_jawaban'] ?? '') ?: null;

    } elseif ($jenis_soal === 'menjodohkan') {
        $pasangan_kiri    = trim($_POST['pasangan_kiri']    ?? '') ?: null;
        $pasangan_kanan   = trim($_POST['pasangan_kanan']   ?? '') ?: null;
        $pasangan_jawaban = trim($_POST['pasangan_jawaban'] ?? '') ?: null;

    } elseif ($jenis_soal === 'benar_salah') {
        $bs_val     = trim($_POST['jawaban_bs'] ?? '');
        $jawaban_bs = in_array($bs_val, ['benar','salah']) ? $bs_val : null;
    }

    // Check if already exists (upsert logic)
    $check = $conn->prepare("SELECT id FROM soal WHERE bank_soal_id = ? AND nomor_soal = ?");
    $check->bind_param("ii", $bank_soal_id, $nomor_soal);
    $check->execute();
    $existing = $check->get_result()->fetch_assoc();

    if ($existing) {
        // ---- UPDATE ----
        // 12 SET params (s×12) + 1 WHERE param (i) = 13 total → "ssssssssssssi"
        $soal_id = $existing['id'];
        $stmt    = $conn->prepare(
            "UPDATE soal
             SET jenis_soal=?, pertanyaan=?,
                 opsi_a=?, opsi_b=?, opsi_c=?, opsi_d=?, opsi_e=?,
                 kunci_jawaban=?, pasangan_kiri=?, pasangan_kanan=?, pasangan_jawaban=?,
                 jawaban_bs=?
             WHERE id = ?"
        );
        $stmt->bind_param(
            "ssssssssssssi",
            $jenis_soal, $pertanyaan,
            $opsi_a, $opsi_b, $opsi_c, $opsi_d, $opsi_e,
            $kunci_jawaban, $pasangan_kiri, $pasangan_kanan, $pasangan_jawaban,
            $jawaban_bs,
            $soal_id
        );

        if ($stmt->execute()) {
            updateJumlahSoal($conn, $bank_soal_id);
            echo json_encode(['status' => 'success', 'soal_id' => $soal_id, 'message' => 'Soal berhasil diupdate']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal mengupdate soal: ' . $conn->error]);
        }

    } else {
        // ---- INSERT ----
        // 2 INT params + 12 STRING params = 14 total → "iissssssssssss"
        $stmt = $conn->prepare(
            "INSERT INTO soal
             (bank_soal_id, nomor_soal, jenis_soal, pertanyaan,
              opsi_a, opsi_b, opsi_c, opsi_d, opsi_e,
              kunci_jawaban, pasangan_kiri, pasangan_kanan, pasangan_jawaban, jawaban_bs)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param(
            "iissssssssssss",
            $bank_soal_id, $nomor_soal, $jenis_soal, $pertanyaan,
            $opsi_a, $opsi_b, $opsi_c, $opsi_d, $opsi_e,
            $kunci_jawaban, $pasangan_kiri, $pasangan_kanan, $pasangan_jawaban, $jawaban_bs
        );

        if ($stmt->execute()) {
            $soal_id = $conn->insert_id;
            updateJumlahSoal($conn, $bank_soal_id);
            echo json_encode(['status' => 'success', 'soal_id' => $soal_id, 'message' => 'Soal berhasil disimpan']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan soal: ' . $conn->error]);
        }
    }
    exit;
}

/* =====================================================================
   DELETE SOAL  –  delete then renumber sequentially
   ===================================================================== */
if ($action === 'delete_soal') {
    $soal_id      = (int)($_POST['soal_id']      ?? 0);
    $bank_soal_id = (int)($_POST['bank_soal_id'] ?? 0);

    if (!$soal_id || !$bank_soal_id) {
        echo json_encode(['status' => 'error', 'message' => 'Parameter tidak valid']);
        exit;
    }

    // Delete the soal
    $del = $conn->prepare("DELETE FROM soal WHERE id = ? AND bank_soal_id = ?");
    $del->bind_param("ii", $soal_id, $bank_soal_id);
    if (!$del->execute()) {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus soal']);
        exit;
    }

    // Renumber remaining soal sequentially (1, 2, 3, …)
    $remaining = $conn->prepare(
        "SELECT id FROM soal WHERE bank_soal_id = ? ORDER BY nomor_soal ASC"
    );
    $remaining->bind_param("i", $bank_soal_id);
    $remaining->execute();
    $rows = $remaining->get_result()->fetch_all(MYSQLI_ASSOC);

    foreach ($rows as $idx => $row) {
        $newNum = $idx + 1;
        $upd    = $conn->prepare("UPDATE soal SET nomor_soal = ? WHERE id = ?");
        $upd->bind_param("ii", $newNum, $row['id']);
        $upd->execute();
    }

    updateJumlahSoal($conn, $bank_soal_id);
    echo json_encode(['status' => 'success', 'message' => 'Soal berhasil dihapus dan nomor diurutkan ulang']);
    exit;
}

/* =====================================================================
   IMPORT EXCEL  –  uses PhpSpreadsheet if available
   ===================================================================== */
if ($action === 'import_excel') {
    $bank_soal_id = (int)($_POST['bank_soal_id'] ?? 0);
    if (!$bank_soal_id) {
        echo json_encode(['status' => 'error', 'message' => 'bank_soal_id tidak valid']);
        exit;
    }

    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['status' => 'error', 'message' => 'File tidak ditemukan atau gagal diupload']);
        exit;
    }

    $file     = $_FILES['file'];
    $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['xlsx', 'xls'])) {
        echo json_encode(['status' => 'error', 'message' => 'Format file harus .xlsx atau .xls']);
        exit;
    }

    // Check if PhpSpreadsheet is available via Composer autoload
    $autoload = __DIR__ . '/../../vendor/autoload.php';
    if (!file_exists($autoload)) {
        echo json_encode(['status' => 'error', 'message' => 'PhpSpreadsheet belum terinstall. Jalankan: composer require phpoffice/phpspreadsheet']);
        exit;
    }

    require_once $autoload;

    try {
        $reader      = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($file['tmp_name']);
        $spreadsheet = $reader->load($file['tmp_name']);
        $sheet       = $spreadsheet->getActiveSheet();
        $rows        = $sheet->toArray(null, true, true, true);

        // Expected columns: A=nomor, B=jenis, C=pertanyaan, D=opsi_a, E=opsi_b, F=opsi_c, G=opsi_d, H=opsi_e, I=kunci_jawaban, J=jawaban_bs
        $imported = 0;
        $errors   = [];

        // Get current max nomor_soal
        $maxRes    = $conn->prepare("SELECT COALESCE(MAX(nomor_soal),0) AS m FROM soal WHERE bank_soal_id = ?");
        $maxRes->bind_param("i", $bank_soal_id);
        $maxRes->execute();
        $maxNomor  = (int)$maxRes->get_result()->fetch_assoc()['m'];

        foreach ($rows as $rowNum => $row) {
            if ($rowNum === 1) continue; // Skip header
            $jenis       = strtolower(trim($row['B'] ?? ''));
            $pertanyaan  = trim($row['C'] ?? '');
            if (empty($pertanyaan) || empty($jenis)) continue;
            if (!in_array($jenis, ['pg','esai','menjodohkan','benar_salah'])) {
                $errors[] = "Baris $rowNum: jenis soal '$jenis' tidak valid";
                continue;
            }

            $maxNomor++;
            $opsi_a = trim($row['D'] ?? '') ?: null;
            $opsi_b = trim($row['E'] ?? '') ?: null;
            $opsi_c = trim($row['F'] ?? '') ?: null;
            $opsi_d = trim($row['G'] ?? '') ?: null;
            $opsi_e = trim($row['H'] ?? '') ?: null;
            $kunci  = trim($row['I'] ?? '') ?: null;
            $bs     = trim($row['J'] ?? '');
            $jawaban_bs = in_array(strtolower($bs), ['benar','salah']) ? strtolower($bs) : null;

            // 10 placeholders: ii (bank_soal_id, nomor_soal) + ssssssss (jenis…kunci) = "iissssssss"
            $ins = $conn->prepare(
                "INSERT INTO soal (bank_soal_id, nomor_soal, jenis_soal, pertanyaan, opsi_a, opsi_b, opsi_c, opsi_d, opsi_e, kunci_jawaban)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $ins->bind_param("iissssssss", $bank_soal_id, $maxNomor, $jenis, $pertanyaan, $opsi_a, $opsi_b, $opsi_c, $opsi_d, $opsi_e, $kunci);
            if ($ins->execute()) {
                if ($jawaban_bs !== null) {
                    $sid  = $conn->insert_id;
                    $upd2 = $conn->prepare("UPDATE soal SET jawaban_bs=? WHERE id=?");
                    $upd2->bind_param("si", $jawaban_bs, $sid);
                    $upd2->execute();
                }
                $imported++;
            } else {
                $errors[] = "Baris $rowNum: gagal insert";
            }
        }

        updateJumlahSoal($conn, $bank_soal_id);
        $msg = "$imported soal berhasil diimport";
        if (!empty($errors)) $msg .= '. Beberapa baris dilewati: ' . implode('; ', array_slice($errors, 0, 3));
        echo json_encode(['status' => 'success', 'message' => $msg]);

    } catch (\Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Gagal membaca file: ' . $e->getMessage()]);
    }
    exit;
}

/* =====================================================================
   IMPORT WORD  –  placeholder (requires external library)
   ===================================================================== */
if ($action === 'import_word') {
    $bank_soal_id = (int)($_POST['bank_soal_id'] ?? 0);
    if (!$bank_soal_id) {
        echo json_encode(['status' => 'error', 'message' => 'bank_soal_id tidak valid']);
        exit;
    }
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['status' => 'error', 'message' => 'File tidak ditemukan atau gagal diupload']);
        exit;
    }
    $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
    if ($ext !== 'docx') {
        echo json_encode(['status' => 'error', 'message' => 'Format file harus .docx']);
        exit;
    }

    // Check for PhpWord via Composer
    $autoload = __DIR__ . '/../../vendor/autoload.php';
    if (!file_exists($autoload)) {
        echo json_encode(['status' => 'error', 'message' => 'PhpWord belum terinstall. Jalankan: composer require phpoffice/phpword']);
        exit;
    }

    require_once $autoload;

    try {
        $phpWord   = \PhpOffice\PhpWord\IOFactory::load($_FILES['file']['tmp_name']);
        $sections  = $phpWord->getSections();
        $imported  = 0;
        $maxRes    = $conn->prepare("SELECT COALESCE(MAX(nomor_soal),0) AS m FROM soal WHERE bank_soal_id = ?");
        $maxRes->bind_param("i", $bank_soal_id);
        $maxRes->execute();
        $maxNomor  = (int)$maxRes->get_result()->fetch_assoc()['m'];

        foreach ($sections as $section) {
            foreach ($section->getElements() as $element) {
                if ($element instanceof \PhpOffice\PhpWord\Element\TextRun) {
                    $text = trim($element->getText());
                    if (empty($text)) continue;
                    $maxNomor++;
                    $ins = $conn->prepare(
                        "INSERT INTO soal (bank_soal_id, nomor_soal, jenis_soal, pertanyaan) VALUES (?, ?, 'pg', ?)"
                    );
                    $ins->bind_param("iis", $bank_soal_id, $maxNomor, $text);
                    if ($ins->execute()) $imported++;
                }
            }
        }

        updateJumlahSoal($conn, $bank_soal_id);
        echo json_encode(['status' => 'success', 'message' => "$imported soal berhasil diimport dari Word"]);

    } catch (\Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Gagal membaca file: ' . $e->getMessage()]);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Action tidak dikenali']);
