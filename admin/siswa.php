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
    <title>Data Siswa - CBT MTsN 1 Mesuji</title>
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
                <h1 class="text-xl font-bold text-gray-800">Data Siswa</h1>
                <p class="text-gray-500 text-sm">Kelola data siswa</p>
            </div>
            <div class="flex items-center gap-2">
                <button id="btn-reset-terpilih" onclick="resetPasswordTerpilih()" class="hidden bg-yellow-500 hover:bg-yellow-600 text-white text-sm px-4 py-2 rounded-lg flex items-center gap-1 transition-colors">
                    <i data-lucide="key-round" class="w-4 h-4"></i> Reset Password Terpilih
                </button>
                <button id="btn-hapus-terpilih" onclick="hapusTerpilih()" class="hidden bg-red-500 hover:bg-red-600 text-white text-sm px-4 py-2 rounded-lg flex items-center gap-1 transition-colors">
                    <i data-lucide="trash-2" class="w-4 h-4"></i> Hapus Terpilih
                </button>
                <button onclick="openModalImport()" class="bg-emerald-600 hover:bg-emerald-700 text-white text-sm px-4 py-2 rounded-lg flex items-center gap-1 transition-colors">
                    <i data-lucide="upload" class="w-4 h-4"></i> Import Siswa
                </button>
                <button onclick="openModalTambah()" class="bg-blue-700 hover:bg-blue-800 text-white text-sm px-4 py-2 rounded-lg flex items-center gap-1 transition-colors">
                    <i data-lucide="plus" class="w-4 h-4"></i> Tambah Data
                </button>
            </div>
        </div>

        <div class="p-6">
            <div class="bg-white rounded-xl shadow-sm p-4">
                <!-- Toolbar -->
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <label class="text-sm text-gray-600">Tampilkan:</label>
                        <select id="per-page" onchange="loadData()" class="border border-gray-300 rounded-lg text-sm px-3 py-1.5 focus:ring-2 focus:ring-blue-500">
                            <option value="10">10</option>
                            <option value="32">32</option>
                            <option value="all">Semua</option>
                        </select>
                    </div>
                    <div class="text-sm text-gray-500" id="info-data">Memuat data...</div>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200">
                                <th class="px-4 py-3 text-left w-10">
                                    <input type="checkbox" id="check-all" onchange="toggleAll(this)" class="rounded border-gray-300">
                                </th>
                                <th class="px-4 py-3 text-left text-gray-600 font-semibold w-12">No</th>
                                <th class="px-4 py-3 text-left text-gray-600 font-semibold">Nama Siswa</th>
                                <th class="px-4 py-3 text-left text-gray-600 font-semibold">NISN</th>
                                <th class="px-4 py-3 text-left text-gray-600 font-semibold">Kelas</th>
                                <th class="px-4 py-3 text-left text-gray-600 font-semibold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="table-body">
                            <tr><td colspan="6" class="text-center py-10 text-gray-400">Memuat data...</td></tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="flex items-center justify-between mt-4 pt-4 border-t border-gray-100" id="pagination-area"></div>
            </div>
        </div>
    </div>
</div>

<!-- ── Modal Import ────────────────────────────────────────────────────────── -->
<div id="modal-import" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-800">Import Siswa</h2>
            <button onclick="closeModalImport()" class="text-gray-400 hover:text-gray-600 transition-colors">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <div class="p-6 space-y-4">
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 flex items-start gap-3">
                <i data-lucide="info" class="w-5 h-5 text-blue-500 shrink-0 mt-0.5"></i>
                <div>
                    <p class="text-sm text-blue-700 font-medium">Petunjuk Import</p>
                    <p class="text-xs text-blue-600 mt-1">Download template terlebih dahulu, isi data siswa, lalu upload file xlsx.</p>
                </div>
            </div>
            <a href="/template/template_siswa.php"
                class="flex items-center gap-2 w-full justify-center bg-emerald-600 hover:bg-emerald-700 text-white text-sm px-4 py-2.5 rounded-lg transition-colors font-medium">
                <i data-lucide="download" class="w-4 h-4"></i> Download Template
            </a>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Upload File (xlsx)</label>
                <input type="file" id="import-file" accept=".xlsx,.xls"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 file:mr-3 file:py-1 file:px-3 file:rounded file:border-0 file:text-sm file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
            </div>
        </div>
        <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-3">
            <button onclick="closeModalImport()" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800 border border-gray-300 rounded-lg hover:bg-gray-50">Batal</button>
            <button onclick="importSiswa()" class="bg-blue-700 hover:bg-blue-800 text-white px-5 py-2 rounded-lg text-sm font-medium flex items-center gap-2">
                <i data-lucide="upload-cloud" class="w-4 h-4"></i> Import
            </button>
        </div>
    </div>
</div>

<!-- ── Modal Tambah ────────────────────────────────────────────────────────── -->
<div id="modal-tambah" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-800">Tambah Siswa</h2>
            <button onclick="closeModalTambah()" class="text-gray-400 hover:text-gray-600 transition-colors">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <div class="p-6 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Siswa <span class="text-red-500">*</span></label>
                <input type="text" id="tambah-nama" placeholder="Nama lengkap siswa"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">NISN <span class="text-red-500">*</span></label>
                <input type="text" id="tambah-nisn" placeholder="10 digit NISN" maxlength="10" inputmode="numeric"
                    oninput="this.value=this.value.replace(/\D/g,'')"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 font-mono">
                <p class="text-xs text-gray-400 mt-1">Harus 10 digit angka</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Kelas <span class="text-red-500">*</span></label>
                <select id="tambah-kelas" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">-- Pilih Kelas --</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <div class="relative">
                    <input type="text" id="tambah-password" value="123456"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 font-mono pr-10">
                    <button type="button" onclick="generatePassword('tambah-password')"
                        class="absolute right-2 top-2 text-gray-400 hover:text-blue-600 transition-colors" title="Generate password">
                        <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>
        </div>
        <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-3">
            <button onclick="closeModalTambah()" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800 border border-gray-300 rounded-lg hover:bg-gray-50">Batal</button>
            <button onclick="saveSiswa()" class="bg-blue-700 hover:bg-blue-800 text-white px-5 py-2 rounded-lg text-sm font-medium flex items-center gap-2">
                <i data-lucide="save" class="w-4 h-4"></i> Simpan
            </button>
        </div>
    </div>
</div>

<!-- ── Modal Edit ──────────────────────────────────────────────────────────── -->
<div id="modal-edit" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-800">Edit Siswa</h2>
            <button onclick="closeModalEdit()" class="text-gray-400 hover:text-gray-600 transition-colors">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <div class="p-6 space-y-4">
            <input type="hidden" id="edit-id">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Siswa <span class="text-red-500">*</span></label>
                <input type="text" id="edit-nama" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">NISN <span class="text-red-500">*</span></label>
                <input type="text" id="edit-nisn" maxlength="10" inputmode="numeric"
                    oninput="this.value=this.value.replace(/\D/g,'')"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 font-mono">
                <p class="text-xs text-gray-400 mt-1">Harus 10 digit angka</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Kelas <span class="text-red-500">*</span></label>
                <select id="edit-kelas" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">-- Pilih Kelas --</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Password Baru <span class="text-gray-400 font-normal">(kosongkan jika tidak diubah)</span></label>
                <div class="relative">
                    <input type="text" id="edit-password" placeholder="Kosongkan jika tidak diubah"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 font-mono pr-10">
                    <button type="button" onclick="generatePassword('edit-password')"
                        class="absolute right-2 top-2 text-gray-400 hover:text-blue-600 transition-colors" title="Generate password">
                        <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>
        </div>
        <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-3">
            <button onclick="closeModalEdit()" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800 border border-gray-300 rounded-lg hover:bg-gray-50">Batal</button>
            <button onclick="updateSiswa()" class="bg-blue-700 hover:bg-blue-800 text-white px-5 py-2 rounded-lg text-sm font-medium flex items-center gap-2">
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
let kelasList   = [];

// ── Load kelas dropdown ────────────────────────────────────────────────────────
function loadKelas(selectIds) {
    fetch('/admin/ajax/kelas_handler.php?action=get_list')
        .then(r => r.json())
        .then(res => {
            if (res.status !== 'success') return;
            kelasList = res.data;
            selectIds.forEach(selId => {
                const sel = document.getElementById(selId);
                sel.innerHTML = '<option value="">-- Pilih Kelas --</option>' +
                    kelasList.map(k => `<option value="${escHtml(k.id)}">${escHtml(k.nama_kelas)}</option>`).join('');
            });
        });
}

// ── Load data ─────────────────────────────────────────────────────────────────
function loadData() {
    const perPage = document.getElementById('per-page').value;
    const url = `/admin/ajax/siswa_handler.php?action=get_all&page=${currentPage}&per_page=${perPage}`;
    fetch(url)
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') renderTable(res.data, res.total, res.per_page, res.page);
            else document.getElementById('table-body').innerHTML = '<tr><td colspan="6" class="text-center py-10 text-red-400">Gagal memuat data</td></tr>';
        });
}

// ── Render table ──────────────────────────────────────────────────────────────
function renderTable(data, total, perPage, page) {
    const tbody = document.getElementById('table-body');
    const info  = document.getElementById('info-data');
    const pag   = document.getElementById('pagination-area');

    if (data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-10 text-gray-400">Tidak ada data siswa</td></tr>';
        info.textContent = 'Menampilkan 0 dari 0 data';
        pag.innerHTML = '';
        return;
    }

    const start = perPage === 'all' ? 1 : (page - 1) * parseInt(perPage) + 1;
    const end   = perPage === 'all' ? total : Math.min(page * parseInt(perPage), total);
    info.textContent = `Menampilkan ${start}–${end} dari ${total} data`;

    tbody.innerHTML = data.map((row, i) => `
        <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
            <td class="px-4 py-3">
                <input type="checkbox" class="row-check rounded border-gray-300" value="${row.id}" onchange="updateSelected()">
            </td>
            <td class="px-4 py-3 text-gray-500">${start + i}</td>
            <td class="px-4 py-3">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-bold text-xs shrink-0">
                        ${escHtml(row.nama.charAt(0).toUpperCase())}
                    </div>
                    <div>
                        <p class="font-medium text-gray-800">${escHtml(row.nama)}</p>
                        <p class="text-xs text-gray-400">${formatDate(row.created_at)}</p>
                    </div>
                </div>
            </td>
            <td class="px-4 py-3 font-mono text-gray-600">${escHtml(row.nisn)}</td>
            <td class="px-4 py-3">
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700">
                    ${escHtml(row.nama_kelas || '-')}
                </span>
            </td>
            <td class="px-4 py-3">
                <div class="flex items-center gap-1.5">
                    <button onclick="resetPassword(${row.id}, '${escHtml(row.nama).replace(/'/g,"&#39;")}')"
                        class="inline-flex items-center gap-1 text-yellow-600 hover:text-yellow-800 bg-yellow-50 hover:bg-yellow-100 px-2 py-1 rounded-lg text-xs font-medium transition-colors" title="Reset Password">
                        <i data-lucide="key-round" class="w-3.5 h-3.5"></i> Reset
                    </button>
                    <button onclick="openModalEdit(${row.id}, '${escHtml(row.nama).replace(/'/g,"&#39;")}', '${escHtml(row.nisn)}', '${escHtml(row.kelas_id || '')}')"
                        class="inline-flex items-center gap-1 text-blue-600 hover:text-blue-800 bg-blue-50 hover:bg-blue-100 px-2 py-1 rounded-lg text-xs font-medium transition-colors" title="Edit">
                        <i data-lucide="pencil" class="w-3.5 h-3.5"></i> Edit
                    </button>
                    <button onclick="deleteSiswa(${row.id}, '${escHtml(row.nama).replace(/'/g,"&#39;")}')"
                        class="inline-flex items-center gap-1 text-red-600 hover:text-red-800 bg-red-50 hover:bg-red-100 px-2 py-1 rounded-lg text-xs font-medium transition-colors" title="Hapus">
                        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i> Hapus
                    </button>
                </div>
            </td>
        </tr>
    `).join('');

    renderPagination(pag, page, total, perPage);
    lucide.createIcons();
}

// ── Pagination ────────────────────────────────────────────────────────────────
function renderPagination(pag, page, total, perPage) {
    if (perPage === 'all') { pag.innerHTML = ''; return; }
    const totalPages = Math.ceil(total / parseInt(perPage));
    if (totalPages <= 1) { pag.innerHTML = ''; return; }
    let html = '<div class="flex items-center gap-1">';
    if (page > 1) html += `<button onclick="changePage(${page-1})" class="px-3 py-1 rounded border text-sm hover:bg-gray-50 transition-colors">‹</button>`;
    for (let i = Math.max(1, page-2); i <= Math.min(totalPages, page+2); i++) {
        html += `<button onclick="changePage(${i})" class="px-3 py-1 rounded border text-sm transition-colors ${i === page ? 'bg-blue-700 text-white border-blue-700' : 'hover:bg-gray-50'}">${i}</button>`;
    }
    if (page < totalPages) html += `<button onclick="changePage(${page+1})" class="px-3 py-1 rounded border text-sm hover:bg-gray-50 transition-colors">›</button>`;
    html += '</div>';
    pag.innerHTML = html;
}

function changePage(p) { currentPage = p; loadData(); }

// ── Selection ─────────────────────────────────────────────────────────────────
function updateSelected() {
    selectedIds = Array.from(document.querySelectorAll('.row-check:checked')).map(c => c.value);
    const hasSel = selectedIds.length > 0;
    document.getElementById('btn-hapus-terpilih').classList.toggle('hidden', !hasSel);
    document.getElementById('btn-reset-terpilih').classList.toggle('hidden', !hasSel);
    const all = document.querySelectorAll('.row-check');
    document.getElementById('check-all').indeterminate = hasSel && selectedIds.length < all.length;
    document.getElementById('check-all').checked = hasSel && selectedIds.length === all.length;
}

function toggleAll(cb) {
    document.querySelectorAll('.row-check').forEach(c => c.checked = cb.checked);
    updateSelected();
}

// ── Modal Import ──────────────────────────────────────────────────────────────
function openModalImport() {
    document.getElementById('import-file').value = '';
    document.getElementById('modal-import').classList.remove('hidden');
    lucide.createIcons();
}
function closeModalImport() { document.getElementById('modal-import').classList.add('hidden'); }

function importSiswa() {
    const file = document.getElementById('import-file').files[0];
    if (!file) { Swal.fire({ icon: 'warning', text: 'Pilih file terlebih dahulu', confirmButtonColor: '#1d4ed8' }); return; }
    Swal.fire({ icon: 'info', title: 'Info', text: 'Fitur import akan tersedia setelah konfigurasi library PhpSpreadsheet.', confirmButtonColor: '#1d4ed8' });
}

// ── Modal Tambah ──────────────────────────────────────────────────────────────
function openModalTambah() {
    document.getElementById('tambah-nama').value     = '';
    document.getElementById('tambah-nisn').value     = '';
    document.getElementById('tambah-password').value = '123456';
    document.getElementById('modal-tambah').classList.remove('hidden');
    loadKelas(['tambah-kelas']);
    lucide.createIcons();
    setTimeout(() => document.getElementById('tambah-nama').focus(), 100);
}
function closeModalTambah() { document.getElementById('modal-tambah').classList.add('hidden'); }

function saveSiswa() {
    const nama     = document.getElementById('tambah-nama').value.trim();
    const nisn     = document.getElementById('tambah-nisn').value.trim();
    const kelasId  = document.getElementById('tambah-kelas').value;
    const password = document.getElementById('tambah-password').value.trim();

    if (!nama)                      { Swal.fire({ icon: 'warning', text: 'Nama wajib diisi', confirmButtonColor: '#1d4ed8' }); return; }
    if (!/^\d{10}$/.test(nisn))     { Swal.fire({ icon: 'warning', text: 'NISN harus 10 digit angka', confirmButtonColor: '#1d4ed8' }); return; }
    if (!kelasId)                   { Swal.fire({ icon: 'warning', text: 'Kelas wajib dipilih', confirmButtonColor: '#1d4ed8' }); return; }

    const fd = new FormData();
    fd.append('action', 'add');
    fd.append('nama', nama);
    fd.append('nisn', nisn);
    fd.append('kelas_id', kelasId);
    fd.append('password', password || '123456');

    fetch('/admin/ajax/siswa_handler.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                Swal.fire({ icon: 'success', title: 'Berhasil', text: res.message, timer: 1500, showConfirmButton: false });
                closeModalTambah();
                loadData();
            } else {
                Swal.fire({ icon: 'error', title: 'Gagal', text: res.message, confirmButtonColor: '#1d4ed8' });
            }
        });
}

// ── Modal Edit ────────────────────────────────────────────────────────────────
function openModalEdit(id, nama, nisn, kelasId) {
    document.getElementById('edit-id').value       = id;
    document.getElementById('edit-nama').value     = nama;
    document.getElementById('edit-nisn').value     = nisn;
    document.getElementById('edit-password').value = '';
    document.getElementById('modal-edit').classList.remove('hidden');

    // Load kelas then set selected value
    fetch('/admin/ajax/kelas_handler.php?action=get_list')
        .then(r => r.json())
        .then(res => {
            const sel = document.getElementById('edit-kelas');
            sel.innerHTML = '<option value="">-- Pilih Kelas --</option>' +
                (res.data || []).map(k =>
                    `<option value="${escHtml(k.id)}" ${k.id === kelasId ? 'selected' : ''}>${escHtml(k.nama_kelas)}</option>`
                ).join('');
        });

    lucide.createIcons();
    setTimeout(() => document.getElementById('edit-nama').focus(), 100);
}
function closeModalEdit() { document.getElementById('modal-edit').classList.add('hidden'); }

function updateSiswa() {
    const id       = document.getElementById('edit-id').value;
    const nama     = document.getElementById('edit-nama').value.trim();
    const nisn     = document.getElementById('edit-nisn').value.trim();
    const kelasId  = document.getElementById('edit-kelas').value;
    const password = document.getElementById('edit-password').value.trim();

    if (!nama)                  { Swal.fire({ icon: 'warning', text: 'Nama wajib diisi', confirmButtonColor: '#1d4ed8' }); return; }
    if (!/^\d{10}$/.test(nisn)) { Swal.fire({ icon: 'warning', text: 'NISN harus 10 digit angka', confirmButtonColor: '#1d4ed8' }); return; }
    if (!kelasId)               { Swal.fire({ icon: 'warning', text: 'Kelas wajib dipilih', confirmButtonColor: '#1d4ed8' }); return; }

    const fd = new FormData();
    fd.append('action', 'edit');
    fd.append('id', id);
    fd.append('nama', nama);
    fd.append('nisn', nisn);
    fd.append('kelas_id', kelasId);
    fd.append('password', password);

    fetch('/admin/ajax/siswa_handler.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                Swal.fire({ icon: 'success', title: 'Berhasil', text: res.message, timer: 1500, showConfirmButton: false });
                closeModalEdit();
                loadData();
            } else {
                Swal.fire({ icon: 'error', title: 'Gagal', text: res.message, confirmButtonColor: '#1d4ed8' });
            }
        });
}

// ── Reset Password (single) ───────────────────────────────────────────────────
function resetPassword(id, nama) {
    Swal.fire({
        title: 'Reset Password?',
        html: `Password <strong>${escHtml(nama)}</strong> akan direset ke <code>123456</code>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#d97706',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Reset',
        cancelButtonText: 'Batal'
    }).then(r => {
        if (!r.isConfirmed) return;
        const fd = new FormData();
        fd.append('action', 'reset_password');
        fd.append('id', id);
        fetch('/admin/ajax/siswa_handler.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(res => {
                if (res.status === 'success') Swal.fire({ icon: 'success', text: res.message, timer: 1500, showConfirmButton: false });
                else Swal.fire({ icon: 'error', text: res.message, confirmButtonColor: '#1d4ed8' });
            });
    });
}

// ── Reset Password (multiple) ─────────────────────────────────────────────────
function resetPasswordTerpilih() {
    if (selectedIds.length === 0) return;
    Swal.fire({
        title: `Reset ${selectedIds.length} Password?`,
        html: `Password <strong>${selectedIds.length} siswa</strong> akan direset ke <code>123456</code>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#d97706',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Reset',
        cancelButtonText: 'Batal'
    }).then(r => {
        if (!r.isConfirmed) return;
        const fd = new FormData();
        fd.append('action', 'reset_password_multiple');
        selectedIds.forEach(id => fd.append('ids[]', id));
        fetch('/admin/ajax/siswa_handler.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(res => {
                if (res.status === 'success') { Swal.fire({ icon: 'success', text: res.message, timer: 1500, showConfirmButton: false }); loadData(); }
                else Swal.fire({ icon: 'error', text: res.message, confirmButtonColor: '#1d4ed8' });
            });
    });
}

// ── Delete (single) ───────────────────────────────────────────────────────────
function deleteSiswa(id, nama) {
    Swal.fire({
        title: 'Hapus Siswa?',
        html: `<strong>${escHtml(nama)}</strong> akan dihapus permanen.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Hapus',
        cancelButtonText: 'Batal'
    }).then(r => {
        if (!r.isConfirmed) return;
        const fd = new FormData();
        fd.append('action', 'delete');
        fd.append('id', id);
        fetch('/admin/ajax/siswa_handler.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(res => {
                if (res.status === 'success') { Swal.fire({ icon: 'success', text: res.message, timer: 1500, showConfirmButton: false }); loadData(); }
                else Swal.fire({ icon: 'error', text: res.message, confirmButtonColor: '#1d4ed8' });
            });
    });
}

// ── Delete (multiple) ─────────────────────────────────────────────────────────
function hapusTerpilih() {
    if (selectedIds.length === 0) return;
    Swal.fire({
        title: `Hapus ${selectedIds.length} Siswa?`,
        text: 'Data yang dihapus tidak dapat dikembalikan.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Hapus',
        cancelButtonText: 'Batal'
    }).then(r => {
        if (!r.isConfirmed) return;
        const fd = new FormData();
        fd.append('action', 'delete_multiple');
        selectedIds.forEach(id => fd.append('ids[]', id));
        fetch('/admin/ajax/siswa_handler.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(res => {
                if (res.status === 'success') { Swal.fire({ icon: 'success', text: res.message, timer: 1500, showConfirmButton: false }); loadData(); }
                else Swal.fire({ icon: 'error', text: res.message, confirmButtonColor: '#1d4ed8' });
            });
    });
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function generatePassword(inputId) {
    const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
    let pwd = '';
    for (let i = 0; i < 8; i++) pwd += chars[Math.floor(Math.random() * chars.length)];
    document.getElementById(inputId).value = pwd;
}

function formatDate(dt) {
    if (!dt) return '-';
    const d = new Date(dt);
    return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
}

function escHtml(s) {
    return String(s)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

loadData();
</script>
</body>
</html>
