<?php
session_start();
date_default_timezone_set('Asia/Jakarta');
require_once __DIR__ . '/../includes/auth.php';
cekLogin('admin');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relasi Guru - CBT MTsN 1 Mesuji</title>
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
        <div class="bg-white shadow-sm px-6 py-4 flex items-center justify-between sticky top-0 z-10">
            <div>
                <h1 class="text-xl font-bold text-gray-800">Relasi Guru</h1>
                <p class="text-gray-500 text-sm">Kelola relasi kelas &amp; mata pelajaran guru</p>
            </div>
        </div>

        <div class="p-6">
            <div class="bg-white rounded-xl shadow-sm p-4">
                <!-- Toolbar -->
                <div class="flex items-center justify-between mb-4">
                    <div class="text-sm text-gray-500" id="info-data">Memuat data...</div>
                    <div class="relative">
                        <input type="text" id="search-input" oninput="filterTable()" placeholder="Cari nama guru..."
                            class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm pl-9 focus:ring-2 focus:ring-blue-500 w-56">
                        <i data-lucide="search" class="w-4 h-4 text-gray-400 absolute left-2.5 top-2 pointer-events-none"></i>
                    </div>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200">
                                <th class="px-4 py-3 text-left text-gray-600 font-semibold w-12">No</th>
                                <th class="px-4 py-3 text-left text-gray-600 font-semibold">Nama Guru</th>
                                <th class="px-4 py-3 text-center text-gray-600 font-semibold">Jumlah Mapel</th>
                                <th class="px-4 py-3 text-center text-gray-600 font-semibold">Jumlah Kelas</th>
                                <th class="px-4 py-3 text-left text-gray-600 font-semibold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="table-body">
                            <tr><td colspan="5" class="text-center py-10 text-gray-400">Memuat data...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ── Modal Relasi ─────────────────────────────────────────────────────────── -->
<div id="modal-relasi" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between shrink-0">
            <div>
                <h2 class="text-lg font-semibold text-gray-800">Relasikan Guru</h2>
                <p class="text-sm text-gray-500 mt-0.5">Pilih kelas dan mata pelajaran untuk guru ini</p>
            </div>
            <button onclick="closeModalRelasi()" class="text-gray-400 hover:text-gray-600 transition-colors">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <!-- Body -->
        <div class="p-6 overflow-y-auto flex-1 space-y-5">
            <!-- Guru info -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg px-4 py-3 flex items-center gap-3">
                <div class="bg-blue-100 rounded-full p-2">
                    <i data-lucide="user" class="w-5 h-5 text-blue-600"></i>
                </div>
                <div>
                    <p class="text-xs text-blue-500 font-medium uppercase tracking-wide">Guru</p>
                    <p class="text-base font-semibold text-blue-800" id="modal-guru-nama">-</p>
                </div>
                <input type="hidden" id="modal-guru-id">
            </div>

            <div class="grid grid-cols-2 gap-6">
                <!-- Kelas checkboxes -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="text-sm font-semibold text-gray-700 flex items-center gap-1.5">
                            <i data-lucide="layout-grid" class="w-4 h-4 text-indigo-500"></i> Kelas
                        </label>
                        <button onclick="toggleAllCheck('kelas')" class="text-xs text-blue-600 hover:underline">Pilih Semua</button>
                    </div>
                    <div id="kelas-list" class="space-y-1.5 border border-gray-200 rounded-lg p-3 max-h-56 overflow-y-auto bg-gray-50">
                        <p class="text-xs text-gray-400">Memuat...</p>
                    </div>
                </div>

                <!-- Mapel checkboxes -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="text-sm font-semibold text-gray-700 flex items-center gap-1.5">
                            <i data-lucide="book-open" class="w-4 h-4 text-emerald-500"></i> Mata Pelajaran
                        </label>
                        <button onclick="toggleAllCheck('mapel')" class="text-xs text-blue-600 hover:underline">Pilih Semua</button>
                    </div>
                    <div id="mapel-list" class="space-y-1.5 border border-gray-200 rounded-lg p-3 max-h-56 overflow-y-auto bg-gray-50">
                        <p class="text-xs text-gray-400">Memuat...</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-3 shrink-0">
            <button onclick="closeModalRelasi()" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                Batal
            </button>
            <button onclick="saveRelasi()" class="bg-blue-700 hover:bg-blue-800 text-white px-5 py-2 rounded-lg text-sm font-medium flex items-center gap-2 transition-colors">
                <i data-lucide="save" class="w-4 h-4"></i> Simpan Relasi
            </button>
        </div>
    </div>
</div>

<footer class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 py-2 text-center text-xs text-gray-500 z-10">
    &copy; <?= date('Y') ?> | Develop by Asmin Pratama
</footer>

<script>
let allGuru = [];
let allKelas = [];
let allMapel = [];

// ── Load initial data ────────────────────────────────────────────────────────
function loadAll() {
    Promise.all([
        fetch('/admin/ajax/relasi_handler.php?action=get_guru_list').then(r => r.json()),
        fetch('/admin/ajax/relasi_handler.php?action=get_kelas').then(r => r.json()),
        fetch('/admin/ajax/relasi_handler.php?action=get_mapel').then(r => r.json()),
    ]).then(([guruRes, kelasRes, mapelRes]) => {
        allGuru  = guruRes.status  === 'success' ? guruRes.data  : [];
        allKelas = kelasRes.status === 'success' ? kelasRes.data : [];
        allMapel = mapelRes.status === 'success' ? mapelRes.data : [];
        renderTable(allGuru);
    });
}

// ── Render main table ────────────────────────────────────────────────────────
function renderTable(data) {
    const tbody = document.getElementById('table-body');
    const info  = document.getElementById('info-data');
    info.textContent = `Total ${data.length} guru`;

    if (data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center py-10 text-gray-400">Tidak ada data guru</td></tr>';
        return;
    }

    tbody.innerHTML = data.map((row, i) => `
        <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
            <td class="px-4 py-3 text-gray-500">${i + 1}</td>
            <td class="px-4 py-3">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-700 font-bold text-xs shrink-0">
                        ${escHtml(row.nama.charAt(0).toUpperCase())}
                    </div>
                    <span class="font-medium text-gray-800">${escHtml(row.nama)}</span>
                </div>
            </td>
            <td class="px-4 py-3 text-center">
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold
                    ${row.jml_mapel > 0 ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500'}">
                    <i data-lucide="book-open" class="w-3 h-3"></i> ${row.jml_mapel}
                </span>
            </td>
            <td class="px-4 py-3 text-center">
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold
                    ${row.jml_kelas > 0 ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-100 text-gray-500'}">
                    <i data-lucide="layout-grid" class="w-3 h-3"></i> ${row.jml_kelas}
                </span>
            </td>
            <td class="px-4 py-3">
                <button onclick="openModalRelasi(${row.id}, '${escHtml(row.nama).replace(/'/g,"&#39;")}')"
                    class="inline-flex items-center gap-1.5 bg-blue-700 hover:bg-blue-800 text-white text-xs px-3 py-1.5 rounded-lg transition-colors font-medium">
                    <i data-lucide="link-2" class="w-3.5 h-3.5"></i> Relasikan
                </button>
            </td>
        </tr>
    `).join('');
    lucide.createIcons();
}

// ── Filter by search ─────────────────────────────────────────────────────────
function filterTable() {
    const q = document.getElementById('search-input').value.toLowerCase();
    const filtered = allGuru.filter(g => g.nama.toLowerCase().includes(q));
    renderTable(filtered);
}

// ── Open relasi modal ─────────────────────────────────────────────────────────
function openModalRelasi(guruId, guruNama) {
    document.getElementById('modal-guru-id').value   = guruId;
    document.getElementById('modal-guru-nama').textContent = guruNama;
    document.getElementById('modal-relasi').classList.remove('hidden');

    // Render kelas & mapel checkboxes first (unchecked), then fetch existing relasi
    renderCheckboxes('kelas-list', allKelas, 'kelas', 'id', 'nama_kelas', []);
    renderCheckboxes('mapel-list', allMapel, 'mapel', 'id', 'nama_mapel', []);

    fetch(`/admin/ajax/relasi_handler.php?action=get_relasi&guru_id=${guruId}`)
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                renderCheckboxes('kelas-list', allKelas, 'kelas', 'id', 'nama_kelas', res.kelas_ids);
                renderCheckboxes('mapel-list', allMapel, 'mapel', 'id', 'nama_mapel', res.mapel_ids);
                lucide.createIcons();
            }
        });
}

function closeModalRelasi() {
    document.getElementById('modal-relasi').classList.add('hidden');
}

// ── Render checkbox lists ─────────────────────────────────────────────────────
function renderCheckboxes(containerId, items, group, idKey, labelKey, checkedIds) {
    const container = document.getElementById(containerId);
    if (items.length === 0) {
        container.innerHTML = '<p class="text-xs text-gray-400 py-2">Belum ada data</p>';
        return;
    }
    container.innerHTML = items.map(item => `
        <label class="flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-white cursor-pointer transition-colors group">
            <input type="checkbox" name="${group}_ids[]" value="${escHtml(item[idKey])}"
                ${checkedIds.includes(String(item[idKey])) ? 'checked' : ''}
                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
            <span class="text-sm text-gray-700 group-hover:text-gray-900">${escHtml(item[labelKey])}</span>
        </label>
    `).join('');
}

// ── Toggle select-all for a group ─────────────────────────────────────────────
function toggleAllCheck(group) {
    const checks = document.querySelectorAll(`input[name="${group}_ids[]"]`);
    const allChecked = Array.from(checks).every(c => c.checked);
    checks.forEach(c => c.checked = !allChecked);
}

// ── Save relasi ───────────────────────────────────────────────────────────────
function saveRelasi() {
    const guruId   = document.getElementById('modal-guru-id').value;
    const kelasIds = Array.from(document.querySelectorAll('input[name="kelas_ids[]"]:checked')).map(c => c.value);
    const mapelIds = Array.from(document.querySelectorAll('input[name="mapel_ids[]"]:checked')).map(c => c.value);

    const fd = new FormData();
    fd.append('action', 'save_relasi');
    fd.append('guru_id', guruId);
    kelasIds.forEach(v => fd.append('kelas_ids[]', v));
    mapelIds.forEach(v => fd.append('mapel_ids[]', v));

    fetch('/admin/ajax/relasi_handler.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                Swal.fire({ icon: 'success', title: 'Berhasil', text: res.message, timer: 1500, showConfirmButton: false });
                closeModalRelasi();
                loadAll();
            } else {
                Swal.fire({ icon: 'error', title: 'Gagal', text: res.message, confirmButtonColor: '#1d4ed8' });
            }
        });
}

function escHtml(s) {
    return String(s)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

loadAll();
</script>
</body>
</html>
