<?php
session_start();
require_once __DIR__ . '/../../includes/auth.php';
cekLogin('guru');
require_once __DIR__ . '/../../config/database.php';
header('Content-Type: application/json');

$action  = $_REQUEST['action'] ?? '';
$guru_id = (int)$_SESSION['user_id'];

function generateTokenGuru($conn) {
    do {
        $token = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 6));
        $check = $conn->query("SELECT id FROM ruang_ujian WHERE token = '$token'");
    } while ($check->num_rows > 0);
    return $token;
}

if ($action === 'get_all') {
    $page    = max(1,(int)($_GET['page']??1));
    $perPage = $_GET['per_page']??'10';
    $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM ruang_ujian WHERE guru_id=?");
    $stmt->bind_param("i",$guru_id); $stmt->execute();
    $total = $stmt->get_result()->fetch_assoc()['c'];
    $baseQuery = "SELECT ru.*, bs.nama_soal, GROUP_CONCAT(k.nama_kelas ORDER BY k.nama_kelas SEPARATOR ',') AS kelas_list FROM ruang_ujian ru LEFT JOIN bank_soal bs ON ru.bank_soal_id=bs.id LEFT JOIN ruang_ujian_kelas ruk ON ru.id=ruk.ruang_ujian_id LEFT JOIN kelas k ON ruk.kelas_id=k.id WHERE ru.guru_id=? GROUP BY ru.id ORDER BY ru.id DESC";
    if ($perPage==='all') {
        $stmt2=$conn->prepare($baseQuery); $stmt2->bind_param("i",$guru_id); $stmt2->execute();
        $data=[]; $r=$stmt2->get_result(); while($row=$r->fetch_assoc()) $data[]=$row;
        echo json_encode(['status'=>'success','data'=>$data,'total'=>$total,'per_page'=>'all','page'=>1]);
    } else {
        $perPage=(int)$perPage; $offset=($page-1)*$perPage;
        $stmt2=$conn->prepare($baseQuery." LIMIT ? OFFSET ?"); $stmt2->bind_param("iii",$guru_id,$perPage,$offset); $stmt2->execute();
        $data=[]; $r=$stmt2->get_result(); while($row=$r->fetch_assoc()) $data[]=$row;
        echo json_encode(['status'=>'success','data'=>$data,'total'=>$total,'per_page'=>$perPage,'page'=>$page]);
    }
    exit;
}

if ($action === 'get_detail') {
    $id=(int)($_GET['id']??0);
    $stmt=$conn->prepare("SELECT * FROM ruang_ujian WHERE id=? AND guru_id=?"); $stmt->bind_param("ii",$id,$guru_id); $stmt->execute();
    $data=$stmt->get_result()->fetch_assoc();
    if (!$data) { echo json_encode(['status'=>'error','message'=>'Data tidak ditemukan']); exit; }
    $ks=$conn->prepare("SELECT kelas_id FROM ruang_ujian_kelas WHERE ruang_ujian_id=?"); $ks->bind_param("i",$id); $ks->execute();
    $kelasIds=[]; $kr=$ks->get_result(); while($r=$kr->fetch_assoc()) $kelasIds[]=$r['kelas_id'];
    echo json_encode(['status'=>'success','data'=>$data,'kelas_ids'=>$kelasIds]); exit;
}

if ($action === 'generate_token') {
    echo json_encode(['status'=>'success','token'=>generateTokenGuru($conn)]); exit;
}

if ($action === 'get_bank_soal') {
    $stmt=$conn->prepare("SELECT bs.id, bs.nama_soal FROM bank_soal bs WHERE bs.guru_id=? ORDER BY bs.nama_soal");
    $stmt->bind_param("i",$guru_id); $stmt->execute();
    $data=[]; $r=$stmt->get_result(); while($row=$r->fetch_assoc()) $data[]=$row;
    echo json_encode(['status'=>'success','data'=>$data]); exit;
}

if ($action === 'get_kelas') {
    $stmt=$conn->prepare("SELECT DISTINCT k.id, k.nama_kelas FROM relasi_guru rg JOIN kelas k ON rg.kelas_id=k.id WHERE rg.guru_id=? ORDER BY k.nama_kelas");
    $stmt->bind_param("i",$guru_id); $stmt->execute();
    $data=[]; $r=$stmt->get_result(); while($row=$r->fetch_assoc()) $data[]=$row;
    echo json_encode(['status'=>'success','data'=>$data]); exit;
}

if ($action === 'add') {
    $namaRuang=trim($_POST['nama_ruang']??''); $bankSoalId=(int)($_POST['bank_soal_id']??0);
    $waktuHentikan=(int)($_POST['waktu_hentikan']??0); $batasKeluar=(int)($_POST['batas_keluar']??3);
    $kelasIds=$_POST['kelas_ids']??[]; $tMulai=trim($_POST['tanggal_mulai']??'');
    $tSelesai=trim($_POST['tanggal_selesai']??''); $acakSoal=(int)($_POST['acak_soal']??0);
    $acakJawaban=(int)($_POST['acak_jawaban']??0); $token=strtoupper(trim($_POST['token']??''));
    if (empty($namaRuang)||!$bankSoalId||!$waktuHentikan||empty($tMulai)||empty($tSelesai)||empty($token)) { echo json_encode(['status'=>'error','message'=>'Semua field wajib diisi']); exit; }
    if (empty($kelasIds)) { echo json_encode(['status'=>'error','message'=>'Pilih minimal satu kelas']); exit; }
    $verif=$conn->prepare("SELECT id FROM bank_soal WHERE id=? AND guru_id=?"); $verif->bind_param("ii",$bankSoalId,$guru_id); $verif->execute();
    if ($verif->get_result()->num_rows===0) { echo json_encode(['status'=>'error','message'=>'Bank soal tidak valid']); exit; }
    $chk=$conn->prepare("SELECT id FROM ruang_ujian WHERE token=?"); $chk->bind_param("s",$token); $chk->execute();
    if ($chk->get_result()->num_rows>0) { echo json_encode(['status'=>'error','message'=>'Token sudah digunakan']); exit; }
    $tMulaiDb=date('Y-m-d H:i:s',strtotime(str_replace('T',' ',$tMulai)));
    $tSelesaiDb=date('Y-m-d H:i:s',strtotime(str_replace('T',' ',$tSelesai)));
    $stmt=$conn->prepare("INSERT INTO ruang_ujian (nama_ruang,token,guru_id,bank_soal_id,waktu_hentikan,batas_keluar,tanggal_mulai,tanggal_selesai,acak_soal,acak_jawaban) VALUES (?,?,?,?,?,?,?,?,?,?)");
    $stmt->bind_param("ssiiiiisii",$namaRuang,$token,$guru_id,$bankSoalId,$waktuHentikan,$batasKeluar,$tMulaiDb,$tSelesaiDb,$acakSoal,$acakJawaban);
    if (!$stmt->execute()) { echo json_encode(['status'=>'error','message'=>'Gagal: '.$conn->error]); exit; }
    $ruangId=$conn->insert_id;
    $sk=$conn->prepare("INSERT INTO ruang_ujian_kelas (ruang_ujian_id,kelas_id) VALUES (?,?)");
    foreach($kelasIds as $kId) { $sk->bind_param("is",$ruangId,$kId); $sk->execute(); }
    echo json_encode(['status'=>'success','message'=>'Ruang ujian berhasil ditambahkan']); exit;
}

if ($action === 'delete') {
    $id=(int)($_POST['id']??0);
    $stmt=$conn->prepare("DELETE FROM ruang_ujian WHERE id=? AND guru_id=?"); $stmt->bind_param("ii",$id,$guru_id);
    if ($stmt->execute()&&$stmt->affected_rows>0) echo json_encode(['status'=>'success','message'=>'Berhasil dihapus']);
    else echo json_encode(['status'=>'error','message'=>'Gagal menghapus']); exit;
}

if ($action === 'get_monitoring') {
    $ruang_id=(int)($_GET['ruang_id']??0);
    $verif=$conn->prepare("SELECT id FROM ruang_ujian WHERE id=? AND guru_id=?"); $verif->bind_param("ii",$ruang_id,$guru_id); $verif->execute();
    if ($verif->get_result()->num_rows===0) { echo json_encode(['status'=>'error','message'=>'Akses ditolak']); exit; }
    $stmt=$conn->prepare("SELECT us.*, s.nama, s.nisn, k.nama_kelas FROM ujian_siswa us JOIN siswa s ON us.siswa_id=s.id LEFT JOIN kelas k ON s.kelas_id=k.id WHERE us.ruang_ujian_id=? ORDER BY s.nama");
    $stmt->bind_param("i",$ruang_id); $stmt->execute();
    $data=[]; $r=$stmt->get_result();
    while($row=$r->fetch_assoc()) {
        if ($row['waktu_mulai']&&$row['waktu_selesai']) { $diff=strtotime($row['waktu_selesai'])-strtotime($row['waktu_mulai']); $row['durasi']=floor($diff/60).'m '.($diff%60).'s'; }
        else { $row['durasi']='-'; }
        $data[]=$row;
    }
    echo json_encode(['status'=>'success','data'=>$data]); exit;
}

if ($action === 'reset_ujian') {
    $ujian_id=(int)($_POST['ujian_id']??0);
    $verif=$conn->prepare("SELECT us.id FROM ujian_siswa us JOIN ruang_ujian ru ON us.ruang_ujian_id=ru.id WHERE us.id=? AND ru.guru_id=?"); $verif->bind_param("ii",$ujian_id,$guru_id); $verif->execute();
    if ($verif->get_result()->num_rows===0) { echo json_encode(['status'=>'error','message'=>'Akses ditolak']); exit; }
    $conn->query("DELETE FROM jawaban_siswa WHERE ujian_siswa_id=$ujian_id");
    $stmt=$conn->prepare("UPDATE ujian_siswa SET status='belum',waktu_mulai=NULL,waktu_selesai=NULL,jumlah_benar=0,jumlah_salah=0,nilai=0,jumlah_keluar=0,acak_soal_order=NULL,acak_jawaban_order=NULL WHERE id=?");
    $stmt->bind_param("i",$ujian_id); $stmt->execute();
    echo json_encode(['status'=>'success','message'=>'Ujian berhasil direset']); exit;
}

if ($action === 'tambah_waktu') {
    $ujian_id=(int)($_POST['ujian_id']??0); $menit=(int)($_POST['menit']??0);
    $verif=$conn->prepare("SELECT us.id FROM ujian_siswa us JOIN ruang_ujian ru ON us.ruang_ujian_id=ru.id WHERE us.id=? AND ru.guru_id=?"); $verif->bind_param("ii",$ujian_id,$guru_id); $verif->execute();
    if ($verif->get_result()->num_rows===0) { echo json_encode(['status'=>'error','message'=>'Akses ditolak']); exit; }
    $stmt=$conn->prepare("UPDATE ujian_siswa SET waktu_tambahan=waktu_tambahan+? WHERE id=?"); $stmt->bind_param("ii",$menit,$ujian_id); $stmt->execute();
    echo json_encode(['status'=>'success','message'=>"Waktu +{$menit} menit ditambahkan"]); exit;
}

echo json_encode(['status'=>'error','message'=>'Action tidak dikenali']);
