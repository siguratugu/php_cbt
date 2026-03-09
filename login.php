<?php
session_start();
date_default_timezone_set('Asia/Jakarta');

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['role'];
    if ($role === 'admin') header("Location: /admin/index.php");
    elseif ($role === 'guru') header("Location: /guru/index.php");
    elseif ($role === 'siswa') header("Location: /siswa/index.php");
    exit;
}

require_once __DIR__ . '/config/database.php';

// Handle AJAX login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    $action = $_POST['action'];

    if ($action === 'login_admin') {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            echo json_encode(['status' => 'error', 'message' => 'Email dan password wajib diisi']);
            exit;
        }

        $stmt = $conn->prepare("SELECT * FROM admin WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = 'admin';
            $_SESSION['nama'] = $user['nama'];
            $_SESSION['email'] = $user['email'];
            echo json_encode(['status' => 'success', 'message' => 'Login berhasil', 'redirect' => '/admin/index.php']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Email atau password salah']);
        }
        exit;
    }

    if ($action === 'login_guru') {
        $nik = trim($_POST['nik'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($nik) || empty($password)) {
            echo json_encode(['status' => 'error', 'message' => 'NIK dan password wajib diisi']);
            exit;
        }

        $stmt = $conn->prepare("SELECT * FROM guru WHERE nik = ?");
        $stmt->bind_param("s", $nik);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = 'guru';
            $_SESSION['nama'] = $user['nama'];
            $_SESSION['nik'] = $user['nik'];
            echo json_encode(['status' => 'success', 'message' => 'Login berhasil', 'redirect' => '/guru/index.php']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'NIK atau password salah']);
        }
        exit;
    }

    if ($action === 'login_siswa') {
        $nisn = trim($_POST['nisn'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($nisn) || empty($password)) {
            echo json_encode(['status' => 'error', 'message' => 'NISN dan password wajib diisi']);
            exit;
        }

        $stmt = $conn->prepare("SELECT * FROM siswa WHERE nisn = ?");
        $stmt->bind_param("s", $nisn);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = 'siswa';
            $_SESSION['nama'] = $user['nama'];
            $_SESSION['nisn'] = $user['nisn'];
            $_SESSION['kelas_id'] = $user['kelas_id'];
            echo json_encode(['status' => 'success', 'message' => 'Login berhasil', 'redirect' => '/siswa/index.php']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'NISN atau password salah']);
        }
        exit;
    }

    if ($action === 'login_token') {
        $token = strtoupper(trim($_POST['token'] ?? ''));
        $nisn  = trim($_POST['nisn'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($token) || empty($nisn) || empty($password)) {
            echo json_encode(['status' => 'error', 'message' => 'Token, NISN dan password wajib diisi']);
            exit;
        }

        // Verify siswa credentials first
        $stmt = $conn->prepare("SELECT s.*, k.nama_kelas FROM siswa s LEFT JOIN kelas k ON s.kelas_id = k.id WHERE s.nisn = ?");
        $stmt->bind_param("s", $nisn);
        $stmt->execute();
        $result = $stmt->get_result();
        $siswa = $result->fetch_assoc();

        if (!$siswa || !password_verify($password, $siswa['password'])) {
            echo json_encode(['status' => 'error', 'message' => 'NISN atau password salah']);
            exit;
        }

        // Check token
        $stmt2 = $conn->prepare("SELECT ru.* FROM ruang_ujian ru WHERE ru.token = ? AND NOW() BETWEEN ru.tanggal_mulai AND ru.tanggal_selesai");
        $stmt2->bind_param("s", $token);
        $stmt2->execute();
        $result2 = $stmt2->get_result();
        $ruang = $result2->fetch_assoc();

        if (!$ruang) {
            echo json_encode(['status' => 'error', 'message' => 'Token tidak valid atau ujian belum/sudah berakhir']);
            exit;
        }

        // Check kelas access
        $stmt3 = $conn->prepare("SELECT id FROM ruang_ujian_kelas WHERE ruang_ujian_id = ? AND kelas_id = ?");
        $stmt3->bind_param("is", $ruang['id'], $siswa['kelas_id']);
        $stmt3->execute();
        $result3 = $stmt3->get_result();
        if ($result3->num_rows === 0) {
            echo json_encode(['status' => 'error', 'message' => 'Kelas Anda tidak terdaftar di ruang ujian ini']);
            exit;
        }

        // Login siswa
        $_SESSION['user_id'] = $siswa['id'];
        $_SESSION['role'] = 'siswa';
        $_SESSION['nama'] = $siswa['nama'];
        $_SESSION['nisn'] = $siswa['nisn'];
        $_SESSION['kelas_id'] = $siswa['kelas_id'];

        echo json_encode([
            'status' => 'success',
            'message' => 'Login berhasil',
            'redirect' => '/siswa/ujian.php?ruang_id=' . $ruang['id']
        ]);
        exit;
    }

    if ($action === 'get_exambrowser') {
        $res = $conn->query("SELECT nilai FROM setting WHERE nama_setting = 'exambrowser_mode'");
        $row = $res->fetch_assoc();
        echo json_encode(['status' => 'success', 'mode' => $row ? $row['nilai'] : '0']);
        exit;
    }

    echo json_encode(['status' => 'error', 'message' => 'Action tidak valid']);
    exit;
}

// Get exambrowser mode for page load
$eb_res = $conn->query("SELECT nilai FROM setting WHERE nama_setting = 'exambrowser_mode'");
$eb_row = $eb_res->fetch_assoc();
$exambrowser_mode = $eb_row ? (int)$eb_row['nilai'] : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CBT MTsN 1 Mesuji</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <style>
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .fade-in { animation: fadeIn 0.5s ease forwards; }
        .tab-btn.active { background: #1d4ed8; color: white; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
    </style>
</head>
<body class="min-h-screen bg-blue-700 flex items-center justify-center p-4">
    <div class="w-full max-w-5xl fade-in">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            <!-- LEFT BOX -->
            <div class="bg-white rounded-2xl shadow-2xl p-8 flex flex-col items-center justify-center">
                <img src="https://exam1.unimed.ac.id/landpage/images/icons/mku.gif" alt="Animated" class="w-48 h-48 object-contain mb-4 rounded-xl">
                <h2 class="text-xl font-bold text-gray-800 text-center mb-2">Sistem Ujian Berbasis Komputer</h2>
                <p class="text-gray-500 text-sm text-center mb-6">Platform ujian online terpercaya untuk MTsN 1 Mesuji</p>
                <div class="flex flex-wrap gap-2 justify-center">
                    <span class="flex items-center gap-1 bg-blue-100 text-blue-700 text-xs font-semibold px-3 py-1.5 rounded-full">
                        <i data-lucide="smartphone" class="w-3 h-3"></i> Responsif
                    </span>
                    <span class="flex items-center gap-1 bg-green-100 text-green-700 text-xs font-semibold px-3 py-1.5 rounded-full">
                        <i data-lucide="zap" class="w-3 h-3"></i> Cepat
                    </span>
                    <span class="flex items-center gap-1 bg-purple-100 text-purple-700 text-xs font-semibold px-3 py-1.5 rounded-full">
                        <i data-lucide="smile" class="w-3 h-3"></i> Mudah
                    </span>
                </div>
                <div class="mt-6 text-center">
                    <div class="flex items-center gap-2 text-gray-400 text-xs">
                        <i data-lucide="shield" class="w-3 h-3"></i>
                        <span>Aman &amp; Terpercaya</span>
                    </div>
                </div>
            </div>

            <!-- RIGHT BOX -->
            <div class="bg-white rounded-2xl shadow-2xl p-8">
                <div class="flex flex-col items-center mb-6">
                    <img src="https://e-learning.mtsn1mesuji.sch.id/__statics/img/logo.png" alt="Logo MTsN" class="w-16 h-16 object-contain mb-2" onerror="this.src='https://via.placeholder.com/64x64/1d4ed8/white?text=MTsN'">
                    <h1 class="text-2xl font-bold text-blue-700">CBT MTsN 1 Mesuji</h1>
                    <p class="text-gray-500 text-sm">Masuk ke akun Anda</p>
                </div>

                <!-- Tabs -->
                <div class="flex rounded-xl overflow-hidden border border-gray-200 mb-6">
                    <button class="tab-btn flex-1 py-2 text-sm font-medium transition-colors active" onclick="switchTab('admin')">
                        <i data-lucide="shield" class="w-3 h-3 inline mr-1"></i>Admin
                    </button>
                    <button class="tab-btn flex-1 py-2 text-sm font-medium transition-colors border-l border-gray-200" onclick="switchTab('guru')">
                        <i data-lucide="user-check" class="w-3 h-3 inline mr-1"></i>Guru
                    </button>
                    <button class="tab-btn flex-1 py-2 text-sm font-medium transition-colors border-l border-gray-200" onclick="switchTab('siswa')">
                        <i data-lucide="users" class="w-3 h-3 inline mr-1"></i>Siswa
                    </button>
                </div>

                <!-- Tab Admin -->
                <div class="tab-content active" id="tab-admin">
                    <form onsubmit="doLogin(event,'admin')" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"><i data-lucide="mail" class="w-4 h-4"></i></span>
                                <input type="email" id="admin-email" class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="admin@cbt.com">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"><i data-lucide="lock" class="w-4 h-4"></i></span>
                                <input type="password" id="admin-password" class="w-full pl-10 pr-10 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Password">
                                <button type="button" onclick="togglePass('admin-password',this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </div>
                        <button type="submit" class="w-full bg-blue-700 hover:bg-blue-800 text-white font-semibold py-2.5 rounded-lg transition-colors flex items-center justify-center gap-2">
                            <i data-lucide="log-in" class="w-4 h-4"></i> Login Admin
                        </button>
                    </form>
                </div>

                <!-- Tab Guru -->
                <div class="tab-content" id="tab-guru">
                    <form onsubmit="doLogin(event,'guru')" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">NIK (16 digit)</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"><i data-lucide="credit-card" class="w-4 h-4"></i></span>
                                <input type="text" id="guru-nik" maxlength="16" class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="16 Digit NIK">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"><i data-lucide="lock" class="w-4 h-4"></i></span>
                                <input type="password" id="guru-password" class="w-full pl-10 pr-10 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Password">
                                <button type="button" onclick="togglePass('guru-password',this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </div>
                        <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-2.5 rounded-lg transition-colors flex items-center justify-center gap-2">
                            <i data-lucide="log-in" class="w-4 h-4"></i> Login Guru
                        </button>
                    </form>
                </div>

                <!-- Tab Siswa -->
                <div class="tab-content" id="tab-siswa">
                    <?php if ($exambrowser_mode): ?>
                    <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg flex items-start gap-2">
                        <i data-lucide="alert-triangle" class="w-4 h-4 text-red-500 mt-0.5 flex-shrink-0"></i>
                        <div>
                            <p class="text-red-700 text-xs font-semibold">Mode Exambrowser Aktif</p>
                            <p class="text-red-600 text-xs">Login siswa hanya dapat dilakukan melalui Safe Exam Browser (SEB).</p>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg flex items-start gap-2">
                        <i data-lucide="check-circle" class="w-4 h-4 text-green-500 mt-0.5 flex-shrink-0"></i>
                        <p class="text-green-700 text-xs">Siswa dapat login menggunakan browser biasa.</p>
                    </div>
                    <?php endif; ?>

                    <!-- Normal login -->
                    <div id="siswa-normal-form">
                        <form onsubmit="doLogin(event,'siswa')" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">NISN (10 digit)</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"><i data-lucide="hash" class="w-4 h-4"></i></span>
                                    <input type="text" id="siswa-nisn" maxlength="10" class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="10 Digit NISN">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"><i data-lucide="lock" class="w-4 h-4"></i></span>
                                    <input type="password" id="siswa-password" class="w-full pl-10 pr-10 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Password">
                                    <button type="button" onclick="togglePass('siswa-password',this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                        <i data-lucide="eye" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </div>
                            <button type="submit" <?= $exambrowser_mode ? 'disabled' : '' ?> class="w-full bg-purple-600 hover:bg-purple-700 disabled:bg-gray-400 disabled:cursor-not-allowed text-white font-semibold py-2.5 rounded-lg transition-colors flex items-center justify-center gap-2">
                                <i data-lucide="log-in" class="w-4 h-4"></i> Login Siswa
                            </button>
                        </form>
                        <div class="relative my-4">
                            <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-gray-200"></div></div>
                            <div class="relative flex justify-center"><span class="bg-white px-3 text-xs text-gray-400">atau</span></div>
                        </div>
                        <button onclick="showTokenForm()" class="w-full border-2 border-blue-600 text-blue-600 hover:bg-blue-50 font-semibold py-2.5 rounded-lg transition-colors flex items-center justify-center gap-2 text-sm">
                            <i data-lucide="key" class="w-4 h-4"></i> Login menggunakan TOKEN
                        </button>
                    </div>

                    <!-- Token login -->
                    <div id="siswa-token-form" class="hidden">
                        <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                            <p class="text-blue-700 text-xs font-semibold">Login dengan Token Ujian</p>
                            <p class="text-blue-600 text-xs">Masukkan NISN, password, dan token yang diberikan pengawas.</p>
                        </div>
                        <div class="space-y-3">
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"><i data-lucide="hash" class="w-4 h-4"></i></span>
                                <input type="text" id="token-nisn" maxlength="10" class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg text-sm" placeholder="NISN (10 digit)">
                            </div>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"><i data-lucide="lock" class="w-4 h-4"></i></span>
                                <input type="password" id="token-password" class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg text-sm" placeholder="Password">
                            </div>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"><i data-lucide="key" class="w-4 h-4"></i></span>
                                <input type="text" id="token-input" maxlength="6" class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg text-sm uppercase tracking-widest font-mono" placeholder="TOKEN (6 karakter)" oninput="this.value=this.value.toUpperCase()">
                            </div>
                            <button onclick="doTokenLogin()" class="w-full bg-blue-700 hover:bg-blue-800 text-white font-semibold py-2.5 rounded-lg transition-colors flex items-center justify-center gap-2">
                                <i data-lucide="arrow-right" class="w-4 h-4"></i> Mulai Ujian
                            </button>
                            <button onclick="hideTokenForm()" class="w-full text-gray-500 hover:text-gray-700 text-sm py-1">
                                ← Kembali ke login biasa
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="mt-6 pt-4 border-t border-gray-100 text-center text-xs text-gray-400">
                    &copy; <?= date('Y') ?> | Developer by Asmin Pratama
                </div>
            </div>
        </div>
    </div>

<script>
const EXAMBROWSER_MODE = <?= $exambrowser_mode ? 'true' : 'false' ?>;
const userAgent = navigator.userAgent;
const isSEB = userAgent.includes('SEB');

// Check SEB requirement
if (EXAMBROWSER_MODE && !isSEB) {
    // Disable siswa login buttons
    document.addEventListener('DOMContentLoaded', function() {
        const btn = document.querySelector('#tab-siswa button[type=submit]');
        if (btn) {
            btn.disabled = true;
            btn.classList.add('opacity-50', 'cursor-not-allowed');
        }
    });
}

function switchTab(tab) {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
    document.getElementById('tab-' + tab).classList.add('active');
    event.currentTarget.classList.add('active');
}

function togglePass(id, btn) {
    const inp = document.getElementById(id);
    if (inp.type === 'password') {
        inp.type = 'text';
        btn.innerHTML = '<i data-lucide="eye-off" class="w-4 h-4"></i>';
    } else {
        inp.type = 'password';
        btn.innerHTML = '<i data-lucide="eye" class="w-4 h-4"></i>';
    }
    lucide.createIcons();
}

function showTokenForm() {
    document.getElementById('siswa-normal-form').classList.add('hidden');
    document.getElementById('siswa-token-form').classList.remove('hidden');
}

function hideTokenForm() {
    document.getElementById('siswa-token-form').classList.add('hidden');
    document.getElementById('siswa-normal-form').classList.remove('hidden');
}

function doLogin(e, type) {
    e.preventDefault();

    if (type === 'siswa' && EXAMBROWSER_MODE && !isSEB) {
        Swal.fire({
            icon: 'error',
            title: 'Akses Ditolak',
            text: 'Login siswa hanya dapat dilakukan melalui Safe Exam Browser (SEB).',
            confirmButtonColor: '#1d4ed8'
        });
        return;
    }

    let data = new FormData();
    data.append('action', 'login_' + type);

    if (type === 'admin') {
        data.append('email', document.getElementById('admin-email').value);
        data.append('password', document.getElementById('admin-password').value);
    } else if (type === 'guru') {
        data.append('nik', document.getElementById('guru-nik').value);
        data.append('password', document.getElementById('guru-password').value);
    } else if (type === 'siswa') {
        data.append('nisn', document.getElementById('siswa-nisn').value);
        data.append('password', document.getElementById('siswa-password').value);
    }

    Swal.fire({
        title: 'Memproses...',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    fetch('/login.php', { method: 'POST', body: data })
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: res.message,
                    timer: 1000,
                    showConfirmButton: false
                }).then(() => window.location.href = res.redirect);
            } else {
                Swal.fire({ icon: 'error', title: 'Gagal', text: res.message, confirmButtonColor: '#1d4ed8' });
            }
        })
        .catch(() => Swal.fire({ icon: 'error', title: 'Error', text: 'Terjadi kesalahan jaringan', confirmButtonColor: '#1d4ed8' }));
}

function doTokenLogin() {
    const nisn = document.getElementById('token-nisn').value.trim();
    const password = document.getElementById('token-password').value;
    const token = document.getElementById('token-input').value.trim().toUpperCase();

    if (!nisn || !password || !token) {
        Swal.fire({ icon: 'warning', title: 'Perhatian', text: 'Semua field wajib diisi', confirmButtonColor: '#1d4ed8' });
        return;
    }

    if (EXAMBROWSER_MODE && !isSEB) {
        Swal.fire({ icon: 'error', title: 'Akses Ditolak', text: 'Login hanya dapat dilakukan melalui Safe Exam Browser (SEB).', confirmButtonColor: '#1d4ed8' });
        return;
    }

    Swal.fire({
        title: 'Konfirmasi Ujian',
        html: '<p class="text-gray-600">Apakah kamu sudah siap mengerjakan ujian?</p><p class="text-gray-500 text-sm mt-1">Jika siap klik tombol <strong>Kerjakan</strong></p>',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#1d4ed8',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Kerjakan',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (!result.isConfirmed) return;

        let data = new FormData();
        data.append('action', 'login_token');
        data.append('nisn', nisn);
        data.append('password', password);
        data.append('token', token);

        Swal.fire({ title: 'Memproses...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

        fetch('/login.php', { method: 'POST', body: data })
            .then(r => r.json())
            .then(res => {
                if (res.status === 'success') {
                    Swal.fire({ icon: 'success', title: 'Berhasil!', text: 'Selamat mengerjakan!', timer: 1000, showConfirmButton: false })
                        .then(() => window.location.href = res.redirect);
                } else {
                    Swal.fire({ icon: 'error', title: 'Gagal', text: res.message, confirmButtonColor: '#1d4ed8' });
                }
            })
            .catch(() => Swal.fire({ icon: 'error', title: 'Error', text: 'Terjadi kesalahan jaringan', confirmButtonColor: '#1d4ed8' }));
    });
}

lucide.createIcons();
</script>
</body>
</html>
