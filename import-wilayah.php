<?php

require 'config.php';
require_admin();

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ?><!doctype html><html lang="id"><head><meta charset="utf-8"><title>Import Wilayah</title></head><body>
    <h1>Import ulang data wilayah</h1>
    <p>Proses ini akan mengganti seluruh data provinsi dan kota/kabupaten saat ini.</p>
    <form method="post">
        <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
        <button type="submit">Lanjutkan Import</button>
        <a href="admin.php">Batal</a>
    </form></body></html><?php
    exit;
}

if (!isset($_POST['csrf']) || !hash_equals($_SESSION['csrf'], (string)$_POST['csrf'])) {
    http_response_code(403);
    exit('Token keamanan tidak valid.');
}

$provinceFile = __DIR__ . '/csv/provinces.csv';
$regencyFile  = __DIR__ . '/csv/regencies.csv';

try {

    if (!file_exists($provinceFile)) {
        die('File provinces.csv tidak ditemukan.');
    }

    if (!file_exists($regencyFile)) {
        die('File regencies.csv tidak ditemukan.');
    }

    $pdo->beginTransaction();

    // Hapus data wilayah lama terlebih dahulu
    $pdo->exec("DELETE FROM daerah");
    $pdo->exec("DELETE FROM wilayah");

    // =========================
    // IMPORT PROVINSI
    // =========================

    $file = fopen($provinceFile, 'r');

    if (!$file) {
        throw new Exception('Gagal membuka provinces.csv');
    }

    // Lewati baris judul
    fgetcsv($file);

    $insertProvince = $pdo->prepare("
        INSERT INTO wilayah (id, nama)
        VALUES (?, ?)
    ");

    $jumlahProvinsi = 0;

    while (($row = fgetcsv($file)) !== false) {

        if (count($row) < 3) {
            continue;
        }

        $id   = trim($row[0]);
        $name = trim($row[2]);

        if ($id === '' || $name === '') {
            continue;
        }

        $insertProvince->execute([
            (int)$id,
            $name
        ]);

        $jumlahProvinsi++;
    }

    fclose($file);

    // =========================
    // IMPORT KOTA / KABUPATEN
    // =========================

    $file = fopen($regencyFile, 'r');

    if (!$file) {
        throw new Exception('Gagal membuka regencies.csv');
    }

    // Lewati baris judul
    fgetcsv($file);

    $insertRegency = $pdo->prepare("
        INSERT INTO daerah (id, wilayah_id, jenis, nama)
        VALUES (?, ?, ?, ?)
    ");

    $jumlahDaerah = 0;

    while (($row = fgetcsv($file)) !== false) {

        if (count($row) < 4) {
            continue;
        }

        $id         = trim($row[0]);
        $provinceId = trim($row[1]);
        $name       = trim($row[3]);

        if ($id === '' || $provinceId === '' || $name === '') {
            continue;
        }

        // Tentukan jenis berdasarkan nama
        if (stripos($name, 'Kota ') === 0) {
            $jenis = 'kota';
        } else {
            $jenis = 'kabupaten';
        }

        $insertRegency->execute([
            (int)$id,
            (int)$provinceId,
            $jenis,
            $name
        ]);

        $jumlahDaerah++;
    }

    fclose($file);

    $pdo->commit();

    echo '<!DOCTYPE html>';
    echo '<html lang="id">';
    echo '<head>';
    echo '<meta charset="UTF-8">';
    echo '<title>Import Wilayah</title>';
    echo '<style>';
    echo 'body{font-family:Arial;background:#f7f4ed;padding:40px;}';
    echo '.box{max-width:700px;margin:auto;background:white;padding:30px;border-radius:12px;box-shadow:0 5px 20px rgba(0,0,0,.08);}';
    echo 'h1{color:#17324d;}';
    echo '.success{color:green;font-size:18px;font-weight:bold;}';
    echo '.number{font-size:28px;color:#17324d;font-weight:bold;}';
    echo '</style>';
    echo '</head>';
    echo '<body>';
    echo '<div class="box">';
    echo '<h1>Import Wilayah Indonesia Berhasil</h1>';
    echo '<p class="success">Data berhasil dimasukkan ke database.</p>';
    echo '<p>Provinsi:</p>';
    echo '<div class="number">' . $jumlahProvinsi . '</div>';
    echo '<p>Kota/Kabupaten:</p>';
    echo '<div class="number">' . $jumlahDaerah . '</div>';
    echo '<hr>';
    echo '<p>Jika jumlahnya sudah sesuai, hapus file <b>import-wilayah.php</b>.</p>';
    echo '</div>';
    echo '</body>';
    echo '</html>';

} catch (Exception $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo '<h2>Import gagal</h2>';
    echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
}
?>
