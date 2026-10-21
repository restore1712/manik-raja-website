<?php

require 'config.php';
require_admin();

if (!function_exists('e')) {
    function e($value)
    {
        return htmlspecialchars(
            (string)$value,
            ENT_QUOTES,
            'UTF-8'
        );
    }
}

$memberId = isset($_GET['id'])
    ? (int)$_GET['id']
    : 0;

if ($memberId <= 0) {
    exit('ID anggota tidak valid.');
}

$stmt = $pdo->prepare("
    SELECT
        m.id,
        m.full_name,
        m.husband_name,
        m.wife_name,
        m.children_count,
        m.father_name,
        w.nama AS wilayah_nama,
        d.nama AS daerah_nama,
        d.jenis AS daerah_jenis
    FROM members m
    LEFT JOIN wilayah w
        ON w.id = m.wilayah_id
    LEFT JOIN daerah d
        ON d.id = m.daerah_id
    WHERE m.id = ?
      AND m.status = 'active'
    LIMIT 1
");

$stmt->execute([$memberId]);

$member = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$member) {
    exit('Data anggota tidak ditemukan.');
}

$pageTitle = 'Profil Anggota';

include 'inc/header.php';

?>

<style>

.profil-container {
    max-width: 850px;
    margin: 0 auto;
}

.profil-header {
    background: #17324d;
    color: white;
    padding: 35px 25px;
    border-radius: 14px;
    text-align: center;
    margin-bottom: 25px;
}

.profil-header h1 {
    margin: 0 0 8px;
    font-size: 30px;
}

.profil-header p {
    margin: 0;
    opacity: .9;
}

.profil-card {
    background: white;
    border-radius: 14px;
    padding: 25px;
    margin-bottom: 20px;
    box-shadow: 0 4px 15px rgba(0,0,0,.08);
}

.profil-card h2 {
    margin-top: 0;
    color: #17324d;
    border-bottom: 2px solid #d8a84e;
    padding-bottom: 10px;
}

.profil-table {
    width: 100%;
    border-collapse: collapse;
}

.profil-table tr {
    border-bottom: 1px solid #eee;
}

.profil-table tr:last-child {
    border-bottom: none;
}

.profil-table td {
    padding: 13px 8px;
    vertical-align: top;
}

.profil-table td:first-child {
    width: 220px;
    font-weight: bold;
    color: #17324d;
}

.deceased-mark {
    color: #8b0000;
    font-weight: bold;
    margin-right: 5px;
}

.btn-kembali {
    display: inline-block;
    background: #17324d;
    color: white;
    text-decoration: none;
    padding: 11px 18px;
    border-radius: 7px;
    margin-bottom: 20px;
}

.btn-kembali:hover {
    background: #244b6d;
}

@media(max-width:600px) {

    .profil-header {
        padding: 25px 18px;
    }

    .profil-header h1 {
        font-size: 24px;
    }

    .profil-card {
        padding: 18px;
    }

    .profil-table td {
        display: block;
        width: 100% !important;
        padding: 8px;
    }

    .profil-table td:first-child {
        padding-bottom: 2px;
    }

}

</style>

<div class="profil-container">


<a
    href="index.php"
    class="btn-kembali"
>
    ← Kembali ke Daftar Anggota
</a>


<div class="profil-header">

    <h1>
        <?= e($member['full_name']) ?>
    </h1>

    <p>
        Profil Anggota Pomparan Manik Raja
    </p>

</div>


<div class="profil-card">

    <h2>
        👤 Data Anggota
    </h2>

    <table class="profil-table">

        <tr>
            <td>
                Nama Lengkap
            </td>

            <td>
                <strong>
                    <?php if (
                        !empty($member['member_status']) &&
                        strtolower($member['member_status']) === 'meninggal'
                    ): ?>

                        <span class="deceased-mark">†</span>

                    <?php endif; ?>

                    <?= e($member['full_name']) ?>
                </strong>
            </td>
        </tr>

        <tr>
            <td>
                Pasangan
            </td>

            <td>

                <?php
                $husband = trim($member['husband_name'] ?? '');
                $wife = trim($member['wife_name'] ?? '');

                if ($husband !== ''):
                ?>

                    <?= e($husband) ?>

                <?php elseif ($wife !== ''): ?>

                    <?= e($wife) ?>

                <?php else: ?>

                    -

                <?php endif; ?>

            </td>
        </tr>

        <tr>
            <td>
                Jumlah Anak
            </td>

            <td>
                <strong>
                    <?= (int)($member['children_count'] ?? 0) ?>

