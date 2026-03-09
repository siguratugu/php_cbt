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
    <title>Bank Soal - CBT MTsN 1 Mesuji</title>
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
                <h1 class="text-xl font-bold text-gray-800">Bank Soal</h1>
                <p class="text-gray-500 text-sm">Kelola bank soal Anda</p>
            </div>
            <div class="flex items-center gap-2">
                <button id="btn-hapus-terpilih" onclick="hapusTerpilih()"
                    class="hidden bg-red-500 hover:bg-red-600 text-white text-sm px-4 py-2 rounded-lg flex items-center gap-2 transition-colors">
                    <i data-lucide="trash-2" class="w-4 h-4"></i> Hapus Terpilih
                </button>
                <button onclick="openModalTambah()"
                    class="bg-green-700 hover:bg-green-800 text-white text-sm px-4 py-2 rounded-lg flex items-center gap-2 transition-colors">
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
                            class="border border-gray-300 rounded-lg text-sm px-3 py-1.5 focus:ring-2 focus:ring-green-500">
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
                                    <input type="checkbox" id="check-all" onchange="toggleAll(this)" class="rounded">
                                </th>
                                <th class="px-4 py-3 text-left text-gray-600 font-semibold w-12">No</th>
                                <th class="px-4 py-3 text-left text-gray-600 font-semibold">Nama Soal</th>
                                <th class="px-4 py-3 text-left text-gray-600 font-semibold">Mapel</th>
                                <th class="px-4 py-3 text-center text-gray-600 font-semibold">Waktu (menit)</th>
                                <th class="px-4 py-3 text-center text-gray-600 font-semibold">Jumlah Soal</th>
                                <th class="px-4 py-3 text-left text-gray-600 font-semibold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="table-body">
                            <tr><td colspan="7" class="text-center py-10 text-gray-400">Memuat data...</td></tr>
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
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-xl max-h-[92vh] overflow-y-auto">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between sticky top-0 bg-white rounded-t-2xl z-10">
            <h2 class="text-lg font-bold text-gray-800">Tambah Bank Soal</h2>
            <button onclick="closeModalTambah()" class="text-gray-400 hover:text-gray-700">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <div class="p-6 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Soal <span class="text-red-500">*</span></label>
                <input type="text" id="add-nama-soal" placeholder="Contoh: UTS Matematika"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Pilih Mapel</label>
                <select id="add-mapel" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500">
                    <option value="">-- Pilih Mapel --</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Waktu Mengerjakan (menit) <span class="text-red-500">*</span></label>
                <input type="number" id="add-waktu" min="1" value="60"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500">
            </div>
            <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-sm font-semibold text-gray-700">Bobot Penilaian</p>
                    <span id="total-bobot-info" class="text-xs font-semibold px-2.5 py-1 rounded-full bg-green-100 text-green-700">Total: 100%</span>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Bobot PG (%)</label>
                        <input type="number" id="add-bobot-pg" min="0" max="100" value="100" oninput="updateTotalBobot()"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Bobot Esai (%)</label>
                        <input type="number" id="add-bobot-esai" min="0" max="100" value="0" oninput="updateTotalBobot()"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Bobot Menjodohkan (%)</label>
                        <input type="number" id="add-bobot-menjodohkan" min="0" max="100" value="0" oninput="updateTotalBobot()"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Bobot Benar/Salah (%)</label>
                        <input type="number" id="add-bobot-bs" min="0" max="100" value="0" oninput="updateTotalBobot()"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500">
                    </div>
                </div>
            </div>
        </div>
        <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-3">
            <button onclick="closeModalTambah()" class="px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-lg">Batal</button>
            <button onclick="saveOnly()" class="bg-green-700 hover:bg-green-800 text-white px-5 py-2 rounded-lg text-sm font-medium flex items-center gap-2">
                <i data-lucide="save" class="w-4 h-4"></i> Simpan Saja
            </button>
            <button onclick="saveAndCreate()" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg text-sm font-medium flex items-center gap-2">
                <i data-lucide="wand-2" class="w-4 h-4"></i> Simpan &amp; Buat Soal
            </button>
        </div>
    </div>
</div>

<!-- Modal Edit -->
<div id="modal-edit" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-xl max-h-[92vh] overflow-y-auto">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between sticky top-0 bg-white rounded-t-2xl z-10">
            <h2 class="text-lg font-bold text-gray-800">Edit Bank Soal</h2>
            <button onclick="closeModalEdit()" class="text-gray-400 hover:text-gray-700">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <div class="p-6 space-y-4">
            <input type="hidden" id="edit-id">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Soal <span class="text-red-500">*</span></label>
                <input type="text" id="edit-nama-soal" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Pilih Mapel</label>
                <select id="edit-mapel" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500">
                    <option value="">-- Pilih Mapel --</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Waktu Mengerjakan (menit) <span class="text-red-500">*</span></label>
                <input type="number" id="edit-waktu" min="1" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500">
            </div>
            <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-sm font-semibold text-gray-700">Bobot Penilaian</p>
                    <span id="edit-total-bobot-info" class="text-xs font-semibold px-2.5 py-1 rounded-full bg-gray-200 text-gray-600">Total: 0%</span>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Bobot PG (%)</label>
                        <input type="number" id="edit-bobot-pg" min="0" max="100" oninput="updateTotalBobotEdit()"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Bobot Esai (%)</label>
                        <input type="number" id="edit-bobot-esai" min="0" max="100" oninput="updateTotalBobotEdit()"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Bobot Menjodohkan (%)</label>
                        <input type="number" id="edit-bobot-menjodohkan" min="0" max="100" oninput="updateTotalBobotEdit()"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Bobot Benar/Salah (%)</label>
                        <input type="number" id="edit-bobot-bs" min="0" max="100" oninput="updateTotalBobotEdit()"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500">
                    </div>
                </div>
            </div>
        </div>
        <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-3">
            <button onclick="closeModalEdit()" class="px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-lg">Batal</button>
            <button onclick="updateBankSoal()" class="bg-green-700 hover:bg-green-800 text-white px-5 py-2 rounded-lg text-sm font-medium flex items-center gap-2">
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
let mapelList = [];

function loadData() {
    const perPage = document.getElementById('per-page').value;
    fetch(`/guru/ajax/bank_soal_handler.php?action=get_all&page=${currentPage}&per_page=${perPage}`)
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') renderTable(res.data, res.total, res.per_page, res.page);
        })
        .catch(() => { document.getElementById('table-body').innerHTML = '<tr><td colspan="7" class="text-center py-8 text-red-400">Gagal memuat data</td></tr>'; });
}

function renderTable(data, total, perPage, page) {
    const tbody = document.getElementById('table-body');
    const info  = document.getElementById('info-data');
    const pag   = document.getElementById('pagination-area');

    if (!data.length) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center py-14 text-gray-400"><div class="flex flex-col items-center gap-3"><i data-lucide="inbox" class="w-12 h-12 text-gray-300"></i><p>Belum ada bank soal</p></div></td></tr>';
        info.textContent = '';
        pag.innerHTML = '';
        if (typeof lucide !== 'undefined') lucide.createIcons();
        return;
    }

    const start = (perPage === 'all') ? 1 : (page - 1) * parseInt(perPage) + 1;
    const end   = (perPage === 'all') ? total : Math.min(page * parseInt(perPage), total);
    info.textContent = `${start}–${end} dari ${total} data`;

    tbody.innerHTML = data.map((row, idx) => {
        const no    = (perPage === 'all') ? (idx + 1) : ((page - 1) * parseInt(perPage) + idx + 1);
        const mapel = row.nama_mapel ? `<span class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full text-xs">${escHtml(row.nama_mapel)}</span>` : '<span class="text-gray-400 text-xs">—</span>';
        return `<tr class="border-b border-gray-100 hover:bg-gray-50">
            <td class="px-4 py-3"><input type="checkbox" class="row-check rounded" value="${row.id}" onchange="updateSelected()"></td>
            <td class="px-4 py-3 text-gray-500 text-xs">${no}</td>
            <td class="px-4 py-3"><div class="font-semibold text-gray-800">${escHtml(row.nama_soal)}</div><div class="text-xs text-gray-400">ID: #${row.id}</div></td>
            <td class="px-4 py-3">${mapel}</td>
            <td class="px-4 py-3 text-center text-gray-600">${row.waktu_mengerjakan}</td>
            <td class="px-4 py-3 text-center"><span class="px-2.5 py-1 bg-green-100 text-green-700 rounded-full text-xs font-bold">${row.jumlah_soal}</span></td>
            <td class="px-4 py-3">
                <div class="flex items-center gap-1.5">
                    <a href="/guru/buat_soal.php?id=${row.id}" class="inline-flex items-center gap-1 bg-green-600 hover:bg-green-700 text-white text-xs px-2.5 py-1.5 rounded-lg font-medium"><i data-lucide="file-pen" class="w-3.5 h-3.5"></i> Buat Soal</a>
                    <button onclick="openModalEdit(${row.id})" class="inline-flex items-center gap-1 bg-blue-500 hover:bg-blue-600 text-white text-xs px-2.5 py-1.5 rounded-lg"><i data-lucide="pencil" class="w-3.5 h-3.5"></i></button>
                    <button onclick="deleteBankSoal(${row.id}, '${escAttr(row.nama_soal)}')" class="inline-flex items-center bg-red-500 hover:bg-red-600 text-white text-xs px-2 py-1.5 rounded-lg"><i data-lucide="trash-2" class="w-3.5 h-3.5"></i></button>
                </div>
            </td>
        </tr>`;
    }).join('');

    if (perPage !== 'all') {
        const totalPages = Math.ceil(total / parseInt(perPage));
        let html = `<div class="text-xs text-gray-500">Halaman ${page} dari ${totalPages}</div><div class="flex items-center gap-1">`;
        if (page > 1) html += `<button onclick="changePage(${page-1})" class="px-3 py-1.5 rounded-lg border border-gray-300 text-sm hover:bg-gray-50">‹</button>`;
        const s2 = Math.max(1, page-2), e2 = Math.min(totalPages, page+2);
        for (let i = s2; i <= e2; i++) html += `<button onclick="changePage(${i})" class="px-3 py-1.5 rounded-lg border text-sm ${i===page?'bg-green-700 text-white border-green-700':'border-gray-300 hover:bg-gray-50'}">${i}</button>`;
        if (page < totalPages) html += `<button onclick="changePage(${page+1})" class="px-3 py-1.5 rounded-lg border border-gray-300 text-sm hover:bg-gray-50">›</button>`;
        html += '</div>';
        pag.innerHTML = html;
    } else {
        pag.innerHTML = `<div class="text-xs text-gray-500">Semua ${total} data</div>`;
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

function loadMapelList(targetId, selectedVal) {
    fetch('/guru/ajax/bank_soal_handler.php?action=get_mapel_list')
        .then(r => r.json())
        .then(res => {
            if (res.status !== 'success') return;
            const sel = document.getElementById(targetId);
            sel.innerHTML = '<option value="">-- Pilih Mapel --</option>' +
                res.data.map(m => `<option value="${m.id}" ${m.id==selectedVal?'selected':''}>${escHtml(m.nama_mapel)}</option>`).join('');
            if (typeof lucide !== 'undefined') lucide.createIcons();
        });
}

function updateTotalBobot() {
    const total = (parseFloat(document.getElementById('add-bobot-pg').value)||0) +
        (parseFloat(document.getElementById('add-bobot-esai').value)||0) +
        (parseFloat(document.getElementById('add-bobot-menjodohkan').value)||0) +
        (parseFloat(document.getElementById('add-bobot-bs').value)||0);
    const info = document.getElementById('total-bobot-info');
    info.textContent = `Total: ${total}%`;
    info.className = `text-xs font-semibold px-2.5 py-1 rounded-full ${Math.round(total)===100?'bg-green-100 text-green-700':'bg-red-100 text-red-700'}`;
}

function updateTotalBobotEdit() {
    const total = (parseFloat(document.getElementById('edit-bobot-pg').value)||0) +
        (parseFloat(document.getElementById('edit-bobot-esai').value)||0) +
        (parseFloat(document.getElementById('edit-bobot-menjodohkan').value)||0) +
        (parseFloat(document.getElementById('edit-bobot-bs').value)||0);
    const info = document.getElementById('edit-total-bobot-info');
    info.textContent = `Total: ${total}%`;
    info.className = `text-xs font-semibold px-2.5 py-1 rounded-full ${Math.round(total)===100?'bg-green-100 text-green-700':'bg-red-100 text-red-700'}`;
}

function openModalTambah() {
    document.getElementById('add-nama-soal').value = '';
    document.getElementById('add-waktu').value = 60;
    document.getElementById('add-bobot-pg').value = 100;
    document.getElementById('add-bobot-esai').value = 0;
    document.getElementById('add-bobot-menjodohkan').value = 0;
    document.getElementById('add-bobot-bs').value = 0;
    updateTotalBobot();
    loadMapelList('add-mapel', '');
    document.getElementById('modal-tambah').classList.remove('hidden');
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function closeModalTambah() { document.getElementById('modal-tambah').classList.add('hidden'); }

function getFormData(prefix) {
    return {
        nama_soal: document.getElementById(prefix+'-nama-soal').value.trim(),
        mapel_id: document.getElementById(prefix+'-mapel').value,
        waktu_mengerjakan: document.getElementById(prefix+'-waktu').value,
        bobot_pg: document.getElementById(prefix+'-bobot-pg').value || 0,
        bobot_esai: document.getElementById(prefix+'-bobot-esai').value || 0,
        bobot_menjodohkan: document.getElementById(prefix+'-bobot-menjodohkan').value || 0,
        bobot_benar_salah: document.getElementById(prefix+'-bobot-bs').value || 0
    };
}

function saveOnly() {
    const d = getFormData('add');
    if (!d.nama_soal) { Swal.fire('Error', 'Nama soal wajib diisi', 'error'); return; }
    const fd = new FormData();
    fd.append('action', 'add');
    Object.entries(d).forEach(([k,v]) => fd.append(k, v));
    fetch('/guru/ajax/bank_soal_handler.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                closeModalTambah();
                Swal.fire('Berhasil', res.message, 'success');
                loadData();
            } else {
                Swal.fire('Error', res.message, 'error');
            }
        });
}

function saveAndCreate() {
    const d = getFormData('add');
    if (!d.nama_soal) { Swal.fire('Error', 'Nama soal wajib diisi', 'error'); return; }
    const fd = new FormData();
    fd.append('action', 'add');
    Object.entries(d).forEach(([k,v]) => fd.append(k, v));
    fetch('/guru/ajax/bank_soal_handler.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                closeModalTambah();
                window.location.href = `/guru/buat_soal.php?id=${res.id}`;
            } else {
                Swal.fire('Error', res.message, 'error');
            }
        });
}

function openModalEdit(id) {
    fetch(`/guru/ajax/bank_soal_handler.php?action=get_single&id=${id}`)
        .then(r => r.json())
        .then(res => {
            if (res.status !== 'success') return;
            const d = res.data;
            document.getElementById('edit-id').value = d.id;
            document.getElementById('edit-nama-soal').value = d.nama_soal;
            document.getElementById('edit-waktu').value = d.waktu_mengerjakan;
            document.getElementById('edit-bobot-pg').value = d.bobot_pg || 0;
            document.getElementById('edit-bobot-esai').value = d.bobot_esai || 0;
            document.getElementById('edit-bobot-menjodohkan').value = d.bobot_menjodohkan || 0;
            document.getElementById('edit-bobot-bs').value = d.bobot_benar_salah || 0;
            updateTotalBobotEdit();
            loadMapelList('edit-mapel', d.mapel_id);
            document.getElementById('modal-edit').classList.remove('hidden');
            if (typeof lucide !== 'undefined') lucide.createIcons();
        });
}

function closeModalEdit() { document.getElementById('modal-edit').classList.add('hidden'); }

function updateBankSoal() {
    const id = document.getElementById('edit-id').value;
    const d = getFormData('edit');
    if (!d.nama_soal) { Swal.fire('Error', 'Nama soal wajib diisi', 'error'); return; }
    const fd = new FormData();
    fd.append('action', 'edit');
    fd.append('id', id);
    Object.entries(d).forEach(([k,v]) => fd.append(k, v));
    fetch('/guru/ajax/bank_soal_handler.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                closeModalEdit();
                Swal.fire('Berhasil', res.message, 'success');
                loadData();
            } else {
                Swal.fire('Error', res.message, 'error');
            }
        });
}

function deleteBankSoal(id, nama) {
    Swal.fire({
        title: 'Hapus Bank Soal?',
        text: `"${nama}" akan dihapus beserta semua soalnya.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal'
    }).then(result => {
        if (result.isConfirmed) {
            const fd = new FormData();
            fd.append('action', 'delete'); fd.append('id', id);
            fetch('/guru/ajax/bank_soal_handler.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    if (res.status === 'success') { Swal.fire('Berhasil', res.message, 'success'); loadData(); }
                    else Swal.fire('Error', res.message, 'error');
                });
        }
    });
}

function hapusTerpilih() {
    if (!selectedIds.length) return;
    Swal.fire({
        title: 'Hapus Terpilih?',
        text: `${selectedIds.length} bank soal akan dihapus.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, Hapus'
    }).then(result => {
        if (result.isConfirmed) {
            const fd = new FormData();
            fd.append('action', 'delete_multiple');
            selectedIds.forEach(id => fd.append('ids[]', id));
            fetch('/guru/ajax/bank_soal_handler.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    if (res.status === 'success') { selectedIds = []; Swal.fire('Berhasil', res.message, 'success'); loadData(); }
                    else Swal.fire('Error', res.message, 'error');
                });
        }
    });
}

function escHtml(s) { if (!s) return ''; return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
function escAttr(s) { if (!s) return ''; return String(s).replace(/'/g,"\\'"); }

loadData();
</script>
</body>
</html>
