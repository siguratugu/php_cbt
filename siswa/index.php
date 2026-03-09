<?php
session_start();
date_default_timezone_set('Asia/Jakarta');
require_once __DIR__ . '/../includes/auth.php';
cekLogin('siswa');
require_once __DIR__ . '/../config/database.php';

$siswa_id = (int)$_SESSION['user_id'];
$kelas_id = $_SESSION['kelas_id'] ?? '';

// Stats
$stmt = $conn->prepare("SELECT COUNT(*) as c FROM ruang_ujian ru JOIN ruang_ujian_kelas ruk ON ru.id=ruk.ruang_ujian_id WHERE ruk.kelas_id=? AND NOW() BETWEEN ru.tanggal_mulai AND ru.tanggal_selesai");
$stmt->bind_param("s", $kelas_id); $stmt->execute();
$total_ruang = $stmt->get_result()->fetch_assoc()['c'];

$stmt2 = $conn->prepare("SELECT COUNT(*) as c FROM ruang_ujian ru JOIN ruang_ujian_kelas ruk ON ru.id=ruk.ruang_ujian_id LEFT JOIN ujian_siswa us ON ru.id=us.ruang_ujian_id AND us.siswa_id=? WHERE ruk.kelas_id=? AND (us.id IS NULL OR us.status='belum')");
$stmt2->bind_param("is", $siswa_id, $kelas_id); $stmt2->execute();
$belum = $stmt2->get_result()->fetch_assoc()['c'];

$stmt3 = $conn->prepare("SELECT COUNT(*) as c FROM ujian_siswa WHERE siswa_id=? AND status='sedang'");
$stmt3->bind_param("i", $siswa_id); $stmt3->execute();
$sedang = $stmt3->get_result()->fetch_assoc()['c'];

$stmt4 = $conn->prepare("SELECT COUNT(*) as c FROM ujian_siswa WHERE siswa_id=? AND status='selesai'");
$stmt4->bind_param("i", $siswa_id); $stmt4->execute();
$selesai = $stmt4->get_result()->fetch_assoc()['c'];

// Announcements
$stmt5 = $conn->prepare("SELECT p.judul, p.isi, p.created_at FROM pengumuman p JOIN pengumuman_kelas pk ON p.id=pk.pengumuman_id WHERE pk.kelas_id=? ORDER BY p.created_at DESC LIMIT 5");
$stmt5->bind_param("s", $kelas_id); $stmt5->execute();
$pengumuman_list = $stmt5->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Siswa - CBT MTsN 1 Mesuji</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="bg-gray-100 min-h-screen">
<div class="flex h-screen overflow-hidden">
    <?php include __DIR__ . '/../includes/sidebar_siswa.php'; ?>
    <div class="flex-1 ml-64 overflow-y-auto pb-16">
        <div class="bg-white shadow-sm px-6 py-4 flex items-center justify-between sticky top-0 z-10">
            <div>
                <h1 class="text-xl font-bold text-gray-800">Dashboard</h1>
                <p class="text-gray-500 text-sm">Selamat datang, <?= htmlspecialchars($_SESSION['nama']) ?>!</p>
            </div>
            <span class="bg-purple-100 text-purple-700 text-xs font-semibold px-3 py-1 rounded-full">Siswa</span>
        </div>
        <div class="p-6">
            <!-- Stats -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5 mb-6">
                <div class="bg-white rounded-xl shadow-sm p-6 flex items-center gap-4">
                    <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                        <i data-lucide="monitor" class="w-6 h-6 text-blue-600"></i>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">Ruang Ujian</p>
                        <p class="text-2xl font-bold text-gray-800"><?= $total_ruang ?></p>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-6 flex items-center gap-4">
                    <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center">
                        <i data-lucide="clock" class="w-6 h-6 text-red-600"></i>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">Belum Dikerjakan</p>
                        <p class="text-2xl font-bold text-gray-800"><?= $belum ?></p>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-6 flex items-center gap-4">
                    <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center">
                        <i data-lucide="pen-tool" class="w-6 h-6 text-orange-600"></i>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">Sedang Dikerjakan</p>
                        <p class="text-2xl font-bold text-gray-800"><?= $sedang ?></p>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-6 flex items-center gap-4">
                    <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                        <i data-lucide="check-circle" class="w-6 h-6 text-green-600"></i>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">Selesai</p>
                        <p class="text-2xl font-bold text-gray-800"><?= $selesai ?></p>
                    </div>
                </div>
            </div>

            <!-- Announcements -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                    <i data-lucide="megaphone" class="w-5 h-5 text-purple-600"></i> Pengumuman
                </h2>
                <?php if (empty($pengumuman_list)): ?>
                <p class="text-gray-400 text-sm text-center py-6">Tidak ada pengumuman saat ini.</p>
                <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($pengumuman_list as $peng): ?>
                    <div class="border border-gray-200 rounded-lg p-4">
                        <?php if (!empty($peng['judul'])): ?>
                        <h3 class="font-semibold text-gray-800 mb-1"><?= htmlspecialchars($peng['judul']) ?></h3>
                        <?php endif; ?>
                        <div class="text-sm text-gray-600 prose max-w-none"><?= $peng['isi'] ?></div>
                        <p class="text-xs text-gray-400 mt-2"><?= date('d/m/Y H:i', strtotime($peng['created_at'])) ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
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
