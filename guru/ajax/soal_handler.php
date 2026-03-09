<?php
session_start();
require_once __DIR__ . '/../../includes/auth.php';
cekLogin('guru');
require_once __DIR__ . '/../../config/database.php';
header('Content-Type: application/json');

$action  = $_REQUEST['action'] ?? '';
$guru_id = (int)$_SESSION['user_id'];

function updateJumlahSoal($conn, $bank_soal_id) {
    $stmt = $conn->prepare("UPDATE bank_soal SET jumlah_soal = (SELECT COUNT(*) FROM soal WHERE bank_soal_id = ?) WHERE id = ?");
    $stmt->bind_param("ii", $bank_soal_id, $bank_soal_id);
    $stmt->execute();
}

function verifyBankSoalOwner($conn, $bank_soal_id, $guru_id) {
    $stmt = $conn->prepare("SELECT id FROM bank_soal WHERE id = ? AND guru_id = ?");
    $stmt->bind_param("ii", $bank_soal_id, $guru_id);
    $stmt->execute();
    return $stmt->get_result()->num_rows > 0;
}

if ($action === 'get_soal') {
    $bank_soal_id = (int)($_GET['bank_soal_id'] ?? 0);
    if (!verifyBankSoalOwner($conn, $bank_soal_id, $guru_id)) { echo json_encode(['status'=>'error','message'=>'Akses ditolak']); exit; }
    $stmt = $conn->prepare("SELECT * FROM soal WHERE bank_soal_id = ? ORDER BY nomor_soal ASC");
    $stmt->bind_param("i", $bank_soal_id); $stmt->execute();
    $data = []; $r = $stmt->get_result(); while($row = $r->fetch_assoc()) $data[] = $row;
    echo json_encode(['status'=>'success','data'=>$data]); exit;
}

if ($action === 'get_single') {
    $soal_id = (int)($_GET['soal_id'] ?? 0);
    $stmt = $conn->prepare("SELECT s.* FROM soal s JOIN bank_soal bs ON s.bank_soal_id=bs.id WHERE s.id=? AND bs.guru_id=?");
    $stmt->bind_param("ii", $soal_id, $guru_id); $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!$row) { echo json_encode(['status'=>'error','message'=>'Soal tidak ditemukan']); exit; }
    echo json_encode(['status'=>'success','data'=>$row]); exit;
}

if ($action === 'get_count') {
    $bank_soal_id = (int)($_GET['bank_soal_id'] ?? 0);
    if (!verifyBankSoalOwner($conn, $bank_soal_id, $guru_id)) { echo json_encode(['status'=>'error','message'=>'Akses ditolak']); exit; }
    $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM soal WHERE bank_soal_id = ?");
    $stmt->bind_param("i", $bank_soal_id); $stmt->execute();
    echo json_encode(['status'=>'success','count'=>(int)$stmt->get_result()->fetch_assoc()['c']]); exit;
}

if ($action === 'save_soal') {
    $bank_soal_id = (int)($_POST['bank_soal_id'] ?? 0);
    $nomor_soal   = (int)($_POST['nomor_soal'] ?? 0);
    $jenis_soal   = trim($_POST['jenis_soal'] ?? '');
    $pertanyaan   = trim($_POST['pertanyaan'] ?? '');
    if (!verifyBankSoalOwner($conn, $bank_soal_id, $guru_id)) { echo json_encode(['status'=>'error','message'=>'Akses ditolak']); exit; }
    if (!$bank_soal_id || !$nomor_soal || empty($jenis_soal) || empty($pertanyaan)) { echo json_encode(['status'=>'error','message'=>'Data tidak lengkap']); exit; }
    $allowed = ['pg','esai','menjodohkan','benar_salah'];
    if (!in_array($jenis_soal, $allowed)) { echo json_encode(['status'=>'error','message'=>'Jenis soal tidak valid']); exit; }
    $opsi_a=$opsi_b=$opsi_c=$opsi_d=$opsi_e=$kunci_jawaban=$pasangan_kiri=$pasangan_kanan=$pasangan_jawaban=$jawaban_bs=null;
    if ($jenis_soal==='pg') { $opsi_a=trim($_POST['opsi_a']??'')?:null; $opsi_b=trim($_POST['opsi_b']??'')?:null; $opsi_c=trim($_POST['opsi_c']??'')?:null; $opsi_d=trim($_POST['opsi_d']??'')?:null; $opsi_e=trim($_POST['opsi_e']??'')?:null; $kunci_jawaban=trim($_POST['kunci_jawaban']??'')?:null; }
    elseif ($jenis_soal==='esai') { $kunci_jawaban=trim($_POST['kunci_jawaban']??'')?:null; }
    elseif ($jenis_soal==='menjodohkan') { $pasangan_kiri=trim($_POST['pasangan_kiri']??'')?:null; $pasangan_kanan=trim($_POST['pasangan_kanan']??'')?:null; $pasangan_jawaban=trim($_POST['pasangan_jawaban']??'')?:null; }
    elseif ($jenis_soal==='benar_salah') { $bs=trim($_POST['jawaban_bs']??''); $jawaban_bs=in_array($bs,['benar','salah'])?$bs:null; }
    $check=$conn->prepare("SELECT id FROM soal WHERE bank_soal_id=? AND nomor_soal=?"); $check->bind_param("ii",$bank_soal_id,$nomor_soal); $check->execute(); $existing=$check->get_result()->fetch_assoc();
    if ($existing) {
        $soal_id=$existing['id'];
        $stmt=$conn->prepare("UPDATE soal SET jenis_soal=?,pertanyaan=?,opsi_a=?,opsi_b=?,opsi_c=?,opsi_d=?,opsi_e=?,kunci_jawaban=?,pasangan_kiri=?,pasangan_kanan=?,pasangan_jawaban=?,jawaban_bs=? WHERE id=?");
        $stmt->bind_param("ssssssssssssi",$jenis_soal,$pertanyaan,$opsi_a,$opsi_b,$opsi_c,$opsi_d,$opsi_e,$kunci_jawaban,$pasangan_kiri,$pasangan_kanan,$pasangan_jawaban,$jawaban_bs,$soal_id);
        if ($stmt->execute()) { updateJumlahSoal($conn,$bank_soal_id); echo json_encode(['status'=>'success','soal_id'=>$soal_id,'message'=>'Soal berhasil diupdate']); }
        else { echo json_encode(['status'=>'error','message'=>'Gagal update: '.$conn->error]); }
    } else {
        $stmt=$conn->prepare("INSERT INTO soal (bank_soal_id,nomor_soal,jenis_soal,pertanyaan,opsi_a,opsi_b,opsi_c,opsi_d,opsi_e,kunci_jawaban,pasangan_kiri,pasangan_kanan,pasangan_jawaban,jawaban_bs) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param("iissssssssssss",$bank_soal_id,$nomor_soal,$jenis_soal,$pertanyaan,$opsi_a,$opsi_b,$opsi_c,$opsi_d,$opsi_e,$kunci_jawaban,$pasangan_kiri,$pasangan_kanan,$pasangan_jawaban,$jawaban_bs);
        if ($stmt->execute()) { updateJumlahSoal($conn,$bank_soal_id); echo json_encode(['status'=>'success','soal_id'=>$conn->insert_id,'message'=>'Soal berhasil disimpan']); }
        else { echo json_encode(['status'=>'error','message'=>'Gagal insert: '.$conn->error]); }
    }
    exit;
}

if ($action === 'delete_soal') {
    $soal_id=$bank_soal_id=(int)($_POST['soal_id']??0);
    $bank_soal_id=(int)($_POST['bank_soal_id']??0);
    if (!verifyBankSoalOwner($conn,$bank_soal_id,$guru_id)) { echo json_encode(['status'=>'error','message'=>'Akses ditolak']); exit; }
    $del=$conn->prepare("DELETE FROM soal WHERE id=? AND bank_soal_id=?"); $del->bind_param("ii",$soal_id,$bank_soal_id);
    if (!$del->execute()) { echo json_encode(['status'=>'error','message'=>'Gagal hapus']); exit; }
    $rem=$conn->prepare("SELECT id FROM soal WHERE bank_soal_id=? ORDER BY nomor_soal ASC"); $rem->bind_param("i",$bank_soal_id); $rem->execute();
    $rows=$rem->get_result()->fetch_all(MYSQLI_ASSOC);
    foreach($rows as $idx=>$row){ $n=$idx+1; $upd=$conn->prepare("UPDATE soal SET nomor_soal=? WHERE id=?"); $upd->bind_param("ii",$n,$row['id']); $upd->execute(); }
    updateJumlahSoal($conn,$bank_soal_id);
    echo json_encode(['status'=>'success','message'=>'Soal berhasil dihapus']); exit;
}

echo json_encode(['status'=>'error','message'=>'Action tidak dikenali']);
