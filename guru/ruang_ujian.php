<?php
session_start();
date_default_timezone_set('Asia/Jakarta');
require_once __DIR__ . '/../includes/auth.php';
cekLogin('guru');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ruang Ujian - CBT MTsN 1 Mesuji</title>
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
            <div>
                <h1 class="text-xl font-bold text-gray-800">Ruang Ujian</h1>
                <p class="text-gray-500 text-sm">Kelola ruang ujian Anda</p>
            </div>
            <div class="flex items-center gap-2">
                <button id="btn-hapus-terpilih" onclick="hapusTerpilih()" class="hidden bg-red-500 hover:bg-red-600 text-white text-sm px-4 py-2 rounded-lg flex items-center gap-1">
                    <i data-lucide="trash-2" class="w-4 h-4"></i> Hapus Terpilih
                </button>
                <button onclick="openModalTambah()" class="bg-green-700 hover:bg-green-800 text-white text-sm px-4 py-2 rounded-lg flex items-center gap-1">
                    <i data-lucide="plus" class="w-4 h-4"></i> Tambah Ruang Ujian
                </button>
            </div>
        </div>

        <div class="p-6">
            <div class="bg-white rounded-xl shadow-sm p-4">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <label class="text-sm text-gray-600">Tampilkan:</label>
                        <select id="per-page" onchange="loadData()" class="border border-gray-300 rounded-lg text-sm px-3 py-1.5">
                            <option value="10">10</option>
                            <option value="32">32</option>
                            <option value="all">Semua</option>
                        </select>
                    </div>
                    <div class="text-sm text-gray-500" id="info-data">Memuat data...</div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200">
                                <th class="px-4 py-3 text-left w-10"><input type="checkbox" id="check-all" onchange="toggleAll(this)" class="rounded"></th>
                                <th class="px-4 py-3 text-left text-gray-600 font-semibold">No</th>
                                <th class="px-4 py-3 text-left text-gray-600 font-semibold">Nama Ruang</th>
                                <th class="px-4 py-3 text-left text-gray-600 font-semibold">Token</th>
                                <th class="px-4 py-3 text-left text-gray-600 font-semibold">Bank Soal</th>
                                <th class="px-4 py-3 text-left text-gray-600 font-semibold">Kelas</th>
                                <th class="px-4 py-3 text-left text-gray-600 font-semibold">Tanggal</th>
                                <th class="px-4 py-3 text-left text-gray-600 font-semibold">Status</th>
                                <th class="px-4 py-3 text-left text-gray-600 font-semibold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="table-body">
                            <tr><td colspan="9" class="text-center py-8 text-gray-400">Memuat data...</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="flex items-center justify-between mt-4 pt-4 border-t border-gray-100" id="pagination-area"></div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah -->
<div id="modal-tambah" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between sticky top-0 bg-white z-10">
            <h2 class="text-lg font-semibold text-gray-800">Tambah Ruang Ujian</h2>
            <button onclick="closeModalTambah()" class="text-gray-400 hover:text-gray-600"><i data-lucide="x" class="w-5 h-5"></i></button>
        </div>
        <div class="p-6 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Ruang <span class="text-red-500">*</span></label>
                <input type="text" id="tambah-nama-ruang" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500" placeholder="Contoh: Ujian Matematika Kelas 7">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Pilih Bank Soal <span class="text-red-500">*</span></label>
                <select id="tambah-bank-soal-id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500">
                    <option value="">-- Pilih Bank Soal --</option>
                </select>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Waktu Hentikan (menit) <span class="text-red-500">*</span></label>
                    <input type="number" id="tambah-waktu-hentikan" min="1" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500" placeholder="30">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Batas Keluar (kali)</label>
                    <input type="number" id="tambah-batas-keluar" min="0" value="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Pilih Kelas <span class="text-red-500">*</span></label>
                <div id="tambah-kelas-list" class="grid grid-cols-3 gap-2 max-h-36 overflow-y-auto p-2 border border-gray-200 rounded-lg">
                    <p class="text-gray-400 text-xs col-span-3">Memuat kelas...</p>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Mulai <span class="text-red-500">*</span></label>
                    <input type="datetime-local" id="tambah-tanggal-mulai" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Selesai <span class="text-red-500">*</span></label>
                    <input type="datetime-local" id="tambah-tanggal-selesai" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500">
                </div>
            </div>
            <div class="flex items-center gap-6">
                <label class="flex items-center gap-2 cursor-pointer">
                    <div class="relative">
                        <input type="checkbox" id="tambah-acak-soal" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                    </div>
                    <span class="text-sm text-gray-700">Acak Urutan Soal</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <div class="relative">
                        <input type="checkbox" id="tambah-acak-jawaban" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                    </div>
                    <span class="text-sm text-gray-700">Acak Urutan Jawaban</span>
                </label>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Token <span class="text-red-500">*</span></label>
                <div class="flex gap-2">
                    <input type="text" id="tambah-token" maxlength="6" class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono uppercase tracking-widest focus:ring-2 focus:ring-green-500 bg-gray-50" placeholder="6 karakter" readonly>
                    <button onclick="generateToken('tambah-token')" class="bg-gray-700 hover:bg-gray-800 text-white text-sm px-4 py-2 rounded-lg flex items-center gap-1">
                        <i data-lucide="refresh-cw" class="w-4 h-4"></i> Generate
                    </button>
                </div>
            </div>
        </div>
        <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-3 sticky bottom-0 bg-white">
            <button onclick="closeModalTambah()" class="px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-lg">Batal</button>
            <button onclick="saveRuangUjian()" class="bg-green-700 hover:bg-green-800 text-white px-5 py-2 rounded-lg text-sm font-medium flex items-center gap-2">
                <i data-lucide="save" class="w-4 h-4"></i> Simpan
            </button>
        </div>
    </div>
</div>

<!-- Modal Monitoring -->
<div id="modal-monitoring" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-5xl max-h-[92vh] overflow-y-auto">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between sticky top-0 bg-white z-10">
            <div>
                <h2 class="text-lg font-bold text-gray-800">Monitoring Ujian: <span id="monitoring-nama-ruang" class="text-green-700"></span></h2>
                <p class="text-xs text-gray-500 mt-0.5">TOKEN: <span id="monitoring-token" class="font-mono font-bold text-gray-700"></span></p>
            </div>
            <button onclick="closeModalMonitoring()" class="text-gray-400 hover:text-gray-600"><i data-lucide="x" class="w-5 h-5"></i></button>
        </div>
        <div class="p-4">
            <div class="flex items-center justify-between mb-4 gap-3">
                <div class="flex items-center gap-2">
                    <select id="filter-kelas" onchange="filterMonitoringTable()" class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-green-500">
                        <option value="">Semua Kelas</option>
                    </select>
                    <a id="btn-export-nilai" href="#" class="bg-green-600 hover:bg-green-700 text-white text-xs px-3 py-1.5 rounded-lg flex items-center gap-1">
                        <i data-lucide="download" class="w-3.5 h-3.5"></i> Export Nilai
                    </a>
                </div>
                <input type="text" id="search-siswa" placeholder="Cari nama/NISN..." oninput="filterMonitoringTable()"
                    class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-green-500 w-48">
            </div>
            <!-- Stats -->
            <div class="grid grid-cols-3 gap-3 mb-4">
                <div class="bg-red-50 rounded-lg p-3 text-center"><p class="text-red-500 text-xs">Belum</p><p class="text-xl font-bold text-red-600" id="stat-belum">0</p></div>
                <div class="bg-orange-50 rounded-lg p-3 text-center"><p class="text-orange-500 text-xs">Sedang</p><p class="text-xl font-bold text-orange-600" id="stat-sedang">0</p></div>
                <div class="bg-green-50 rounded-lg p-3 text-center"><p class="text-green-500 text-xs">Selesai</p><p class="text-xl font-bold text-green-600" id="stat-selesai">0</p></div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 border-b">
                            <th class="px-3 py-2 text-left w-8"><input type="checkbox" id="check-monitor-all" onchange="toggleMonitorAll(this)" class="rounded"></th>
                            <th class="px-3 py-2 text-left text-gray-600 font-semibold">No</th>
                            <th class="px-3 py-2 text-left text-gray-600 font-semibold">Nama Siswa</th>
                            <th class="px-3 py-2 text-left text-gray-600 font-semibold">Kelas</th>
                            <th class="px-3 py-2 text-left text-gray-600 font-semibold">Durasi</th>
                            <th class="px-3 py-2 text-left text-gray-600 font-semibold">B/S</th>
                            <th class="px-3 py-2 text-left text-gray-600 font-semibold">Status</th>
                            <th class="px-3 py-2 text-left text-gray-600 font-semibold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="monitor-tbody">
                        <tr><td colspan="8" class="text-center py-8 text-gray-400">Memuat data...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah Waktu -->
<div id="modal-tambah-waktu" class="fixed inset-0 bg-black bg-opacity-50 z-60 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="font-bold text-gray-800">Tambah Waktu Mengerjakan</h3>
            <button onclick="closeTambahWaktu()" class="text-gray-400 hover:text-gray-600"><i data-lucide="x" class="w-5 h-5"></i></button>
        </div>
        <div class="p-6">
            <input type="hidden" id="tw-ujian-id">
            <label class="block text-sm font-medium text-gray-700 mb-1">Waktu Tambahan (menit)</label>
            <input type="number" id="tw-menit" min="1" value="10" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500">
        </div>
        <div class="px-6 pb-4 flex justify-end gap-3">
            <button onclick="closeTambahWaktu()" class="px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-lg">Batal</button>
            <button onclick="submitTambahWaktu()" class="bg-green-700 hover:bg-green-800 text-white px-4 py-2 rounded-lg text-sm">Tambah</button>
        </div>
    </div>
</div>

<footer class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 py-2 text-center text-xs text-gray-500 z-10">
    &copy; <?= date('Y') ?> | Develop by Asmin Pratama
</footer>

<script>
let currentPage = 1;
let selectedIds = [];
let monitoringRuangId = null;
let monitoringData = [];
let monitorSelectedIds = [];

function loadData() {
    const perPage = document.getElementById('per-page').value;
    fetch(`/guru/ajax/ruang_ujian_handler.php?action=get_all&page=${currentPage}&per_page=${perPage}`)
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') renderTable(res.data, res.total, res.per_page, res.page);
        })
        .catch(() => { document.getElementById('table-body').innerHTML = '<tr><td colspan="9" class="text-center py-8 text-red-400">Gagal memuat data</td></tr>'; });
}

function renderTable(data, total, perPage, page) {
    const tbody = document.getElementById('table-body');
    const info  = document.getElementById('info-data');
    const pag   = document.getElementById('pagination-area');

    if (!data || !data.length) {
        tbody.innerHTML = '<tr><td colspan="9" class="text-center py-10 text-gray-400"><div class="flex flex-col items-center gap-2"><i data-lucide="inbox" class="w-10 h-10 text-gray-300"></i><p>Belum ada ruang ujian</p></div></td></tr>';
        info.textContent = '';
        pag.innerHTML = '';
        if (typeof lucide !== 'undefined') lucide.createIcons();
        return;
    }

    const start = (perPage === 'all') ? 1 : (page - 1) * parseInt(perPage) + 1;
    const end   = (perPage === 'all') ? total : Math.min(page * parseInt(perPage), total);
    info.textContent = `${start}–${end} dari ${total} data`;

    const now = Date.now();
    tbody.innerHTML = data.map((row, idx) => {
        const no = (perPage === 'all') ? (idx+1) : ((page-1) * parseInt(perPage) + idx + 1);
        const mulai   = new Date(row.tanggal_mulai.replace(' ', 'T'));
        const selesai = new Date(row.tanggal_selesai.replace(' ', 'T'));
        let statusBadge;
        if (now < mulai.getTime()) statusBadge = '<span class="px-2 py-0.5 bg-yellow-100 text-yellow-700 rounded-full text-xs">Belum Mulai</span>';
        else if (now > selesai.getTime()) statusBadge = '<span class="px-2 py-0.5 bg-gray-100 text-gray-600 rounded-full text-xs">Selesai</span>';
        else statusBadge = '<span class="px-2 py-0.5 bg-green-100 text-green-700 rounded-full text-xs">Aktif</span>';
        const kelas = row.kelas_list ? escHtml(row.kelas_list).split(',').map(k => `<span class="px-1.5 py-0.5 bg-blue-100 text-blue-700 rounded text-xs">${k}</span>`).join(' ') : '-';
        return `<tr class="border-b border-gray-100 hover:bg-gray-50">
            <td class="px-4 py-3"><input type="checkbox" class="row-check rounded" value="${row.id}" onchange="updateSelected()"></td>
            <td class="px-4 py-3 text-gray-500 text-xs">${no}</td>
            <td class="px-4 py-3 font-semibold text-gray-800">${escHtml(row.nama_ruang)}</td>
            <td class="px-4 py-3"><span class="font-mono bg-gray-100 px-2 py-0.5 rounded text-xs">${escHtml(row.token)}</span></td>
            <td class="px-4 py-3 text-xs text-gray-600">${escHtml(row.nama_soal||'-')}</td>
            <td class="px-4 py-3"><div class="flex flex-wrap gap-1">${kelas}</div></td>
            <td class="px-4 py-3 text-xs text-gray-600">
                <div>${formatDatetime(row.tanggal_mulai)}</div>
                <div class="text-gray-400">s/d</div>
                <div>${formatDatetime(row.tanggal_selesai)}</div>
            </td>
            <td class="px-4 py-3">${statusBadge}</td>
            <td class="px-4 py-3">
                <div class="flex items-center gap-1">
                    <button onclick="openMonitoring(${row.id}, '${escAttr(row.nama_ruang)}', '${escAttr(row.token)}')" class="text-green-600 hover:text-green-800 bg-green-50 hover:bg-green-100 px-2 py-1 rounded text-xs flex items-center gap-1">
                        <i data-lucide="activity" class="w-3.5 h-3.5"></i> Monitor
                    </button>
                    <button onclick="deleteRuangUjian(${row.id})" class="text-red-500 hover:text-red-700" title="Hapus">
                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                    </button>
                </div>
            </td>
        </tr>`;
    }).join('');

    if (perPage !== 'all') {
        const totalPages = Math.ceil(total / parseInt(perPage));
        let pagHtml = '<div class="flex items-center gap-1">';
        if (page > 1) pagHtml += `<button onclick="changePage(${page-1})" class="px-3 py-1 rounded border text-sm hover:bg-gray-50">&#8249;</button>`;
        for (let i = Math.max(1, page-2); i <= Math.min(totalPages, page+2); i++) {
            pagHtml += `<button onclick="changePage(${i})" class="px-3 py-1 rounded border text-sm ${i===page?'bg-green-700 text-white border-green-700':'hover:bg-gray-50'}">${i}</button>`;
        }
        if (page < totalPages) pagHtml += `<button onclick="changePage(${page+1})" class="px-3 py-1 rounded border text-sm hover:bg-gray-50">&#8250;</button>`;
        pagHtml += '</div>';
        pag.innerHTML = pagHtml;
    } else {
        pag.innerHTML = '';
    }
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function changePage(p) { currentPage = p; loadData(); }

function updateSelected() {
    selectedIds = Array.from(document.querySelectorAll('.row-check:checked')).map(c => c.value);
    document.getElementById('btn-hapus-terpilih').classList.toggle('hidden', selectedIds.length === 0);
    const all = document.querySelectorAll('.row-check');
    document.getElementById('check-all').checked = all.length > 0 && Array.from(all).every(c => c.checked);
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function toggleAll(cb) {
    document.querySelectorAll('.row-check').forEach(c => c.checked = cb.checked);
    updateSelected();
}

function loadBankSoalList() {
    fetch('/guru/ajax/ruang_ujian_handler.php?action=get_bank_soal')
        .then(r => r.json())
        .then(res => {
            const sel = document.getElementById('tambah-bank-soal-id');
            sel.innerHTML = '<option value="">-- Pilih Bank Soal --</option>';
            if (res.status === 'success' && res.data.length) {
                res.data.forEach(b => {
                    const opt = document.createElement('option');
                    opt.value = b.id; opt.textContent = b.nama_soal;
                    sel.appendChild(opt);
                });
            }
        });
}

function loadKelasList(prefix, checkedIds = []) {
    fetch('/guru/ajax/ruang_ujian_handler.php?action=get_kelas')
        .then(r => r.json())
        .then(res => {
            const container = document.getElementById(`${prefix}-kelas-list`);
            if (res.status === 'success' && res.data.length) {
                container.innerHTML = res.data.map(k => `
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" class="${prefix}-kelas-check rounded text-green-600" value="${k.id}" ${checkedIds.includes(k.id)?'checked':''}>
                        <span class="text-sm text-gray-700">${escHtml(k.nama_kelas)}</span>
                    </label>`).join('');
            } else {
                container.innerHTML = '<p class="text-gray-400 text-xs col-span-3">Tidak ada kelas yang terhubung</p>';
            }
        });
}

function openModalTambah() {
    document.getElementById('tambah-nama-ruang').value = '';
    document.getElementById('tambah-waktu-hentikan').value = '';
    document.getElementById('tambah-batas-keluar').value = '3';
    document.getElementById('tambah-tanggal-mulai').value = '';
    document.getElementById('tambah-tanggal-selesai').value = '';
    document.getElementById('tambah-acak-soal').checked = false;
    document.getElementById('tambah-acak-jawaban').checked = false;
    document.getElementById('tambah-token').value = '';
    loadBankSoalList();
    loadKelasList('tambah');
    document.getElementById('modal-tambah').classList.remove('hidden');
    generateToken('tambah-token');
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function closeModalTambah() { document.getElementById('modal-tambah').classList.add('hidden'); }

function saveRuangUjian() {
    const namaRuang = document.getElementById('tambah-nama-ruang').value.trim();
    const bankSoal  = document.getElementById('tambah-bank-soal-id').value;
    const waktu     = document.getElementById('tambah-waktu-hentikan').value;
    const batas     = document.getElementById('tambah-batas-keluar').value;
    const tMulai    = document.getElementById('tambah-tanggal-mulai').value;
    const tSelesai  = document.getElementById('tambah-tanggal-selesai').value;
    const token     = document.getElementById('tambah-token').value.trim();
    const kelasIds  = Array.from(document.querySelectorAll('.tambah-kelas-check:checked')).map(c => c.value);

    if (!namaRuang || !bankSoal || !waktu || !tMulai || !tSelesai || !token) {
        Swal.fire({icon:'warning', text:'Semua field wajib diisi'}); return;
    }
    if (!kelasIds.length) { Swal.fire({icon:'warning', text:'Pilih minimal satu kelas'}); return; }

    const fd = new FormData();
    fd.append('action','add'); fd.append('nama_ruang', namaRuang);
    fd.append('bank_soal_id', bankSoal); fd.append('waktu_hentikan', waktu);
    fd.append('batas_keluar', batas); fd.append('tanggal_mulai', tMulai);
    fd.append('tanggal_selesai', tSelesai);
    fd.append('acak_soal', document.getElementById('tambah-acak-soal').checked ? '1' : '0');
    fd.append('acak_jawaban', document.getElementById('tambah-acak-jawaban').checked ? '1' : '0');
    fd.append('token', token.toUpperCase());
    kelasIds.forEach(id => fd.append('kelas_ids[]', id));

    fetch('/guru/ajax/ruang_ujian_handler.php', {method:'POST', body:fd})
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                Swal.fire({icon:'success', text:res.message, timer:1500, showConfirmButton:false});
                closeModalTambah(); loadData();
            } else {
                Swal.fire({icon:'error', text:res.message});
            }
        });
}

function deleteRuangUjian(id) {
    Swal.fire({
        title:'Hapus Ruang Ujian?', text:'Data ujian siswa terkait juga akan dihapus.', icon:'warning',
        showCancelButton:true, confirmButtonColor:'#ef4444', cancelButtonColor:'#6b7280',
        confirmButtonText:'Hapus', cancelButtonText:'Batal'
    }).then(r => {
        if (!r.isConfirmed) return;
        const fd = new FormData(); fd.append('action','delete'); fd.append('id', id);
        fetch('/guru/ajax/ruang_ujian_handler.php', {method:'POST', body:fd})
            .then(r => r.json())
            .then(res => {
                if (res.status === 'success') { Swal.fire({icon:'success', text:res.message, timer:1500, showConfirmButton:false}); loadData(); }
                else Swal.fire({icon:'error', text:res.message});
            });
    });
}

function hapusTerpilih() {
    if (!selectedIds.length) return;
    Swal.fire({
        title:`Hapus ${selectedIds.length} Ruang?`, icon:'warning', showCancelButton:true,
        confirmButtonColor:'#ef4444', cancelButtonColor:'#6b7280', confirmButtonText:'Hapus'
    }).then(r => {
        if (!r.isConfirmed) return;
        const promises = selectedIds.map(id => {
            const fd = new FormData(); fd.append('action','delete'); fd.append('id', id);
            return fetch('/guru/ajax/ruang_ujian_handler.php', {method:'POST', body:fd}).then(r => r.json());
        });
        Promise.all(promises).then(() => { selectedIds = []; loadData(); });
    });
}

function generateToken(targetId) {
    fetch('/guru/ajax/ruang_ujian_handler.php?action=generate_token')
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') document.getElementById(targetId).value = res.token;
        });
}

// ===================== MONITORING =====================
function openMonitoring(ruangId, namaRuang, token) {
    monitoringRuangId = ruangId;
    document.getElementById('monitoring-nama-ruang').textContent = namaRuang;
    document.getElementById('monitoring-token').textContent = token;
    document.getElementById('btn-export-nilai').href = `/guru/ajax/export_nilai.php?ruang_id=${ruangId}`;
    document.getElementById('modal-monitoring').classList.remove('hidden');
    loadMonitoringData();
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function closeModalMonitoring() {
    document.getElementById('modal-monitoring').classList.add('hidden');
    monitoringRuangId = null;
    monitoringData = [];
}

function loadMonitoringData() {
    fetch(`/guru/ajax/ruang_ujian_handler.php?action=get_monitoring&ruang_id=${monitoringRuangId}`)
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                monitoringData = res.data;
                updateStats(monitoringData);
                populateKelasFilter(monitoringData);
                renderMonitoringTable(monitoringData);
            }
        });
}

function updateStats(data) {
    document.getElementById('stat-belum').textContent = data.filter(d => d.status==='belum'||!d.status).length;
    document.getElementById('stat-sedang').textContent = data.filter(d => d.status==='sedang').length;
    document.getElementById('stat-selesai').textContent = data.filter(d => d.status==='selesai').length;
}

function populateKelasFilter(data) {
    const kelasList = [...new Set(data.map(d => d.nama_kelas).filter(Boolean))];
    const sel = document.getElementById('filter-kelas');
    sel.innerHTML = '<option value="">Semua Kelas</option>' + kelasList.map(k => `<option value="${k}">${escHtml(k)}</option>`).join('');
}

function filterMonitoringTable() {
    const kelas = document.getElementById('filter-kelas').value;
    const search = document.getElementById('search-siswa').value.toLowerCase();
    const filtered = monitoringData.filter(d => {
        const matchKelas = !kelas || d.nama_kelas === kelas;
        const matchSearch = !search || d.nama.toLowerCase().includes(search) || (d.nisn && d.nisn.includes(search));
        return matchKelas && matchSearch;
    });
    renderMonitoringTable(filtered);
}

function renderMonitoringTable(data) {
    const tbody = document.getElementById('monitor-tbody');
    if (!data.length) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center py-8 text-gray-400">Tidak ada data</td></tr>';
        if (typeof lucide !== 'undefined') lucide.createIcons();
        return;
    }
    tbody.innerHTML = data.map((d, idx) => {
        let statusBadge;
        if (d.status === 'selesai') statusBadge = '<span class="px-2 py-0.5 bg-green-100 text-green-700 rounded-full text-xs font-semibold">Selesai</span>';
        else if (d.status === 'sedang') statusBadge = '<span class="px-2 py-0.5 bg-orange-100 text-orange-700 rounded-full text-xs font-semibold">Sedang</span>';
        else statusBadge = '<span class="px-2 py-0.5 bg-red-100 text-red-700 rounded-full text-xs font-semibold">Belum</span>';
        const bs = d.jumlah_benar !== undefined ? `${d.jumlah_benar}/${d.jumlah_salah}` : '-';
        return `<tr class="border-b border-gray-100 hover:bg-gray-50">
            <td class="px-3 py-2"><input type="checkbox" class="monitor-check rounded" value="${d.id}" onchange="updateMonitorSelected()"></td>
            <td class="px-3 py-2 text-xs text-gray-500">${idx+1}</td>
            <td class="px-3 py-2 font-medium text-gray-800">${escHtml(d.nama)}<div class="text-xs text-gray-400">${d.nisn||''}</div></td>
            <td class="px-3 py-2 text-xs">${escHtml(d.nama_kelas||'-')}</td>
            <td class="px-3 py-2 text-xs text-gray-600">${d.durasi||'-'}</td>
            <td class="px-3 py-2 text-xs font-medium">${bs}</td>
            <td class="px-3 py-2">${statusBadge}</td>
            <td class="px-3 py-2">
                <div class="flex gap-1">
                    <button onclick="resetUjian(${d.id})" class="text-xs bg-yellow-500 hover:bg-yellow-600 text-white px-2 py-1 rounded flex items-center gap-1" title="Reset">
                        <i data-lucide="rotate-ccw" class="w-3 h-3"></i>
                    </button>
                    <button onclick="openTambahWaktu(${d.id})" class="text-xs bg-blue-500 hover:bg-blue-600 text-white px-2 py-1 rounded flex items-center gap-1" title="Tambah Waktu">
                        <i data-lucide="clock-4" class="w-3 h-3"></i>
                    </button>
                </div>
            </td>
        </tr>`;
    }).join('');
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function updateMonitorSelected() {
    monitorSelectedIds = Array.from(document.querySelectorAll('.monitor-check:checked')).map(c => c.value);
    const all = document.querySelectorAll('.monitor-check');
    document.getElementById('check-monitor-all').checked = all.length > 0 && Array.from(all).every(c => c.checked);
}

function toggleMonitorAll(cb) {
    document.querySelectorAll('.monitor-check').forEach(c => c.checked = cb.checked);
    updateMonitorSelected();
}

function resetUjian(ujianId) {
    Swal.fire({
        title: 'Reset Ujian?', text: 'Semua jawaban siswa akan dihapus.', icon: 'warning',
        showCancelButton: true, confirmButtonColor: '#f59e0b', cancelButtonColor: '#6b7280',
        confirmButtonText: 'Reset', cancelButtonText: 'Batal'
    }).then(r => {
        if (!r.isConfirmed) return;
        const fd = new FormData(); fd.append('action','reset_ujian'); fd.append('ujian_id', ujianId);
        fetch('/guru/ajax/ruang_ujian_handler.php', {method:'POST', body:fd})
            .then(r => r.json())
            .then(res => {
                if (res.status === 'success') { Swal.fire({icon:'success', text:res.message, timer:1500, showConfirmButton:false}); loadMonitoringData(); }
                else Swal.fire({icon:'error', text:res.message});
            });
    });
}

function openTambahWaktu(ujianId) {
    document.getElementById('tw-ujian-id').value = ujianId;
    document.getElementById('tw-menit').value = 10;
    document.getElementById('modal-tambah-waktu').classList.remove('hidden');
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function closeTambahWaktu() { document.getElementById('modal-tambah-waktu').classList.add('hidden'); }

function submitTambahWaktu() {
    const ujianId = document.getElementById('tw-ujian-id').value;
    const menit   = document.getElementById('tw-menit').value;
    const fd = new FormData(); fd.append('action','tambah_waktu'); fd.append('ujian_id', ujianId); fd.append('menit', menit);
    fetch('/guru/ajax/ruang_ujian_handler.php', {method:'POST', body:fd})
        .then(r => r.json())
        .then(res => {
            closeTambahWaktu();
            if (res.status === 'success') { Swal.fire({icon:'success', text:res.message, timer:1500, showConfirmButton:false}); loadMonitoringData(); }
            else Swal.fire({icon:'error', text:res.message});
        });
}

function formatDatetime(str) {
    if (!str) return '-';
    const d = new Date(str.replace(' ', 'T'));
    return d.toLocaleDateString('id-ID', {day:'2-digit',month:'2-digit',year:'numeric'}) + ' ' + d.toLocaleTimeString('id-ID', {hour:'2-digit',minute:'2-digit'});
}

function escHtml(s) { if (!s) return ''; return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
function escAttr(s) { if (!s) return ''; return String(s).replace(/'/g,"\\'").replace(/"/g,'&quot;'); }

loadData();
</script>
</body>
</html>
