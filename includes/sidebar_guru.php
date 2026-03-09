<?php
$current_page = basename($_SERVER['PHP_SELF']);
function isActiveGuru($pages) {
    global $current_page;
    return in_array($current_page, (array)$pages) ? 'bg-green-700 text-white' : 'text-gray-700 hover:bg-green-50';
}
?>
<div class="w-64 bg-white shadow-lg flex flex-col h-screen fixed top-0 left-0 z-20 overflow-y-auto" id="sidebar">
    <div class="flex items-center gap-3 px-4 py-4 border-b border-gray-200 bg-green-700">
        <img src="https://kemenag.go.id/storage/shares/m/n/ot/mnot_logo_kemenag-1673249174.png" alt="Logo" class="w-10 h-10 object-contain bg-white rounded-full p-1">
        <div>
            <div class="text-white font-bold text-sm leading-tight">CBT MTsN 1</div>
            <div class="text-green-200 text-xs">Mesuji - Guru</div>
        </div>
    </div>
    <nav class="flex-1 px-3 py-4 space-y-1">
        <a href="/guru/index.php" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors <?= isActiveGuru('index.php') ?>">
            <i data-lucide="layout-dashboard" class="w-4 h-4"></i> Dashboard
        </a>
        <a href="/guru/bank_soal.php" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors <?= isActiveGuru('bank_soal.php') ?>">
            <i data-lucide="database" class="w-4 h-4"></i> Bank Soal
        </a>
        <a href="/guru/ruang_ujian.php" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors <?= isActiveGuru('ruang_ujian.php') ?>">
            <i data-lucide="monitor" class="w-4 h-4"></i> Ruang Ujian
        </a>
        <div class="pt-4 border-t border-gray-200 mt-4">
            <a href="/logout.php" onclick="return confirmLogoutGuru()" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-red-600 hover:bg-red-50 transition-colors">
                <i data-lucide="log-out" class="w-4 h-4"></i> Logout
            </a>
        </div>
    </nav>
</div>
<script>
function confirmLogoutGuru() {
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
