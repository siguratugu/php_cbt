<?php
session_start();
date_default_timezone_set('Asia/Jakarta');
require_once __DIR__ . '/../includes/auth.php';
cekLogin('guru');
require_once __DIR__ . '/../config/database.php';

$bank_soal_id = (int)($_GET['id'] ?? 0);
if (!$bank_soal_id) {
    header('Location: /guru/bank_soal.php');
    exit;
}

$guru_id = (int)$_SESSION['user_id'];
$stmt = $conn->prepare(
    "SELECT bs.*, m.nama_mapel, g.nama AS nama_guru
     FROM bank_soal bs
     LEFT JOIN mapel m ON bs.mapel_id = m.id
     LEFT JOIN guru  g ON bs.guru_id  = g.id
     WHERE bs.id = ? AND bs.guru_id = ?"
);
$stmt->bind_param("ii", $bank_soal_id, $guru_id);
$stmt->execute();
$bankSoal = $stmt->get_result()->fetch_assoc();
if (!$bankSoal) {
    header('Location: /guru/bank_soal.php');
    exit;
}

$pembuat = $bankSoal['nama_guru'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Soal: <?= htmlspecialchars($bankSoal['nama_soal']) ?> - CBT MTsN 1 Mesuji</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>
    <style>
        .ck-editor__editable { min-height: 80px; max-height: 200px; overflow-y: auto; }
        .ck-editor__editable_inline { min-height: 80px; }
        .tab-active { border-bottom: 3px solid #1d4ed8; color: #1d4ed8; font-weight: 600; }
        .tab-inactive { border-bottom: 3px solid transparent; color: #6b7280; }
        .soal-btn-active  { background: #2563eb; color: #fff; box-shadow: 0 0 0 3px rgba(37,99,235,.3); }
        .soal-btn-done    { background: #16a34a; color: #fff; }
        .soal-btn-empty   { background: #e5e7eb; color: #374151; }
        #soal-grid::-webkit-scrollbar { width: 4px; }
        #soal-grid::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        .spinner { border: 3px solid #e5e7eb; border-top-color: #2563eb; border-radius: 50%; width: 28px; height: 28px; animation: spin .7s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body class="bg-gray-50 flex flex-col h-screen overflow-hidden">

<!-- ==================== TOP HEADER BAR ==================== -->
<header class="bg-green-700 text-white px-5 py-3 flex items-center justify-between shadow-lg flex-shrink-0 z-20">
    <div class="flex items-center gap-4 min-w-0">
        <a href="/guru/bank_soal.php"
            class="flex items-center gap-1.5 bg-green-600 hover:bg-green-500 px-3 py-1.5 rounded-lg text-sm font-medium transition-colors flex-shrink-0">
            <i data-lucide="arrow-left" class="w-4 h-4"></i> Kembali
        </a>
        <div class="min-w-0">
            <h1 class="font-bold text-base leading-tight truncate">
                Buat Soal: <span class="text-yellow-300"><?= htmlspecialchars($bankSoal['nama_soal']) ?></span>
            </h1>
            <p class="text-green-200 text-xs truncate">
                <?= $bankSoal['nama_mapel'] ? htmlspecialchars($bankSoal['nama_mapel']).' · ' : '' ?>
                <?= $bankSoal['waktu_mengerjakan'] ?> menit · Pembuat: <?= htmlspecialchars($pembuat) ?>
            </p>
        </div>
    </div>
    <div class="flex items-center gap-2 flex-shrink-0">
        <span id="total-count-badge"
            class="bg-green-600 text-white text-xs font-semibold px-3 py-1.5 rounded-full flex items-center gap-1.5">
            <i data-lucide="list-ordered" class="w-3.5 h-3.5"></i>
            <span id="total-count-text">0 soal</span>
        </span>
        <button onclick="simpanKeBank()"
            class="bg-white text-green-700 hover:bg-blue-50 text-sm font-semibold px-4 py-1.5 rounded-lg flex items-center gap-2 transition-colors shadow">
            <i data-lucide="save" class="w-4 h-4"></i> Simpan ke Bank
        </button>
    </div>
</header>

<!-- ==================== TABS ==================== -->
<div class="bg-white border-b border-gray-200 flex px-5 flex-shrink-0 shadow-sm z-10">
    <button onclick="switchTab('manual')" id="tab-btn-manual"
        class="px-5 py-3 text-sm transition-all tab-active flex items-center gap-2">
        <i data-lucide="pencil-line" class="w-4 h-4"></i> Buat Soal Manual
    </button>
    <button onclick="switchTab('word')" id="tab-btn-word"
        class="px-5 py-3 text-sm transition-all tab-inactive flex items-center gap-2">
        <i data-lucide="file-text" class="w-4 h-4"></i> Import Word (.docx)
    </button>
    <button onclick="switchTab('excel')" id="tab-btn-excel"
        class="px-5 py-3 text-sm transition-all tab-inactive flex items-center gap-2">
        <i data-lucide="table-2" class="w-4 h-4"></i> Import Excel (.xlsx)
    </button>
</div>

<!-- ==================== MAIN CONTENT ==================== -->
<div class="flex-1 overflow-hidden">

    <!-- TAB: MANUAL -->
    <div id="tab-manual" class="flex h-full">

        <!-- LEFT PANEL: Question Navigator -->
        <div class="w-56 xl:w-64 bg-white border-r border-gray-200 flex flex-col flex-shrink-0 shadow-sm">
            <div class="px-4 py-3 border-b border-gray-100 bg-gray-50">
                <h3 class="text-sm font-semibold text-gray-700 flex items-center gap-2">
                    <i data-lucide="layers" class="w-4 h-4 text-blue-600"></i> Daftar Soal
                </h3>
                <p class="text-xs text-gray-400 mt-0.5">Klik nomor untuk edit soal</p>
            </div>

            <!-- Question grid -->
            <div id="soal-grid" class="flex-1 overflow-y-auto p-3 flex flex-wrap gap-2 content-start">
                <div class="w-full flex justify-center py-6">
                    <div class="spinner"></div>
                </div>
            </div>

            <!-- Legend -->
            <div class="p-3 border-t border-gray-100 bg-gray-50 space-y-1.5">
                <div class="flex items-center gap-2 text-xs text-gray-500">
                    <span class="w-4 h-4 rounded bg-green-600 inline-block flex-shrink-0"></span> Aktif
                </div>
                <div class="flex items-center gap-2 text-xs text-gray-500">
                    <span class="w-4 h-4 rounded bg-green-600 inline-block flex-shrink-0"></span> Terisi
                </div>
                <div class="flex items-center gap-2 text-xs text-gray-500">
                    <span class="w-4 h-4 rounded bg-gray-200 inline-block flex-shrink-0"></span> Kosong
                </div>
            </div>
        </div>

        <!-- RIGHT PANEL: Question Editor -->
        <div class="flex-1 flex flex-col overflow-hidden">

            <!-- Empty state -->
            <div id="empty-state" class="flex-1 flex items-center justify-center text-center p-8">
                <div class="max-w-sm">
                    <div class="w-20 h-20 bg-blue-50 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="file-question" class="w-10 h-10 text-blue-400"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-700 mb-2">Pilih atau Tambah Soal</h3>
                    <p class="text-gray-400 text-sm mb-5">
                        Klik nomor soal di panel kiri untuk mulai mengedit, atau klik tombol
                        <span class="font-semibold text-blue-600">+</span> untuk menambah soal baru.
                    </p>
                    <button onclick="addNewSoal()"
                        class="bg-green-700 hover:bg-blue-800 text-white px-5 py-2 rounded-xl text-sm font-medium inline-flex items-center gap-2 transition-colors">
                        <i data-lucide="plus-circle" class="w-4 h-4"></i> Tambah Soal Pertama
                    </button>
                </div>
            </div>

            <!-- Editor Panel (hidden until question is selected) -->
            <div id="editor-panel" class="flex-1 flex flex-col overflow-hidden hidden">

                <!-- Editor toolbar -->
                <div class="bg-white border-b border-gray-200 px-5 py-3 flex items-center justify-between flex-shrink-0 shadow-sm">
                    <div class="flex items-center gap-3">
                        <span id="soal-number-display"
                            class="bg-green-700 text-white text-sm font-bold px-3 py-1 rounded-lg min-w-[72px] text-center">
                            Soal #1
                        </span>
                        <div>
                            <label class="text-xs text-gray-500 block mb-0.5">Jenis Soal</label>
                            <select id="sel-jenis-soal" onchange="handleJenisChange()"
                                class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white">
                                <option value="pg">Pilihan Ganda (PG)</option>
                                <option value="esai">Esai</option>
                                <option value="menjodohkan">Menjodohkan</option>
                                <option value="benar_salah">Benar / Salah</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button id="btn-hapus-soal" onclick="hapusSoal()"
                            class="flex items-center gap-1.5 text-red-500 hover:text-white hover:bg-red-500 border border-red-300 hover:border-red-500 px-3 py-1.5 rounded-lg text-sm font-medium transition-all">
                            <i data-lucide="trash-2" class="w-4 h-4"></i> Hapus Soal
                        </button>
                        <button onclick="saveSoal()"
                            class="flex items-center gap-1.5 bg-green-700 hover:bg-blue-800 text-white px-4 py-1.5 rounded-lg text-sm font-medium transition-colors shadow-sm">
                            <i data-lucide="save" class="w-4 h-4"></i> Simpan Soal
                        </button>
                    </div>
                </div>

                <!-- Dynamic question form area -->
                <div id="question-form-area" class="flex-1 overflow-y-auto p-5">
                    <!-- Filled by JS -->
                </div>
            </div>
        </div>
    </div><!-- end tab-manual -->

    <!-- TAB: IMPORT WORD -->
    <div id="tab-word" class="hidden h-full overflow-y-auto p-8">
        <div class="max-w-xl mx-auto">
            <div class="bg-white rounded-2xl shadow-sm p-8 border border-gray-200">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                        <i data-lucide="file-text" class="w-6 h-6 text-blue-600"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-gray-800">Import dari Word</h2>
                        <p class="text-sm text-gray-500">Upload file .docx sesuai template</p>
                    </div>
                </div>

                <div class="mb-5 p-4 bg-blue-50 rounded-xl border border-blue-200 flex items-start gap-3">
                    <i data-lucide="info" class="w-5 h-5 text-blue-500 flex-shrink-0 mt-0.5"></i>
                    <div>
                        <p class="text-sm font-medium text-blue-800">Panduan Import Word</p>
                        <p class="text-xs text-green-700 mt-1">Download template di bawah, isi soal sesuai format yang tersedia, lalu upload kembali.</p>
                    </div>
                </div>

                <a href="/template/template_soal.php?type=word"
                    class="flex items-center gap-2 bg-gray-800 hover:bg-gray-900 text-white px-4 py-2.5 rounded-xl text-sm font-medium transition-colors mb-6 w-fit">
                    <i data-lucide="download" class="w-4 h-4"></i> Download Template Word
                </a>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Pilih File .docx</label>
                        <div id="drop-word"
                            class="border-2 border-dashed border-gray-300 rounded-xl p-8 text-center hover:border-blue-400 hover:bg-blue-50/50 transition-all cursor-pointer"
                            onclick="document.getElementById('file-word').click()">
                            <i data-lucide="upload-cloud" class="w-10 h-10 text-gray-400 mx-auto mb-3"></i>
                            <p class="text-sm text-gray-600 font-medium">Klik untuk memilih file</p>
                            <p class="text-xs text-gray-400 mt-1">atau drag & drop file .docx di sini</p>
                            <p id="word-file-name" class="text-xs text-blue-600 font-medium mt-2 hidden"></p>
                        </div>
                        <input type="file" id="file-word" accept=".docx" class="hidden" onchange="previewFileName('word')">
                    </div>
                    <button onclick="importWord()"
                        class="w-full bg-green-700 hover:bg-blue-800 text-white py-2.5 rounded-xl text-sm font-semibold flex items-center justify-center gap-2 transition-colors">
                        <i data-lucide="upload" class="w-4 h-4"></i> Upload dan Import
                    </button>
                </div>
            </div>
        </div>
    </div><!-- end tab-word -->

    <!-- TAB: IMPORT EXCEL -->
    <div id="tab-excel" class="hidden h-full overflow-y-auto p-8">
        <div class="max-w-xl mx-auto">
            <div class="bg-white rounded-2xl shadow-sm p-8 border border-gray-200">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                        <i data-lucide="table-2" class="w-6 h-6 text-green-600"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-gray-800">Import dari Excel</h2>
                        <p class="text-sm text-gray-500">Upload file .xlsx sesuai template</p>
                    </div>
                </div>

                <div class="mb-5 p-4 bg-green-50 rounded-xl border border-green-200 flex items-start gap-3">
                    <i data-lucide="info" class="w-5 h-5 text-green-600 flex-shrink-0 mt-0.5"></i>
                    <div>
                        <p class="text-sm font-medium text-green-800">Panduan Import Excel</p>
                        <p class="text-xs text-green-700 mt-1">Download template, isi data soal di setiap kolom sesuai format, lalu upload. Mendukung PG, Esai, Menjodohkan, dan Benar/Salah.</p>
                    </div>
                </div>

                <a href="/template/template_soal.php?type=excel"
                    class="flex items-center gap-2 bg-green-700 hover:bg-green-800 text-white px-4 py-2.5 rounded-xl text-sm font-medium transition-colors mb-6 w-fit">
                    <i data-lucide="download" class="w-4 h-4"></i> Download Template Excel
                </a>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Pilih File .xlsx</label>
                        <div id="drop-excel"
                            class="border-2 border-dashed border-gray-300 rounded-xl p-8 text-center hover:border-green-400 hover:bg-green-50/50 transition-all cursor-pointer"
                            onclick="document.getElementById('file-excel').click()">
                            <i data-lucide="upload-cloud" class="w-10 h-10 text-gray-400 mx-auto mb-3"></i>
                            <p class="text-sm text-gray-600 font-medium">Klik untuk memilih file</p>
                            <p class="text-xs text-gray-400 mt-1">atau drag & drop file .xlsx di sini</p>
                            <p id="excel-file-name" class="text-xs text-green-600 font-medium mt-2 hidden"></p>
                        </div>
                        <input type="file" id="file-excel" accept=".xlsx,.xls" class="hidden" onchange="previewFileName('excel')">
                    </div>
                    <button onclick="importExcel()"
                        class="w-full bg-green-700 hover:bg-green-800 text-white py-2.5 rounded-xl text-sm font-semibold flex items-center justify-center gap-2 transition-colors">
                        <i data-lucide="upload" class="w-4 h-4"></i> Upload dan Import
                    </button>
                </div>
            </div>
        </div>
    </div><!-- end tab-excel -->
</div><!-- end main content -->

<!-- Footer -->
<footer class="bg-white border-t border-gray-200 py-2 text-center text-xs text-gray-400 flex-shrink-0 z-10">
    &copy; <?= date('Y') ?> | Develop by Asmin Pratama
</footer>

<!-- ==================== JAVASCRIPT ==================== -->
<script>
/* ============================================================
   CONSTANTS & STATE
   ============================================================ */
const BANK_SOAL_ID = <?= $bank_soal_id ?>;
const BOBOT = {
    pg:           <?= (float)$bankSoal['bobot_pg'] ?>,
    esai:         <?= (float)$bankSoal['bobot_esai'] ?>,
    menjodohkan:  <?= (float)$bankSoal['bobot_menjodohkan'] ?>,
    benar_salah:  <?= (float)$bankSoal['bobot_benar_salah'] ?>,
};

let soalList    = [];    // array of soal objects loaded from server
let currentNomor = null; // currently displayed nomor_soal
let editors     = {};    // active CKEditor instances { key: editorInstance }
let isSaving    = false;

const CK_CONFIG = {
    toolbar: ['bold', 'italic', 'underline', '|', 'numberedList', 'bulletedList', '|', 'undo', 'redo'],
};
const CK_CONFIG_SM = {
    toolbar: ['bold', 'italic', 'underline', '|', 'undo', 'redo'],
};

/* ============================================================
   INIT
   ============================================================ */
document.addEventListener('DOMContentLoaded', () => {
    lucide.createIcons();
    loadSoalList();
});

/* ============================================================
   TABS
   ============================================================ */
function switchTab(tab) {
    ['manual', 'word', 'excel'].forEach(t => {
        document.getElementById(`tab-${t}`).classList.toggle('hidden', t !== tab);
        const btn = document.getElementById(`tab-btn-${t}`);
        btn.className = btn.className.replace(/tab-active|tab-inactive/g, '').trim()
            + (t === tab ? ' tab-active' : ' tab-inactive');
    });
    if (tab === 'manual') {
        lucide.createIcons();
    }
}

/* ============================================================
   LOAD SOAL LIST
   ============================================================ */
async function loadSoalList() {
    try {
        const res  = await fetch(`/guru/ajax/soal_handler.php?action=get_soal&bank_soal_id=${BANK_SOAL_ID}`);
        const data = await res.json();
        if (data.status === 'success') {
            soalList = data.data;
            renderLeftPanel();
            updateTotalCount();
        }
    } catch (e) {
        console.error('Gagal memuat soal:', e);
    }
}

/* ============================================================
   RENDER LEFT PANEL
   ============================================================ */
function renderLeftPanel() {
    const grid = document.getElementById('soal-grid');
    let html   = '';

    soalList.forEach(s => {
        const isActive  = s.nomor_soal === currentNomor;
        const hasContent = s.pertanyaan && s.pertanyaan.replace(/<[^>]+>/g,'').trim() !== '';
        let cls = 'w-10 h-10 rounded-lg text-sm font-bold transition-all hover:scale-105 ';
        if (isActive)       cls += 'soal-btn-active';
        else if (hasContent) cls += 'soal-btn-done';
        else                 cls += 'soal-btn-empty hover:bg-gray-300';
        html += `<button class="${cls}" onclick="clickSoal(${s.nomor_soal})" id="btn-soal-${s.nomor_soal}">${s.nomor_soal}</button>`;
    });

    // "+" add button
    html += `<button onclick="addNewSoal()"
        class="w-10 h-10 rounded-lg text-sm font-bold text-gray-400 hover:text-blue-600 bg-gray-100 hover:bg-blue-50 border-2 border-dashed border-gray-300 hover:border-blue-400 flex items-center justify-center transition-all"
        title="Tambah Soal Baru">
        <i data-lucide="plus" class="w-4 h-4"></i>
    </button>`;

    grid.innerHTML = html;
    lucide.createIcons();
}

/* ============================================================
   UPDATE TOTAL COUNT
   ============================================================ */
function updateTotalCount() {
    const answered = soalList.filter(s => s.pertanyaan && s.pertanyaan.replace(/<[^>]+>/g,'').trim() !== '').length;
    document.getElementById('total-count-text').textContent = `${answered} soal`;
}

/* ============================================================
   CLICK QUESTION BUTTON
   ============================================================ */
async function clickSoal(nomor) {
    if (nomor === currentNomor) return;
    currentNomor = nomor;
    renderLeftPanel();
    await showEditorForNomor(nomor);
}

/* ============================================================
   ADD NEW SOAL
   ============================================================ */
async function addNewSoal() {
    const nextNomor = soalList.length > 0 ? Math.max(...soalList.map(s => s.nomor_soal)) + 1 : 1;

    // Check if already in list (shouldn't happen but guard)
    if (soalList.find(s => s.nomor_soal === nextNomor)) {
        await clickSoal(nextNomor);
        return;
    }

    // Add optimistic entry to soalList
    soalList.push({ id: null, nomor_soal: nextNomor, jenis_soal: 'pg', pertanyaan: '' });
    currentNomor = nextNomor;
    renderLeftPanel();
    await showEditorForNomor(nextNomor);
}

/* ============================================================
   SHOW EDITOR FOR NOMOR
   ============================================================ */
async function showEditorForNomor(nomor) {
    document.getElementById('empty-state').classList.add('hidden');
    document.getElementById('editor-panel').classList.remove('hidden');

    const soal = soalList.find(s => s.nomor_soal === nomor);
    const jenis = soal ? (soal.jenis_soal || 'pg') : 'pg';

    document.getElementById('soal-number-display').textContent = `Soal #${nomor}`;
    document.getElementById('sel-jenis-soal').value = jenis;

    await buildForm(jenis, soal);
}

/* ============================================================
   HANDLE JENIS CHANGE (dropdown)
   ============================================================ */
async function handleJenisChange() {
    const jenis = document.getElementById('sel-jenis-soal').value;
    // Get current pertanyaan data before rebuilding
    const prevPertanyaan = editors['pertanyaan'] ? editors['pertanyaan'].getData() : '';
    await buildForm(jenis, { pertanyaan: prevPertanyaan });
}

/* ============================================================
   BUILD FORM
   ============================================================ */
async function buildForm(jenis, soalData) {
    await destroyAllEditors();

    const area = document.getElementById('question-form-area');
    area.innerHTML = buildFormHTML(jenis);
    lucide.createIcons();

    // Init CKEditor: Pertanyaan
    try {
        const pertEl = document.getElementById('ck-pertanyaan');
        if (pertEl) {
            editors['pertanyaan'] = await ClassicEditor.create(pertEl, CK_CONFIG);
            if (soalData && soalData.pertanyaan) {
                editors['pertanyaan'].setData(soalData.pertanyaan);
            }
        }
    } catch (e) { console.error('CKEditor pertanyaan error:', e); }

    // PG: init option editors
    if (jenis === 'pg') {
        for (const opt of ['a','b','c','d','e']) {
            try {
                const el = document.getElementById(`ck-opsi-${opt}`);
                if (el) {
                    editors[`opsi_${opt}`] = await ClassicEditor.create(el, CK_CONFIG_SM);
                    if (soalData && soalData[`opsi_${opt}`]) {
                        editors[`opsi_${opt}`].setData(soalData[`opsi_${opt}`]);
                    }
                }
            } catch (e) { console.error(`CKEditor opsi_${opt} error:`, e); }
        }
        if (soalData && soalData.kunci_jawaban) {
            const sel = document.getElementById('sel-kunci');
            if (sel) sel.value = soalData.kunci_jawaban;
        }

    } else if (jenis === 'esai') {
        if (soalData && soalData.kunci_jawaban) {
            const ta = document.getElementById('txt-kunci-esai');
            if (ta) ta.value = soalData.kunci_jawaban;
        }

    } else if (jenis === 'menjodohkan') {
        let kiri = [], kanan = [];
        if (soalData && soalData.pasangan_kiri) {
            try { kiri  = JSON.parse(soalData.pasangan_kiri);  } catch(e) {}
            try { kanan = JSON.parse(soalData.pasangan_kanan); } catch(e) {}
        }
        if (kiri.length === 0) kiri = [''];
        if (kanan.length === 0) kanan = [''];
        for (let i = 0; i < Math.max(kiri.length, kanan.length); i++) {
            addMenjodohkanPair(kiri[i] || '', kanan[i] || '');
        }
        lucide.createIcons();

    } else if (jenis === 'benar_salah') {
        if (soalData && soalData.jawaban_bs) {
            const radio = document.querySelector(`input[name="jawaban_bs"][value="${soalData.jawaban_bs}"]`);
            if (radio) radio.checked = true;
        }
    }
}

/* ============================================================
   BUILD FORM HTML
   ============================================================ */
function buildFormHTML(jenis) {
    const sectionClass = 'bg-white rounded-xl border border-gray-200 p-5 shadow-sm mb-4';
    const labelClass   = 'block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-1.5';

    let html = `
    <div class="${sectionClass}">
        <label class="${labelClass}"><i data-lucide="help-circle" class="w-4 h-4 text-blue-500"></i> Pertanyaan</label>
        <div id="ck-pertanyaan"></div>
    </div>`;

    if (jenis === 'pg') {
        const opts = [
            { key: 'a', color: 'blue'   },
            { key: 'b', color: 'green'  },
            { key: 'c', color: 'yellow' },
            { key: 'd', color: 'orange' },
            { key: 'e', color: 'purple' },
        ];
        html += `<div class="${sectionClass}">
            <label class="${labelClass}"><i data-lucide="list" class="w-4 h-4 text-blue-500"></i> Pilihan Jawaban</label>
            <div class="space-y-3">`;
        opts.forEach(({ key, color }) => {
            html += `<div class="flex items-start gap-3">
                <span class="flex-shrink-0 w-7 h-7 rounded-full bg-${color}-100 text-${color}-700 text-sm font-bold flex items-center justify-center mt-1">${key.toUpperCase()}</span>
                <div class="flex-1"><div id="ck-opsi-${key}"></div></div>
            </div>`;
        });
        html += `</div></div>`;

        html += `<div class="${sectionClass}">
            <label class="${labelClass}"><i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i> Kunci Jawaban</label>
            <select id="sel-kunci" class="border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-blue-500 w-40 bg-white">
                <option value="A">A</option>
                <option value="B">B</option>
                <option value="C">C</option>
                <option value="D">D</option>
                <option value="E">E</option>
            </select>
        </div>`;

    } else if (jenis === 'esai') {
        html += `<div class="${sectionClass}">
            <label class="${labelClass}"><i data-lucide="edit-3" class="w-4 h-4 text-purple-500"></i> Kunci Jawaban <span class="text-gray-400 font-normal text-xs">(Opsional)</span></label>
            <textarea id="txt-kunci-esai" rows="4" placeholder="Masukkan kunci jawaban atau pedoman penilaian (opsional)..."
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 resize-none"></textarea>
        </div>`;

    } else if (jenis === 'menjodohkan') {
        html += `<div class="${sectionClass}">
            <div class="flex items-center justify-between mb-3">
                <label class="${labelClass} mb-0"><i data-lucide="git-merge" class="w-4 h-4 text-orange-500"></i> Pasangan Jawaban</label>
                <button onclick="addMenjodohkanPair('','')"
                    class="flex items-center gap-1.5 text-sm text-blue-600 hover:text-blue-800 font-medium border border-blue-300 hover:border-blue-500 px-3 py-1 rounded-lg transition-colors">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i> Tambah Pasangan
                </button>
            </div>
            <div class="grid grid-cols-2 gap-2 mb-2 px-1">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Pernyataan (Kiri)</p>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Jawaban (Kanan)</p>
            </div>
            <div id="pairs-container" class="space-y-2"></div>
        </div>`;

    } else if (jenis === 'benar_salah') {
        html += `<div class="${sectionClass}">
            <label class="${labelClass}"><i data-lucide="toggle-left" class="w-4 h-4 text-teal-500"></i> Jawaban Benar</label>
            <div class="flex items-center gap-6 mt-2">
                <label class="flex items-center gap-3 cursor-pointer group">
                    <input type="radio" name="jawaban_bs" value="benar"
                        class="w-5 h-5 text-green-600 border-gray-300 focus:ring-green-500 cursor-pointer">
                    <div class="flex items-center gap-2 group-hover:text-green-700 transition-colors">
                        <span class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center">
                            <i data-lucide="check" class="w-4 h-4 text-green-600"></i>
                        </span>
                        <span class="text-sm font-semibold text-gray-700">Benar</span>
                    </div>
                </label>
                <label class="flex items-center gap-3 cursor-pointer group">
                    <input type="radio" name="jawaban_bs" value="salah"
                        class="w-5 h-5 text-red-500 border-gray-300 focus:ring-red-500 cursor-pointer">
                    <div class="flex items-center gap-2 group-hover:text-red-700 transition-colors">
                        <span class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center">
                            <i data-lucide="x" class="w-4 h-4 text-red-500"></i>
                        </span>
                        <span class="text-sm font-semibold text-gray-700">Salah</span>
                    </div>
                </label>
            </div>
        </div>`;
    }

    return html;
}

/* ============================================================
   MENJODOHKAN PAIRS
   ============================================================ */
function addMenjodohkanPair(kiri = '', kanan = '') {
    const container = document.getElementById('pairs-container');
    if (!container) return;
    const row = document.createElement('div');
    row.className = 'flex items-center gap-2 pair-row';
    row.innerHTML = `
        <input type="text" class="inp-kiri flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500"
            placeholder="Pernyataan..." value="${escHtml(kiri)}">
        <i data-lucide="arrow-right" class="w-4 h-4 text-gray-400 flex-shrink-0"></i>
        <input type="text" class="inp-kanan flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500"
            placeholder="Jawaban..." value="${escHtml(kanan)}">
        <button type="button" onclick="removeMenjodohkanPair(this)"
            class="text-red-400 hover:text-red-600 transition-colors flex-shrink-0" title="Hapus Pasangan">
            <i data-lucide="x-circle" class="w-5 h-5"></i>
        </button>`;
    container.appendChild(row);
    lucide.createIcons();
}

function removeMenjodohkanPair(btn) {
    const rows = document.querySelectorAll('.pair-row');
    if (rows.length <= 1) {
        Swal.fire({ icon: 'warning', text: 'Minimal harus ada 1 pasangan!', toast: true, position: 'top-end', timer: 2000, showConfirmButton: false });
        return;
    }
    btn.closest('.pair-row').remove();
}

/* ============================================================
   SAVE SOAL
   ============================================================ */
async function saveSoal() {
    if (currentNomor === null) { Swal.fire({ icon: 'warning', text: 'Tidak ada soal yang aktif' }); return; }
    if (isSaving) return;
    isSaving = true;

    const jenis       = document.getElementById('sel-jenis-soal').value;
    const pertanyaan  = editors['pertanyaan'] ? editors['pertanyaan'].getData() : '';

    if (!pertanyaan || pertanyaan.replace(/<[^>]+>/g, '').trim() === '') {
        Swal.fire({ icon: 'warning', title: 'Perhatian', text: 'Pertanyaan tidak boleh kosong!', confirmButtonColor: '#1d4ed8' });
        isSaving = false;
        return;
    }

    const fd = new FormData();
    fd.append('action',        'save_soal');
    fd.append('bank_soal_id',  BANK_SOAL_ID);
    fd.append('nomor_soal',    currentNomor);
    fd.append('jenis_soal',    jenis);
    fd.append('pertanyaan',    pertanyaan);

    if (jenis === 'pg') {
        ['a','b','c','d','e'].forEach(opt => {
            fd.append(`opsi_${opt}`, editors[`opsi_${opt}`] ? editors[`opsi_${opt}`].getData() : '');
        });
        fd.append('kunci_jawaban', document.getElementById('sel-kunci')?.value || 'A');

    } else if (jenis === 'esai') {
        fd.append('kunci_jawaban', document.getElementById('txt-kunci-esai')?.value || '');

    } else if (jenis === 'menjodohkan') {
        const kiri = [], kanan = [];
        document.querySelectorAll('.pair-row').forEach(row => {
            kiri.push(row.querySelector('.inp-kiri')?.value || '');
            kanan.push(row.querySelector('.inp-kanan')?.value || '');
        });
        fd.append('pasangan_kiri',    JSON.stringify(kiri));
        fd.append('pasangan_kanan',   JSON.stringify(kanan));
        fd.append('pasangan_jawaban', JSON.stringify(kanan)); // direct mapping

    } else if (jenis === 'benar_salah') {
        const checked = document.querySelector('input[name="jawaban_bs"]:checked');
        if (!checked) {
            Swal.fire({ icon: 'warning', text: 'Pilih jawaban Benar atau Salah!', confirmButtonColor: '#1d4ed8' });
            isSaving = false;
            return;
        }
        fd.append('jawaban_bs', checked.value);
    }

    try {
        const res  = await fetch('/guru/ajax/soal_handler.php', { method: 'POST', body: fd });
        const data = await res.json();

        if (data.status === 'success') {
            // Update local state
            const idx = soalList.findIndex(s => s.nomor_soal === currentNomor);
            const updated = { nomor_soal: currentNomor, id: data.soal_id, jenis_soal: jenis, pertanyaan };
            if (idx >= 0) soalList[idx] = { ...soalList[idx], ...updated };
            else          soalList.push(updated);

            renderLeftPanel();
            updateTotalCount();
            Swal.fire({ icon: 'success', text: `Soal #${currentNomor} berhasil disimpan`, timer: 1200, showConfirmButton: false, toast: true, position: 'top-end' });
        } else {
            Swal.fire({ icon: 'error', title: 'Gagal Menyimpan', text: data.message, confirmButtonColor: '#1d4ed8' });
        }
    } catch (e) {
        Swal.fire({ icon: 'error', text: 'Koneksi gagal, coba lagi', confirmButtonColor: '#1d4ed8' });
    }
    isSaving = false;
}

/* ============================================================
   DELETE SOAL
   ============================================================ */
function hapusSoal() {
    if (currentNomor === null) return;
    const soal = soalList.find(s => s.nomor_soal === currentNomor);

    Swal.fire({
        title: `Hapus Soal #${currentNomor}?`,
        text: 'Soal ini akan dihapus dan nomor soal akan diurutkan ulang.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal'
    }).then(async r => {
        if (!r.isConfirmed) return;

        // If soal has no server ID yet (never saved), just remove from local list
        if (!soal || !soal.id) {
            soalList = soalList.filter(s => s.nomor_soal !== currentNomor);
            // Renumber locally
            soalList.sort((a, b) => a.nomor_soal - b.nomor_soal);
            soalList.forEach((s, i) => s.nomor_soal = i + 1);
            currentNomor = null;
            await destroyAllEditors();
            document.getElementById('editor-panel').classList.add('hidden');
            document.getElementById('empty-state').classList.remove('hidden');
            renderLeftPanel();
            updateTotalCount();
            return;
        }

        const fd = new FormData();
        fd.append('action',       'delete_soal');
        fd.append('soal_id',      soal.id);
        fd.append('bank_soal_id', BANK_SOAL_ID);

        try {
            const res  = await fetch('/guru/ajax/soal_handler.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.status === 'success') {
                Swal.fire({ icon: 'success', text: data.message, timer: 1200, showConfirmButton: false, toast: true, position: 'top-end' });
                currentNomor = null;
                await destroyAllEditors();
                document.getElementById('editor-panel').classList.add('hidden');
                document.getElementById('empty-state').classList.remove('hidden');
                await loadSoalList();
            } else {
                Swal.fire({ icon: 'error', text: data.message, confirmButtonColor: '#1d4ed8' });
            }
        } catch (e) {
            Swal.fire({ icon: 'error', text: 'Koneksi gagal', confirmButtonColor: '#1d4ed8' });
        }
    });
}

/* ============================================================
   SIMPAN KE BANK  (save current + go back)
   ============================================================ */
async function simpanKeBank() {
    if (currentNomor !== null && editors['pertanyaan']) {
        const pertanyaan = editors['pertanyaan'].getData();
        if (pertanyaan && pertanyaan.replace(/<[^>]+>/g,'').trim() !== '') {
            await saveSoal();
        }
    }
    Swal.fire({
        title: 'Kembali ke Bank Soal?',
        text: 'Pastikan semua soal sudah disimpan.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#1d4ed8',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, Kembali',
        cancelButtonText: 'Tetap di Sini'
    }).then(r => { if (r.isConfirmed) window.location.href = '/guru/bank_soal.php'; });
}

/* ============================================================
   DESTROY EDITORS
   ============================================================ */
async function destroyAllEditors() {
    for (const key in editors) {
        try { await editors[key].destroy(); } catch (e) {}
    }
    editors = {};
}

/* ============================================================
   IMPORT WORD
   ============================================================ */
function importWord() {
    const file = document.getElementById('file-word').files[0];
    if (!file) { Swal.fire({ icon: 'warning', text: 'Pilih file .docx terlebih dahulu!', confirmButtonColor: '#1d4ed8' }); return; }

    Swal.fire({ title: 'Mengimport...', text: 'Sedang memproses file Word', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

    const fd = new FormData();
    fd.append('action',       'import_word');
    fd.append('bank_soal_id', BANK_SOAL_ID);
    fd.append('file',         file);

    fetch('/guru/ajax/soal_handler.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                Swal.fire({ icon: 'success', title: 'Import Berhasil', text: res.message, confirmButtonColor: '#1d4ed8' })
                    .then(() => { switchTab('manual'); loadSoalList(); });
            } else {
                Swal.fire({ icon: 'error', title: 'Import Gagal', text: res.message, confirmButtonColor: '#1d4ed8' });
            }
        })
        .catch(() => Swal.fire({ icon: 'error', text: 'Terjadi kesalahan koneksi', confirmButtonColor: '#1d4ed8' }));
}

/* ============================================================
   IMPORT EXCEL
   ============================================================ */
function importExcel() {
    const file = document.getElementById('file-excel').files[0];
    if (!file) { Swal.fire({ icon: 'warning', text: 'Pilih file .xlsx terlebih dahulu!', confirmButtonColor: '#1d4ed8' }); return; }

    Swal.fire({ title: 'Mengimport...', text: 'Sedang memproses file Excel', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

    const fd = new FormData();
    fd.append('action',       'import_excel');
    fd.append('bank_soal_id', BANK_SOAL_ID);
    fd.append('file',         file);

    fetch('/guru/ajax/soal_handler.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                Swal.fire({ icon: 'success', title: 'Import Berhasil', text: res.message, confirmButtonColor: '#1d4ed8' })
                    .then(() => { switchTab('manual'); loadSoalList(); });
            } else {
                Swal.fire({ icon: 'error', title: 'Import Gagal', text: res.message, confirmButtonColor: '#1d4ed8' });
            }
        })
        .catch(() => Swal.fire({ icon: 'error', text: 'Terjadi kesalahan koneksi', confirmButtonColor: '#1d4ed8' }));
}

/* ============================================================
   PREVIEW FILE NAME
   ============================================================ */
function previewFileName(type) {
    const input = document.getElementById(`file-${type}`);
    const label = document.getElementById(`${type}-file-name`);
    if (input.files[0]) {
        label.textContent = '📎 ' + input.files[0].name;
        label.classList.remove('hidden');
    }
}

/* ============================================================
   UTILS
   ============================================================ */
function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>
</body>
</html>
