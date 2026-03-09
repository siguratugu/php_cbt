<?php
$current_page = basename($_SERVER['PHP_SELF']);
function isActiveSiswa($pages) {
    global $current_page;
    return in_array($current_page, (array)$pages) ? 'bg-purple-700 text-white' : 'text-gray-700 hover:bg-purple-50';
}
?>
<div class="w-64 bg-white shadow-lg flex flex-col h-screen fixed top-0 left-0 z-20 overflow-y-auto" id="sidebar">
    <div class="flex items-center gap-3 px-4 py-4 border-b border-gray-200 bg-purple-700">
        <img src="https://e-learning.mtsn1mesuji.sch.id/__statics/img/logo.png" alt="Logo" class="w-10 h-10 object-contain bg-white rounded-full p-1">
        <div>
            <div class="text-white font-bold text-sm leading-tight">CBT MTsN 1</div>
            <div class="text-purple-200 text-xs">Mesuji - Siswa</div>
        </div>
    </div>
    <nav class="flex-1 px-3 py-4 space-y-1">
        <a href="/siswa/index.php" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors <?= isActiveSiswa('index.php') ?>">
            <i data-lucide="layout-dashboard" class="w-4 h-4"></i> Dashboard
        </a>
        <a href="/siswa/ruang_ujian.php" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors <?= isActiveSiswa('ruang_ujian.php') ?>">
            <i data-lucide="pen-tool" class="w-4 h-4"></i> Ruang Ujian
        </a>
        <div class="pt-4 border-t border-gray-200 mt-4">
            <a href="/logout.php" onclick="return confirmLogoutSiswa()" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-red-600 hover:bg-red-50 transition-colors">
                <i data-lucide="log-out" class="w-4 h-4"></i> Logout
            </a>
        </div>
    </nav>
</div>
<script>
function confirmLogoutSiswa() {
    Swal.fire({
        title: 'Konfirmasi Logout',
        text: 'Apakah Anda yakin ingin keluar?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, Logout',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) window.location.href = '/logout.php';
    });
    return false;
}
</script>
