<?php
session_start();
require_once __DIR__ . '/../../includes/auth.php';
cekLogin('admin');
require_once __DIR__ . '/../../config/database.php';
header('Content-Type: application/json');

$action = $_REQUEST['action'] ?? '';

// ===================== GET_STATUS =====================
if ($action === 'get_status') {
    $row = $conn->query("SELECT nilai FROM setting WHERE nama_setting='exambrowser_mode'")->fetch_assoc();
    $mode = $row ? $row['nilai'] : '0';
    echo json_encode(['status' => 'success', 'mode' => $mode]);
    exit;
}

// ===================== TOGGLE_MODE =====================
if ($action === 'toggle_mode') {
    $newMode = (int)($_POST['new_mode'] ?? 0);
    $newMode = $newMode ? 1 : 0;

    $stmt = $conn->prepare("UPDATE setting SET nilai = ? WHERE nama_setting = 'exambrowser_mode'");
    $stmt->bind_param("s", $newMode);

    if ($stmt->execute()) {
        $message = $newMode
            ? 'Mode Exambrowser aktif. Siswa hanya bisa login via SEB.'
            : 'Mode Normal aktif. Siswa bisa login via browser biasa.';
        echo json_encode(['status' => 'success', 'mode' => $newMode, 'message' => $message]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal mengubah pengaturan']);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Action tidak dikenali']);
