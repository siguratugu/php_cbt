<?php
session_start();
date_default_timezone_set('Asia/Jakarta');
require_once __DIR__ . '/../includes/auth.php';
cekLogin('admin');
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    if ($_POST['action'] === 'ubah_password') {
        $password_lama = $_POST['password_lama'] ?? '';
        $password_baru = $_POST['password_baru'] ?? '';
        $konfirmasi    = $_POST['konfirmasi'] ?? '';
        $admin_id      = $_SESSION['user_id'];

        if (empty($password_lama) || empty($password_baru) || empty($konfirmasi)) {
            echo json_encode(['status' => 'error', 'message' => 'Semua field wajib diisi']);
            exit;
        }
        if ($password_baru !== $konfirmasi) {
            echo json_encode(['status' => 'error', 'message' => 'Konfirmasi password tidak cocok']);
            exit;
        }
        if (strlen($password_baru) < 6) {
            echo json_encode(['status' => 'error', 'message' => 'Password minimal 6 karakter']);
            exit;
        }

        $stmt = $conn->prepare("SELECT password FROM admin WHERE id = ?");
        $stmt->bind_param("s", $admin_id);
        $stmt->execute();
        $admin = $stmt->get_result()->fetch_assoc();

        if (!$admin || !password_verify($password_lama, $admin['password'])) {
            echo json_encode(['status' => 'error', 'message' => 'Password lama tidak benar']);
            exit;
        }

        $hash = password_hash($password_baru, PASSWORD_BCRYPT);
        $upd  = $conn->prepare("UPDATE admin SET password = ? WHERE id = ?");
        $upd->bind_param("ss", $hash, $admin_id);
        if ($upd->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Password berhasil diubah']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal mengubah password']);
        }
        exit;
    }
    echo json_encode(['status' => 'error', 'message' => 'Action tidak valid']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ubah Password - CBT MTsN 1 Mesuji</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="bg-gray-100 min-h-screen">
<div class="flex h-screen overflow-hidden">
    <?php include __DIR__ . '/../includes/sidebar_admin.php'; ?>
    <div class="flex-1 ml-64 overflow-y-auto pb-16">
        <div class="bg-white shadow-sm px-6 py-4 flex items-center justify-between sticky top-0 z-10">
            <div>
                <h1 class="text-xl font-bold text-gray-800">Ubah Password</h1>
                <p class="text-gray-500 text-sm">Ganti password akun administrator Anda</p>
            </div>
        </div>
        <div class="p-6 max-w-md">
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Password Lama <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <input type="password" id="password-lama" placeholder="Password lama"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 pr-10">
                            <button type="button" onclick="togglePassword('password-lama', this)" class="absolute right-3 top-2.5 text-gray-400 hover:text-gray-600">
                                <i data-lucide="eye" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Password Baru <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <input type="password" id="password-baru" placeholder="Minimal 6 karakter"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 pr-10">
                            <button type="button" onclick="togglePassword('password-baru', this)" class="absolute right-3 top-2.5 text-gray-400 hover:text-gray-600">
                                <i data-lucide="eye" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Konfirmasi Password Baru <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <input type="password" id="konfirmasi" placeholder="Ulangi password baru"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 pr-10">
                            <button type="button" onclick="togglePassword('konfirmasi', this)" class="absolute right-3 top-2.5 text-gray-400 hover:text-gray-600">
                                <i data-lucide="eye" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>
                    <button onclick="ubahPassword()"
                        class="w-full bg-blue-700 hover:bg-blue-800 text-white font-semibold py-2.5 rounded-lg transition-colors flex items-center justify-center gap-2">
                        <i data-lucide="key" class="w-4 h-4"></i> Ubah Password
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
<footer class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 py-2 text-center text-xs text-gray-500 z-10">
    &copy; <?= date('Y') ?> | Develop by Asmin Pratama
</footer>
<script>
function togglePassword(id, btn) {
    const input = document.getElementById(id);
    const isPass = input.type === 'password';
    input.type = isPass ? 'text' : 'password';
    btn.innerHTML = isPass ? '<i data-lucide="eye-off" class="w-4 h-4"></i>' : '<i data-lucide="eye" class="w-4 h-4"></i>';
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function ubahPassword() {
    const fd = new FormData();
    fd.append('action', 'ubah_password');
    fd.append('password_lama', document.getElementById('password-lama').value);
    fd.append('password_baru', document.getElementById('password-baru').value);
    fd.append('konfirmasi', document.getElementById('konfirmasi').value);

    fetch('/admin/ubah_password.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                Swal.fire({ icon: 'success', title: 'Berhasil', text: res.message });
                document.getElementById('password-lama').value = '';
                document.getElementById('password-baru').value = '';
                document.getElementById('konfirmasi').value = '';
            } else {
                Swal.fire({ icon: 'error', title: 'Gagal', text: res.message });
            }
        });
}

lucide.createIcons();
</script>
</body>
</html>
