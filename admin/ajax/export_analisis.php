<?php
session_start();
require_once __DIR__ . '/../../includes/auth.php';
cekLogin('admin');
require_once __DIR__ . '/../../config/database.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

$ruangId = (int)($_GET['ruang_id'] ?? 0);
if (!$ruangId) { http_response_code(400); die('Parameter ruang_id diperlukan'); }

$ruangStmt = $conn->prepare("SELECT ru.nama_ruang, ru.bank_soal_id FROM ruang_ujian ru WHERE ru.id = ?");
$ruangStmt->bind_param("i", $ruangId);
$ruangStmt->execute();
$ruang = $ruangStmt->get_result()->fetch_assoc();
if (!$ruang) { http_response_code(404); die('Ruang ujian tidak ditemukan'); }

$bankSoalId = (int)$ruang['bank_soal_id'];

// Get all questions for this bank
$soalStmt = $conn->prepare("SELECT id, nomor_soal FROM soal WHERE bank_soal_id = ? ORDER BY nomor_soal");
$soalStmt->bind_param("i", $bankSoalId);
$soalStmt->execute();
$soalResult = $soalStmt->get_result();
$soalList = [];
while ($s = $soalResult->fetch_assoc()) $soalList[] = $s;

// Get all students in this exam
$siswaStmt = $conn->prepare("
    SELECT us.id AS ujian_siswa_id, s.nama, s.nisn, k.nama_kelas,
           us.jumlah_benar, us.jumlah_salah, us.nilai, us.status
    FROM ujian_siswa us
    JOIN siswa s ON us.siswa_id = s.id
    LEFT JOIN kelas k ON s.kelas_id = k.id
    WHERE us.ruang_ujian_id = ?
    ORDER BY k.nama_kelas, s.nama
");
$siswaStmt->bind_param("i", $ruangId);
$siswaStmt->execute();
$siswaList = $siswaStmt->get_result()->fetchAll(MYSQLI_ASSOC);

// Get all answers indexed by [ujian_siswa_id][soal_id]
$jawabanMap = [];
if (!empty($siswaList)) {
    $ujianIds = array_column($siswaList, 'ujian_siswa_id');
    $placeholders = implode(',', array_fill(0, count($ujianIds), '?'));
    $types = str_repeat('i', count($ujianIds));
    $jwStmt = $conn->prepare("SELECT ujian_siswa_id, soal_id, is_benar FROM jawaban_siswa WHERE ujian_siswa_id IN ($placeholders)");
    $jwStmt->bind_param($types, ...$ujianIds);
    $jwStmt->execute();
    $jwResult = $jwStmt->get_result();
    while ($jw = $jwResult->fetch_assoc()) {
        $jawabanMap[$jw['ujian_siswa_id']][$jw['soal_id']] = $jw['is_benar'];
    }
}

$filename = 'Analisis Ujian ' . preg_replace('/[^a-zA-Z0-9\s\-_]/', '', $ruang['nama_ruang']);
$spreadsheetAvailable = file_exists(__DIR__ . '/../../vendor/autoload.php');

if ($spreadsheetAvailable) {
    require_once __DIR__ . '/../../vendor/autoload.php';

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Analisis Ujian');

    $totalSoal = count($soalList);
    $lastCol = 4 + $totalSoal + 2; // No,Nama,NISN,Kelas + soal cols + Benar,Salah,Nilai
    $lastColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($lastCol);

    // Title row
    $sheet->mergeCells("A1:{$lastColLetter}1");
    $sheet->setCellValue('A1', 'ANALISIS UJIAN - ' . strtoupper($ruang['nama_ruang']));
    $sheet->getStyle('A1')->applyFromArray([
        'font' => ['bold' => true, 'size' => 13, 'color' => ['argb' => 'FFFFFFFF']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1D4ED8']],
    ]);
    $sheet->getRowDimension(1)->setRowHeight(28);

    // Header row
    $sheet->setCellValue('A2', 'No');
    $sheet->setCellValue('B2', 'Nama Siswa');
    $sheet->setCellValue('C2', 'NISN');
    $sheet->setCellValue('D2', 'Kelas');
    $sheet->getColumnDimension('A')->setWidth(5);
    $sheet->getColumnDimension('B')->setWidth(28);
    $sheet->getColumnDimension('C')->setWidth(14);
    $sheet->getColumnDimension('D')->setWidth(14);

    foreach ($soalList as $idx => $soal) {
        $colIdx = 5 + $idx;
        $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
        $sheet->setCellValue($colLetter . '2', 'S' . $soal['nomor_soal']);
        $sheet->getColumnDimension($colLetter)->setWidth(5);
    }

    $benarColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(5 + $totalSoal);
    $salahColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(6 + $totalSoal);
    $nilaiColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(7 + $totalSoal);
    $sheet->setCellValue($benarColLetter . '2', 'Benar');
    $sheet->setCellValue($salahColLetter . '2', 'Salah');
    $sheet->setCellValue($nilaiColLetter . '2', 'Nilai');
    $sheet->getColumnDimension($benarColLetter)->setWidth(8);
    $sheet->getColumnDimension($salahColLetter)->setWidth(8);
    $sheet->getColumnDimension($nilaiColLetter)->setWidth(10);

    $sheet->getStyle("A2:{$lastColLetter}2")->applyFromArray([
        'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF374151']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF555555']]],
    ]);
    $sheet->getRowDimension(2)->setRowHeight(20);

    // Data rows
    foreach ($siswaList as $i => $siswa) {
        $r = $i + 3;
        $ujianId = $siswa['ujian_siswa_id'];
        $sheet->setCellValue('A' . $r, $i + 1);
        $sheet->setCellValue('B' . $r, $siswa['nama']);
        $sheet->setCellValue('C' . $r, $siswa['nisn']);
        $sheet->setCellValue('D' . $r, $siswa['nama_kelas'] ?? '-');

        foreach ($soalList as $idx => $soal) {
            $colIdx = 5 + $idx;
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
            $soalId = $soal['id'];

            if (isset($jawabanMap[$ujianId][$soalId])) {
                $isBenar = (int)$jawabanMap[$ujianId][$soalId];
                if ($isBenar) {
                    $sheet->setCellValue($colLetter . $r, '✓');
                    $sheet->getStyle($colLetter . $r)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFD1FAE5');
                    $sheet->getStyle($colLetter . $r)->getFont()->getColor()->setARGB('FF065F46');
                } else {
                    $sheet->setCellValue($colLetter . $r, '✗');
                    $sheet->getStyle($colLetter . $r)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFEE2E2');
                    $sheet->getStyle($colLetter . $r)->getFont()->getColor()->setARGB('FF991B1B');
                }
            } else {
                $sheet->setCellValue($colLetter . $r, '-');
                $sheet->getStyle($colLetter . $r)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF3F4F6');
                $sheet->getStyle($colLetter . $r)->getFont()->getColor()->setARGB('FF9CA3AF');
            }
            $sheet->getStyle($colLetter . $r)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        $sheet->setCellValue($benarColLetter . $r, (int)$siswa['jumlah_benar']);
        $sheet->setCellValue($salahColLetter . $r, (int)$siswa['jumlah_salah']);
        $sheet->setCellValue($nilaiColLetter . $r, round((float)$siswa['nilai'], 0));

        $sheet->getStyle("A{$r}:{$lastColLetter}{$r}")->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFDDDDDD']]],
        ]);
        if ($i % 2 === 1) {
            $sheet->getStyle("A{$r}:D{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF9FAFB');
        }
    }

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '.xlsx"');
    header('Cache-Control: max-age=0');
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

// Fallback: CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF");

$headerRow = ['No', 'Nama Siswa', 'NISN', 'Kelas'];
foreach ($soalList as $soal) $headerRow[] = 'S' . $soal['nomor_soal'];
$headerRow[] = 'Benar'; $headerRow[] = 'Salah'; $headerRow[] = 'Nilai';
fputcsv($out, $headerRow);

foreach ($siswaList as $i => $siswa) {
    $dataRow = [$i + 1, $siswa['nama'], $siswa['nisn'], $siswa['nama_kelas'] ?? '-'];
    $ujianId = $siswa['ujian_siswa_id'];
    foreach ($soalList as $soal) {
        if (isset($jawabanMap[$ujianId][$soal['id']])) {
            $dataRow[] = $jawabanMap[$ujianId][$soal['id']] ? 'Benar' : 'Salah';
        } else {
            $dataRow[] = '-';
        }
    }
    $dataRow[] = $siswa['jumlah_benar']; $dataRow[] = $siswa['jumlah_salah']; $dataRow[] = round($siswa['nilai'], 0);
    fputcsv($out, $dataRow);
}
fclose($out);
