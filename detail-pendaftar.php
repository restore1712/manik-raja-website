<?php

require 'config.php';
require_admin();

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    exit('Data pendaftar tidak valid.');
}

$stmt = $pdo->prepare("
    SELECT *
    FROM registrations
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$id]);

$r = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$r) {
    exit('Data pendaftar tidak ditemukan.');
}

/*
|--------------------------------------------------------------------------
| Fungsi tampilan nama keluarga
|--------------------------------------------------------------------------
| Nama tetap disimpan bersih di database.
| Simbol † hanya ditampilkan jika status = meninggal.
*/

function tampilNamaKeluarga($nama, $status)
{
    $nama = trim((string)$nama);

    if ($nama === '') {
        return '-';
    }

    if ($status === 'meninggal') {
        return '† ' . $nama;
    }

    return $nama;
}

$husbandDisplay = tampilNamaKeluarga(
    $r['husband_name'] ?? '',
    $r['husband_status'] ?? 'hidup'
);

$wifeDisplay = tampilNamaKeluarga(
    $r['wife_name'] ?? '',
    $r['wife_status'] ?? 'hidup'
);

/*
|--------------------------------------------------------------------------
| Ambil nama wilayah
|--------------------------------------------------------------------------
*/

$wilayahNama = '-';

if (!empty($r['wilayah_id'])) {

    $stmtWilayah = $pdo->prepare("
        SELECT nama
        FROM wilayah
        WHERE id = ?
        LIMIT 1
    ");

    $stmtWilayah->execute([$r['wilayah_id']]);

    $wilayahNama = $stmtWilayah->fetchColumn() ?: '-';
}

/*
|--------------------------------------------------------------------------
| Ambil nama kota / kabupaten
|--------------------------------------------------------------------------
*/

$daerahNama = '-';

if (!empty($r['daerah_id'])) {

    $stmtDaerah = $pdo->prepare("
        SELECT nama
        FROM daerah
        WHERE id = ?
        LIMIT 1
    ");

    $stmtDaerah->execute([$r['daerah_id']]);

    $daerahNama = $stmtDaerah->fetchColumn() ?: '-';
}

$pageTitle = 'Detail Pendaftar';

require 'header.php';

?>

<h1>Detail Pendaftar</h1>

<div class="card" style="max-width:800px;">


<h2>Data Keluarga</h2>

<hr>

<p>
    <strong>Nama Panggoaran</strong><br>
    <?= e($r['panggoaran'] ?? '-') ?>
</p>

<p>
    <strong>Nama Suami</strong><br>
    <?= e($husbandDisplay) ?>
</p>

<p>
    <strong>Nama Istri</strong><br>
    <?= e($wifeDisplay) ?>
</p>

<p>
    <strong>Jumlah Anak</strong><br>
    <?= (int)($r['children_count'] ?? 0) ?> orang
</p>


<h2 style="margin-top:30px;">Kontak</h2>

<hr>

<p>
    <strong>Nomor HP / WhatsApp</strong><br>

    <?= e($r['phone'] ?? '-') ?>

    <?php

    $waNumber = preg_replace(
        '/[^0-9]/',
        '',
        $r['phone'] ?? ''
    );

    if (substr($waNumber, 0, 1) === '0') {
        $waNumber = '62' . substr($waNumber, 1);
    }

    ?>

    <?php if ($waNumber !== ''): ?>

        <br><br>

        <a
            href="https://wa.me/<?= e($waNumber) ?>"
            target="_blank"
            rel="noopener noreferrer"
            class="btn"
        >
            💬 Hubungi via WhatsApp
        </a>

    <?php endif; ?>

</p>

<p>
    <strong>Email</strong><br>
    <?= e($r['email'] ?? '') ?: '-' ?>
</p>


<h2 style="margin-top:30px;">Domisili</h2>

<hr>

<p>
    <strong>Wilayah / Provinsi</strong><br>
    <?= e($wilayahNama) ?>
</p>

<p>
    <strong>Kota / Kabupaten</strong><br>
    <?= e($daerahNama) ?>
</p>


<h2 style="margin-top:30px;">Informasi Tambahan</h2>

<hr>

<p>
    <strong>Catatan</strong><br>

    <?= !empty($r['notes']) ? nl2br(e($r['notes'])) : '-' ?>
</p>

<p>
    <strong>Tanggal Pendaftaran</strong><br>
    <?= e($r['created_at'] ?? '-') ?>
</p>

<p>
    <strong>Status Pendaftaran</strong><br>

    <?php if (($r['status'] ?? '') === 'pending'): ?>

        <span style="background:#fff3cd;color:#856404;padding:6px 10px;border-radius:20px;font-size:13px;font-weight:bold;">
            🟡 Menunggu
        </span>

    <?php elseif (($r['status'] ?? '') === 'approved'): ?>

        <span style="background:#d4edda;color:#155724;padding:6px 10px;border-radius:20px;font-size:13px;font-weight:bold;">
            🟢 Disetujui
        </span>

    <?php elseif (($r['status'] ?? '') === 'rejected'): ?>

        <span style="background:#f8d7da;color:#721c24;padding:6px 10px;border-radius:20px;font-size:13px;font-weight:bold;">
            🔴 Ditolak
        </span>

    <?php endif; ?>

</p>


<br>

<a href="admin.php" class="btn">
    ← Kembali ke Admin
</a>


</div>

<?php require 'footer.php'; ?>
