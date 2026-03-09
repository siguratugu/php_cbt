<?php
session_start();
require_once __DIR__ . '/../../includes/auth.php';
cekLogin('guru');
require_once __DIR__ . '/../../config/database.php';
header('Content-Type: application/json');

$action  = $_REQUEST['action'] ?? '';
$guru_id = (int)$_SESSION['user_id'];

if ($action === 'get_all') {
    $page    = max(1,(int)($_GET['page']??1));
    $perPage = $_GET['per_page']??'10';
    $stmt    = $conn->prepare("SELECT COUNT(*) as c FROM bank_soal WHERE guru_id=?");
    $stmt->bind_param("i",$guru_id); $stmt->execute();
    $total   = $stmt->get_result()->fetch_assoc()['c'];
    if ($perPage==='all') {
        $stmt2 = $conn->prepare("SELECT bs.*, m.nama_mapel FROM bank_soal bs LEFT JOIN mapel m ON bs.mapel_id=m.id WHERE bs.guru_id=? ORDER BY bs.created_at DESC");
        $stmt2->bind_param("i",$guru_id); $stmt2->execute();
        $data=[]; $r=$stmt2->get_result(); while($row=$r->fetch_assoc()) $data[]=$row;
        echo json_encode(['status'=>'success','data'=>$data,'total'=>$total,'per_page'=>'all','page'=>1]);
    } else {
        $perPage=(int)$perPage; $offset=($page-1)*$perPage;
        $stmt2=$conn->prepare("SELECT bs.*, m.nama_mapel FROM bank_soal bs LEFT JOIN mapel m ON bs.mapel_id=m.id WHERE bs.guru_id=? ORDER BY bs.created_at DESC LIMIT ? OFFSET ?");
        $stmt2->bind_param("iii",$guru_id,$perPage,$offset); $stmt2->execute();
        $data=[]; $r=$stmt2->get_result(); while($row=$r->fetch_assoc()) $data[]=$row;
        echo json_encode(['status'=>'success','data'=>$data,'total'=>$total,'per_page'=>$perPage,'page'=>$page]);
    }
    exit;
}

if ($action==='get_mapel_list') {
    $stmt=$conn->prepare("SELECT DISTINCT m.id, m.nama_mapel FROM relasi_guru rg JOIN mapel m ON rg.mapel_id=m.id WHERE rg.guru_id=?");
    $stmt->bind_param("i",$guru_id); $stmt->execute();
    $data=[]; $r=$stmt->get_result(); while($row=$r->fetch_assoc()) $data[]=$row;
    echo json_encode(['status'=>'success','data'=>$data]); exit;
}

if ($action==='add') {
    $nama=trim($_POST['nama_soal']??''); $mapel_id=trim($_POST['mapel_id']??'');
    $waktu=(int)($_POST['waktu_mengerjakan']??0);
    $b_pg=(float)($_POST['bobot_pg']??0); $b_esai=(float)($_POST['bobot_esai']??0);
    $b_menj=(float)($_POST['bobot_menjodohkan']??0); $b_bs=(float)($_POST['bobot_benar_salah']??0);
    $total=$b_pg+$b_esai+$b_menj+$b_bs;
    if(empty($nama)){echo json_encode(['status'=>'error','message'=>'Nama soal wajib diisi']);exit;}
    if(round($total,2)!=100.00&&$total!=0){echo json_encode(['status'=>'error','message'=>'Total bobot harus 100%']);exit;}
    $mapel_id=empty($mapel_id)?null:$mapel_id;
    $stmt=$conn->prepare("INSERT INTO bank_soal(guru_id,nama_soal,mapel_id,waktu_mengerjakan,bobot_pg,bobot_esai,bobot_menjodohkan,bobot_benar_salah) VALUES(?,?,?,?,?,?,?,?)");
    $stmt->bind_param("ississdd",$guru_id,$nama,$mapel_id,$waktu,$b_pg,$b_esai,$b_menj,$b_bs);
    if($stmt->execute()) echo json_encode(['status'=>'success','message'=>'Bank soal berhasil ditambahkan','id'=>$conn->insert_id]);
    else echo json_encode(['status'=>'error','message'=>'Gagal menambahkan bank soal']);
    exit;
}

if ($action==='edit') {
    $id=(int)($_POST['id']??0); $nama=trim($_POST['nama_soal']??''); $mapel_id=trim($_POST['mapel_id']??'')?:null;
    $waktu=(int)($_POST['waktu_mengerjakan']??0);
    $b_pg=(float)($_POST['bobot_pg']??0); $b_esai=(float)($_POST['bobot_esai']??0);
    $b_menj=(float)($_POST['bobot_menjodohkan']??0); $b_bs=(float)($_POST['bobot_benar_salah']??0);
    $stmt=$conn->prepare("UPDATE bank_soal SET nama_soal=?,mapel_id=?,waktu_mengerjakan=?,bobot_pg=?,bobot_esai=?,bobot_menjodohkan=?,bobot_benar_salah=? WHERE id=? AND guru_id=?");
    $stmt->bind_param("ssidddddii",$nama,$mapel_id,$waktu,$b_pg,$b_esai,$b_menj,$b_bs,$id,$guru_id);
    echo $stmt->execute()?json_encode(['status'=>'success','message'=>'Berhasil diupdate']):json_encode(['status'=>'error','message'=>'Gagal update']);
    exit;
}

if ($action==='delete') {
    $id=(int)($_POST['id']??0);
    $stmt=$conn->prepare("DELETE FROM bank_soal WHERE id=? AND guru_id=?");
    $stmt->bind_param("ii",$id,$guru_id);
    echo $stmt->execute()?json_encode(['status'=>'success','message'=>'Berhasil dihapus']):json_encode(['status'=>'error','message'=>'Gagal hapus']);
    exit;
}

if ($action==='delete_multiple') {
    $ids=$_POST['ids']??[];
    if(empty($ids)){echo json_encode(['status'=>'error','message'=>'Pilih minimal satu']);exit;}
    $pls=implode(',',array_fill(0,count($ids),'?'));
    $types='i'.str_repeat('i',count($ids));
    $params=array_merge([$guru_id],$ids);
    $stmt=$conn->prepare("DELETE FROM bank_soal WHERE guru_id=? AND id IN($pls)");
    $stmt->bind_param($types,...$params);
    echo $stmt->execute()?json_encode(['status'=>'success','message'=>count($ids).' bank soal dihapus']):json_encode(['status'=>'error','message'=>'Gagal hapus']);
    exit;
}

if ($action==='get_single') {
    $id=(int)($_GET['id']??0);
    $stmt=$conn->prepare("SELECT bs.*,m.nama_mapel FROM bank_soal bs LEFT JOIN mapel m ON bs.mapel_id=m.id WHERE bs.id=? AND bs.guru_id=?");
    $stmt->bind_param("ii",$id,$guru_id); $stmt->execute();
    $row=$stmt->get_result()->fetch_assoc();
    echo $row?json_encode(['status'=>'success','data'=>$row]):json_encode(['status'=>'error','message'=>'Tidak ditemukan']);
    exit;
}

echo json_encode(['status'=>'error','message'=>'Action tidak dikenali']);
