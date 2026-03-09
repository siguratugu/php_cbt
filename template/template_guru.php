<?php
session_start();
date_default_timezone_set('Asia/Jakarta');
require_once __DIR__ . '/../includes/auth.php';
cekLogin('admin');

header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="template_import_guru.xls"');
header('Cache-Control: max-age=0');
?>
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head>
<body>
<table border="1">
    <tr>
        <th style="background:#4472C4;color:white;font-weight:bold;padding:8px">No</th>
        <th style="background:#4472C4;color:white;font-weight:bold;padding:8px">Nama Guru</th>
        <th style="background:#4472C4;color:white;font-weight:bold;padding:8px">NIK (16 digit)</th>
    </tr>
    <tr>
        <td style="color:#aaa;font-style:italic">1</td>
        <td style="color:#aaa;font-style:italic">Contoh: Ahmad Fauzi</td>
        <td style="color:#aaa;font-style:italic">1234567890123456</td>
    </tr>
</table>
</body>
</html>
