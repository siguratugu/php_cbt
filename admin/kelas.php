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
    <title>Data Kelas - CBT MTsN 1 Mesuji</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="bg-gray-100 min-h-screen">
<div class="flex h-screen overflow-hidden">
    <?php include __DIR__ . '/../includes/sidebar_admin.php'; ?>

    <div class="flex-1 ml-64 overflow-y-auto pb-16">
        <div class="bg-white shadow-sm px-6 py-4 flex items-center justify-between sticky top-0 z-10">
            <div>
                <h1 class="text-xl font-bold text-gray-800">Data Kelas</h1>
                <p class="text-gray-500 text-sm">Kelola data kelas</p>
            </div>
            <div class="flex items-center gap-2">
                <button id="btn-hapus-terpilih" onclick="hapusTerpilih()" class="hidden bg-red-500 hover:bg-red-600 text-white text-sm px-4 py-2 rounded-lg flex items-center gap-1">
                    <i data-lucide="trash-2" class="w-4 h-4"></i> Hapus Terpilih
                </button>
                <button onclick="openModalTambah()" class="bg-blue-700 hover:bg-blue-800 text-white text-sm px-4 py-2 rounded-lg flex items-center gap-1">
                    <i data-lucide="plus" class="w-4 h-4"></i> Tambah Kelas
                </button>
            </div>
        </div>

        <div class="p-6">
            <div class="bg-white rounded-xl shadow-sm p-4">
                <!-- Toolbar -->
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

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200">
                                <th class="px-4 py-3 text-left w-10"><input type="checkbox" id="check-all" onchange="toggleAll(this)" class="rounded"></th>
                                <th class="px-4 py-3 text-left text-gray-600 font-semibold">ID</th>
                                <th class="px-4 py-3 text-left text-gray-600 font-semibold">Nama Kelas</th>
                                <th class="px-4 py-3 text-left text-gray-600 font-semibold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="table-body">
                            <tr><td colspan="4" class="text-center py-8 text-gray-400">Memuat data...</td></tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="flex items-center justify-between mt-4 pt-4 border-t border-gray-100" id="pagination-area"></div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah -->
<div id="modal-tambah" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-800">Tambah Kelas</h2>
            <button onclick="closeModalTambah()" class="text-gray-400 hover:text-gray-600"><i data-lucide="x" class="w-5 h-5"></i></button>
        </div>
        <div class="p-6">
            <div id="rows-container" class="space-y-3 mb-4">
                <div class="flex items-center gap-2 row-item">
                    <input type="text" class="kelas-input flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500" placeholder="Nama Kelas (e.g. VII A)">
                    <button type="button" onclick="removeRow(this)" class="text-red-400 hover:text-red-600"><i data-lucide="minus-circle" class="w-5 h-5"></i></button>
                </div>
            </div>
            <button onclick="addRow()" class="flex items-center gap-1 text-blue-600 hover:text-blue-800 text-sm font-medium mb-4">
                <i data-lucide="plus-circle" class="w-4 h-4"></i> Tambah Baris
            </button>
        </div>
        <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-3">
            <button onclick="closeModalTambah()" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Batal</button>
            <button onclick="saveKelas()" class="bg-blue-700 hover:bg-blue-800 text-white px-5 py-2 rounded-lg text-sm font-medium flex items-center gap-2">
                <i data-lucide="save" class="w-4 h-4"></i> Simpan
            </button>
        </div>
    </div>
</div>

<!-- Modal Edit -->
<div id="modal-edit" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-800">Edit Kelas</h2>
            <button onclick="closeModalEdit()" class="text-gray-400 hover:text-gray-600"><i data-lucide="x" class="w-5 h-5"></i></button>
        </div>
        <div class="p-6 space-y-4">
            <input type="hidden" id="edit-id">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Kelas</label>
                <input type="text" id="edit-nama" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
            </div>
        </div>
        <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-3">
            <button onclick="closeModalEdit()" class="px-4 py-2 text-sm text-gray-600">Batal</button>
            <button onclick="updateKelas()" class="bg-blue-700 hover:bg-blue-800 text-white px-5 py-2 rounded-lg text-sm font-medium">
                <i data-lucide="save" class="w-4 h-4 inline mr-1"></i>Update
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

function loadData() {
    const perPage = document.getElementById('per-page').value;
    const url = `/admin/ajax/kelas_handler.php?action=get_all&page=${currentPage}&per_page=${perPage}`;
    fetch(url)
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') renderTable(res.data, res.total, res.per_page, res.page);
        });
}

function renderTable(data, total, perPage, page) {
    const tbody = document.getElementById('table-body');
    const info = document.getElementById('info-data');
    const pag = document.getElementById('pagination-area');

    if (data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center py-8 text-gray-400">Tidak ada data</td></tr>';
        info.textContent = 'Menampilkan 0 dari 0 data';
        pag.innerHTML = '';
        return;
    }

    const start = perPage === 'all' ? 1 : (page - 1) * parseInt(perPage) + 1;
    const end = perPage === 'all' ? total : Math.min(page * parseInt(perPage), total);
    info.textContent = `Menampilkan ${start}-${end} dari ${total} data`;

    tbody.innerHTML = data.map(row => `
        <tr class="border-b border-gray-100 hover:bg-gray-50">
            <td class="px-4 py-3"><input type="checkbox" class="row-check rounded" value="${row.id}" onchange="updateSelected()"></td>
            <td class="px-4 py-3 font-mono text-blue-600 font-semibold">${row.id}</td>
            <td class="px-4 py-3 font-medium">${escHtml(row.nama_kelas)}</td>
            <td class="px-4 py-3">
                <div class="flex items-center gap-2">
                    <button onclick="openModalEdit('${row.id}','${escHtml(row.nama_kelas)}')" class="text-blue-500 hover:text-blue-700" title="Edit">
                        <i data-lucide="pencil" class="w-4 h-4"></i>
                    </button>
                    <button onclick="deleteKelas('${row.id}')" class="text-red-500 hover:text-red-700" title="Hapus">
                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');

    // Pagination
    if (perPage !== 'all') {
        const totalPages = Math.ceil(total / parseInt(perPage));
        let pagHtml = '<div class="flex items-center gap-1">';
        if (page > 1) pagHtml += `<button onclick="changePage(${page-1})" class="px-3 py-1 rounded border text-sm hover:bg-gray-50">‹</button>`;
        for (let i = Math.max(1, page-2); i <= Math.min(totalPages, page+2); i++) {
            pagHtml += `<button onclick="changePage(${i})" class="px-3 py-1 rounded border text-sm ${i === page ? 'bg-blue-700 text-white border-blue-700' : 'hover:bg-gray-50'}">${i}</button>`;
        }
        if (page < totalPages) pagHtml += `<button onclick="changePage(${page+1})" class="px-3 py-1 rounded border text-sm hover:bg-gray-50">›</button>`;
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
    document.getElementById('check-all').indeterminate = selectedIds.length > 0 && selectedIds.length < document.querySelectorAll('.row-check').length;
    document.getElementById('check-all').checked = selectedIds.length > 0 && selectedIds.length === document.querySelectorAll('.row-check').length;
}

function toggleAll(cb) {
    document.querySelectorAll('.row-check').forEach(c => c.checked = cb.checked);
    updateSelected();
}

function openModalTambah() {
    document.getElementById('rows-container').innerHTML = `
        <div class="flex items-center gap-2 row-item">
            <input type="text" class="kelas-input flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500" placeholder="Nama Kelas (e.g. VII A)">
            <button type="button" onclick="removeRow(this)" class="text-red-400 hover:text-red-600"><i data-lucide="minus-circle" class="w-5 h-5"></i></button>
        </div>`;
    document.getElementById('modal-tambah').classList.remove('hidden');
    lucide.createIcons();
}

function closeModalTambah() { document.getElementById('modal-tambah').classList.add('hidden'); }

function addRow() {
    const cont = document.getElementById('rows-container');
    const div = document.createElement('div');
    div.className = 'flex items-center gap-2 row-item';
    div.innerHTML = `<input type="text" class="kelas-input flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500" placeholder="Nama Kelas">
        <button type="button" onclick="removeRow(this)" class="text-red-400 hover:text-red-600"><i data-lucide="minus-circle" class="w-5 h-5"></i></button>`;
    cont.appendChild(div);
    lucide.createIcons();
}

function removeRow(btn) {
    const rows = document.querySelectorAll('.row-item');
    if (rows.length > 1) btn.closest('.row-item').remove();
}

function saveKelas() {
    const inputs = Array.from(document.querySelectorAll('.kelas-input')).map(i => i.value.trim()).filter(v => v);
    if (inputs.length === 0) { Swal.fire({icon:'warning',title:'Peringatan',text:'Nama kelas wajib diisi',confirmButtonColor:'#1d4ed8'}); return; }

    const fd = new FormData();
    fd.append('action', 'add');
    inputs.forEach(v => fd.append('nama_kelas[]', v));

    fetch('/admin/ajax/kelas_handler.php', {method:'POST', body: fd})
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                Swal.fire({icon:'success',title:'Berhasil',text:res.message,timer:1500,showConfirmButton:false});
                closeModalTambah();
                loadData();
            } else {
                Swal.fire({icon:'error',title:'Gagal',text:res.message,confirmButtonColor:'#1d4ed8'});
            }
        });
}

function openModalEdit(id, nama) {
    document.getElementById('edit-id').value = id;
    document.getElementById('edit-nama').value = nama;
    document.getElementById('modal-edit').classList.remove('hidden');
}

function closeModalEdit() { document.getElementById('modal-edit').classList.add('hidden'); }

function updateKelas() {
    const id = document.getElementById('edit-id').value;
    const nama = document.getElementById('edit-nama').value.trim();
    if (!nama) { Swal.fire({icon:'warning',text:'Nama kelas wajib diisi',confirmButtonColor:'#1d4ed8'}); return; }

    const fd = new FormData();
    fd.append('action','edit'); fd.append('id',id); fd.append('nama_kelas',nama);

    fetch('/admin/ajax/kelas_handler.php', {method:'POST', body:fd})
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                Swal.fire({icon:'success',title:'Berhasil',text:res.message,timer:1500,showConfirmButton:false});
                closeModalEdit(); loadData();
            } else Swal.fire({icon:'error',title:'Gagal',text:res.message,confirmButtonColor:'#1d4ed8'});
        });
}

function deleteKelas(id) {
    Swal.fire({
        title:'Hapus Kelas?', text:`Kelas ${id} akan dihapus.`, icon:'warning',
        showCancelButton:true, confirmButtonColor:'#ef4444', cancelButtonColor:'#6b7280',
        confirmButtonText:'Hapus', cancelButtonText:'Batal'
    }).then(r => {
        if (!r.isConfirmed) return;
        const fd = new FormData(); fd.append('action','delete'); fd.append('id',id);
        fetch('/admin/ajax/kelas_handler.php', {method:'POST', body:fd})
            .then(r => r.json())
            .then(res => {
                if (res.status === 'success') { Swal.fire({icon:'success',text:res.message,timer:1500,showConfirmButton:false}); loadData(); }
                else Swal.fire({icon:'error',text:res.message,confirmButtonColor:'#1d4ed8'});
            });
    });
}

function hapusTerpilih() {
    if (selectedIds.length === 0) return;
    Swal.fire({
        title:`Hapus ${selectedIds.length} Kelas?`, icon:'warning', showCancelButton:true,
        confirmButtonColor:'#ef4444', cancelButtonColor:'#6b7280', confirmButtonText:'Hapus', cancelButtonText:'Batal'
    }).then(r => {
        if (!r.isConfirmed) return;
        const fd = new FormData(); fd.append('action','delete_multiple'); selectedIds.forEach(id => fd.append('ids[]',id));
        fetch('/admin/ajax/kelas_handler.php', {method:'POST', body:fd})
            .then(r => r.json())
            .then(res => {
                if (res.status === 'success') { Swal.fire({icon:'success',text:res.message,timer:1500,showConfirmButton:false}); loadData(); }
                else Swal.fire({icon:'error',text:res.message,confirmButtonColor:'#1d4ed8'});
            });
    });
}

function escHtml(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

loadData();
</script>
</body>
</html>
