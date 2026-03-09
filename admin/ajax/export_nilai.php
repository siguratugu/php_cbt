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
if (!$ruangId) {
    http_response_code(400);
    die('Parameter ruang_id diperlukan');
}

// Get room info
$ruangStmt = $conn->prepare("SELECT nama_ruang FROM ruang_ujian WHERE id = ?");
$ruangStmt->bind_param("i", $ruangId);
$ruangStmt->execute();
$ruang = $ruangStmt->get_result()->fetch_assoc();
if (!$ruang) {
    http_response_code(404);
    die('Ruang ujian tidak ditemukan');
}

// Get score data
$stmt = $conn->prepare("
    SELECT s.nama, s.nisn, k.nama_kelas,
           us.jumlah_benar, us.jumlah_salah, us.nilai, us.status
    FROM ujian_siswa us
    JOIN siswa s ON us.siswa_id = s.id
    LEFT JOIN kelas k ON s.kelas_id = k.id
    WHERE us.ruang_ujian_id = ?
    ORDER BY k.nama_kelas, s.nama
");
$stmt->bind_param("i", $ruangId);
$stmt->execute();
$result = $stmt->get_result();
$rows = [];
while ($row = $result->fetch_assoc()) $rows[] = $row;

$filename = 'Nilai Ujian ' . preg_replace('/[^a-zA-Z0-9\s\-_]/', '', $ruang['nama_ruang']);

// Try PhpSpreadsheet
$spreadsheetAvailable = file_exists(__DIR__ . '/../../vendor/autoload.php');

if ($spreadsheetAvailable) {
    require_once __DIR__ . '/../../vendor/autoload.php';

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Nilai Ujian');

    // Title
    $sheet->mergeCells('A1:H1');
    $sheet->setCellValue('A1', 'NILAI UJIAN - ' . strtoupper($ruang['nama_ruang']));
    $sheet->getStyle('A1')->applyFromArray([
        'font' => ['bold' => true, 'size' => 14],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1D4ED8']],
        'font' => ['bold' => true, 'size' => 13, 'color' => ['argb' => 'FFFFFFFF']],
    ]);
    $sheet->getRowDimension(1)->setRowHeight(30);

    // Headers
    $headers = ['No', 'Nama Siswa', 'NISN', 'Kelas', 'Jumlah Benar', 'Jumlah Salah', 'Nilai', 'Status'];
    $colWidths = [5, 30, 15, 15, 14, 14, 12, 12];

    foreach ($headers as $i => $header) {
        $col = chr(65 + $i);
        $sheet->setCellValue($col . '2', $header);
        $sheet->getColumnDimension($col)->setWidth($colWidths[$i]);
    }

    $headerStyle = [
        'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF374151']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFAAAAAA']]],
    ];
    $sheet->getStyle('A2:H2')->applyFromArray($headerStyle);
    $sheet->getRowDimension(2)->setRowHeight(22);

    // Data rows
    foreach ($rows as $i => $row) {
        $r = $i + 3;
        $statusLabel = ['belum' => 'Belum Mengerjakan', 'sedang' => 'Sedang Mengerjakan', 'selesai' => 'Selesai'][$row['status']] ?? $row['status'];
        $sheet->setCellValue('A' . $r, $i + 1);
        $sheet->setCellValue('B' . $r, $row['nama']);
        $sheet->setCellValue('C' . $r, $row['nisn']);
        $sheet->setCellValue('D' . $r, $row['nama_kelas'] ?? '-');
        $sheet->setCellValue('E' . $r, (int)$row['jumlah_benar']);
        $sheet->setCellValue('F' . $r, (int)$row['jumlah_salah']);
        $sheet->setCellValue('G' . $r, round((float)$row['nilai'], 0));
        $sheet->setCellValue('H' . $r, $statusLabel);

        $rowStyle = [
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFDDDDDD']]],
        ];
        if ($i % 2 === 1) $rowStyle['fill'] = ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF9FAFB']];
        $sheet->getStyle("A{$r}:H{$r}")->applyFromArray($rowStyle);
        $sheet->getStyle("A{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("E{$r}:G{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Color nilai cell
        if ($row['status'] === 'selesai') {
            $nilai = (float)$row['nilai'];
            $nilaiColor = $nilai >= 75 ? 'FFD1FAE5' : ($nilai >= 60 ? 'FFFEF3C7' : 'FFFEE2E2');
            $sheet->getStyle("G{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($nilaiColor);
        }
    }

    // Summary row
    $totalRows = count($rows);
    if ($totalRows > 0) {
        $sumRow = $totalRows + 3;
        $sheet->mergeCells("A{$sumRow}:D{$sumRow}");
        $sheet->setCellValue("A{$sumRow}", 'Rata-rata Nilai');
        $sheet->setCellValue("G{$sumRow}", "=AVERAGE(G3:G" . ($sumRow - 1) . ")");
        $sheet->getStyle("A{$sumRow}:H{$sumRow}")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE5E7EB']],
        ]);
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
// BOM for Excel UTF-8
fwrite($out, "\xEF\xBB\xBF");
fputcsv($out, ['No', 'Nama Siswa', 'NISN', 'Kelas', 'Jumlah Benar', 'Jumlah Salah', 'Nilai', 'Status']);
foreach ($rows as $i => $row) {
    $statusLabel = ['belum' => 'Belum Mengerjakan', 'sedang' => 'Sedang Mengerjakan', 'selesai' => 'Selesai'][$row['status']] ?? $row['status'];
    fputcsv($out, [
        $i + 1, $row['nama'], $row['nisn'], $row['nama_kelas'] ?? '-',
        $row['jumlah_benar'], $row['jumlah_salah'], round($row['nilai'], 0), $statusLabel
    ]);
}
fclose($out);
