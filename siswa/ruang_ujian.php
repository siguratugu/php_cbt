<?php
session_start();
date_default_timezone_set('Asia/Jakarta');
require_once __DIR__ . '/../includes/auth.php';
cekLogin('siswa');
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
    <?php include __DIR__ . '/../includes/sidebar_siswa.php'; ?>
    <div class="flex-1 ml-64 overflow-y-auto pb-16">
        <div class="bg-white shadow-sm px-6 py-4 flex items-center justify-between sticky top-0 z-10">
            <div>
                <h1 class="text-xl font-bold text-gray-800">Ruang Ujian</h1>
                <p class="text-gray-500 text-sm">Daftar ruang ujian yang tersedia</p>
            </div>
        </div>
        <div class="p-6">
            <div class="bg-white rounded-xl shadow-sm p-4">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <label class="text-sm text-gray-600">Tampilkan:</label>
                        <select id="per-page" onchange="currentPage=1; loadData()"
                            class="border border-gray-300 rounded-lg text-sm px-3 py-1.5">
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
                                <th class="px-4 py-3 text-left text-gray-600 font-semibold">No</th>
                                <th class="px-4 py-3 text-left text-gray-600 font-semibold">Nama Ruang Ujian</th>
                                <th class="px-4 py-3 text-left text-gray-600 font-semibold">Mapel</th>
                                <th class="px-4 py-3 text-left text-gray-600 font-semibold">Status</th>
                                <th class="px-4 py-3 text-left text-gray-600 font-semibold">Benar/Salah</th>
                                <th class="px-4 py-3 text-left text-gray-600 font-semibold">Nilai</th>
                                <th class="px-4 py-3 text-left text-gray-600 font-semibold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="table-body">
                            <tr><td colspan="7" class="text-center py-8 text-gray-400">Memuat data...</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="flex items-center justify-between mt-4">
                    <div class="text-sm text-gray-500" id="table-info"></div>
                    <div id="pagination" class="flex gap-1"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Token -->
<div id="modal-token" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-lg font-bold text-gray-800">Masukkan Token Ujian</h3>
            <button onclick="closeModalToken()" class="text-gray-400 hover:text-gray-600">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <div class="p-6">
            <p class="text-sm text-gray-600 mb-4">Masukkan token ujian yang diberikan oleh guru/admin untuk memulai ujian.</p>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Token Ujian</label>
                <input type="text" id="input-token" placeholder="Masukkan token (contoh: ABC123)"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 uppercase"
                    maxlength="10">
            </div>
            <div class="flex justify-end gap-3">
                <button onclick="closeModalToken()" class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">Batal</button>
                <button onclick="verifyToken()" class="px-4 py-2 text-sm bg-blue-700 hover:bg-blue-800 text-white rounded-lg">Mulai Ujian</button>
            </div>
        </div>
    </div>
</div>

<footer class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 py-2 text-center text-xs text-gray-500 z-10">
    &copy; <?= date('Y') ?> | Develop by Asmin Pratama
</footer>

<script>
let currentPage = 1;
let selectedRuangId = null;
let selectedRuangToken = null;

function loadData() {
    const perPage = document.getElementById('per-page').value;
    fetch(`/siswa/ajax/ujian_handler.php?action=get_ruang_list&page=${currentPage}&per_page=${perPage}`)
        .then(r => r.json())
        .then(res => {
            const tbody = document.getElementById('table-body');
            const info = document.getElementById('info-data');
            const pag = document.getElementById('pagination');
            const tinfo = document.getElementById('table-info');

            if (!res.data || res.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center py-8 text-gray-400">Tidak ada ruang ujian yang tersedia</td></tr>';
                info.textContent = '';
                pag.innerHTML = '';
                tinfo.textContent = '';
                if (typeof lucide !== 'undefined') lucide.createIcons();
                return;
            }

            const total = res.total;
            const start = perPage === 'all' ? 1 : (currentPage - 1) * parseInt(perPage) + 1;
            const end = perPage === 'all' ? total : Math.min(currentPage * parseInt(perPage), total);
            info.textContent = `${start}-${end} dari ${total} data`;
            tinfo.textContent = `Menampilkan ${start}-${end} dari ${total} data`;

            tbody.innerHTML = res.data.map((row, idx) => {
                const no = perPage === 'all' ? idx + 1 : start + idx;
                let statusBadge = '';
                let aksi = '';
                const status = row.status || 'belum';
                if (status === 'belum') {
                    statusBadge = '<span class="px-2 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700">Belum</span>';
                    aksi = `<button onclick="openModalToken(${row.id})" class="bg-blue-600 hover:bg-blue-700 text-white text-xs px-3 py-1.5 rounded-lg flex items-center gap-1"><i data-lucide="play" class="w-3 h-3"></i> Mulai Ujian</button>`;
                } else if (status === 'sedang') {
                    statusBadge = '<span class="px-2 py-1 rounded-full text-xs font-semibold bg-orange-100 text-orange-700">Sedang</span>';
                    aksi = `<a href="/siswa/ujian.php?ruang_id=${row.id}" class="bg-orange-500 hover:bg-orange-600 text-white text-xs px-3 py-1.5 rounded-lg flex items-center gap-1"><i data-lucide="rotate-ccw" class="w-3 h-3"></i> Lanjutkan</a>`;
                } else {
                    statusBadge = '<span class="px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">Selesai</span>';
                    aksi = `<button disabled class="bg-gray-300 text-gray-500 text-xs px-3 py-1.5 rounded-lg flex items-center gap-1 cursor-not-allowed"><i data-lucide="check" class="w-3 h-3"></i> Selesai</button>`;
                }
                const benarSalah = status === 'selesai' ? `${row.jumlah_benar}/${row.jumlah_salah}` : '-';
                const nilai = status === 'selesai' ? `<span class="font-bold text-blue-700">${parseFloat(row.nilai).toFixed(2)}</span>` : '-';
                return `<tr class="border-b border-gray-100 hover:bg-gray-50">
                    <td class="px-4 py-3">${no}</td>
                    <td class="px-4 py-3 font-medium text-gray-800">${escHtml(row.nama_ruang)}</td>
                    <td class="px-4 py-3 text-gray-600">${escHtml(row.nama_mapel || '-')}</td>
                    <td class="px-4 py-3">${statusBadge}</td>
                    <td class="px-4 py-3 text-gray-600">${benarSalah}</td>
                    <td class="px-4 py-3">${nilai}</td>
                    <td class="px-4 py-3">${aksi}</td>
                </tr>`;
            }).join('');

            // Pagination
            if (perPage !== 'all' && total > parseInt(perPage)) {
                const totalPages = Math.ceil(total / parseInt(perPage));
                let pagHtml = '';
                for (let i = 1; i <= totalPages; i++) {
                    pagHtml += `<button onclick="goPage(${i})" class="px-3 py-1 text-xs rounded-lg border ${i === currentPage ? 'bg-purple-700 text-white border-purple-700' : 'border-gray-300 hover:bg-gray-50'}">${i}</button>`;
                }
                pag.innerHTML = pagHtml;
            } else {
                pag.innerHTML = '';
            }
            if (typeof lucide !== 'undefined') lucide.createIcons();
        })
        .catch(() => {
            document.getElementById('table-body').innerHTML = '<tr><td colspan="7" class="text-center py-8 text-red-400">Gagal memuat data</td></tr>';
        });
}

function goPage(p) { currentPage = p; loadData(); }

function escHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function openModalToken(ruangId) {
    selectedRuangId = ruangId;
    document.getElementById('input-token').value = '';
    document.getElementById('modal-token').classList.remove('hidden');
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function closeModalToken() {
    document.getElementById('modal-token').classList.add('hidden');
    selectedRuangId = null;
}

function verifyToken() {
    const token = document.getElementById('input-token').value.trim().toUpperCase();
    if (!token) { Swal.fire('Error', 'Token wajib diisi', 'error'); return; }

    fetch('/siswa/ajax/ujian_handler.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=verify_token&token=${encodeURIComponent(token)}&ruang_id=${selectedRuangId}`
    })
    .then(r => r.json())
    .then(res => {
        if (res.status === 'success') {
            closeModalToken();
            Swal.fire({
                title: 'Siap Mengerjakan Ujian?',
                text: 'Apakah kamu sudah siap mengerjakan ujian? Pastikan koneksi internet stabil.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#1d4ed8',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, Mulai!',
                cancelButtonText: 'Batal'
            }).then(result => {
                if (result.isConfirmed) {
                    window.location.href = `/siswa/ujian.php?ruang_id=${selectedRuangId}`;
                }
            });
        } else {
            Swal.fire('Gagal', res.message || 'Token tidak valid', 'error');
        }
    });
}

document.getElementById('input-token').addEventListener('input', function() {
    this.value = this.value.toUpperCase();
});

loadData();
</script>
</body>
</html>
