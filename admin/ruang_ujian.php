<?php
session_start();
date_default_timezone_set('Asia/Jakarta');
require_once __DIR__ . '/../includes/auth.php';
cekLogin('admin');
require_once __DIR__ . '/../config/database.php';
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
    <?php include __DIR__ . '/../includes/sidebar_admin.php'; ?>

    <div class="flex-1 ml-64 overflow-y-auto pb-16">
        <!-- Top Bar -->
        <div class="bg-white shadow-sm px-6 py-4 flex items-center justify-between sticky top-0 z-10">
            <div>
                <h1 class="text-xl font-bold text-gray-800">Ruang Ujian</h1>
                <p class="text-gray-500 text-sm">Kelola ruang ujian CBT</p>
            </div>
            <div class="flex items-center gap-2">
                <button id="btn-hapus-terpilih" onclick="hapusTerpilih()" class="hidden bg-red-500 hover:bg-red-600 text-white text-sm px-4 py-2 rounded-lg flex items-center gap-1">
                    <i data-lucide="trash-2" class="w-4 h-4"></i> Hapus Terpilih
                </button>
                <button onclick="openModalTambah()" class="bg-blue-700 hover:bg-blue-800 text-white text-sm px-4 py-2 rounded-lg flex items-center gap-1">
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
                                <th class="px-4 py-3 text-left text-gray-600 font-semibold">Guru</th>
                                <th class="px-4 py-3 text-left text-gray-600 font-semibold">Bank Soal</th>
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

<!-- ==================== MODAL TAMBAH ==================== -->
<div id="modal-tambah" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between sticky top-0 bg-white z-10">
            <h2 class="text-lg font-semibold text-gray-800">Tambah Ruang Ujian</h2>
            <button onclick="closeModalTambah()" class="text-gray-400 hover:text-gray-600"><i data-lucide="x" class="w-5 h-5"></i></button>
        </div>
        <div class="p-6 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Ruang <span class="text-red-500">*</span></label>
                <input type="text" id="tambah-nama-ruang" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500" placeholder="Contoh: Ujian Matematika Kelas 7">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Pilih Guru <span class="text-red-500">*</span></label>
                <select id="tambah-guru-id" onchange="onGuruChange('tambah')" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">-- Pilih Guru --</option>
                    <option value="admin">-- Admin --</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Pilih Bank Soal <span class="text-red-500">*</span></label>
                <select id="tambah-bank-soal-id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">-- Pilih Guru Terlebih Dahulu --</option>
                </select>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Waktu Hentikan (menit) <span class="text-red-500">*</span></label>
                    <input type="number" id="tambah-waktu-hentikan" min="1" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500" placeholder="90">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Batas Keluar (kali)</label>
                    <input type="number" id="tambah-batas-keluar" min="0" value="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
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
                    <input type="datetime-local" id="tambah-tanggal-mulai" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Selesai <span class="text-red-500">*</span></label>
                    <input type="datetime-local" id="tambah-tanggal-selesai" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
            <div class="flex items-center gap-6">
                <label class="flex items-center gap-2 cursor-pointer">
                    <div class="relative">
                        <input type="checkbox" id="tambah-acak-soal" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </div>
                    <span class="text-sm font-medium text-gray-700">Acak Urutan Soal</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <div class="relative">
                        <input type="checkbox" id="tambah-acak-jawaban" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </div>
                    <span class="text-sm font-medium text-gray-700">Acak Urutan Jawaban</span>
                </label>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Token <span class="text-red-500">*</span></label>
                <div class="flex gap-2">
                    <input type="text" id="tambah-token" maxlength="6" class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono uppercase tracking-widest focus:ring-2 focus:ring-blue-500 bg-gray-50" placeholder="6 karakter" readonly>
                    <button onclick="generateToken('tambah-token')" class="bg-gray-700 hover:bg-gray-800 text-white text-sm px-4 py-2 rounded-lg flex items-center gap-1">
                        <i data-lucide="refresh-cw" class="w-4 h-4"></i> Generate
                    </button>
                </div>
            </div>
        </div>
        <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-3 sticky bottom-0 bg-white">
            <button onclick="closeModalTambah()" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Batal</button>
            <button onclick="saveRuangUjian()" class="bg-blue-700 hover:bg-blue-800 text-white px-5 py-2 rounded-lg text-sm font-medium flex items-center gap-2">
                <i data-lucide="save" class="w-4 h-4"></i> Simpan
            </button>
        </div>
    </div>
</div>

<!-- ==================== MODAL EDIT ==================== -->
<div id="modal-edit" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between sticky top-0 bg-white z-10">
            <h2 class="text-lg font-semibold text-gray-800">Edit Ruang Ujian</h2>
            <button onclick="closeModalEdit()" class="text-gray-400 hover:text-gray-600"><i data-lucide="x" class="w-5 h-5"></i></button>
        </div>
        <div class="p-6 space-y-4">
            <input type="hidden" id="edit-id">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Ruang <span class="text-red-500">*</span></label>
                <input type="text" id="edit-nama-ruang" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Pilih Guru <span class="text-red-500">*</span></label>
                <select id="edit-guru-id" onchange="onGuruChange('edit')" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">-- Pilih Guru --</option>
                    <option value="admin">-- Admin --</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Pilih Bank Soal <span class="text-red-500">*</span></label>
                <select id="edit-bank-soal-id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">-- Pilih Guru Terlebih Dahulu --</option>
                </select>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Waktu Hentikan (menit) <span class="text-red-500">*</span></label>
                    <input type="number" id="edit-waktu-hentikan" min="1" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Batas Keluar (kali)</label>
                    <input type="number" id="edit-batas-keluar" min="0" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Pilih Kelas <span class="text-red-500">*</span></label>
                <div id="edit-kelas-list" class="grid grid-cols-3 gap-2 max-h-36 overflow-y-auto p-2 border border-gray-200 rounded-lg">
                    <p class="text-gray-400 text-xs col-span-3">Memuat kelas...</p>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Mulai <span class="text-red-500">*</span></label>
                    <input type="datetime-local" id="edit-tanggal-mulai" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Selesai <span class="text-red-500">*</span></label>
                    <input type="datetime-local" id="edit-tanggal-selesai" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
            <div class="flex items-center gap-6">
                <label class="flex items-center gap-2 cursor-pointer">
                    <div class="relative">
                        <input type="checkbox" id="edit-acak-soal" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </div>
                    <span class="text-sm font-medium text-gray-700">Acak Urutan Soal</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <div class="relative">
                        <input type="checkbox" id="edit-acak-jawaban" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </div>
                    <span class="text-sm font-medium text-gray-700">Acak Urutan Jawaban</span>
                </label>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Token <span class="text-red-500">*</span></label>
                <div class="flex gap-2">
                    <input type="text" id="edit-token" maxlength="6" class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono uppercase tracking-widest focus:ring-2 focus:ring-blue-500 bg-gray-50" placeholder="6 karakter" readonly>
                    <button onclick="generateToken('edit-token')" class="bg-gray-700 hover:bg-gray-800 text-white text-sm px-4 py-2 rounded-lg flex items-center gap-1">
                        <i data-lucide="refresh-cw" class="w-4 h-4"></i> Generate
                    </button>
                </div>
            </div>
        </div>
        <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-3 sticky bottom-0 bg-white">
            <button onclick="closeModalEdit()" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Batal</button>
            <button onclick="updateRuangUjian()" class="bg-blue-700 hover:bg-blue-800 text-white px-5 py-2 rounded-lg text-sm font-medium flex items-center gap-2">
                <i data-lucide="save" class="w-4 h-4"></i> Update
            </button>
        </div>
    </div>
</div>

<!-- ==================== MODAL MONITORING ==================== -->
<div id="modal-monitoring" class="fixed inset-0 bg-black bg-opacity-60 z-50 hidden flex items-start justify-center p-4 overflow-y-auto">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-6xl my-4">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between sticky top-0 bg-white z-10 rounded-t-2xl">
            <div class="flex items-center gap-3">
                <i data-lucide="monitor" class="w-5 h-5 text-blue-700"></i>
                <h2 class="text-lg font-semibold text-gray-800" id="monitoring-title">Monitoring</h2>
                <span id="monitoring-token-badge" class="bg-blue-100 text-blue-700 px-3 py-0.5 rounded-full text-sm font-mono font-bold"></span>
            </div>
            <button onclick="closeModalMonitoring()" class="text-gray-400 hover:text-gray-600"><i data-lucide="x" class="w-5 h-5"></i></button>
        </div>
        <div class="p-6">
            <!-- Filter & Action Bar -->
            <div class="flex flex-wrap items-center gap-3 mb-4">
                <select id="monitor-filter-kelas" onchange="loadMonitoringData()" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">Semua Kelas</option>
                </select>
                <input type="text" id="monitor-search" oninput="filterMonitoringTable()" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 w-56" placeholder="Cari nama / NISN...">
                <div class="flex-1"></div>
                <button onclick="bulkResetUjian()" id="btn-bulk-reset" class="hidden bg-red-500 hover:bg-red-600 text-white text-sm px-4 py-2 rounded-lg flex items-center gap-1">
                    <i data-lucide="rotate-ccw" class="w-4 h-4"></i> Reset Terpilih
                </button>
                <a id="btn-export-nilai" href="#" target="_blank" class="bg-emerald-600 hover:bg-emerald-700 text-white text-sm px-4 py-2 rounded-lg flex items-center gap-1">
                    <i data-lucide="file-spreadsheet" class="w-4 h-4"></i> Export Nilai
                </a>
                <a id="btn-export-analisis" href="#" target="_blank" class="bg-orange-500 hover:bg-orange-600 text-white text-sm px-4 py-2 rounded-lg flex items-center gap-1">
                    <i data-lucide="bar-chart-2" class="w-4 h-4"></i> Export Analisis
                </a>
            </div>

            <!-- Summary Stats -->
            <div class="grid grid-cols-4 gap-3 mb-4" id="monitor-stats">
                <div class="bg-gray-50 rounded-lg p-3 text-center">
                    <p class="text-xs text-gray-500">Total Peserta</p>
                    <p class="text-2xl font-bold text-gray-700" id="stat-total">-</p>
                </div>
                <div class="bg-red-50 rounded-lg p-3 text-center">
                    <p class="text-xs text-red-500">Belum Mulai</p>
                    <p class="text-2xl font-bold text-red-600" id="stat-belum">-</p>
                </div>
                <div class="bg-orange-50 rounded-lg p-3 text-center">
                    <p class="text-xs text-orange-500">Sedang Ujian</p>
                    <p class="text-2xl font-bold text-orange-600" id="stat-sedang">-</p>
                </div>
                <div class="bg-green-50 rounded-lg p-3 text-center">
                    <p class="text-xs text-green-500">Selesai</p>
                    <p class="text-2xl font-bold text-green-600" id="stat-selesai">-</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th class="px-3 py-3 text-left w-10"><input type="checkbox" id="monitor-check-all" onchange="toggleMonitorAll(this)" class="rounded"></th>
                            <th class="px-3 py-3 text-left text-gray-600 font-semibold">No</th>
                            <th class="px-3 py-3 text-left text-gray-600 font-semibold">Nama Siswa</th>
                            <th class="px-3 py-3 text-left text-gray-600 font-semibold">NISN</th>
                            <th class="px-3 py-3 text-left text-gray-600 font-semibold">Kelas</th>
                            <th class="px-3 py-3 text-left text-gray-600 font-semibold">Waktu Mulai</th>
                            <th class="px-3 py-3 text-left text-gray-600 font-semibold">Waktu Selesai</th>
                            <th class="px-3 py-3 text-left text-gray-600 font-semibold">Benar</th>
                            <th class="px-3 py-3 text-left text-gray-600 font-semibold">Salah</th>
                            <th class="px-3 py-3 text-left text-gray-600 font-semibold">Nilai</th>
                            <th class="px-3 py-3 text-left text-gray-600 font-semibold">Status</th>
                            <th class="px-3 py-3 text-left text-gray-600 font-semibold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="monitor-table-body">
                        <tr><td colspan="12" class="text-center py-8 text-gray-400">Pilih ruang ujian untuk monitoring</td></tr>
                    </tbody>
                </table>
            </div>
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
let monitoringInterval = null;
let monitoringAllData = [];
let monitorSelectedIds = [];
let allKelasData = [];
let allGuruData = [];

// ===================== LOAD DATA TABLE =====================
function loadData() {
    const perPage = document.getElementById('per-page').value;
    const url = `/admin/ajax/ruang_ujian_handler.php?action=get_all&page=${currentPage}&per_page=${perPage}`;
    fetch(url)
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') renderTable(res.data, res.total, res.per_page, res.page);
        });
}

function renderTable(data, total, perPage, page) {
    const tbody = document.getElementById('table-body');
    const info  = document.getElementById('info-data');
    const pag   = document.getElementById('pagination-area');

    if (!data || data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9" class="text-center py-8 text-gray-400">Tidak ada data</td></tr>';
        info.textContent = 'Menampilkan 0 dari 0 data';
        pag.innerHTML = '';
        return;
    }

    const now  = new Date();
    const start = perPage === 'all' ? 1 : (page - 1) * parseInt(perPage) + 1;
    const end   = perPage === 'all' ? total : Math.min(page * parseInt(perPage), total);
    info.textContent = `Menampilkan ${start}-${end} dari ${total} data`;

    tbody.innerHTML = data.map((row, idx) => {
        const mulai   = new Date(row.tanggal_mulai);
        const selesai = new Date(row.tanggal_selesai);
        let statusBadge;
        if (now < mulai) {
            statusBadge = '<span class="bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full text-xs font-medium">Belum</span>';
        } else if (now >= mulai && now <= selesai) {
            statusBadge = '<span class="bg-green-100 text-green-700 px-2 py-0.5 rounded-full text-xs font-medium">Aktif</span>';
        } else {
            statusBadge = '<span class="bg-red-100 text-red-700 px-2 py-0.5 rounded-full text-xs font-medium">Selesai</span>';
        }
        const kelasList = row.kelas_list ? row.kelas_list.split(',').map(k => `<span class="bg-blue-50 text-blue-600 px-1.5 py-0.5 rounded text-xs">${escHtml(k)}</span>`).join(' ') : '-';
        const guruName  = escHtml(row.guru_nama || 'Admin');
        const no = perPage === 'all' ? idx + 1 : start + idx;

        return `<tr class="border-b border-gray-100 hover:bg-gray-50" data-id="${row.id}">
            <td class="px-4 py-3"><input type="checkbox" class="row-check rounded" value="${row.id}" onchange="updateSelected()"></td>
            <td class="px-4 py-3 text-gray-600">${no}</td>
            <td class="px-4 py-3">
                <div class="font-medium text-gray-800">${escHtml(row.nama_ruang)}</div>
                <div class="flex flex-wrap gap-1 mt-1">${kelasList}</div>
            </td>
            <td class="px-4 py-3"><span class="bg-purple-100 text-purple-700 px-2 py-0.5 rounded-full text-xs font-mono font-bold">${escHtml(row.token)}</span></td>
            <td class="px-4 py-3 text-gray-600">${guruName}</td>
            <td class="px-4 py-3 text-gray-600">${escHtml(row.nama_soal || '-')}</td>
            <td class="px-4 py-3 text-xs text-gray-500">
                <div>${formatDatetime(row.tanggal_mulai)}</div>
                <div class="text-gray-400">s/d</div>
                <div>${formatDatetime(row.tanggal_selesai)}</div>
            </td>
            <td class="px-4 py-3">${statusBadge}</td>
            <td class="px-4 py-3">
                <div class="flex items-center gap-1">
                    <button onclick="openMonitoring(${row.id}, '${escHtml(row.nama_ruang)}', '${escHtml(row.token)}')" class="text-blue-600 hover:text-blue-800 bg-blue-50 hover:bg-blue-100 px-2 py-1 rounded text-xs flex items-center gap-1" title="Monitoring">
                        <i data-lucide="activity" class="w-3.5 h-3.5"></i> Monitor
                    </button>
                    <button onclick="openModalEdit(${row.id})" class="text-yellow-500 hover:text-yellow-700" title="Edit">
                        <i data-lucide="pencil" class="w-4 h-4"></i>
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
            pagHtml += `<button onclick="changePage(${i})" class="px-3 py-1 rounded border text-sm ${i===page?'bg-blue-700 text-white border-blue-700':'hover:bg-gray-50'}">${i}</button>`;
        }
        if (page < totalPages) pagHtml += `<button onclick="changePage(${page+1})" class="px-3 py-1 rounded border text-sm hover:bg-gray-50">&#8250;</button>`;
        pagHtml += '</div>';
        pag.innerHTML = pagHtml;
    } else {
        pag.innerHTML = '';
    }
    lucide.createIcons();
}

function changePage(p) { currentPage = p; loadData(); }

function updateSelected() {
    selectedIds = Array.from(document.querySelectorAll('.row-check:checked')).map(c => c.value);
    document.getElementById('btn-hapus-terpilih').classList.toggle('hidden', selectedIds.length === 0);
    const all = document.querySelectorAll('.row-check');
    document.getElementById('check-all').indeterminate = selectedIds.length > 0 && selectedIds.length < all.length;
    document.getElementById('check-all').checked = selectedIds.length > 0 && selectedIds.length === all.length;
}

function toggleAll(cb) {
    document.querySelectorAll('.row-check').forEach(c => c.checked = cb.checked);
    updateSelected();
}

// ===================== GURU & BANK SOAL LOADING =====================
function loadGuruList() {
    return fetch('/admin/ajax/ruang_ujian_handler.php?action=get_guru_list')
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                allGuruData = res.data;
                ['tambah-guru-id', 'edit-guru-id'].forEach(id => {
                    const sel = document.getElementById(id);
                    const cur = sel.value;
                    sel.innerHTML = '<option value="">-- Pilih Guru --</option><option value="admin">-- Admin --</option>';
                    res.data.forEach(g => {
                        const opt = document.createElement('option');
                        opt.value = g.id; opt.textContent = g.nama;
                        sel.appendChild(opt);
                    });
                    if (cur) sel.value = cur;
                });
            }
        });
}

function onGuruChange(prefix) {
    const guruId = document.getElementById(`${prefix}-guru-id`).value;
    const bankSel = document.getElementById(`${prefix}-bank-soal-id`);
    bankSel.innerHTML = '<option value="">Memuat...</option>';
    if (!guruId) {
        bankSel.innerHTML = '<option value="">-- Pilih Guru Terlebih Dahulu --</option>';
        return;
    }
    fetch(`/admin/ajax/ruang_ujian_handler.php?action=get_bank_soal_by_guru&guru_id=${guruId}`)
        .then(r => r.json())
        .then(res => {
            bankSel.innerHTML = '<option value="">-- Pilih Bank Soal --</option>';
            if (res.status === 'success' && res.data.length > 0) {
                res.data.forEach(b => {
                    const opt = document.createElement('option');
                    opt.value = b.id; opt.textContent = b.nama_soal;
                    bankSel.appendChild(opt);
                });
            } else {
                bankSel.innerHTML = '<option value="">Tidak ada bank soal</option>';
            }
        });
}

function loadKelasList(prefix, checkedIds = []) {
    fetch('/admin/ajax/ruang_ujian_handler.php?action=get_kelas_list')
        .then(r => r.json())
        .then(res => {
            const container = document.getElementById(`${prefix}-kelas-list`);
            if (res.status === 'success' && res.data.length > 0) {
                container.innerHTML = res.data.map(k => `
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" class="${prefix}-kelas-check rounded text-blue-600" value="${k.id}" ${checkedIds.includes(k.id) ? 'checked' : ''}>
                        <span class="text-sm text-gray-700">${escHtml(k.nama_kelas)}</span>
                    </label>`).join('');
            } else {
                container.innerHTML = '<p class="text-gray-400 text-xs col-span-3">Tidak ada kelas</p>';
            }
        });
}

// ===================== MODAL TAMBAH =====================
function openModalTambah() {
    document.getElementById('tambah-nama-ruang').value = '';
    document.getElementById('tambah-guru-id').value = '';
    document.getElementById('tambah-bank-soal-id').innerHTML = '<option value="">-- Pilih Guru Terlebih Dahulu --</option>';
    document.getElementById('tambah-waktu-hentikan').value = '';
    document.getElementById('tambah-batas-keluar').value = '3';
    document.getElementById('tambah-tanggal-mulai').value = '';
    document.getElementById('tambah-tanggal-selesai').value = '';
    document.getElementById('tambah-acak-soal').checked = false;
    document.getElementById('tambah-acak-jawaban').checked = false;
    document.getElementById('tambah-token').value = '';
    loadGuruList();
    loadKelasList('tambah');
    document.getElementById('modal-tambah').classList.remove('hidden');
    lucide.createIcons();
}

function closeModalTambah() { document.getElementById('modal-tambah').classList.add('hidden'); }

function saveRuangUjian() {
    const namaRuang = document.getElementById('tambah-nama-ruang').value.trim();
    const guruId    = document.getElementById('tambah-guru-id').value;
    const bankSoal  = document.getElementById('tambah-bank-soal-id').value;
    const waktu     = document.getElementById('tambah-waktu-hentikan').value;
    const batas     = document.getElementById('tambah-batas-keluar').value;
    const tMulai    = document.getElementById('tambah-tanggal-mulai').value;
    const tSelesai  = document.getElementById('tambah-tanggal-selesai').value;
    const token     = document.getElementById('tambah-token').value.trim();
    const kelasIds  = Array.from(document.querySelectorAll('.tambah-kelas-check:checked')).map(c => c.value);

    if (!namaRuang || !guruId || !bankSoal || !waktu || !tMulai || !tSelesai || !token) {
        Swal.fire({icon:'warning', title:'Peringatan', text:'Semua field wajib diisi', confirmButtonColor:'#1d4ed8'}); return;
    }
    if (kelasIds.length === 0) {
        Swal.fire({icon:'warning', title:'Peringatan', text:'Pilih minimal satu kelas', confirmButtonColor:'#1d4ed8'}); return;
    }

    const fd = new FormData();
    fd.append('action', 'add');
    fd.append('nama_ruang', namaRuang);
    fd.append('guru_id', guruId);
    fd.append('bank_soal_id', bankSoal);
    fd.append('waktu_hentikan', waktu);
    fd.append('batas_keluar', batas);
    fd.append('tanggal_mulai', tMulai);
    fd.append('tanggal_selesai', tSelesai);
    fd.append('acak_soal', document.getElementById('tambah-acak-soal').checked ? '1' : '0');
    fd.append('acak_jawaban', document.getElementById('tambah-acak-jawaban').checked ? '1' : '0');
    fd.append('token', token.toUpperCase());
    kelasIds.forEach(id => fd.append('kelas_ids[]', id));

    fetch('/admin/ajax/ruang_ujian_handler.php', {method:'POST', body:fd})
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                Swal.fire({icon:'success', title:'Berhasil', text:res.message, timer:1500, showConfirmButton:false});
                closeModalTambah(); loadData();
            } else {
                Swal.fire({icon:'error', title:'Gagal', text:res.message, confirmButtonColor:'#1d4ed8'});
            }
        });
}

// ===================== MODAL EDIT =====================
function openModalEdit(id) {
    fetch(`/admin/ajax/ruang_ujian_handler.php?action=get_detail&id=${id}`)
        .then(r => r.json())
        .then(res => {
            if (res.status !== 'success') { Swal.fire({icon:'error', text:'Gagal memuat data'}); return; }
            const d = res.data;
            document.getElementById('edit-id').value = d.id;
            document.getElementById('edit-nama-ruang').value = d.nama_ruang;
            document.getElementById('edit-waktu-hentikan').value = d.waktu_hentikan;
            document.getElementById('edit-batas-keluar').value = d.batas_keluar;
            document.getElementById('edit-tanggal-mulai').value = d.tanggal_mulai ? d.tanggal_mulai.replace(' ', 'T').substring(0, 16) : '';
            document.getElementById('edit-tanggal-selesai').value = d.tanggal_selesai ? d.tanggal_selesai.replace(' ', 'T').substring(0, 16) : '';
            document.getElementById('edit-acak-soal').checked = d.acak_soal == 1;
            document.getElementById('edit-acak-jawaban').checked = d.acak_jawaban == 1;
            document.getElementById('edit-token').value = d.token;

            const guruVal = d.admin_id ? 'admin' : (d.guru_id ? d.guru_id : '');
            loadGuruList().then(() => {
                document.getElementById('edit-guru-id').value = guruVal;
                fetch(`/admin/ajax/ruang_ujian_handler.php?action=get_bank_soal_by_guru&guru_id=${guruVal}`)
                    .then(r => r.json())
                    .then(bRes => {
                        const bankSel = document.getElementById('edit-bank-soal-id');
                        bankSel.innerHTML = '<option value="">-- Pilih Bank Soal --</option>';
                        if (bRes.status === 'success') {
                            bRes.data.forEach(b => {
                                const opt = document.createElement('option');
                                opt.value = b.id; opt.textContent = b.nama_soal;
                                bankSel.appendChild(opt);
                            });
                        }
                        bankSel.value = d.bank_soal_id;
                    });
            });

            const checkedKelas = res.kelas_ids || [];
            loadKelasList('edit', checkedKelas);
            document.getElementById('modal-edit').classList.remove('hidden');
            lucide.createIcons();
        });
}

function closeModalEdit() { document.getElementById('modal-edit').classList.add('hidden'); }

function updateRuangUjian() {
    const id       = document.getElementById('edit-id').value;
    const namaRuang = document.getElementById('edit-nama-ruang').value.trim();
    const guruId   = document.getElementById('edit-guru-id').value;
    const bankSoal = document.getElementById('edit-bank-soal-id').value;
    const waktu    = document.getElementById('edit-waktu-hentikan').value;
    const batas    = document.getElementById('edit-batas-keluar').value;
    const tMulai   = document.getElementById('edit-tanggal-mulai').value;
    const tSelesai = document.getElementById('edit-tanggal-selesai').value;
    const token    = document.getElementById('edit-token').value.trim();
    const kelasIds = Array.from(document.querySelectorAll('.edit-kelas-check:checked')).map(c => c.value);

    if (!namaRuang || !guruId || !bankSoal || !waktu || !tMulai || !tSelesai || !token) {
        Swal.fire({icon:'warning', text:'Semua field wajib diisi', confirmButtonColor:'#1d4ed8'}); return;
    }
    if (kelasIds.length === 0) {
        Swal.fire({icon:'warning', text:'Pilih minimal satu kelas', confirmButtonColor:'#1d4ed8'}); return;
    }

    const fd = new FormData();
    fd.append('action', 'edit');
    fd.append('id', id);
    fd.append('nama_ruang', namaRuang);
    fd.append('guru_id', guruId);
    fd.append('bank_soal_id', bankSoal);
    fd.append('waktu_hentikan', waktu);
    fd.append('batas_keluar', batas);
    fd.append('tanggal_mulai', tMulai);
    fd.append('tanggal_selesai', tSelesai);
    fd.append('acak_soal', document.getElementById('edit-acak-soal').checked ? '1' : '0');
    fd.append('acak_jawaban', document.getElementById('edit-acak-jawaban').checked ? '1' : '0');
    fd.append('token', token.toUpperCase());
    kelasIds.forEach(kid => fd.append('kelas_ids[]', kid));

    fetch('/admin/ajax/ruang_ujian_handler.php', {method:'POST', body:fd})
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                Swal.fire({icon:'success', text:res.message, timer:1500, showConfirmButton:false});
                closeModalEdit(); loadData();
            } else {
                Swal.fire({icon:'error', text:res.message, confirmButtonColor:'#1d4ed8'});
            }
        });
}

// ===================== DELETE =====================
function deleteRuangUjian(id) {
    Swal.fire({
        title:'Hapus Ruang Ujian?', text:'Data ujian siswa terkait juga akan dihapus.', icon:'warning',
        showCancelButton:true, confirmButtonColor:'#ef4444', cancelButtonColor:'#6b7280',
        confirmButtonText:'Hapus', cancelButtonText:'Batal'
    }).then(r => {
        if (!r.isConfirmed) return;
        const fd = new FormData(); fd.append('action','delete'); fd.append('id', id);
        fetch('/admin/ajax/ruang_ujian_handler.php', {method:'POST', body:fd})
            .then(r => r.json())
            .then(res => {
                if (res.status === 'success') { Swal.fire({icon:'success', text:res.message, timer:1500, showConfirmButton:false}); loadData(); }
                else Swal.fire({icon:'error', text:res.message, confirmButtonColor:'#1d4ed8'});
            });
    });
}

function hapusTerpilih() {
    if (selectedIds.length === 0) return;
    Swal.fire({
        title:`Hapus ${selectedIds.length} Ruang Ujian?`, icon:'warning', showCancelButton:true,
        confirmButtonColor:'#ef4444', cancelButtonColor:'#6b7280', confirmButtonText:'Hapus', cancelButtonText:'Batal'
    }).then(r => {
        if (!r.isConfirmed) return;
        const fd = new FormData(); fd.append('action','delete_multiple');
        selectedIds.forEach(id => fd.append('ids[]', id));
        fetch('/admin/ajax/ruang_ujian_handler.php', {method:'POST', body:fd})
            .then(r => r.json())
            .then(res => {
                if (res.status === 'success') { Swal.fire({icon:'success', text:res.message, timer:1500, showConfirmButton:false}); loadData(); }
                else Swal.fire({icon:'error', text:res.message, confirmButtonColor:'#1d4ed8'});
            });
    });
}

// ===================== TOKEN GENERATOR =====================
function generateToken(targetId) {
    fetch('/admin/ajax/ruang_ujian_handler.php?action=generate_token')
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') document.getElementById(targetId).value = res.token;
        });
}

// ===================== MONITORING =====================
function openMonitoring(ruangId, namaRuang, token) {
    monitoringRuangId = ruangId;
    document.getElementById('monitoring-title').textContent = namaRuang;
    document.getElementById('monitoring-token-badge').textContent = token;
    document.getElementById('btn-export-nilai').href = `/admin/ajax/export_nilai.php?ruang_id=${ruangId}`;
    document.getElementById('btn-export-analisis').href = `/admin/ajax/export_analisis.php?ruang_id=${ruangId}`;
    document.getElementById('monitor-search').value = '';
    document.getElementById('monitor-filter-kelas').innerHTML = '<option value="">Semua Kelas</option>';

    fetch('/admin/ajax/ruang_ujian_handler.php?action=get_kelas_list')
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                res.data.forEach(k => {
                    const opt = document.createElement('option');
                    opt.value = k.id; opt.textContent = k.nama_kelas;
                    document.getElementById('monitor-filter-kelas').appendChild(opt);
                });
            }
        });

    document.getElementById('modal-monitoring').classList.remove('hidden');
    loadMonitoringData();
    if (monitoringInterval) clearInterval(monitoringInterval);
    monitoringInterval = setInterval(loadMonitoringData, 10000);
    lucide.createIcons();
}

function closeModalMonitoring() {
    document.getElementById('modal-monitoring').classList.add('hidden');
    if (monitoringInterval) { clearInterval(monitoringInterval); monitoringInterval = null; }
    monitoringRuangId = null;
}

function loadMonitoringData() {
    if (!monitoringRuangId) return;
    const kelasId = document.getElementById('monitor-filter-kelas').value;
    let url = `/admin/ajax/ruang_ujian_handler.php?action=get_monitoring_data&ruang_id=${monitoringRuangId}`;
    if (kelasId) url += `&kelas_id=${kelasId}`;

    fetch(url)
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                monitoringAllData = res.data;
                updateStats(res.data);
                renderMonitoringTable(res.data);
            }
        });
}

function updateStats(data) {
    document.getElementById('stat-total').textContent = data.length;
    document.getElementById('stat-belum').textContent = data.filter(d => d.status === 'belum').length;
    document.getElementById('stat-sedang').textContent = data.filter(d => d.status === 'sedang').length;
    document.getElementById('stat-selesai').textContent = data.filter(d => d.status === 'selesai').length;
}

function filterMonitoringTable() {
    const q = document.getElementById('monitor-search').value.toLowerCase();
    const filtered = monitoringAllData.filter(d =>
        d.nama.toLowerCase().includes(q) || d.nisn.toLowerCase().includes(q)
    );
    renderMonitoringTable(filtered);
}

function renderMonitoringTable(data) {
    const tbody = document.getElementById('monitor-table-body');
    if (!data || data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="12" class="text-center py-8 text-gray-400">Tidak ada data</td></tr>';
        return;
    }
    tbody.innerHTML = data.map((row, idx) => {
        let statusBadge;
        if (row.status === 'belum') statusBadge = '<span class="bg-red-100 text-red-600 px-2 py-0.5 rounded-full text-xs">Belum</span>';
        else if (row.status === 'sedang') statusBadge = '<span class="bg-orange-100 text-orange-600 px-2 py-0.5 rounded-full text-xs">Sedang</span>';
        else statusBadge = '<span class="bg-green-100 text-green-600 px-2 py-0.5 rounded-full text-xs">Selesai</span>';

        return `<tr class="border-b border-gray-100 hover:bg-gray-50">
            <td class="px-3 py-2"><input type="checkbox" class="monitor-check rounded" value="${row.ujian_siswa_id}" onchange="updateMonitorSelected()"></td>
            <td class="px-3 py-2 text-gray-600">${idx+1}</td>
            <td class="px-3 py-2 font-medium">${escHtml(row.nama)}</td>
            <td class="px-3 py-2 text-gray-600 font-mono text-xs">${escHtml(row.nisn)}</td>
            <td class="px-3 py-2 text-gray-600">${escHtml(row.nama_kelas || '-')}</td>
            <td class="px-3 py-2 text-xs text-gray-500">${row.waktu_mulai ? formatDatetime(row.waktu_mulai) : '-'}</td>
            <td class="px-3 py-2 text-xs text-gray-500">${row.waktu_selesai ? formatDatetime(row.waktu_selesai) : '-'}</td>
            <td class="px-3 py-2 text-center text-green-700 font-semibold">${row.jumlah_benar || 0}</td>
            <td class="px-3 py-2 text-center text-red-600 font-semibold">${row.jumlah_salah || 0}</td>
            <td class="px-3 py-2 text-center font-bold text-blue-700">${row.nilai !== null ? parseFloat(row.nilai).toFixed(0) : '-'}</td>
            <td class="px-3 py-2">${statusBadge}</td>
            <td class="px-3 py-2">
                <div class="flex items-center gap-1">
                    <button onclick="resetUjian(${row.ujian_siswa_id})" class="bg-red-100 text-red-600 hover:bg-red-200 px-2 py-1 rounded text-xs flex items-center gap-1">
                        <i data-lucide="rotate-ccw" class="w-3 h-3"></i> Reset
                    </button>
                    <div class="flex items-center gap-1">
                        <input type="number" id="tambah-waktu-${row.ujian_siswa_id}" min="1" value="5" class="w-14 border border-gray-300 rounded px-1 py-1 text-xs">
                        <button onclick="tambahWaktu(${row.ujian_siswa_id})" class="bg-orange-100 text-orange-600 hover:bg-orange-200 px-2 py-1 rounded text-xs flex items-center gap-1">
                            <i data-lucide="clock" class="w-3 h-3"></i> +Waktu
                        </button>
                    </div>
                </div>
            </td>
        </tr>`;
    }).join('');
    lucide.createIcons();
}

function updateMonitorSelected() {
    monitorSelectedIds = Array.from(document.querySelectorAll('.monitor-check:checked')).map(c => c.value);
    document.getElementById('btn-bulk-reset').classList.toggle('hidden', monitorSelectedIds.length === 0);
    const all = document.querySelectorAll('.monitor-check');
    document.getElementById('monitor-check-all').checked = monitorSelectedIds.length > 0 && monitorSelectedIds.length === all.length;
    document.getElementById('monitor-check-all').indeterminate = monitorSelectedIds.length > 0 && monitorSelectedIds.length < all.length;
}

function toggleMonitorAll(cb) {
    document.querySelectorAll('.monitor-check').forEach(c => c.checked = cb.checked);
    updateMonitorSelected();
}

function resetUjian(ujianSiswaId) {
    Swal.fire({
        title:'Reset Ujian?', text:'Jawaban siswa akan dihapus dan status dikembalikan ke belum.', icon:'warning',
        showCancelButton:true, confirmButtonColor:'#ef4444', cancelButtonColor:'#6b7280',
        confirmButtonText:'Reset', cancelButtonText:'Batal'
    }).then(r => {
        if (!r.isConfirmed) return;
        const fd = new FormData(); fd.append('action','reset_ujian'); fd.append('ujian_siswa_id', ujianSiswaId);
        fetch('/admin/ajax/ruang_ujian_handler.php', {method:'POST', body:fd})
            .then(r => r.json())
            .then(res => {
                if (res.status === 'success') { Swal.fire({icon:'success', text:res.message, timer:1200, showConfirmButton:false}); loadMonitoringData(); }
                else Swal.fire({icon:'error', text:res.message, confirmButtonColor:'#1d4ed8'});
            });
    });
}

function bulkResetUjian() {
    if (monitorSelectedIds.length === 0) return;
    Swal.fire({
        title:`Reset ${monitorSelectedIds.length} Siswa?`, icon:'warning', showCancelButton:true,
        confirmButtonColor:'#ef4444', cancelButtonColor:'#6b7280', confirmButtonText:'Reset', cancelButtonText:'Batal'
    }).then(r => {
        if (!r.isConfirmed) return;
        Promise.all(monitorSelectedIds.map(id => {
            const fd = new FormData(); fd.append('action','reset_ujian'); fd.append('ujian_siswa_id', id);
            return fetch('/admin/ajax/ruang_ujian_handler.php', {method:'POST', body:fd}).then(r => r.json());
        })).then(() => {
            Swal.fire({icon:'success', text:'Reset berhasil', timer:1200, showConfirmButton:false});
            loadMonitoringData();
        });
    });
}

function tambahWaktu(ujianSiswaId) {
    const menit = document.getElementById(`tambah-waktu-${ujianSiswaId}`).value;
    if (!menit || menit < 1) { Swal.fire({icon:'warning', text:'Masukkan jumlah menit yang valid', confirmButtonColor:'#1d4ed8'}); return; }
    const fd = new FormData();
    fd.append('action','tambah_waktu'); fd.append('ujian_siswa_id', ujianSiswaId); fd.append('menit', menit);
    fetch('/admin/ajax/ruang_ujian_handler.php', {method:'POST', body:fd})
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') { Swal.fire({icon:'success', text:res.message, timer:1200, showConfirmButton:false}); }
            else Swal.fire({icon:'error', text:res.message, confirmButtonColor:'#1d4ed8'});
        });
}

// ===================== HELPERS =====================
function formatDatetime(str) {
    if (!str) return '-';
    const d = new Date(str.replace(' ','T'));
    return d.toLocaleDateString('id-ID', {day:'2-digit',month:'short',year:'numeric'}) + ' ' +
           d.toLocaleTimeString('id-ID', {hour:'2-digit',minute:'2-digit'});
}

function escHtml(s) {
    return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

loadData();
</script>
</body>
</html>
