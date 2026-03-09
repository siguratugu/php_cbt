<?php
session_start();
date_default_timezone_set('Asia/Jakarta');
require_once __DIR__ . '/../includes/auth.php';
cekLogin('siswa');
require_once __DIR__ . '/../config/database.php';

$ruang_id = (int)($_GET['ruang_id'] ?? 0);
$siswa_id = (int)$_SESSION['user_id'];
$kelas_id = $_SESSION['kelas_id'] ?? '';

if (!$ruang_id) {
    header('Location: /siswa/ruang_ujian.php');
    exit;
}

// Verify access
$stmt = $conn->prepare(
    "SELECT ru.*, bs.waktu_mengerjakan, bs.nama_soal, bs.id as bs_id
     FROM ruang_ujian ru
     JOIN ruang_ujian_kelas ruk ON ru.id = ruk.ruang_ujian_id
     JOIN bank_soal bs ON ru.bank_soal_id = bs.id
     WHERE ru.id = ? AND ruk.kelas_id = ? AND NOW() BETWEEN ru.tanggal_mulai AND ru.tanggal_selesai"
);
$stmt->bind_param("is", $ruang_id, $kelas_id);
$stmt->execute();
$ruang = $stmt->get_result()->fetch_assoc();

if (!$ruang) {
    header('Location: /siswa/ruang_ujian.php');
    exit;
}

// Check existing ujian or create new
$checkStmt = $conn->prepare("SELECT * FROM ujian_siswa WHERE ruang_ujian_id = ? AND siswa_id = ?");
$checkStmt->bind_param("ii", $ruang_id, $siswa_id);
$checkStmt->execute();
$ujian = $checkStmt->get_result()->fetch_assoc();

if (!$ujian) {
    // Auto-start
    $soalStmt = $conn->prepare("SELECT id FROM soal WHERE bank_soal_id = ? ORDER BY nomor_soal");
    $soalStmt->bind_param("i", $ruang['bs_id']);
    $soalStmt->execute();
    $soalIds = array_column($soalStmt->get_result()->fetch_all(MYSQLI_ASSOC), 'id');

    $acakSoalOrder = null;
    $acakJawabanOrder = null;

    if ($ruang['acak_soal'] && !empty($soalIds)) {
        shuffle($soalIds);
        $acakSoalOrder = implode(',', $soalIds);
    }
    if ($ruang['acak_jawaban'] && !empty($soalIds)) {
        $orders = [];
        foreach ($soalIds as $sid) {
            $opsi = ['a', 'b', 'c', 'd', 'e'];
            shuffle($opsi);
            $orders[$sid] = implode('', $opsi);
        }
        $acakJawabanOrder = json_encode($orders);
    }

    $insStmt = $conn->prepare(
        "INSERT INTO ujian_siswa (ruang_ujian_id, siswa_id, status, waktu_mulai, acak_soal_order, acak_jawaban_order)
         VALUES (?, ?, 'sedang', NOW(), ?, ?)"
    );
    $insStmt->bind_param("iiss", $ruang_id, $siswa_id, $acakSoalOrder, $acakJawabanOrder);
    $insStmt->execute();
    $ujian_id = $conn->insert_id;

    $checkStmt2 = $conn->prepare("SELECT * FROM ujian_siswa WHERE id = ?");
    $checkStmt2->bind_param("i", $ujian_id);
    $checkStmt2->execute();
    $ujian = $checkStmt2->get_result()->fetch_assoc();
}

if ($ujian['status'] === 'selesai') {
    // Show result page
    $showResult = true;
} else {
    $showResult = false;
    // Calculate remaining time
    $waktuMengerjakan = ($ruang['waktu_mengerjakan'] + ($ujian['waktu_tambahan'] ?? 0)) * 60;
    $elapsed = time() - strtotime($ujian['waktu_mulai']);
    $sisaWaktu = max(0, $waktuMengerjakan - $elapsed);
    $ujian_id = $ujian['id'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ujian: <?= htmlspecialchars($ruang['nama_ruang']) ?> - CBT MTsN 1 Mesuji</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <style>
        .nomor-btn { width: 36px; height: 36px; font-size: 0.75rem; font-weight: 600; border-radius: 0.375rem; display: flex; align-items: center; justify-content: center; cursor: pointer; border: none; transition: all 0.15s; }
        .nomor-belum  { background: #e5e7eb; color: #374151; }
        .nomor-dijawab { background: #16a34a; color: #fff; }
        .nomor-ragu   { background: #f97316; color: #fff; }
        .nomor-active { box-shadow: 0 0 0 3px rgba(37,99,235,0.5); }
        .radio-jawaban { display: none; }
        .radio-jawaban + label { display: flex; align-items: flex-start; gap: 10px; padding: 12px 16px; border: 2px solid #e5e7eb; border-radius: 10px; cursor: pointer; transition: all 0.15s; margin-bottom: 8px; }
        .radio-jawaban + label:hover { border-color: #93c5fd; background: #eff6ff; }
        .radio-jawaban:checked + label { border-color: #1d4ed8; background: #eff6ff; }
        .radio-indicator { width: 20px; height: 20px; border-radius: 50%; border: 2px solid #9ca3af; flex-shrink: 0; margin-top: 1px; transition: all 0.15s; }
        .radio-jawaban:checked + label .radio-indicator { border-color: #1d4ed8; background: #1d4ed8; }
        .soal-content img { max-width: 100%; height: auto; border-radius: 8px; margin: 8px 0; }
        @media (max-width: 768px) {
            .exam-layout { flex-direction: column !important; }
            .soal-area { width: 100% !important; }
            .nav-area { width: 100% !important; order: 2; }
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">

<?php if ($showResult): ?>
<!-- Result Page -->
<div class="min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl p-8 w-full max-w-md text-center">
        <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <i data-lucide="check-circle" class="w-10 h-10 text-green-600"></i>
        </div>
        <h1 class="text-2xl font-bold text-gray-800 mb-1">Ujian Selesai!</h1>
        <p class="text-gray-500 text-sm mb-6"><?= htmlspecialchars($ruang['nama_ruang']) ?></p>
        <div class="grid grid-cols-2 gap-4 mb-6">
            <div class="bg-green-50 rounded-xl p-4">
                <p class="text-green-600 text-xs font-semibold">Jumlah Benar</p>
                <p class="text-2xl font-bold text-green-700"><?= $ujian['jumlah_benar'] ?></p>
            </div>
            <div class="bg-red-50 rounded-xl p-4">
                <p class="text-red-600 text-xs font-semibold">Jumlah Salah</p>
                <p class="text-2xl font-bold text-red-700"><?= $ujian['jumlah_salah'] ?></p>
            </div>
        </div>
        <div class="bg-blue-50 rounded-xl p-6 mb-6">
            <p class="text-blue-600 text-sm font-semibold mb-1">NILAI</p>
            <p class="text-5xl font-bold text-blue-700"><?= number_format((float)$ujian['nilai'], 2) ?></p>
        </div>
        <a href="/siswa/ruang_ujian.php" class="block w-full bg-blue-700 hover:bg-blue-800 text-white font-semibold py-3 rounded-xl transition-colors">
            Kembali ke Ruang Ujian
        </a>
    </div>
</div>
<script>lucide.createIcons();</script>
</body></html>
<?php exit; endif; ?>

<!-- Exam Page -->
<div class="min-h-screen flex flex-col">
    <!-- Header -->
    <div class="bg-white shadow-sm px-4 py-3 flex items-center justify-between sticky top-0 z-10">
        <div>
            <h1 class="font-bold text-gray-800 text-sm md:text-base"><?= htmlspecialchars($ruang['nama_ruang']) ?></h1>
            <p class="text-xs text-gray-500"><?= htmlspecialchars($_SESSION['nama']) ?></p>
        </div>
        <div id="timer-badge" class="bg-purple-100 text-purple-800 font-mono font-bold text-sm px-3 py-1.5 rounded-full">
            <span id="timer-display">--:--:--</span>
        </div>
    </div>

    <!-- Exam Layout -->
    <div class="flex exam-layout flex-1 p-4 gap-4">
        <!-- Soal Area (75%) -->
        <div class="soal-area flex-1" style="min-width:0">
            <div class="bg-white rounded-xl shadow-sm p-6">
                <!-- Soal Header -->
                <div class="flex items-center justify-between mb-4">
                    <span id="soal-badge" class="bg-blue-100 text-blue-800 font-bold text-sm px-3 py-1 rounded-full">SOAL NO. 1</span>
                    <span class="text-xs text-gray-400" id="soal-jenis-badge"></span>
                </div>

                <!-- Pertanyaan -->
                <div id="soal-pertanyaan" class="text-gray-800 mb-6 soal-content text-sm leading-relaxed"></div>

                <!-- Jawaban Area -->
                <div id="jawaban-area"></div>

                <!-- Navigation Buttons -->
                <div class="flex items-center justify-between mt-6 pt-4 border-t border-gray-100">
                    <button onclick="prevSoal()" id="btn-prev"
                        class="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm px-4 py-2 rounded-lg transition-colors">
                        <i data-lucide="chevron-left" class="w-4 h-4"></i> Sebelumnya
                    </button>
                    <button onclick="toggleRagu()" id="btn-ragu"
                        class="flex items-center gap-2 bg-orange-500 hover:bg-orange-600 text-white text-sm px-4 py-2 rounded-lg transition-colors">
                        <i data-lucide="help-circle" class="w-4 h-4"></i> Ragu-Ragu
                    </button>
                    <button onclick="saveAndNext()" id="btn-next"
                        class="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm px-4 py-2 rounded-lg transition-colors">
                        Jawab &amp; Lanjutkan <i data-lucide="chevron-right" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Navigation Panel (25%) -->
        <div class="nav-area w-full md:w-72 flex-shrink-0">
            <div class="bg-white rounded-xl shadow-sm p-4 sticky top-20">
                <h3 class="font-bold text-gray-700 text-sm mb-3 text-center">NOMOR SOAL</h3>
                <div id="soal-grid" class="grid grid-cols-6 gap-1.5 mb-4"></div>
                <!-- Legend -->
                <div class="space-y-1.5 mb-4 border-t pt-3">
                    <div class="flex items-center gap-2 text-xs text-gray-600">
                        <div class="w-4 h-4 rounded bg-green-600"></div> Sudah dijawab
                    </div>
                    <div class="flex items-center gap-2 text-xs text-gray-600">
                        <div class="w-4 h-4 rounded bg-orange-500"></div> Ragu-ragu
                    </div>
                    <div class="flex items-center gap-2 text-xs text-gray-600">
                        <div class="w-4 h-4 rounded bg-gray-300"></div> Belum dijawab
                    </div>
                </div>
                <!-- Stop Button -->
                <button onclick="hentikanUjian()" id="btn-hentikan"
                    class="w-full bg-red-600 hover:bg-red-700 text-white text-sm font-semibold py-2.5 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    disabled>
                    <i data-lucide="square" class="w-4 h-4 inline mr-1"></i> Hentikan Ujian
                </button>
                <p id="info-hentikan" class="text-xs text-gray-400 text-center mt-1.5"></p>
            </div>
        </div>
    </div>
</div>

<script>
const RUANG_ID  = <?= $ruang_id ?>;
const UJIAN_ID  = <?= $ujian_id ?>;
const SISA_WAKTU = <?= $sisaWaktu ?>;
const WAKTU_HENTIKAN = <?= (int)$ruang['waktu_hentikan'] ?> * 60; // seconds
const WAKTU_MENGERJAKAN = <?= (int)$ruang['waktu_mengerjakan'] ?> * 60; // seconds

let soalList    = [];
let currentIdx  = 0;
let jawabanMap  = {}; // soal_id => jawaban
let raguMap     = {}; // soal_id => bool
let timerSisa   = SISA_WAKTU;
let timerInterval = null;
let waktuElapsed = 0;

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    loadSoalData();
    startTimer();
    // Ping every 30 seconds
    setInterval(ping, 30000);
    // Check hentikan button every second
    setInterval(checkHentikanBtn, 1000);
});

function loadSoalData() {
    fetch(`/siswa/ajax/ujian_handler.php?action=get_ujian_data&ruang_id=${RUANG_ID}`)
        .then(r => r.json())
        .then(res => {
            if (res.status === 'selesai') {
                window.location.reload();
                return;
            }
            if (res.status !== 'success') {
                Swal.fire('Error', res.message || 'Gagal memuat soal', 'error');
                return;
            }
            soalList = res.soal_list || [];
            // Build jawaban/ragu maps
            soalList.forEach(s => {
                jawabanMap[s.id] = s.jawaban || '';
                raguMap[s.id]    = !!s.is_ragu;
            });
            buildSoalGrid();
            showSoal(0);
        })
        .catch(() => Swal.fire('Error', 'Gagal memuat soal', 'error'));
}

function buildSoalGrid() {
    const grid = document.getElementById('soal-grid');
    grid.innerHTML = soalList.map((s, idx) => {
        const cls = getSoalClass(s.id);
        return `<button class="nomor-btn ${cls}" id="nomor-${s.id}" onclick="goToSoal(${idx})">${idx + 1}</button>`;
    }).join('');
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function getSoalClass(soalId) {
    if (raguMap[soalId]) return 'nomor-ragu';
    if (jawabanMap[soalId]) return 'nomor-dijawab';
    return 'nomor-belum';
}

function showSoal(idx) {
    if (idx < 0 || idx >= soalList.length) return;
    currentIdx = idx;
    const soal = soalList[idx];

    document.getElementById('soal-badge').textContent = `SOAL NO. ${idx + 1}`;

    const jenisBadge = {
        'pg': 'Pilihan Ganda',
        'esai': 'Esai',
        'menjodohkan': 'Menjodohkan',
        'benar_salah': 'Benar/Salah'
    };
    document.getElementById('soal-jenis-badge').textContent = jenisBadge[soal.jenis_soal] || '';
    document.getElementById('soal-pertanyaan').innerHTML = soal.pertanyaan || '';

    // Update ragu button
    const btnRagu = document.getElementById('btn-ragu');
    if (raguMap[soal.id]) {
        btnRagu.classList.remove('bg-orange-500', 'hover:bg-orange-600');
        btnRagu.classList.add('bg-orange-700', 'hover:bg-orange-800');
    } else {
        btnRagu.classList.remove('bg-orange-700', 'hover:bg-orange-800');
        btnRagu.classList.add('bg-orange-500', 'hover:bg-orange-600');
    }

    // Update prev/next buttons
    document.getElementById('btn-prev').disabled = idx === 0;
    if (idx === soalList.length - 1) {
        document.getElementById('btn-next').innerHTML = '<i data-lucide="check" class="w-4 h-4 inline mr-1"></i> Jawab & Selesai';
    } else {
        document.getElementById('btn-next').innerHTML = 'Jawab &amp; Lanjutkan <i data-lucide="chevron-right" class="w-4 h-4 inline ml-1"></i>';
    }

    renderJawabanArea(soal);

    // Highlight active in grid
    document.querySelectorAll('.nomor-btn').forEach(btn => btn.classList.remove('nomor-active'));
    const activeBtn = document.getElementById(`nomor-${soal.id}`);
    if (activeBtn) activeBtn.classList.add('nomor-active');

    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function renderJawabanArea(soal) {
    const area = document.getElementById('jawaban-area');
    const currentJawaban = jawabanMap[soal.id] || '';

    if (soal.jenis_soal === 'pg') {
        const opsiKeys = ['a', 'b', 'c', 'd', 'e'];
        const opsiNames = { a: soal.opsi_a, b: soal.opsi_b, c: soal.opsi_c, d: soal.opsi_d, e: soal.opsi_e };

        // Apply random order if set
        let displayOrder = opsiKeys.filter(k => opsiNames[k]);
        if (soal.acak_jawaban_order) {
            const order = soal.acak_jawaban_order.split('');
            displayOrder = order.filter(k => opsiNames[k]);
        }

        area.innerHTML = displayOrder.map(key => {
            const checked = currentJawaban === key ? 'checked' : '';
            const label = String(displayOrder.indexOf(key) + 1);
            return `
            <input type="radio" name="jawaban_pg" value="${key}" id="opt_${key}" class="radio-jawaban" ${checked} onchange="updateJawaban('${soal.id}', this.value)">
            <label for="opt_${key}">
                <div class="radio-indicator"></div>
                <div class="flex-1">${escHtml(opsiNames[key])}</div>
            </label>`;
        }).join('');
    } else if (soal.jenis_soal === 'esai') {
        area.innerHTML = `<textarea id="jawaban-esai" rows="6"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 resize-none"
            placeholder="Tulis jawaban Anda di sini..."
            onchange="updateJawaban('${soal.id}', this.value)"
            oninput="updateJawaban('${soal.id}', this.value)">${escHtml(currentJawaban)}</textarea>`;
    } else if (soal.jenis_soal === 'menjodohkan') {
        const kiri  = soal.pasangan_kiri ? soal.pasangan_kiri.split('|') : [];
        const kanan = soal.pasangan_kanan ? soal.pasangan_kanan.split('|') : [];
        const saved = currentJawaban ? currentJawaban.split('|') : [];
        area.innerHTML = `<div class="space-y-2">` + kiri.map((k, i) => `
            <div class="flex items-center gap-3">
                <span class="text-sm text-gray-700 flex-1">${escHtml(k)}</span>
                <select id="match_${i}" class="border border-gray-300 rounded-lg px-2 py-1.5 text-sm" onchange="updateMenjodohkan('${soal.id}')">
                    <option value="">-- Pilih --</option>
                    ${kanan.map((kr, j) => `<option value="${j}" ${saved[i] == j ? 'selected' : ''}>${escHtml(kr)}</option>`).join('')}
                </select>
            </div>`).join('') + `</div>`;
    } else if (soal.jenis_soal === 'benar_salah') {
        const opts = ['benar', 'salah'];
        area.innerHTML = opts.map(opt => `
            <input type="radio" name="jawaban_bs" value="${opt}" id="opt_bs_${opt}" class="radio-jawaban" ${currentJawaban === opt ? 'checked' : ''} onchange="updateJawaban('${soal.id}', this.value)">
            <label for="opt_bs_${opt}">
                <div class="radio-indicator"></div>
                <div class="flex-1 font-medium">${opt.charAt(0).toUpperCase() + opt.slice(1)}</div>
            </label>`).join('');
    }
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function updateMenjodohkan(soalId) {
    const soal = soalList.find(s => s.id == soalId);
    if (!soal) return;
    const kiri = soal.pasangan_kiri ? soal.pasangan_kiri.split('|') : [];
    const values = kiri.map((_, i) => {
        const sel = document.getElementById(`match_${i}`);
        return sel ? sel.value : '';
    });
    updateJawaban(soalId, values.join('|'));
}

function updateJawaban(soalId, value) {
    jawabanMap[soalId] = value;
    const btn = document.getElementById(`nomor-${soalId}`);
    if (btn) {
        btn.className = `nomor-btn ${getSoalClass(soalId)} ${btn.classList.contains('nomor-active') ? 'nomor-active' : ''}`;
    }
}

function getCurrentJawaban() {
    const soal = soalList[currentIdx];
    if (!soal) return '';
    if (soal.jenis_soal === 'pg') {
        const checked = document.querySelector('input[name="jawaban_pg"]:checked');
        return checked ? checked.value : jawabanMap[soal.id] || '';
    } else if (soal.jenis_soal === 'esai') {
        const ta = document.getElementById('jawaban-esai');
        return ta ? ta.value : jawabanMap[soal.id] || '';
    } else if (soal.jenis_soal === 'menjodohkan') {
        return jawabanMap[soal.id] || '';
    } else if (soal.jenis_soal === 'benar_salah') {
        const checked = document.querySelector('input[name="jawaban_bs"]:checked');
        return checked ? checked.value : jawabanMap[soal.id] || '';
    }
    return '';
}

function saveCurrentAnswer() {
    const soal = soalList[currentIdx];
    if (!soal) return Promise.resolve();
    const jawaban = getCurrentJawaban();
    if (!jawaban) return Promise.resolve();

    jawabanMap[soal.id] = jawaban;
    const btn = document.getElementById(`nomor-${soal.id}`);
    if (btn) btn.className = `nomor-btn ${getSoalClass(soal.id)} nomor-active`;

    return fetch('/siswa/ajax/jawab_handler.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=simpan_jawaban&ujian_id=${UJIAN_ID}&soal_id=${soal.id}&jawaban=${encodeURIComponent(jawaban)}`
    }).then(r => r.json()).catch(() => {});
}

function saveAndNext() {
    saveCurrentAnswer().then(() => {
        if (currentIdx < soalList.length - 1) {
            showSoal(currentIdx + 1);
        } else {
            // Last question - check if want to finish
            hentikanUjian();
        }
    });
}

function prevSoal() {
    saveCurrentAnswer().then(() => {
        if (currentIdx > 0) showSoal(currentIdx - 1);
    });
}

function goToSoal(idx) {
    saveCurrentAnswer().then(() => showSoal(idx));
}

function toggleRagu() {
    const soal = soalList[currentIdx];
    if (!soal) return;
    raguMap[soal.id] = !raguMap[soal.id];
    const isRagu = raguMap[soal.id] ? 1 : 0;

    fetch('/siswa/ajax/jawab_handler.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=set_ragu&ujian_id=${UJIAN_ID}&soal_id=${soal.id}&is_ragu=${isRagu}`
    }).catch(() => {});

    const btn = document.getElementById(`nomor-${soal.id}`);
    if (btn) btn.className = `nomor-btn ${getSoalClass(soal.id)} nomor-active`;

    const btnRagu = document.getElementById('btn-ragu');
    if (raguMap[soal.id]) {
        btnRagu.classList.remove('bg-orange-500', 'hover:bg-orange-600');
        btnRagu.classList.add('bg-orange-700', 'hover:bg-orange-800');
    } else {
        btnRagu.classList.remove('bg-orange-700', 'hover:bg-orange-800');
        btnRagu.classList.add('bg-orange-500', 'hover:bg-orange-600');
    }
}

function checkHentikanBtn() {
    const btn = document.getElementById('btn-hentikan');
    const info = document.getElementById('info-hentikan');
    const elapsed = WAKTU_MENGERJAKAN - timerSisa;
    if (elapsed >= WAKTU_HENTIKAN) {
        btn.disabled = false;
        if (info) info.textContent = '';
    } else {
        btn.disabled = true;
        const remaining = Math.ceil((WAKTU_HENTIKAN - elapsed) / 60);
        if (info) info.textContent = `Tersedia dalam ${remaining} menit lagi`;
    }
}

function hentikanUjian() {
    Swal.fire({
        title: 'Hentikan Ujian?',
        text: 'Apakah anda benar-benar sudah menyelesaikan semua soal ujian? Klik Selesai untuk lanjut menyelesaikannya.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Selesai',
        cancelButtonText: 'Kembali'
    }).then(result => {
        if (result.isConfirmed) {
            saveCurrentAnswer().then(() => finishExam());
        }
    });
}

function finishExam() {
    Swal.fire({ title: 'Memproses...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
    fetch('/siswa/ajax/ujian_handler.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=selesai&ujian_id=${UJIAN_ID}&ruang_id=${RUANG_ID}`
    })
    .then(r => r.json())
    .then(res => {
        if (res.status === 'success') {
            clearInterval(timerInterval);
            Swal.fire({
                title: 'Ujian Selesai!',
                html: `<div class="text-center">
                    <p class="text-gray-600 mb-4">Hasil ujian Anda:</p>
                    <div class="grid grid-cols-2 gap-3 mb-4">
                        <div class="bg-green-50 rounded-lg p-3"><p class="text-green-600 text-xs">Benar</p><p class="text-2xl font-bold text-green-700">${res.jumlah_benar}</p></div>
                        <div class="bg-red-50 rounded-lg p-3"><p class="text-red-600 text-xs">Salah</p><p class="text-2xl font-bold text-red-700">${res.jumlah_salah}</p></div>
                    </div>
                    <div class="bg-blue-50 rounded-xl p-4"><p class="text-blue-600 text-sm">NILAI</p><p class="text-4xl font-bold text-blue-700">${res.nilai}</p></div>
                </div>`,
                icon: 'success',
                confirmButtonText: 'Kembali ke Ruang Ujian',
                confirmButtonColor: '#1d4ed8',
                allowOutsideClick: false
            }).then(() => { window.location.href = '/siswa/ruang_ujian.php'; });
        } else {
            Swal.fire('Error', res.message || 'Gagal menyelesaikan ujian', 'error');
        }
    });
}

function startTimer() {
    timerSisa = SISA_WAKTU;
    updateTimerDisplay();
    timerInterval = setInterval(() => {
        timerSisa--;
        waktuElapsed++;
        updateTimerDisplay();
        if (timerSisa <= 0) {
            clearInterval(timerInterval);
            Swal.fire({
                title: 'Waktu Habis!',
                text: 'Waktu ujian telah habis. Ujian akan diselesaikan secara otomatis.',
                icon: 'warning',
                allowOutsideClick: false,
                showConfirmButton: false,
                timer: 3000
            }).then(() => { saveCurrentAnswer().then(() => finishExam()); });
        }
        if (timerSisa <= 300) {
            document.getElementById('timer-badge').className = 'bg-red-100 text-red-800 font-mono font-bold text-sm px-3 py-1.5 rounded-full animate-pulse';
        }
    }, 1000);
}

function updateTimerDisplay() {
    const h = Math.floor(timerSisa / 3600);
    const m = Math.floor((timerSisa % 3600) / 60);
    const s = timerSisa % 60;
    document.getElementById('timer-display').textContent =
        `${pad(h)}:${pad(m)}:${pad(s)}`;
}

function pad(n) { return n < 10 ? '0' + n : n; }

function ping() {
    fetch('/siswa/ajax/ujian_handler.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=ping&ujian_id=${UJIAN_ID}`
    }).catch(() => {});
}

function escHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// Prevent page leave
window.addEventListener('beforeunload', (e) => {
    e.preventDefault();
    e.returnValue = '';
});
</script>
</body>
</html>
