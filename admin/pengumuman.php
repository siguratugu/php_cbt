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
    <title>Pengumuman - CBT MTsN 1 Mesuji</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="bg-gray-100 min-h-screen">
<div class="flex h-screen overflow-hidden">
    <?php include __DIR__ . '/../includes/sidebar_admin.php'; ?>

    <div class="flex-1 ml-64 overflow-y-auto pb-16">
        <!-- Top Bar -->
        <div class="bg-white shadow-sm px-6 py-4 flex items-center justify-between sticky top-0 z-10">
            <div>
                <h1 class="text-xl font-bold text-gray-800">Pengumuman</h1>
                <p class="text-gray-500 text-sm">Kelola pengumuman untuk siswa</p>
            </div>
            <div>
                <button onclick="openModalTambah()" class="bg-blue-700 hover:bg-blue-800 text-white text-sm px-4 py-2 rounded-lg flex items-center gap-1">
                    <i data-lucide="plus" class="w-4 h-4"></i> Tambah Pengumuman
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
                                <th class="px-4 py-3 text-left text-gray-600 font-semibold w-10">No</th>
                                <th class="px-4 py-3 text-left text-gray-600 font-semibold">Judul</th>
                                <th class="px-4 py-3 text-left text-gray-600 font-semibold">Kelas</th>
                                <th class="px-4 py-3 text-left text-gray-600 font-semibold">Tanggal</th>
                                <th class="px-4 py-3 text-left text-gray-600 font-semibold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="table-body">
                            <tr><td colspan="5" class="text-center py-8 text-gray-400">Memuat data...</td></tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex items-center justify-between mt-4 pt-4 border-t border-gray-100" id="pagination-area"></div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah -->
<div id="modal-tambah" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-start justify-center p-4 overflow-y-auto">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-3xl my-4">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between sticky top-0 bg-white z-10">
            <h2 class="text-lg font-semibold text-gray-800">Tambah Pengumuman</h2>
            <button onclick="closeModalTambah()" class="text-gray-400 hover:text-gray-600"><i data-lucide="x" class="w-5 h-5"></i></button>
        </div>
        <div class="p-6 space-y-5">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Judul <span class="text-red-500">*</span></label>
                <input type="text" id="tambah-judul" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500" placeholder="Judul pengumuman">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Isi Pengumuman <span class="text-red-500">*</span></label>
                <textarea id="tambah-isi" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" rows="6"></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Pilih Kelas <span class="text-red-500">*</span></label>
                <label class="flex items-center gap-2 mb-2 cursor-pointer">
                    <input type="checkbox" id="tambah-semua-kelas" onchange="toggleSemuaKelas('tambah')" class="rounded text-blue-600">
                    <span class="text-sm font-medium text-blue-700">Semua Kelas</span>
                </label>
                <div id="tambah-kelas-list" class="grid grid-cols-3 gap-2 max-h-40 overflow-y-auto p-3 border border-gray-200 rounded-lg bg-gray-50">
                    <p class="text-gray-400 text-xs col-span-3">Memuat kelas...</p>
                </div>
            </div>
        </div>
        <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-3 sticky bottom-0 bg-white">
            <button onclick="closeModalTambah()" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Batal</button>
            <button onclick="savePengumuman()" class="bg-blue-700 hover:bg-blue-800 text-white px-5 py-2 rounded-lg text-sm font-medium flex items-center gap-2">
                <i data-lucide="save" class="w-4 h-4"></i> Simpan
            </button>
        </div>
    </div>
</div>

<!-- Modal Edit -->
<div id="modal-edit" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-start justify-center p-4 overflow-y-auto">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-3xl my-4">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between sticky top-0 bg-white z-10">
            <h2 class="text-lg font-semibold text-gray-800">Edit Pengumuman</h2>
            <button onclick="closeModalEdit()" class="text-gray-400 hover:text-gray-600"><i data-lucide="x" class="w-5 h-5"></i></button>
        </div>
        <div class="p-6 space-y-5">
            <input type="hidden" id="edit-id">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Judul <span class="text-red-500">*</span></label>
                <input type="text" id="edit-judul" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Isi Pengumuman <span class="text-red-500">*</span></label>
                <textarea id="edit-isi" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" rows="6"></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Pilih Kelas <span class="text-red-500">*</span></label>
                <label class="flex items-center gap-2 mb-2 cursor-pointer">
                    <input type="checkbox" id="edit-semua-kelas" onchange="toggleSemuaKelas('edit')" class="rounded text-blue-600">
                    <span class="text-sm font-medium text-blue-700">Semua Kelas</span>
                </label>
                <div id="edit-kelas-list" class="grid grid-cols-3 gap-2 max-h-40 overflow-y-auto p-3 border border-gray-200 rounded-lg bg-gray-50">
                    <p class="text-gray-400 text-xs col-span-3">Memuat kelas...</p>
                </div>
            </div>
        </div>
        <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-3 sticky bottom-0 bg-white">
            <button onclick="closeModalEdit()" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Batal</button>
            <button onclick="updatePengumuman()" class="bg-blue-700 hover:bg-blue-800 text-white px-5 py-2 rounded-lg text-sm font-medium flex items-center gap-2">
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
let tambahEditor = null;
let editEditor = null;
let allKelasData = [];

// ===================== CKEditor =====================
ClassicEditor.create(document.querySelector('#tambah-isi'), {
    toolbar: ['heading', '|', 'bold', 'italic', 'underline', 'bulletedList', 'numberedList', '|', 'undo', 'redo']
}).then(editor => { tambahEditor = editor; }).catch(console.error);

ClassicEditor.create(document.querySelector('#edit-isi'), {
    toolbar: ['heading', '|', 'bold', 'italic', 'underline', 'bulletedList', 'numberedList', '|', 'undo', 'redo']
}).then(editor => { editEditor = editor; }).catch(console.error);

// ===================== LOAD DATA =====================
function loadData() {
    const perPage = document.getElementById('per-page').value;
    fetch(`/admin/ajax/pengumuman_handler.php?action=get_all&page=${currentPage}&per_page=${perPage}`)
        .then(r => r.json())
        .then(res => { if (res.status === 'success') renderTable(res.data, res.total, res.per_page, res.page); });
}

function renderTable(data, total, perPage, page) {
    const tbody = document.getElementById('table-body');
    const info  = document.getElementById('info-data');
    const pag   = document.getElementById('pagination-area');

    if (!data || data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center py-8 text-gray-400">Tidak ada pengumuman</td></tr>';
        info.textContent = 'Menampilkan 0 dari 0 data'; pag.innerHTML = ''; return;
    }

    const start = perPage === 'all' ? 1 : (page-1)*parseInt(perPage)+1;
    const end   = perPage === 'all' ? total : Math.min(page*parseInt(perPage), total);
    info.textContent = `Menampilkan ${start}-${end} dari ${total} data`;

    tbody.innerHTML = data.map((row, idx) => {
        const no = perPage === 'all' ? idx+1 : start+idx;
        const kelasTags = row.kelas_list
            ? row.kelas_list.split(',').map(k => `<span class="bg-blue-50 text-blue-600 px-2 py-0.5 rounded-full text-xs">${escHtml(k.trim())}</span>`).join(' ')
            : '<span class="text-gray-400 text-xs">Semua Kelas</span>';
        const isiPreview = row.isi ? row.isi.replace(/<[^>]*>/g, '').substring(0, 100) + (row.isi.length > 100 ? '...' : '') : '';
        const tgl = row.created_at ? new Date(row.created_at).toLocaleDateString('id-ID', {day:'2-digit',month:'long',year:'numeric'}) : '-';

        return `<tr class="border-b border-gray-100 hover:bg-gray-50">
            <td class="px-4 py-3 text-gray-500">${no}</td>
            <td class="px-4 py-3">
                <div class="font-medium text-gray-800">${escHtml(row.judul)}</div>
                <div class="text-xs text-gray-400 mt-0.5">${escHtml(isiPreview)}</div>
            </td>
            <td class="px-4 py-3"><div class="flex flex-wrap gap-1">${kelasTags}</div></td>
            <td class="px-4 py-3 text-sm text-gray-500">${tgl}</td>
            <td class="px-4 py-3">
                <div class="flex items-center gap-2">
                    <button onclick="openModalEdit(${row.id})" class="text-yellow-500 hover:text-yellow-700" title="Edit">
                        <i data-lucide="pencil" class="w-4 h-4"></i>
                    </button>
                    <button onclick="deletePengumuman(${row.id})" class="text-red-500 hover:text-red-700" title="Hapus">
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
    } else { pag.innerHTML = ''; }
    lucide.createIcons();
}

function changePage(p) { currentPage = p; loadData(); }

// ===================== KELAS =====================
function loadKelasList(prefix, checkedIds = []) {
    fetch('/admin/ajax/kelas_handler.php?action=get_list')
        .then(r => r.json())
        .then(res => {
            allKelasData = res.data || [];
            const container = document.getElementById(`${prefix}-kelas-list`);
            if (allKelasData.length > 0) {
                container.innerHTML = allKelasData.map(k => `
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" class="${prefix}-kelas-check rounded text-blue-600" value="${k.id}" ${checkedIds.includes(k.id) ? 'checked' : ''} onchange="updateSemuaKelasState('${prefix}')">
                        <span class="text-sm text-gray-700">${escHtml(k.nama_kelas)}</span>
                    </label>`).join('');
                updateSemuaKelasState(prefix);
            } else {
                container.innerHTML = '<p class="text-gray-400 text-xs col-span-3">Tidak ada kelas</p>';
            }
        });
}

function toggleSemuaKelas(prefix) {
    const isChecked = document.getElementById(`${prefix}-semua-kelas`).checked;
    document.querySelectorAll(`.${prefix}-kelas-check`).forEach(cb => cb.checked = isChecked);
}

function updateSemuaKelasState(prefix) {
    const all = document.querySelectorAll(`.${prefix}-kelas-check`);
    const checked = document.querySelectorAll(`.${prefix}-kelas-check:checked`);
    document.getElementById(`${prefix}-semua-kelas`).checked = all.length > 0 && checked.length === all.length;
    document.getElementById(`${prefix}-semua-kelas`).indeterminate = checked.length > 0 && checked.length < all.length;
}

// ===================== MODAL TAMBAH =====================
function openModalTambah() {
    document.getElementById('tambah-judul').value = '';
    if (tambahEditor) tambahEditor.setData('');
    document.getElementById('tambah-semua-kelas').checked = false;
    loadKelasList('tambah');
    document.getElementById('modal-tambah').classList.remove('hidden');
    lucide.createIcons();
}
function closeModalTambah() { document.getElementById('modal-tambah').classList.add('hidden'); }

function savePengumuman() {
    const judul = document.getElementById('tambah-judul').value.trim();
    const isi   = tambahEditor ? tambahEditor.getData() : document.getElementById('tambah-isi').value;
    const kelasIds = Array.from(document.querySelectorAll('.tambah-kelas-check:checked')).map(c => c.value);

    if (!judul) { Swal.fire({icon:'warning', text:'Judul wajib diisi', confirmButtonColor:'#1d4ed8'}); return; }
    if (!isi || isi.replace(/<[^>]*>/g,'').trim() === '') { Swal.fire({icon:'warning', text:'Isi pengumuman wajib diisi', confirmButtonColor:'#1d4ed8'}); return; }
    if (kelasIds.length === 0) { Swal.fire({icon:'warning', text:'Pilih minimal satu kelas', confirmButtonColor:'#1d4ed8'}); return; }

    const fd = new FormData();
    fd.append('action','add'); fd.append('judul',judul); fd.append('isi',isi);
    kelasIds.forEach(id => fd.append('kelas_ids[]',id));

    fetch('/admin/ajax/pengumuman_handler.php', {method:'POST', body:fd})
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                Swal.fire({icon:'success', text:res.message, timer:1500, showConfirmButton:false});
                closeModalTambah(); loadData();
            } else Swal.fire({icon:'error', text:res.message, confirmButtonColor:'#1d4ed8'});
        });
}

// ===================== MODAL EDIT =====================
function openModalEdit(id) {
    fetch(`/admin/ajax/pengumuman_handler.php?action=get_detail&id=${id}`)
        .then(r => r.json())
        .then(res => {
            if (res.status !== 'success') { Swal.fire({icon:'error', text:'Gagal memuat data'}); return; }
            const d = res.data;
            document.getElementById('edit-id').value = d.id;
            document.getElementById('edit-judul').value = d.judul;
            if (editEditor) editEditor.setData(d.isi || '');
            document.getElementById('edit-semua-kelas').checked = false;
            loadKelasList('edit', res.kelas_ids || []);
            document.getElementById('modal-edit').classList.remove('hidden');
            lucide.createIcons();
        });
}
function closeModalEdit() { document.getElementById('modal-edit').classList.add('hidden'); }

function updatePengumuman() {
    const id    = document.getElementById('edit-id').value;
    const judul = document.getElementById('edit-judul').value.trim();
    const isi   = editEditor ? editEditor.getData() : document.getElementById('edit-isi').value;
    const kelasIds = Array.from(document.querySelectorAll('.edit-kelas-check:checked')).map(c => c.value);

    if (!judul) { Swal.fire({icon:'warning', text:'Judul wajib diisi', confirmButtonColor:'#1d4ed8'}); return; }
    if (!isi || isi.replace(/<[^>]*>/g,'').trim() === '') { Swal.fire({icon:'warning', text:'Isi wajib diisi', confirmButtonColor:'#1d4ed8'}); return; }
    if (kelasIds.length === 0) { Swal.fire({icon:'warning', text:'Pilih minimal satu kelas', confirmButtonColor:'#1d4ed8'}); return; }

    const fd = new FormData();
    fd.append('action','edit'); fd.append('id',id); fd.append('judul',judul); fd.append('isi',isi);
    kelasIds.forEach(kid => fd.append('kelas_ids[]',kid));

    fetch('/admin/ajax/pengumuman_handler.php', {method:'POST', body:fd})
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                Swal.fire({icon:'success', text:res.message, timer:1500, showConfirmButton:false});
                closeModalEdit(); loadData();
            } else Swal.fire({icon:'error', text:res.message, confirmButtonColor:'#1d4ed8'});
        });
}

// ===================== DELETE =====================
function deletePengumuman(id) {
    Swal.fire({
        title:'Hapus Pengumuman?', icon:'warning', showCancelButton:true,
        confirmButtonColor:'#ef4444', cancelButtonColor:'#6b7280',
        confirmButtonText:'Hapus', cancelButtonText:'Batal'
    }).then(r => {
        if (!r.isConfirmed) return;
        const fd = new FormData(); fd.append('action','delete'); fd.append('id',id);
        fetch('/admin/ajax/pengumuman_handler.php', {method:'POST', body:fd})
            .then(r => r.json())
            .then(res => {
                if (res.status === 'success') { Swal.fire({icon:'success', text:res.message, timer:1500, showConfirmButton:false}); loadData(); }
                else Swal.fire({icon:'error', text:res.message, confirmButtonColor:'#1d4ed8'});
            });
    });
}

function escHtml(s) {
    return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

loadData();
</script>
</body>
</html>
