<?php
session_start();
date_default_timezone_set('Asia/Jakarta');
require_once __DIR__ . '/../includes/auth.php';
cekLogin('admin');
require_once __DIR__ . '/../config/database.php';

$total_siswa   = $conn->query("SELECT COUNT(*) as c FROM siswa")->fetch_assoc()['c'];
$total_guru    = $conn->query("SELECT COUNT(*) as c FROM guru")->fetch_assoc()['c'];
$total_kelas   = $conn->query("SELECT COUNT(*) as c FROM kelas")->fetch_assoc()['c'];
$total_mapel   = $conn->query("SELECT COUNT(*) as c FROM mapel")->fetch_assoc()['c'];
$total_bank    = $conn->query("SELECT COUNT(*) as c FROM bank_soal")->fetch_assoc()['c'];
$total_ruang   = $conn->query("SELECT COUNT(*) as c FROM ruang_ujian")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - CBT MTsN 1 Mesuji</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="bg-gray-100 min-h-screen">
<div class="flex h-screen overflow-hidden">
    <?php include __DIR__ . '/../includes/sidebar_admin.php'; ?>

    <!-- Main Content -->
    <div class="flex-1 ml-64 overflow-y-auto pb-16">
        <!-- Top Bar -->
        <div class="bg-white shadow-sm px-6 py-4 flex items-center justify-between sticky top-0 z-10">
            <div>
                <h1 class="text-xl font-bold text-gray-800">Dashboard</h1>
                <p class="text-gray-500 text-sm">Selamat datang, <?= htmlspecialchars($_SESSION['nama']) ?>!</p>
            </div>
            <div class="flex items-center gap-3">
                <span class="bg-blue-100 text-blue-700 text-xs font-semibold px-3 py-1 rounded-full">Administrator</span>
                <a href="/logout.php" onclick="return confirmLogout()" class="flex items-center gap-1 text-red-500 hover:text-red-700 text-sm">
                    <i data-lucide="log-out" class="w-4 h-4"></i>
                </a>
            </div>
        </div>

        <div class="p-6">
            <!-- Stats Row 1 -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-5">
                <div class="bg-white rounded-xl shadow-sm p-6 flex items-center gap-4 hover:shadow-md transition-shadow">
                    <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                        <i data-lucide="users" class="w-6 h-6 text-blue-600"></i>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">Total Siswa</p>
                        <p class="text-2xl font-bold text-gray-800"><?= number_format($total_siswa) ?></p>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-6 flex items-center gap-4 hover:shadow-md transition-shadow">
                    <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                        <i data-lucide="user-check" class="w-6 h-6 text-green-600"></i>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">Total Guru</p>
                        <p class="text-2xl font-bold text-gray-800"><?= number_format($total_guru) ?></p>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-6 flex items-center gap-4 hover:shadow-md transition-shadow">
                    <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center">
                        <i data-lucide="school" class="w-6 h-6 text-purple-600"></i>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">Total Kelas</p>
                        <p class="text-2xl font-bold text-gray-800"><?= number_format($total_kelas) ?></p>
                    </div>
                </div>
            </div>

            <!-- Stats Row 2 -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-6">
                <div class="bg-white rounded-xl shadow-sm p-6 flex items-center gap-4 hover:shadow-md transition-shadow">
                    <div class="w-12 h-12 bg-yellow-100 rounded-xl flex items-center justify-center">
                        <i data-lucide="book-open" class="w-6 h-6 text-yellow-600"></i>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">Total Mapel</p>
                        <p class="text-2xl font-bold text-gray-800"><?= number_format($total_mapel) ?></p>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-6 flex items-center gap-4 hover:shadow-md transition-shadow">
                    <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center">
                        <i data-lucide="database" class="w-6 h-6 text-red-600"></i>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">Total Bank Soal</p>
                        <p class="text-2xl font-bold text-gray-800"><?= number_format($total_bank) ?></p>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-6 flex items-center gap-4 hover:shadow-md transition-shadow">
                    <div class="w-12 h-12 bg-indigo-100 rounded-xl flex items-center justify-center">
                        <i data-lucide="monitor" class="w-6 h-6 text-indigo-600"></i>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">Total Ruang Ujian</p>
                        <p class="text-2xl font-bold text-gray-800"><?= number_format($total_ruang) ?></p>
                    </div>
                </div>
            </div>

            <!-- Quick Nav -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Navigasi Cepat</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <a href="/admin/bank_soal.php" class="flex flex-col items-center gap-2 p-4 bg-blue-50 hover:bg-blue-100 rounded-xl transition-colors text-blue-700 text-sm font-medium">
                        <i data-lucide="database" class="w-8 h-8"></i> Bank Soal
                    </a>
                    <a href="/admin/ruang_ujian.php" class="flex flex-col items-center gap-2 p-4 bg-green-50 hover:bg-green-100 rounded-xl transition-colors text-green-700 text-sm font-medium">
                        <i data-lucide="monitor" class="w-8 h-8"></i> Ruang Ujian
                    </a>
                    <a href="/admin/exambrowser.php" class="flex flex-col items-center gap-2 p-4 bg-yellow-50 hover:bg-yellow-100 rounded-xl transition-colors text-yellow-700 text-sm font-medium">
                        <i data-lucide="shield-check" class="w-8 h-8"></i> Exambrowser
                    </a>
                    <a href="/admin/pengumuman.php" class="flex flex-col items-center gap-2 p-4 bg-purple-50 hover:bg-purple-100 rounded-xl transition-colors text-purple-700 text-sm font-medium">
                        <i data-lucide="megaphone" class="w-8 h-8"></i> Pengumuman
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<footer class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 py-2 text-center text-xs text-gray-500 z-10">
    &copy; <?= date('Y') ?> | Develop by Asmin Pratama
</footer>

<script>lucide.createIcons();</script>
</body>
</html>
