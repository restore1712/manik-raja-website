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
        m.*,
        w.nama AS wilayah_nama,
        d.nama AS daerah_nama,
        d.jenis AS daerah_jenis
    FROM members m
    LEFT JOIN wilayah w
        ON w.id = m.wilayah_id
    LEFT JOIN daerah d
        ON d.id = m.daerah_id
    WHERE m.id = ?
      AND m.deleted_at IS NULL
    LIMIT 1
");

$stmt->execute([$memberId]);

$member = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$member) {
    exit('Data anggota tidak ditemukan.');
}

$pageTitle = 'Detail Anggota';

require 'header.php';

?>

<style>

.detail-container {
    max-width: 900px;
    margin: 0 auto;
}

.detail-header {
    background: #17324d;
    color: white;
    padding: 25px;
    border-radius: 12px;
    margin-bottom: 20px;
}

.detail-header h1 {
    margin: 0 0 8px;
}

.detail-header p {
    margin: 0;
    opacity: .9;
}

.detail-card {
    background: white;
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 20px;
    box-shadow: 0 4px 15px rgba(0,0,0,.07);
}

.detail-card h2 {
    margin-top: 0;
    color: #17324d;
    border-bottom: 2px solid #d8a84e;
    padding-bottom: 10px;
}

.detail-table {
    width: 100%;
    border-collapse: collapse;
}

.detail-table tr {
    border-bottom: 1px solid #eee;
}

.detail-table tr:last-child {
    border-bottom: none;
}

.detail-table td {
    padding: 12px 8px;
    vertical-align: top;
}

.detail-table td:first-child {
    width: 220px;
    font-weight: bold;
    color: #17324d;
}

.status-hidup {
    display: inline-block;
    background: #d4edda;
    color: #155724;
    padding: 6px 12px;
    border-radius: 20px;
    font-weight: bold;
}

.status-meninggal {
    display: inline-block;
    background: #f8d7da;
    color: #721c24;
    padding: 6px 12px;
    border-radius: 20px;
    font-weight: bold;
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

.catatan {
    line-height: 1.7;
    white-space: normal;
}

@media(max-width:600px) {

    .detail-card {
        padding: 18px;
    }

    .detail-table td {
        display: block;
        width: 100% !important;
        padding: 8px;
    }

    .detail-table td:first-child {
        padding-bottom: 2px;
    }

}

</style>


<div class="detail-container">

    <a
        href="admin.php#anggota"
        class="btn-kembali"
    >
        ← Kembali ke Data Anggota
    </a>


    <div class="detail-header">

        <h1>
            Detail Anggota
        </h1>

        <p>
            Data lengkap anggota Pomparan Manik Raja
        </p>

    </div>


    <!-- =====================================================
         IDENTITAS ANGGOTA
         ===================================================== -->

    <div class="detail-card">

        <h2>
            👤 Identitas Anggota
        </h2>

        <table class="detail-table">

            <tr>
                <td>
                    Nama Lengkap
                </td>

                <td>
                    <strong>
                        <?= e($member['full_name']) ?>
                    </strong>
                </td>
            </tr>


            <tr>
                <td>
                    Status Anggota
                </td>

                <td>

                    <?php if (
                        ($member['member_status'] ?? 'hidup')
                        === 'meninggal'
                    ): ?>

                        <span class="status-meninggal">
                            † Meninggal
                        </span>

                    <?php else: ?>

                        <span class="status-hidup">
                            🟢 Hidup
                        </span>

                    <?php endif; ?>

                </td>
            </tr>


            <tr>
                <td>
                    Status Data
                </td>

                <td>

                    <?php if (
                        ($member['status'] ?? '')
                        === 'active'
                    ): ?>

                        <span class="status-hidup">
                            🟢 Aktif
                        </span>

                    <?php else: ?>

                        <?= e($member['status'] ?? '-') ?>

                    <?php endif; ?>

                </td>
            </tr>

        </table>

    </div>


    <!-- =====================================================
         KONTAK
         ===================================================== -->

    <div class="detail-card">

        <h2>
            📞 Kontak
        </h2>

        <table class="detail-table">

            <tr>
                <td>
                    Nomor HP
                </td>

                <td>
                    <?= !empty($member['phone'])
                        ? e($member['phone'])
                        : '-'
                    ?>
                </td>
            </tr>


            <tr>
                <td>
                    Email
                </td>

                <td>
                    <?= !empty($member['email'])
                        ? e($member['email'])
                        : '-'
                    ?>
                </td>
            </tr>

        </table>

    </div>


    <!-- =====================================================
         DATA KELUARGA
         ===================================================== -->

    <div class="detail-card">

        <h2>
            👨‍👩‍👧‍👦 Data Keluarga
        </h2>

        <table class="detail-table">

            <!-- SUAMI -->

            <tr>

                <td>
                    Nama Suami
                </td>

                <td>

                    <?php if (!empty($member['husband_name'])): ?>

                        <?php if (
                            ($member['husband_status'] ?? 'hidup')
                            === 'meninggal'
                        ): ?>

                            †

                        <?php endif; ?>

                        <?= e($member['husband_name']) ?>

                    <?php else: ?>

                        -

                    <?php endif; ?>

                </td>

            </tr>


            <!-- STATUS SUAMI -->

            <tr>

                <td>
                    Status Suami
                </td>

                <td>

                    <?php if (
                        ($member['husband_status'] ?? 'hidup')
                        === 'meninggal'
                    ): ?>

                        <span class="status-meninggal">
                            † Meninggal
                        </span>

                    <?php elseif (!empty($member['husband_name'])): ?>

                        <span class="status-hidup">
                            🟢 Hidup
                        </span>

                    <?php else: ?>

                        -

                    <?php endif; ?>

                </td>

            </tr>


            <!-- ISTRI -->

            <tr>

                <td>
                    Nama Istri
                </td>

                <td>

                    <?php if (!empty($member['wife_name'])): ?>

                        <?php if (
                            ($member['wife_status'] ?? 'hidup')
                            === 'meninggal'
                        ): ?>

                            †

                        <?php endif; ?>

                        <?= e($member['wife_name']) ?>

                    <?php else: ?>

                        -

                    <?php endif; ?>

                </td>

            </tr>


            <!-- STATUS ISTRI -->

            <tr>

                <td>
                    Status Istri
                </td>

                <td>

                    <?php if (
                        ($member['wife_status'] ?? 'hidup')
                        === 'meninggal'
                    ): ?>

                        <span class="status-meninggal">
                            † Meninggal
                        </span>

                    <?php elseif (!empty($member['wife_name'])): ?>

                        <span class="status-hidup">
                            🟢 Hidup
                        </span>

                    <?php else: ?>

                        -

                    <?php endif; ?>

                </td>

            </tr>


            <!-- JUMLAH ANAK -->

            <tr>

                <td>
                    Jumlah Anak
                </td>

                <td>

                    <strong>
                        <?= (int)(
                            $member['children_count'] ?? 0
                        ) ?>
                    </strong>

                    orang

                </td>

            </tr>

        </table>

    </div>


    <!-- =====================================================
         ASAL KELUARGA
         HANYA MENAMPILKAN NAMA AYAH
         ===================================================== -->

    <div class="detail-card">

        <h2>
            🌳 Asal Keluarga
        </h2>

        <table class="detail-table">

            <tr>

                <td>
                    Nama Ayah
                </td>

                <td>

                    <?= !empty($member['father_name'])
                        ? e($member['father_name'])
                        : '-'
                    ?>

                </td>

            </tr>

        </table>

    </div>


    <!-- =====================================================
         DOMISILI
         ===================================================== -->

    <div class="detail-card">

        <h2>
            📍 Domisili
        </h2>

        <table class="detail-table">

            <tr>

                <td>
                    Provinsi / Wilayah
                </td>

                <td>

                    <?= !empty($member['wilayah_nama'])
                        ? e($member['wilayah_nama'])
                        : '-'
                    ?>

                </td>

            </tr>


            <tr>

                <td>
                    Kota / Kabupaten
                </td>

                <td>

                    <?php if (!empty($member['daerah_nama'])): ?>

                        <?= ucfirst(
                            e($member['daerah_jenis'] ?? '')
                        ) ?>

                        <?= e($member['daerah_nama']) ?>

                    <?php else: ?>

                        -

                    <?php endif; ?>

                </td>

            </tr>


            <tr>

                <td>
                    Kota Lama
                </td>

                <td>

                    <?= !empty($member['city'])
                        ? e($member['city'])
                        : '-'
                    ?>

                </td>

            </tr>

        </table>

    </div>


    <!-- =====================================================
         CATATAN
         ===================================================== -->

    <div class="detail-card">

        <h2>
            📝 Catatan
        </h2>

        <div class="catatan">

            <?php if (!empty($member['notes'])): ?>

                <?= nl2br(e($member['notes'])) ?>

            <?php else: ?>

                <span style="color:#777;">
                    Tidak ada catatan.
                </span>

            <?php endif; ?>

        </div>

    </div>

</div>


<?php require 'footer.php'; ?>
```
