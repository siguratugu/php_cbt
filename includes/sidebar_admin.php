<?php
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir  = basename(dirname($_SERVER['PHP_SELF']));
function isActive($pages) {
    global $current_page;
    return in_array($current_page, (array)$pages) ? 'bg-blue-700 text-white' : 'text-gray-700 hover:bg-blue-50';
}
?>
<div class="w-64 bg-white shadow-lg flex flex-col h-screen fixed top-0 left-0 z-20 overflow-y-auto" id="sidebar">
    <!-- Logo -->
    <div class="flex items-center gap-3 px-4 py-4 border-b border-gray-200 bg-blue-700">
        <img src="https://kemenag.go.id/storage/shares/m/n/ot/mnot_logo_kemenag-1673249174.png" alt="Logo" class="w-10 h-10 object-contain bg-white rounded-full p-1">
        <div>
            <div class="text-white font-bold text-sm leading-tight">CBT MTsN 1</div>
            <div class="text-blue-200 text-xs">Mesuji</div>
        </div>
    </div>
    <!-- Menu -->
    <nav class="flex-1 px-3 py-4 space-y-1">
        <!-- Dashboard -->
        <a href="/admin/index.php" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors <?= isActive('index.php') ?>">
            <i data-lucide="layout-dashboard" class="w-4 h-4"></i> Dashboard
        </a>

        <!-- Master Data -->
        <div class="pt-2 pb-1">
            <p class="text-xs font-semibold text-gray-400 uppercase px-3">Master Data</p>
        </div>
        <a href="/admin/kelas.php" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors <?= isActive('kelas.php') ?>">
            <i data-lucide="school" class="w-4 h-4"></i> Kelas
        </a>
        <a href="/admin/mapel.php" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors <?= isActive('mapel.php') ?>">
            <i data-lucide="book-open" class="w-4 h-4"></i> Mapel
        </a>
        <a href="/admin/relasi.php" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors <?= isActive('relasi.php') ?>">
            <i data-lucide="link" class="w-4 h-4"></i> Relasi
        </a>
        <a href="/admin/guru.php" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors <?= isActive('guru.php') ?>">
            <i data-lucide="user-check" class="w-4 h-4"></i> Data Guru
        </a>
        <a href="/admin/siswa.php" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors <?= isActive('siswa.php') ?>">
            <i data-lucide="users" class="w-4 h-4"></i> Data Siswa
        </a>

        <!-- Ujian CBT -->
        <div class="pt-2 pb-1">
            <p class="text-xs font-semibold text-gray-400 uppercase px-3">Ujian CBT</p>
        </div>
        <a href="/admin/bank_soal.php" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors <?= isActive('bank_soal.php') ?>">
            <i data-lucide="database" class="w-4 h-4"></i> Bank Soal
        </a>
        <a href="/admin/ruang_ujian.php" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors <?= isActive('ruang_ujian.php') ?>">
            <i data-lucide="monitor" class="w-4 h-4"></i> Ruang Ujian
        </a>
        <a href="/admin/exambrowser.php" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors <?= isActive('exambrowser.php') ?>">
            <i data-lucide="shield-check" class="w-4 h-4"></i> Exambrowser
        </a>

        <!-- Pengaturan -->
        <div class="pt-2 pb-1">
            <p class="text-xs font-semibold text-gray-400 uppercase px-3">Pengaturan</p>
        </div>
        <a href="/admin/administrator.php" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors <?= isActive('administrator.php') ?>">
            <i data-lucide="shield" class="w-4 h-4"></i> Administrator
        </a>
        <a href="/admin/pengumuman.php" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors <?= isActive('pengumuman.php') ?>">
            <i data-lucide="megaphone" class="w-4 h-4"></i> Pengumuman
        </a>

        <!-- Logout -->
        <div class="pt-4 border-t border-gray-200 mt-4">
            <a href="/logout.php" onclick="return confirmLogout()" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-red-600 hover:bg-red-50 transition-colors">
                <i data-lucide="log-out" class="w-4 h-4"></i> Logout
            </a>
        </div>
    </nav>
</div>

<script>
function confirmLogout() {
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
