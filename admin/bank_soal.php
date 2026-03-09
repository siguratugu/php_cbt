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
    <title>Bank Soal - CBT MTsN 1 Mesuji</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
</head>
<body class="bg-gray-100 min-h-screen">
<div class="flex h-screen overflow-hidden">
    <?php include __DIR__ . '/../includes/sidebar_admin.php'; ?>
    <div class="flex-1 ml-64 overflow-y-auto pb-16">

        <!-- Page Header -->
        <div class="bg-white shadow-sm px-6 py-4 flex items-center justify-between sticky top-0 z-10">
            <div>
                <h1 class="text-xl font-bold text-gray-800">Bank Soal</h1>
                <p class="text-gray-500 text-sm">Kelola bank soal untuk ujian CBT</p>
            </div>
            <div class="flex items-center gap-2">
                <button id="btn-hapus-terpilih" onclick="hapusTerpilih()"
                    class="hidden bg-red-500 hover:bg-red-600 text-white text-sm px-4 py-2 rounded-lg flex items-center gap-2 transition-colors">
                    <i data-lucide="trash-2" class="w-4 h-4"></i> Hapus Terpilih
                </button>
                <button onclick="openModalTambah()"
                    class="bg-blue-700 hover:bg-blue-800 text-white text-sm px-4 py-2 rounded-lg flex items-center gap-2 transition-colors">
                    <i data-lucide="plus" class="w-4 h-4"></i> Tambah Soal
                </button>
            </div>
        </div>

        <div class="p-6">
            <div class="bg-white rounded-xl shadow-sm p-4">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <label class="text-sm text-gray-600">Tampilkan:</label>
                        <select id="per-page" onchange="currentPage=1; loadData()"
                            class="border border-gray-300 rounded-lg text-sm px-3 py-1.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
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
                                <th class="px-4 py-3 text-left w-10">
                                    <input type="checkbox" id="check-all" onchange="toggleAll(this)"
                                        class="rounded border-gray-300 text-blue-600">
                                </th>
                                <th class="px-4 py-3 text-left text-gray-600 font-semibold w-12">No</th>
                                <th class="px-4 py-3 text-left text-gray-600 font-semibold">Guru</th>
                                <th class="px-4 py-3 text-left text-gray-600 font-semibold">Nama Soal</th>
                                <th class="px-4 py-3 text-left text-gray-600 font-semibold">Mapel</th>
                                <th class="px-4 py-3 text-center text-gray-600 font-semibold">Waktu (menit)</th>
                                <th class="px-4 py-3 text-center text-gray-600 font-semibold">Jumlah Soal</th>
                                <th class="px-4 py-3 text-left text-gray-600 font-semibold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="table-body">
                            <tr><td colspan="8" class="text-center py-10 text-gray-400">Memuat data...</td></tr>
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
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[92vh] overflow-y-auto">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between sticky top-0 bg-white rounded-t-2xl z-10">
            <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                <i data-lucide="plus-circle" class="w-5 h-5 text-blue-600"></i> Tambah Bank Soal
            </h2>
            <button onclick="closeModalTambah()" class="text-gray-400 hover:text-gray-700 transition-colors">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <div class="p-6 space-y-4">

            <!-- Pilih Pembuat -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Pilih Pembuat <span class="text-red-500">*</span>
                </label>
                <select id="add-guru" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="admin">-- Admin --</option>
                </select>
            </div>

            <!-- Nama Soal -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Nama Soal <span class="text-red-500">*</span>
                </label>
                <input type="text" id="add-nama-soal" placeholder="Contoh: UTS Matematika Semester 1"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>

            <!-- Pilih Mapel -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Pilih Mapel</label>
                <select id="add-mapel" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">-- Pilih Mapel --</option>
                </select>
            </div>

            <!-- Waktu -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Waktu Mengerjakan (menit) <span class="text-red-500">*</span>
                </label>
                <input type="number" id="add-waktu" min="1" value="60"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>

            <!-- Bobot Penilaian -->
            <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-sm font-semibold text-gray-700 flex items-center gap-1.5">
                        <i data-lucide="percent" class="w-4 h-4 text-blue-600"></i> Bobot Penilaian
                    </p>
                    <span id="total-bobot-info"
                        class="text-xs font-semibold px-2.5 py-1 rounded-full bg-green-100 text-green-700">Total: 100%</span>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Bobot PG (%)</label>
                        <input type="number" id="add-bobot-pg" min="0" max="100" value="100"
                            oninput="updateTotalBobot('add')"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Bobot Esai (%)</label>
                        <input type="number" id="add-bobot-esai" min="0" max="100" value="0"
                            oninput="updateTotalBobot('add')"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Bobot Menjodohkan (%)</label>
                        <input type="number" id="add-bobot-menjodohkan" min="0" max="100" value="0"
                            oninput="updateTotalBobot('add')"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Bobot Benar/Salah (%)</label>
                        <input type="number" id="add-bobot-bs" min="0" max="100" value="0"
                            oninput="updateTotalBobot('add')"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
                <p id="add-bobot-warning" class="text-red-500 text-xs mt-2 hidden flex items-center gap-1">
                    <i data-lucide="alert-circle" class="w-3.5 h-3.5"></i> Total bobot harus 100%
                </p>
            </div>
        </div>

        <div class="px-6 py-4 border-t border-gray-200 flex flex-col sm:flex-row justify-end gap-3">
            <button onclick="closeModalTambah()"
                class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded-lg transition-colors">
                Batal
            </button>
            <button onclick="saveOnly()"
                class="bg-blue-700 hover:bg-blue-800 text-white px-5 py-2 rounded-lg text-sm font-medium flex items-center justify-center gap-2 transition-colors">
                <i data-lucide="save" class="w-4 h-4"></i> Simpan Saja
            </button>
            <button onclick="saveAndCreate()"
                class="bg-green-600 hover:bg-green-700 text-white px-5 py-2 rounded-lg text-sm font-medium flex items-center justify-center gap-2 transition-colors">
                <i data-lucide="wand-2" class="w-4 h-4"></i> Simpan dan Buat Soal
            </button>
        </div>
    </div>
</div>

<!-- ==================== MODAL EDIT ==================== -->
<div id="modal-edit" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[92vh] overflow-y-auto">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between sticky top-0 bg-white rounded-t-2xl z-10">
            <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                <i data-lucide="pencil" class="w-5 h-5 text-blue-600"></i> Edit Bank Soal
            </h2>
            <button onclick="closeModalEdit()" class="text-gray-400 hover:text-gray-700 transition-colors">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <div class="p-6 space-y-4">
            <input type="hidden" id="edit-id">

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Pilih Pembuat <span class="text-red-500">*</span></label>
                <select id="edit-guru" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="admin">-- Admin --</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Soal <span class="text-red-500">*</span></label>
                <input type="text" id="edit-nama-soal"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Pilih Mapel</label>
                <select id="edit-mapel" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">-- Pilih Mapel --</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Waktu Mengerjakan (menit) <span class="text-red-500">*</span></label>
                <input type="number" id="edit-waktu" min="1"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-sm font-semibold text-gray-700 flex items-center gap-1.5">
                        <i data-lucide="percent" class="w-4 h-4 text-blue-600"></i> Bobot Penilaian
                    </p>
                    <span id="edit-total-bobot-info"
                        class="text-xs font-semibold px-2.5 py-1 rounded-full bg-gray-200 text-gray-600">Total: 0%</span>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Bobot PG (%)</label>
                        <input type="number" id="edit-bobot-pg" min="0" max="100" oninput="updateTotalBobot('edit')"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Bobot Esai (%)</label>
                        <input type="number" id="edit-bobot-esai" min="0" max="100" oninput="updateTotalBobot('edit')"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Bobot Menjodohkan (%)</label>
                        <input type="number" id="edit-bobot-menjodohkan" min="0" max="100" oninput="updateTotalBobot('edit')"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Bobot Benar/Salah (%)</label>
                        <input type="number" id="edit-bobot-bs" min="0" max="100" oninput="updateTotalBobot('edit')"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
                <p id="edit-bobot-warning" class="text-red-500 text-xs mt-2 hidden flex items-center gap-1">
                    <i data-lucide="alert-circle" class="w-3.5 h-3.5"></i> Total bobot harus 100%
                </p>
            </div>
        </div>

        <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-3">
            <button onclick="closeModalEdit()"
                class="px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">Batal</button>
            <button onclick="updateBankSoal()"
                class="bg-blue-700 hover:bg-blue-800 text-white px-5 py-2 rounded-lg text-sm font-medium flex items-center gap-2 transition-colors">
                <i data-lucide="save" class="w-4 h-4"></i> Update
            </button>
        </div>
    </div>
</div>

<footer class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 py-2 text-center text-xs text-gray-500 z-10">
    &copy; <?= date('Y') ?> | Develop by Asmin Pratama
</footer>

<script>
let currentPage = 1;
let selectedIds = [];

/* ===================== DATA LOADING ===================== */
function loadData() {
    const perPage = document.getElementById('per-page').value;
    fetch(`/admin/ajax/bank_soal_handler.php?action=get_all&page=${currentPage}&per_page=${perPage}`)
        .then(r => r.json())
        .then(res => { if (res.status === 'success') renderTable(res.data, res.total, res.per_page, res.page); })
        .catch(() => { document.getElementById('table-body').innerHTML = '<tr><td colspan="8" class="text-center py-8 text-red-400">Gagal memuat data</td></tr>'; });
}

function renderTable(data, total, perPage, page) {
    const tbody = document.getElementById('table-body');
    const info  = document.getElementById('info-data');
    const pag   = document.getElementById('pagination-area');

    if (!data.length) {
        tbody.innerHTML = `<tr><td colspan="8" class="text-center py-14 text-gray-400">
            <div class="flex flex-col items-center gap-3">
                <i data-lucide="inbox" class="w-12 h-12 text-gray-300"></i>
                <p class="font-medium">Belum ada bank soal</p>
                <p class="text-xs">Klik "Tambah Soal" untuk mulai membuat bank soal baru</p>
            </div></td></tr>`;
        info.textContent = 'Menampilkan 0 dari 0 data';
        pag.innerHTML = '';
        lucide.createIcons();
        return;
    }

    const start = (perPage === 'all') ? 1 : (page - 1) * parseInt(perPage) + 1;
    const end   = (perPage === 'all') ? total : Math.min(page * parseInt(perPage), total);
    info.textContent = `Menampilkan ${start}–${end} dari ${total} data`;

    tbody.innerHTML = data.map((row, idx) => {
        const no    = (perPage === 'all') ? (idx + 1) : ((page - 1) * parseInt(perPage) + idx + 1);
        const guru  = row.nama_guru
            ? `<span class="flex items-center gap-1.5 text-gray-700"><i data-lucide="user" class="w-3.5 h-3.5 text-blue-500"></i>${escHtml(row.nama_guru)}</span>`
            : `<span class="inline-flex items-center gap-1 px-2 py-0.5 bg-purple-100 text-purple-700 rounded-full text-xs font-semibold"><i data-lucide="shield" class="w-3 h-3"></i>Admin</span>`;
        const mapel = row.nama_mapel
            ? `<span class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full text-xs font-medium">${escHtml(row.nama_mapel)}</span>`
            : `<span class="text-gray-400 text-xs">—</span>`;
        return `<tr class="border-b border-gray-100 hover:bg-blue-50/30 transition-colors">
            <td class="px-4 py-3"><input type="checkbox" class="row-check rounded border-gray-300 text-blue-600" value="${row.id}" onchange="updateSelected()"></td>
            <td class="px-4 py-3 text-gray-500 font-medium text-xs">${no}</td>
            <td class="px-4 py-3">${guru}</td>
            <td class="px-4 py-3">
                <div class="font-semibold text-gray-800">${escHtml(row.nama_soal)}</div>
                <div class="text-xs text-gray-400 mt-0.5">ID: #${row.id}</div>
            </td>
            <td class="px-4 py-3">${mapel}</td>
            <td class="px-4 py-3 text-center">
                <span class="inline-flex items-center gap-1 text-gray-600 text-xs font-medium">
                    <i data-lucide="clock" class="w-3.5 h-3.5 text-orange-400"></i>${row.waktu_mengerjakan}
                </span>
            </td>
            <td class="px-4 py-3 text-center">
                <span class="px-2.5 py-1 bg-green-100 text-green-700 rounded-full text-xs font-bold">${row.jumlah_soal}</span>
            </td>
            <td class="px-4 py-3">
                <div class="flex items-center gap-1.5">
                    <a href="/admin/buat_soal.php?id=${row.id}"
                        class="inline-flex items-center gap-1 bg-green-500 hover:bg-green-600 text-white text-xs px-2.5 py-1.5 rounded-lg transition-colors font-medium">
                        <i data-lucide="file-pen" class="w-3.5 h-3.5"></i> Buat Soal
                    </a>
                    <button onclick="openModalEdit(${row.id})"
                        class="inline-flex items-center gap-1 bg-blue-500 hover:bg-blue-600 text-white text-xs px-2.5 py-1.5 rounded-lg transition-colors font-medium">
                        <i data-lucide="pencil" class="w-3.5 h-3.5"></i> Edit
                    </button>
                    <button onclick="deleteBankSoal(${row.id}, '${escAttr(row.nama_soal)}')"
                        class="inline-flex items-center bg-red-500 hover:bg-red-600 text-white text-xs px-2 py-1.5 rounded-lg transition-colors">
                        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                    </button>
                </div>
            </td>
        </tr>`;
    }).join('');

    // Pagination
    if (perPage !== 'all') {
        const totalPages = Math.ceil(total / parseInt(perPage));
        let html = `<div class="text-xs text-gray-500">Halaman ${page} dari ${totalPages}</div><div class="flex items-center gap-1">`;
        if (page > 1) html += `<button onclick="changePage(${page - 1})" class="px-3 py-1.5 rounded-lg border border-gray-300 text-sm hover:bg-gray-50 transition-colors">‹</button>`;
        const start2 = Math.max(1, page - 2), end2 = Math.min(totalPages, page + 2);
        for (let i = start2; i <= end2; i++) {
            html += `<button onclick="changePage(${i})" class="px-3 py-1.5 rounded-lg border text-sm transition-colors ${i === page ? 'bg-blue-700 text-white border-blue-700' : 'border-gray-300 hover:bg-gray-50'}">${i}</button>`;
        }
        if (page < totalPages) html += `<button onclick="changePage(${page + 1})" class="px-3 py-1.5 rounded-lg border border-gray-300 text-sm hover:bg-gray-50 transition-colors">›</button>`;
        html += '</div>';
        pag.innerHTML = html;
    } else {
        pag.innerHTML = `<div class="text-xs text-gray-500">Menampilkan semua ${total} data</div>`;
    }
    lucide.createIcons();
}

function changePage(p) { currentPage = p; loadData(); }

function updateSelected() {
    selectedIds = Array.from(document.querySelectorAll('.row-check:checked')).map(c => c.value);
    document.getElementById('btn-hapus-terpilih').classList.toggle('hidden', selectedIds.length === 0);
    const all = document.querySelectorAll('.row-check');
    document.getElementById('check-all').checked = all.length > 0 && Array.from(all).every(c => c.checked);
    lucide.createIcons();
}

function toggleAll(cb) {
    document.querySelectorAll('.row-check').forEach(c => c.checked = cb.checked);
    updateSelected();
}

/* ===================== HELPERS ===================== */
function loadGuruList(targetId, selectedVal) {
    fetch('/admin/ajax/bank_soal_handler.php?action=get_guru_list')
        .then(r => r.json())
        .then(res => {
            if (res.status !== 'success') return;
            const sel = document.getElementById(targetId);
            sel.innerHTML = '<option value="admin">-- Admin --</option>' +
                res.data.map(g => `<option value="${g.id}"${String(selectedVal) === String(g.id) ? ' selected' : ''}>${escHtml(g.nama)}</option>`).join('');
        });
}

function loadMapelList(targetId, selectedVal) {
    fetch('/admin/ajax/bank_soal_handler.php?action=get_mapel_list')
        .then(r => r.json())
        .then(res => {
            if (res.status !== 'success') return;
            const sel = document.getElementById(targetId);
            sel.innerHTML = '<option value="">-- Pilih Mapel --</option>' +
                res.data.map(m => `<option value="${m.id}"${String(selectedVal) === String(m.id) ? ' selected' : ''}>${escHtml(m.nama_mapel)}</option>`).join('');
        });
}

function updateTotalBobot(prefix) {
    const pg   = parseFloat(document.getElementById(`${prefix}-bobot-pg`).value) || 0;
    const esai = parseFloat(document.getElementById(`${prefix}-bobot-esai`).value) || 0;
    const mj   = parseFloat(document.getElementById(`${prefix}-bobot-menjodohkan`).value) || 0;
    const bs   = parseFloat(document.getElementById(`${prefix}-bobot-bs`).value) || 0;
    const total = Math.round((pg + esai + mj + bs) * 100) / 100;
    const info  = document.getElementById(`${prefix === 'add' ? 'total' : 'edit-total'}-bobot-info`);
    const warn  = document.getElementById(`${prefix}-bobot-warning`);
    info.textContent = `Total: ${total}%`;
    if (total === 100) {
        info.className = 'text-xs font-semibold px-2.5 py-1 rounded-full bg-green-100 text-green-700';
        warn.classList.add('hidden');
    } else {
        info.className = 'text-xs font-semibold px-2.5 py-1 rounded-full bg-red-100 text-red-600';
        warn.classList.remove('hidden');
    }
    lucide.createIcons();
}

function getFormData(prefix) {
    return {
        guru_id:           document.getElementById(`${prefix}-guru`).value,
        nama_soal:         document.getElementById(`${prefix}-nama-soal`).value.trim(),
        mapel_id:          document.getElementById(`${prefix}-mapel`).value,
        waktu_mengerjakan: document.getElementById(`${prefix}-waktu`).value,
        bobot_pg:          document.getElementById(`${prefix}-bobot-pg`).value,
        bobot_esai:        document.getElementById(`${prefix}-bobot-esai`).value,
        bobot_menjodohkan: document.getElementById(`${prefix}-bobot-menjodohkan`).value,
        bobot_benar_salah: document.getElementById(`${prefix}-bobot-bs`).value,
    };
}

function validateFormData(data) {
    if (!data.nama_soal) {
        Swal.fire({ icon: 'warning', title: 'Perhatian', text: 'Nama soal wajib diisi!', confirmButtonColor: '#1d4ed8' });
        return false;
    }
    if (!data.waktu_mengerjakan || parseInt(data.waktu_mengerjakan) <= 0) {
        Swal.fire({ icon: 'warning', title: 'Perhatian', text: 'Waktu mengerjakan harus lebih dari 0!', confirmButtonColor: '#1d4ed8' });
        return false;
    }
    const total = (parseFloat(data.bobot_pg) || 0) + (parseFloat(data.bobot_esai) || 0)
                + (parseFloat(data.bobot_menjodohkan) || 0) + (parseFloat(data.bobot_benar_salah) || 0);
    if (Math.round(total * 100) !== 10000) {
        Swal.fire({ icon: 'warning', title: 'Bobot Tidak Valid', text: `Total bobot harus 100%, saat ini ${total}%`, confirmButtonColor: '#1d4ed8' });
        return false;
    }
    return true;
}

function buildFormData(action, data, extra) {
    const fd = new FormData();
    fd.append('action', action);
    Object.entries(data).forEach(([k, v]) => fd.append(k, v));
    if (extra) Object.entries(extra).forEach(([k, v]) => fd.append(k, v));
    return fd;
}

/* ===================== MODAL TAMBAH ===================== */
function openModalTambah() {
    document.getElementById('add-guru').innerHTML   = '<option value="admin">-- Admin --</option>';
    document.getElementById('add-mapel').innerHTML  = '<option value="">-- Pilih Mapel --</option>';
    document.getElementById('add-nama-soal').value  = '';
    document.getElementById('add-waktu').value      = '60';
    document.getElementById('add-bobot-pg').value   = '100';
    document.getElementById('add-bobot-esai').value = '0';
    document.getElementById('add-bobot-menjodohkan').value = '0';
    document.getElementById('add-bobot-bs').value   = '0';
    updateTotalBobot('add');
    loadGuruList('add-guru', '');
    loadMapelList('add-mapel', '');
    document.getElementById('modal-tambah').classList.remove('hidden');
    lucide.createIcons();
}

function closeModalTambah() {
    document.getElementById('modal-tambah').classList.add('hidden');
}

function doSave(data, onSuccess) {
    const fd = buildFormData('add', data);
    fetch('/admin/ajax/bank_soal_handler.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') { onSuccess(res); }
            else { Swal.fire({ icon: 'error', title: 'Gagal', text: res.message, confirmButtonColor: '#1d4ed8' }); }
        });
}

function saveOnly() {
    const data = getFormData('add');
    if (!validateFormData(data)) return;
    doSave(data, res => {
        Swal.fire({ icon: 'success', text: res.message, timer: 1500, showConfirmButton: false });
        closeModalTambah();
        loadData();
    });
}

function saveAndCreate() {
    const data = getFormData('add');
    if (!validateFormData(data)) return;
    doSave(data, res => {
        closeModalTambah();
        window.location.href = `/admin/buat_soal.php?id=${res.id}`;
    });
}

/* ===================== MODAL EDIT ===================== */
function openModalEdit(id) {
    fetch(`/admin/ajax/bank_soal_handler.php?action=get_single&id=${id}`)
        .then(r => r.json())
        .then(res => {
            if (res.status !== 'success') {
                Swal.fire({ icon: 'error', text: 'Data tidak ditemukan', confirmButtonColor: '#1d4ed8' });
                return;
            }
            const row = res.data;
            document.getElementById('edit-id').value              = row.id;
            document.getElementById('edit-nama-soal').value       = row.nama_soal;
            document.getElementById('edit-waktu').value           = row.waktu_mengerjakan;
            document.getElementById('edit-bobot-pg').value        = row.bobot_pg;
            document.getElementById('edit-bobot-esai').value      = row.bobot_esai;
            document.getElementById('edit-bobot-menjodohkan').value = row.bobot_menjodohkan;
            document.getElementById('edit-bobot-bs').value        = row.bobot_benar_salah;
            const guruVal = row.guru_id ? row.guru_id : 'admin';
            loadGuruList('edit-guru', guruVal);
            loadMapelList('edit-mapel', row.mapel_id || '');
            updateTotalBobot('edit');
            document.getElementById('modal-edit').classList.remove('hidden');
            lucide.createIcons();
        });
}

function closeModalEdit() {
    document.getElementById('modal-edit').classList.add('hidden');
}

function updateBankSoal() {
    const id   = document.getElementById('edit-id').value;
    const data = getFormData('edit');
    if (!validateFormData(data)) return;
    const fd = buildFormData('edit', data, { id });
    fetch('/admin/ajax/bank_soal_handler.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                Swal.fire({ icon: 'success', text: res.message, timer: 1500, showConfirmButton: false });
                closeModalEdit();
                loadData();
            } else {
                Swal.fire({ icon: 'error', text: res.message, confirmButtonColor: '#1d4ed8' });
            }
        });
}

/* ===================== DELETE ===================== */
function deleteBankSoal(id, nama) {
    Swal.fire({
        title: 'Hapus Bank Soal?',
        html: `Bank soal <strong>"${escHtml(nama)}"</strong> beserta semua soal di dalamnya akan dihapus permanen.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: '<i class="swal2-icon-content"></i> Ya, Hapus',
        cancelButtonText: 'Batal'
    }).then(r => {
        if (!r.isConfirmed) return;
        const fd = new FormData();
        fd.append('action', 'delete');
        fd.append('id', id);
        fetch('/admin/ajax/bank_soal_handler.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(res => {
                if (res.status === 'success') {
                    Swal.fire({ icon: 'success', text: res.message, timer: 1500, showConfirmButton: false });
                    loadData();
                } else {
                    Swal.fire({ icon: 'error', text: res.message, confirmButtonColor: '#1d4ed8' });
                }
            });
    });
}

function hapusTerpilih() {
    if (!selectedIds.length) return;
    Swal.fire({
        title: `Hapus ${selectedIds.length} Bank Soal?`,
        text: 'Semua soal di dalamnya juga akan terhapus permanen!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, Hapus Semua',
        cancelButtonText: 'Batal'
    }).then(r => {
        if (!r.isConfirmed) return;
        const fd = new FormData();
        fd.append('action', 'delete_multiple');
        selectedIds.forEach(id => fd.append('ids[]', id));
        fetch('/admin/ajax/bank_soal_handler.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(res => {
                if (res.status === 'success') {
                    Swal.fire({ icon: 'success', text: res.message, timer: 1500, showConfirmButton: false });
                    selectedIds = [];
                    document.getElementById('check-all').checked = false;
                    loadData();
                } else {
                    Swal.fire({ icon: 'error', text: res.message, confirmButtonColor: '#1d4ed8' });
                }
            });
    });
}

/* ===================== UTILS ===================== */
function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function escAttr(s) {
    return String(s).replace(/\\/g,'\\\\').replace(/'/g,"\\'").replace(/"/g,'&quot;');
}

/* ===================== INIT ===================== */
updateTotalBobot('add');
loadData();
</script>
</body>
</html>
