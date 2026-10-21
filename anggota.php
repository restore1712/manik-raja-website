<?php

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

require 'config.php';

$pageTitle = 'Anggota Pomparan Manik Raja';

/* =====================================================
   FILTER
===================================================== */

$search = trim($_GET['search'] ?? '');

$wilayah_id = isset($_GET['wilayah_id'])
    ? (int) $_GET['wilayah_id']
    : 0;

$daerah_id = isset($_GET['daerah_id'])
    ? (int) $_GET['daerah_id']
    : 0;

// Jangan pertahankan pilihan kota/kabupaten dari wilayah sebelumnya.
if ($daerah_id > 0) {
    if ($wilayah_id <= 0) {
        $daerah_id = 0;
    } else {
        $validDaerah = $pdo->prepare('SELECT 1 FROM daerah WHERE id = ? AND wilayah_id = ? LIMIT 1');
        $validDaerah->execute([$daerah_id, $wilayah_id]);
        if (!$validDaerah->fetchColumn()) {
            $daerah_id = 0;
        }
    }
}

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;



/* =====================================================
   DATA WILAYAH
===================================================== */

$stmtWilayah = $pdo->query("
    SELECT id, nama
    FROM wilayah
    ORDER BY nama ASC
");

$wilayahList = $stmtWilayah->fetchAll(PDO::FETCH_ASSOC);


/* =====================================================
   DATA DAERAH
===================================================== */

$daerahList = [];

if ($wilayah_id > 0) {

    $stmtDaerah = $pdo->prepare("
        SELECT id, nama, jenis
        FROM daerah
        WHERE wilayah_id = ?
        ORDER BY FIELD(jenis, 'kota', 'kabupaten'), nama ASC
    ");

    $stmtDaerah->execute([$wilayah_id]);

    $daerahList = $stmtDaerah->fetchAll(PDO::FETCH_ASSOC);
}


/* =====================================================
   DATA ANGGOTA
===================================================== */

$sql = "
    SELECT
        m.panggoaran AS full_name,
        w.nama AS wilayah_nama,
        d.nama AS daerah_nama

    FROM members m

    LEFT JOIN wilayah w
        ON w.id = m.wilayah_id

    LEFT JOIN daerah d
        ON d.id = m.daerah_id

    WHERE m.status = 'active'
      AND m.deleted_at IS NULL
      AND m.panggoaran IS NOT NULL
      AND TRIM(m.panggoaran) <> ''
";

$params = [];


/* =====================================================
   FILTER NAMA
===================================================== */

if ($search !== '') {

    $sql .= "
        AND m.panggoaran LIKE ?
    ";

    $params[] = '%' . $search . '%';
}


/* =====================================================
   FILTER WILAYAH
===================================================== */

if ($wilayah_id > 0) {

    $sql .= "
        AND m.wilayah_id = ?
    ";

    $params[] = $wilayah_id;
}


/* =====================================================
   FILTER DAERAH
===================================================== */

if ($daerah_id > 0) {

    $sql .= "
        AND m.daerah_id = ?
    ";

    $params[] = $daerah_id;
}


$countSql = "
    SELECT COUNT(*)
    FROM members m
    LEFT JOIN wilayah w ON w.id = m.wilayah_id
    LEFT JOIN daerah d ON d.id = m.daerah_id
" . substr($sql, strpos($sql, '    WHERE'));
$countSql = preg_replace('/\s+ORDER BY m\.full_name ASC\s*$/', '', $countSql);
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalMembers = (int)$countStmt->fetchColumn();
$pageCount = max(1, (int)ceil($totalMembers / $perPage));
$page = min($page, $pageCount);
$offset = ($page - 1) * $perPage;

/* =====================================================
   URUTKAN DAN BATASI HALAMAN
===================================================== */

$sql .= "
    ORDER BY m.panggoaran ASC
    LIMIT {$perPage} OFFSET {$offset}
";


/* =====================================================
   EKSEKUSI
===================================================== */

$stmt = $pdo->prepare($sql);

$stmt->execute($params);

$members = $stmt->fetchAll(PDO::FETCH_ASSOC);


/* =====================================================
   HEADER
===================================================== */

require 'inc/header.php';

?>

<style>

.anggota-page{
    max-width:1100px;
    margin:0 auto;
}


/* HEADER */

.anggota-header{
    margin-bottom:25px;
}

.anggota-header h1{
    margin:0 0 8px;
    color:#17324d;
    font-size:32px;
}

.anggota-header p{
    margin:0;
    color:#5f6b75;
}


/* FILTER */

.filter-box{
    background:#ffffff;
    border-radius:12px;
    padding:20px;
    margin-bottom:25px;
    box-shadow:0 4px 18px rgba(0,0,0,.06);
}

.filter-grid{
    display:grid;
    grid-template-columns:2fr 1fr 1fr auto;
    gap:12px;
    align-items:end;
}

.filter-group label{
    display:block;
    font-weight:bold;
    margin-bottom:6px;
    color:#24313d;
    font-size:14px;
}

.filter-group input,
.filter-group select{
    width:100%;
    padding:11px 12px;
    border:1px solid #d7dce0;
    border-radius:7px;
    font-size:14px;
    background:#ffffff;
}

.filter-group input:focus,
.filter-group select:focus{
    outline:none;
    border-color:#17324d;
}


/* BUTTON FILTER */

.btn-filter{
    display:inline-block;
    padding:11px 18px;
    background:#17324d;
    color:#ffffff;
    border:0;
    border-radius:7px;
    text-decoration:none;
    cursor:pointer;
    font-size:14px;
}

.btn-filter:hover{
    background:#d8a84e;
    color:#17324d;
}

.btn-reset{
    display:inline-block;
    margin-top:12px;
    color:#17324d;
    text-decoration:none;
    font-size:14px;
}

.btn-reset:hover{
    color:#d8a84e;
}


/* JUMLAH DATA */

.jumlah-data{
    margin:0 0 12px;
    color:#5f6b75;
    font-size:14px;
}


/* TABEL */

.table-wrap{
    background:#ffffff;
    border-radius:12px;
    overflow-x:auto;
    box-shadow:0 4px 18px rgba(0,0,0,.06);
}

.anggota-table{
    width:100%;
    border-collapse:collapse;
    min-width:520px;
}

.anggota-table th{
    background:#17324d;
    color:#ffffff;
    padding:14px 15px;
    text-align:left;
    font-size:14px;
    white-space:nowrap;
}

.anggota-table td{
    padding:13px 15px;
    border-bottom:1px solid #eceff1;
    font-size:14px;
    vertical-align:middle;
}

.anggota-table tr:last-child td{
    border-bottom:0;
}

.anggota-table tbody tr:hover{
    background:#faf8f2;
}


/* DATA KOSONG */

.empty-data{
    text-align:center;
    padding:40px 20px !important;
    color:#6b737a;
}


/* WILAYAH */

.wilayah-detail{
    color:#24313d;
    line-height:1.5;
}

.wilayah-detail small{
    color:#6b737a;
}


/* RESPONSIVE */

@media(max-width:1000px){

    .filter-grid{
        grid-template-columns:1fr 1fr;
    }

    .filter-search{
        grid-column:1 / -1;
    }

}


@media(max-width:600px){

    .anggota-header h1{
        font-size:26px;
    }

    .filter-grid{
        grid-template-columns:1fr;
    }

    .filter-search{
        grid-column:auto;
    }

    .btn-filter{
        width:100%;
    }

}

</style>

<div class="anggota-page">

<!-- JUDUL -->

<div class="anggota-header">

    <h1>
        Anggota Pomparan Manik Raja
    </h1>

    <p>
        Daftar anggota yang telah terdaftar dan disetujui.
    </p>

</div>


<!-- FILTER -->

<div class="filter-box">

    <form
        method="GET"
        action="anggota.php"
    >
        <input type="hidden" name="page" value="1">

        <div class="filter-grid">


            <!-- CARI NAMA -->

            <div class="filter-group filter-search">

                <label for="search">
                    Cari Nama
                </label>

                <input
                    type="text"
                    id="search"
                    name="search"
                    value="<?= htmlspecialchars($search) ?>"
                    placeholder="Ketik nama anggota..."
                >

            </div>


            <!-- WILAYAH -->

            <div class="filter-group">

                <label for="wilayah_id">
                    Wilayah
                </label>

                <select
                    name="wilayah_id"
                    id="wilayah_id"
                    onchange="this.form.submit()"
                >

                    <option value="0">
                        Semua Wilayah
                    </option>

                    <?php foreach ($wilayahList as $wilayah): ?>

                        <option
                            value="<?= (int)$wilayah['id'] ?>"
                            <?= $wilayah_id == $wilayah['id'] ? 'selected' : '' ?>
                        >

                            <?= htmlspecialchars($wilayah['nama']) ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <!-- KOTA / KABUPATEN -->

            <div class="filter-group">

                <label for="daerah_id">
                    Kota/Kabupaten
                </label>

                <select
                    name="daerah_id"
                    id="daerah_id"
                >

                    <option value="0">
                        Semua
                    </option>

                    <?php foreach ($daerahList as $daerah): ?>

                        <option
                            value="<?= (int)$daerah['id'] ?>"
                            <?= $daerah_id == $daerah['id'] ? 'selected' : '' ?>
                        >

                            <?= htmlspecialchars($daerah['nama']) ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <!-- TOMBOL -->

            <div>

                <button
                    type="submit"
                    class="btn-filter"
                >
                    Cari
                </button>

            </div>

        </div>


        <!-- RESET -->

        <a
            href="anggota.php"
            class="btn-reset"
        >
            Reset Filter
        </a>

    </form>

</div>


<!-- JUMLAH -->

<p class="jumlah-data">

    Menampilkan

    <strong>
        <?= $totalMembers ?>
    </strong>

    anggota.

</p>


<!-- TABEL -->

<div class="table-wrap">

    <table class="anggota-table">

        <thead>

            <tr>

                <th>
                    No.
                </th>

                <th>Nama</th>

                <th>
                    Wilayah
                </th>


            </tr>

        </thead>


        <tbody>


            <?php if (empty($members)): ?>


                <tr>

                    <td
                        colspan="3"
                        class="empty-data"
                    >

                        Tidak ada data anggota
                        yang sesuai dengan filter.

                    </td>

                </tr>


            <?php else: ?>


                <?php $no = 1; ?>


                <?php foreach ($members as $member): ?>


                    <?php

                    /*
                     * Wilayah
                     */

                    $wilayah = '-';

                    if (!empty($member['wilayah_nama'])) {

                        $wilayah =
                            htmlspecialchars(
                                $member['wilayah_nama']
                            );

                    }


                    /*
                     * Kota / Kabupaten
                     */

                    if (!empty($member['daerah_nama'])) {

                        $wilayah .=
                            '<br><small>' .
                            htmlspecialchars(
                                $member['daerah_nama']
                            ) .
                            '</small>';

                    }

                    ?>


                    <tr>


                        <!-- NOMOR -->

                        <td>
                            <?= $no++ ?>
                        </td>


                        <td><strong><?= htmlspecialchars($member['full_name'], ENT_QUOTES, 'UTF-8') ?></strong></td>


                        <!-- WILAYAH -->

                        <td>

                            <div class="wilayah-detail">

                                <?= $wilayah ?>

                            </div>

                        </td>
                    </tr>


                <?php endforeach; ?>


            <?php endif; ?>


        </tbody>

    </table>

</div>

<div class="actions" style="display:flex;justify-content:center;gap:12px;margin:18px 0">
    <?php if ($page > 1): ?>
        <a class="btn-filter" href="?<?= htmlspecialchars(http_build_query(array_merge($_GET, ['page' => $page - 1])), ENT_QUOTES, 'UTF-8') ?>">Sebelumnya</a>
    <?php endif; ?>
    <span style="align-self:center">Halaman <?= $page ?> dari <?= $pageCount ?></span>
    <?php if ($page < $pageCount): ?>
        <a class="btn-filter" href="?<?= htmlspecialchars(http_build_query(array_merge($_GET, ['page' => $page + 1])), ENT_QUOTES, 'UTF-8') ?>">Berikutnya</a>
    <?php endif; ?>
</div>

</div>

<?php

require 'inc/footer.php';

?>
