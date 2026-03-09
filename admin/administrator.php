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
    <title>Administrator - CBT MTsN 1 Mesuji</title>
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
                <h1 class="text-xl font-bold text-gray-800">Administrator</h1>
                <p class="text-gray-500 text-sm">Kelola akun administrator</p>
            </div>
            <div class="flex items-center gap-2">
                <button id="btn-hapus-terpilih" onclick="hapusTerpilih()" class="hidden bg-red-500 hover:bg-red-600 text-white text-sm px-4 py-2 rounded-lg flex items-center gap-1">
                    <i data-lucide="trash-2" class="w-4 h-4"></i> Hapus Terpilih
                </button>
                <button onclick="openModalTambah()" class="bg-blue-700 hover:bg-blue-800 text-white text-sm px-4 py-2 rounded-lg flex items-center gap-1">
                    <i data-lucide="plus" class="w-4 h-4"></i> Tambah Admin
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
                                <th class="px-4 py-3 text-left text-gray-600 font-semibold">ID</th>
                                <th class="px-4 py-3 text-left text-gray-600 font-semibold">Email</th>
                                <th class="px-4 py-3 text-left text-gray-600 font-semibold">Nama Admin</th>
                                <th class="px-4 py-3 text-left text-gray-600 font-semibold">Dibuat</th>
                                <th class="px-4 py-3 text-left text-gray-600 font-semibold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="table-body">
                            <tr><td colspan="6" class="text-center py-8 text-gray-400">Memuat data...</td></tr>
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
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-800">Tambah Admin</h2>
            <button onclick="closeModalTambah()" class="text-gray-400 hover:text-gray-600"><i data-lucide="x" class="w-5 h-5"></i></button>
        </div>
        <div class="p-6 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email <span class="text-red-500">*</span></label>
                <input type="email" id="tambah-email" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500" placeholder="admin@example.com">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Admin <span class="text-red-500">*</span></label>
                <input type="text" id="tambah-nama" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500" placeholder="Nama lengkap">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Password <span class="text-red-500">*</span></label>
                <div class="relative">
                    <input type="password" id="tambah-password" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 pr-10" placeholder="Minimal 6 karakter">
                    <button type="button" onclick="togglePw('tambah-password')" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                        <i data-lucide="eye" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>
        </div>
        <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-3">
            <button onclick="closeModalTambah()" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Batal</button>
            <button onclick="saveAdmin()" class="bg-blue-700 hover:bg-blue-800 text-white px-5 py-2 rounded-lg text-sm font-medium flex items-center gap-2">
                <i data-lucide="save" class="w-4 h-4"></i> Simpan
            </button>
        </div>
    </div>
</div>

<!-- Modal Edit -->
<div id="modal-edit" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-800">Edit Admin</h2>
            <button onclick="closeModalEdit()" class="text-gray-400 hover:text-gray-600"><i data-lucide="x" class="w-5 h-5"></i></button>
        </div>
        <div class="p-6 space-y-4">
            <input type="hidden" id="edit-id">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email <span class="text-red-500">*</span></label>
                <input type="email" id="edit-email" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Admin <span class="text-red-500">*</span></label>
                <input type="text" id="edit-nama" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Password <span class="text-gray-400 font-normal">(Kosongkan jika tidak ingin mengubah)</span></label>
                <div class="relative">
                    <input type="password" id="edit-password" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 pr-10" placeholder="Password baru (opsional)">
                    <button type="button" onclick="togglePw('edit-password')" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                        <i data-lucide="eye" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>
        </div>
        <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-3">
            <button onclick="closeModalEdit()" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Batal</button>
            <button onclick="updateAdmin()" class="bg-blue-700 hover:bg-blue-800 text-white px-5 py-2 rounded-lg text-sm font-medium flex items-center gap-2">
                <i data-lucide="save" class="w-4 h-4"></i> Update
            </button>
        </div>
    </div>
</div>

<footer class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 py-2 text-center text-xs text-gray-500 z-10">
    &copy; <?= date('Y') ?> | Develop by Asmin Pratama
</footer>

<script>
const SESSION_USER_ID = '<?= htmlspecialchars($_SESSION['user_id']) ?>';
let currentPage = 1;
let selectedIds = [];

function loadData() {
    const perPage = document.getElementById('per-page').value;
    fetch(`/admin/ajax/admin_handler.php?action=get_all&page=${currentPage}&per_page=${perPage}`)
        .then(r => r.json())
        .then(res => { if (res.status === 'success') renderTable(res.data, res.total, res.per_page, res.page); });
}

function renderTable(data, total, perPage, page) {
    const tbody = document.getElementById('table-body');
    const info  = document.getElementById('info-data');
    const pag   = document.getElementById('pagination-area');

    if (!data || data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-8 text-gray-400">Tidak ada data</td></tr>';
        info.textContent = 'Menampilkan 0 dari 0 data'; pag.innerHTML = ''; return;
    }

    const start = perPage === 'all' ? 1 : (page-1)*parseInt(perPage)+1;
    const end   = perPage === 'all' ? total : Math.min(page*parseInt(perPage), total);
    info.textContent = `Menampilkan ${start}-${end} dari ${total} data`;

    tbody.innerHTML = data.map((row, idx) => {
        const isMe = row.id === SESSION_USER_ID;
        const no = perPage === 'all' ? idx+1 : start+idx;
        return `<tr class="border-b border-gray-100 hover:bg-gray-50 ${isMe ? 'bg-blue-50' : ''}">
            <td class="px-4 py-3">${isMe ? '<span class="text-blue-400 text-xs">(Anda)</span>' : `<input type="checkbox" class="row-check rounded" value="${row.id}" onchange="updateSelected()">`}</td>
            <td class="px-4 py-3 font-mono text-blue-600 font-semibold">${escHtml(row.id)}</td>
            <td class="px-4 py-3 text-gray-700">${escHtml(row.email)}</td>
            <td class="px-4 py-3 font-medium">${escHtml(row.nama)}</td>
            <td class="px-4 py-3 text-gray-500 text-xs">${row.created_at ? new Date(row.created_at).toLocaleDateString('id-ID',{day:'2-digit',month:'short',year:'numeric'}) : '-'}</td>
            <td class="px-4 py-3">
                <div class="flex items-center gap-2">
                    <button onclick="openModalEdit('${escHtml(row.id)}','${escHtml(row.email)}','${escHtml(row.nama)}')" class="text-yellow-500 hover:text-yellow-700" title="Edit">
                        <i data-lucide="pencil" class="w-4 h-4"></i>
                    </button>
                    ${isMe ? `<span class="text-gray-300" title="Tidak dapat menghapus akun sendiri"><i data-lucide="trash-2" class="w-4 h-4"></i></span>` :
                    `<button onclick="deleteAdmin('${escHtml(row.id)}')" class="text-red-500 hover:text-red-700" title="Hapus">
                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                    </button>`}
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

function openModalTambah() {
    ['tambah-email','tambah-nama','tambah-password'].forEach(id => document.getElementById(id).value = '');
    document.getElementById('modal-tambah').classList.remove('hidden');
    lucide.createIcons();
}
function closeModalTambah() { document.getElementById('modal-tambah').classList.add('hidden'); }

function saveAdmin() {
    const email = document.getElementById('tambah-email').value.trim();
    const nama  = document.getElementById('tambah-nama').value.trim();
    const pw    = document.getElementById('tambah-password').value;
    if (!email || !nama || !pw) { Swal.fire({icon:'warning', text:'Semua field wajib diisi', confirmButtonColor:'#1d4ed8'}); return; }
    if (pw.length < 6) { Swal.fire({icon:'warning', text:'Password minimal 6 karakter', confirmButtonColor:'#1d4ed8'}); return; }

    const fd = new FormData();
    fd.append('action','add'); fd.append('email',email); fd.append('nama',nama); fd.append('password',pw);
    fetch('/admin/ajax/admin_handler.php', {method:'POST', body:fd})
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                Swal.fire({icon:'success', text:res.message, timer:1500, showConfirmButton:false});
                closeModalTambah(); loadData();
            } else Swal.fire({icon:'error', text:res.message, confirmButtonColor:'#1d4ed8'});
        });
}

function openModalEdit(id, email, nama) {
    document.getElementById('edit-id').value = id;
    document.getElementById('edit-email').value = email;
    document.getElementById('edit-nama').value = nama;
    document.getElementById('edit-password').value = '';
    document.getElementById('modal-edit').classList.remove('hidden');
    lucide.createIcons();
}
function closeModalEdit() { document.getElementById('modal-edit').classList.add('hidden'); }

function updateAdmin() {
    const id    = document.getElementById('edit-id').value;
    const email = document.getElementById('edit-email').value.trim();
    const nama  = document.getElementById('edit-nama').value.trim();
    const pw    = document.getElementById('edit-password').value;
    if (!email || !nama) { Swal.fire({icon:'warning', text:'Email dan nama wajib diisi', confirmButtonColor:'#1d4ed8'}); return; }
    if (pw && pw.length < 6) { Swal.fire({icon:'warning', text:'Password minimal 6 karakter', confirmButtonColor:'#1d4ed8'}); return; }

    const fd = new FormData();
    fd.append('action','edit'); fd.append('id',id); fd.append('email',email); fd.append('nama',nama);
    if (pw) fd.append('password',pw);
    fetch('/admin/ajax/admin_handler.php', {method:'POST', body:fd})
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                Swal.fire({icon:'success', text:res.message, timer:1500, showConfirmButton:false});
                closeModalEdit(); loadData();
            } else Swal.fire({icon:'error', text:res.message, confirmButtonColor:'#1d4ed8'});
        });
}

function deleteAdmin(id) {
    if (id === SESSION_USER_ID) { Swal.fire({icon:'warning', text:'Tidak dapat menghapus akun Anda sendiri', confirmButtonColor:'#1d4ed8'}); return; }
    Swal.fire({
        title:'Hapus Admin?', text:`Admin ${id} akan dihapus.`, icon:'warning',
        showCancelButton:true, confirmButtonColor:'#ef4444', cancelButtonColor:'#6b7280',
        confirmButtonText:'Hapus', cancelButtonText:'Batal'
    }).then(r => {
        if (!r.isConfirmed) return;
        const fd = new FormData(); fd.append('action','delete'); fd.append('id',id);
        fetch('/admin/ajax/admin_handler.php', {method:'POST', body:fd})
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
        title:`Hapus ${selectedIds.length} Admin?`, icon:'warning', showCancelButton:true,
        confirmButtonColor:'#ef4444', cancelButtonColor:'#6b7280', confirmButtonText:'Hapus', cancelButtonText:'Batal'
    }).then(r => {
        if (!r.isConfirmed) return;
        const fd = new FormData(); fd.append('action','delete_multiple');
        selectedIds.forEach(id => fd.append('ids[]',id));
        fetch('/admin/ajax/admin_handler.php', {method:'POST', body:fd})
            .then(r => r.json())
            .then(res => {
                if (res.status === 'success') { Swal.fire({icon:'success', text:res.message, timer:1500, showConfirmButton:false}); loadData(); }
                else Swal.fire({icon:'error', text:res.message, confirmButtonColor:'#1d4ed8'});
            });
    });
}

function togglePw(id) {
    const input = document.getElementById(id);
    input.type = input.type === 'password' ? 'text' : 'password';
}

function escHtml(s) {
    return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

loadData();
</script>
</body>
</html>
