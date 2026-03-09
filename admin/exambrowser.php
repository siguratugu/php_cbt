<?php
session_start();
date_default_timezone_set('Asia/Jakarta');
require_once __DIR__ . '/../includes/auth.php';
cekLogin('admin');
require_once __DIR__ . '/../config/database.php';

// Get current mode
$row = $conn->query("SELECT nilai FROM setting WHERE nama_setting='exambrowser_mode'")->fetch_assoc();
$currentMode = $row ? (int)$row['nilai'] : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exambrowser - CBT MTsN 1 Mesuji</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="bg-gray-100 min-h-screen">
<div class="flex h-screen overflow-hidden">
    <?php include __DIR__ . '/../includes/sidebar_admin.php'; ?>

    <div class="flex-1 ml-64 overflow-y-auto pb-16">
        <!-- Top Bar -->
        <div class="bg-white shadow-sm px-6 py-4 sticky top-0 z-10">
            <h1 class="text-xl font-bold text-gray-800">Exambrowser</h1>
            <p class="text-gray-500 text-sm">Pengaturan Safe Exam Browser</p>
        </div>

        <div class="p-6 flex items-start justify-center">
            <div class="bg-white rounded-2xl shadow-sm w-full max-w-xl p-8">
                <!-- Card Header -->
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                        <i data-lucide="shield-check" class="w-6 h-6 text-blue-700"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-gray-800">Safe Exam Browser (SEB) Mode</h2>
                        <p class="text-sm text-gray-500">Kontrol akses login siswa</p>
                    </div>
                </div>

                <!-- Toggle Section -->
                <div class="flex items-center justify-between p-5 rounded-xl border-2 transition-all duration-300" id="toggle-card">
                    <div>
                        <p class="font-semibold text-gray-800 text-base" id="mode-label">Memuat...</p>
                        <p class="text-sm mt-1" id="mode-desc">Memuat pengaturan...</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer ml-4 flex-shrink-0">
                        <input type="checkbox" id="seb-toggle" class="sr-only peer" onchange="toggleMode(this)" <?= $currentMode ? 'checked' : '' ?>>
                        <div class="w-16 h-8 bg-green-500 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 rounded-full peer peer-checked:after:translate-x-8 peer-checked:bg-red-500 after:content-[''] after:absolute after:top-[4px] after:left-[4px] after:bg-white after:border-white after:border after:rounded-full after:h-6 after:w-6 after:transition-all after:shadow-md transition-colors duration-300"></div>
                    </label>
                </div>

                <!-- Status Display -->
                <div class="mt-5 p-4 rounded-xl" id="status-box">
                    <div class="flex items-center gap-3">
                        <div id="status-icon" class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0">
                            <i id="status-icon-inner" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide" id="status-label-small">Status</p>
                            <p class="font-semibold text-base" id="status-value">-</p>
                        </div>
                    </div>
                </div>

                <!-- Info Box -->
                <div class="mt-5 bg-blue-50 border border-blue-200 rounded-xl p-4">
                    <div class="flex items-start gap-3">
                        <i data-lucide="info" class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5"></i>
                        <div class="text-sm text-blue-700">
                            <p class="font-semibold mb-1">Informasi</p>
                            <ul class="space-y-1 text-blue-600">
                                <li>• <strong>Mode Normal:</strong> Siswa dapat login melalui browser biasa.</li>
                                <li>• <strong>Mode SEB Aktif:</strong> Siswa hanya bisa login melalui Safe Exam Browser. Browser biasa akan ditolak.</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Last updated -->
                <p class="text-xs text-gray-400 mt-4 text-center" id="last-updated"></p>
            </div>
        </div>
    </div>
</div>

<footer class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 py-2 text-center text-xs text-gray-500 z-10">
    &copy; <?= date('Y') ?> | Develop by Asmin Pratama
</footer>

<script>
const initialMode = <?= $currentMode ?>;

function updateUI(mode) {
    const card      = document.getElementById('toggle-card');
    const label     = document.getElementById('mode-label');
    const desc      = document.getElementById('mode-desc');
    const statusBox = document.getElementById('status-box');
    const statusIcon = document.getElementById('status-icon');
    const statusIconInner = document.getElementById('status-icon-inner');
    const statusLabelSmall = document.getElementById('status-label-small');
    const statusValue = document.getElementById('status-value');

    if (mode == 1) {
        card.className = 'flex items-center justify-between p-5 rounded-xl border-2 border-red-200 bg-red-50 transition-all duration-300';
        label.textContent = 'Mode Exambrowser Aktif';
        label.className = 'font-semibold text-red-700 text-base';
        desc.textContent = 'Login siswa hanya bisa dilakukan melalui Safe Exam Browser (SEB). Siswa tidak bisa login melalui browser biasa.';
        desc.className = 'text-sm mt-1 text-red-500';

        statusBox.className = 'mt-5 p-4 rounded-xl bg-red-50 border border-red-200';
        statusIcon.className = 'w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0 bg-red-100';
        statusIconInner.setAttribute('data-lucide', 'shield-alert');
        statusIconInner.className = 'w-5 h-5 text-red-600';
        statusLabelSmall.className = 'text-xs font-medium uppercase tracking-wide text-red-500';
        statusValue.textContent = 'SEB Mode Aktif';
        statusValue.className = 'font-semibold text-base text-red-700';
    } else {
        card.className = 'flex items-center justify-between p-5 rounded-xl border-2 border-green-200 bg-green-50 transition-all duration-300';
        label.textContent = 'Mode Normal';
        label.className = 'font-semibold text-green-700 text-base';
        desc.textContent = 'Siswa dapat login menggunakan browser biasa tanpa perlu Safe Exam Browser.';
        desc.className = 'text-sm mt-1 text-green-600';

        statusBox.className = 'mt-5 p-4 rounded-xl bg-green-50 border border-green-200';
        statusIcon.className = 'w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0 bg-green-100';
        statusIconInner.setAttribute('data-lucide', 'check-circle');
        statusIconInner.className = 'w-5 h-5 text-green-600';
        statusLabelSmall.className = 'text-xs font-medium uppercase tracking-wide text-green-600';
        statusValue.textContent = 'Mode Normal - Aktif';
        statusValue.className = 'font-semibold text-base text-green-700';
    }
    lucide.createIcons();
}

function toggleMode(checkbox) {
    const newMode = checkbox.checked ? 1 : 0;
    checkbox.disabled = true;

    const fd = new FormData();
    fd.append('action', 'toggle_mode');
    fd.append('new_mode', newMode);

    fetch('/admin/ajax/exambrowser_handler.php', {method: 'POST', body: fd})
        .then(r => r.json())
        .then(res => {
            checkbox.disabled = false;
            if (res.status === 'success') {
                updateUI(res.mode);
                const now = new Date();
                document.getElementById('last-updated').textContent = 'Terakhir diubah: ' + now.toLocaleString('id-ID');
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: res.message,
                    timer: 2000,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end'
                });
            } else {
                checkbox.checked = !checkbox.checked;
                Swal.fire({icon: 'error', title: 'Gagal', text: res.message, confirmButtonColor: '#1d4ed8'});
            }
        })
        .catch(() => {
            checkbox.disabled = false;
            checkbox.checked = !checkbox.checked;
            Swal.fire({icon: 'error', title: 'Error', text: 'Koneksi gagal', confirmButtonColor: '#1d4ed8'});
        });
}

// Init UI with PHP value
updateUI(initialMode);
document.getElementById('last-updated').textContent = '';
</script>
</body>
</html>
