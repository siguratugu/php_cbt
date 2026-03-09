<?php
session_start();
date_default_timezone_set('Asia/Jakarta');
require_once __DIR__ . '/../includes/auth.php';
cekLogin('guru');
require_once __DIR__ . '/../config/database.php';

$guru_id = $_SESSION['user_id'];

$jml_kelas  = $conn->query("SELECT COUNT(DISTINCT kelas_id) as c FROM relasi_guru WHERE guru_id=$guru_id")->fetch_assoc()['c'];
$jml_mapel  = $conn->query("SELECT COUNT(DISTINCT mapel_id) as c FROM relasi_guru WHERE guru_id=$guru_id")->fetch_assoc()['c'];
$stmt = $conn->prepare("SELECT COUNT(*) as c FROM bank_soal WHERE guru_id=?"); $stmt->bind_param("i",$guru_id); $stmt->execute();
$jml_bank   = $stmt->get_result()->fetch_assoc()['c'];
$stmt2 = $conn->prepare("SELECT COUNT(*) as c FROM ruang_ujian WHERE guru_id=?"); $stmt2->bind_param("i",$guru_id); $stmt2->execute();
$jml_ruang  = $stmt2->get_result()->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Guru - CBT MTsN 1 Mesuji</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="bg-gray-100 min-h-screen">
<div class="flex h-screen overflow-hidden">
    <?php include __DIR__ . '/../includes/sidebar_guru.php'; ?>
    <div class="flex-1 ml-64 overflow-y-auto pb-16">
        <div class="bg-white shadow-sm px-6 py-4 flex items-center justify-between sticky top-0 z-10">
            <div><h1 class="text-xl font-bold text-gray-800">Dashboard</h1><p class="text-gray-500 text-sm">Selamat datang, <?= htmlspecialchars($_SESSION['nama']) ?>!</p></div>
            <span class="bg-green-100 text-green-700 text-xs font-semibold px-3 py-1 rounded-full">Guru</span>
        </div>
        <div class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
            <div class="bg-white rounded-xl shadow-sm p-6 flex items-center gap-4">
                <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center"><i data-lucide="school" class="w-6 h-6 text-blue-600"></i></div>
                <div><p class="text-gray-500 text-sm">Jumlah Kelas</p><p class="text-2xl font-bold text-gray-800"><?= $jml_kelas ?></p></div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-6 flex items-center gap-4">
                <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center"><i data-lucide="book-open" class="w-6 h-6 text-green-600"></i></div>
                <div><p class="text-gray-500 text-sm">Jumlah Mapel</p><p class="text-2xl font-bold text-gray-800"><?= $jml_mapel ?></p></div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-6 flex items-center gap-4">
                <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center"><i data-lucide="database" class="w-6 h-6 text-purple-600"></i></div>
                <div><p class="text-gray-500 text-sm">Bank Soal</p><p class="text-2xl font-bold text-gray-800"><?= $jml_bank ?></p></div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-6 flex items-center gap-4">
                <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center"><i data-lucide="monitor" class="w-6 h-6 text-orange-600"></i></div>
                <div><p class="text-gray-500 text-sm">Ruang Ujian</p><p class="text-2xl font-bold text-gray-800"><?= $jml_ruang ?></p></div>
            </div>
        </div>
    </div>
</div>
<footer class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 py-2 text-center text-xs text-gray-500 z-10">&copy; <?= date('Y') ?> | Develop by Asmin Pratama</footer>
<script>lucide.createIcons();</script>
</body></html>
