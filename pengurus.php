<?php
require 'config.php';

$pageTitle = 'Pengurus';

$level = $_GET['level'] ?? '';
$region = $_GET['region'] ?? '';

function render_officer_tree(array $rows): void {
    $positionRank = static function (string $position): int {
        $position = mb_strtolower(trim($position), 'UTF-8');
        if ($position === 'penasehat') return -10;
        if (in_array($position, ['ketua umum', 'ketua', 'ketua regional', 'ketua daerah'], true)) return 0;
        if (str_starts_with($position, 'wakil ketua')) return 10;
        if ($position === 'sekretaris') return 20;
        if (str_starts_with($position, 'wakil sekretaris')) return 30;
        if ($position === 'bendahara') return 40;
        if (str_starts_with($position, 'wakil bendahara')) return 50;
        if (str_starts_with($position, 'bendahara')) return 60;
        return 100;
    };
    usort($rows, static function (array $left, array $right) use ($positionRank): int {
        $rankOrder = $positionRank($left['position_name']) <=> $positionRank($right['position_name']);
        if ($rankOrder !== 0) return $rankOrder;
        $nameOrder = strnatcasecmp($left['position_name'], $right['position_name']);
        return $nameOrder !== 0 ? $nameOrder : strnatcasecmp($left['name'], $right['name']);
    });
    $advisors = array_values(array_filter($rows, static fn(array $row): bool => mb_strtolower(trim((string)$row['position_name']), 'UTF-8') === 'penasehat'));
    $rows = array_values(array_filter($rows, static fn(array $row): bool => mb_strtolower(trim((string)$row['position_name']), 'UTF-8') !== 'penasehat'));
    $byId = [];
    foreach ($rows as $row) $byId[(int)$row['id']] = $row;
    $roots = [];
    $children = [];
    $chairId = null;
    foreach ($rows as $row) {
        $position = mb_strtolower(trim((string)$row['position_name']), 'UTF-8');
        if ($position === 'ketua umum') { $chairId = (int)$row['id']; break; }
        if (in_array($position, ['ketua', 'ketua regional', 'ketua daerah'], true) && $chairId === null) $chairId = (int)$row['id'];
    }
    $topId = $chairId;
    $ancestorPath = [];
    while ($topId !== null) {
        $parentId = (int)($byId[$topId]['parent_officer_id'] ?? 0);
        if ($parentId < 1 || !isset($byId[$parentId]) || isset($ancestorPath[$parentId])) break;
        $ancestorPath[$parentId] = true;
        $topId = $parentId;
    }
    if ($topId !== null) {
        $roots[] = $byId[$topId];
        foreach ($rows as $row) {
            $id = (int)$row['id'];
            if ($id === $topId) continue;
            $parentId = (int)($row['parent_officer_id'] ?? 0);
            if ($parentId < 1 || !isset($byId[$parentId]) || $parentId === $id) $parentId = $topId;
            $children[$parentId][] = $row;
        }
    } else {
        foreach ($rows as $row) {
            $parentId = (int)($row['parent_officer_id'] ?? 0);
            if ($parentId > 0 && isset($byId[$parentId])) $children[$parentId][] = $row;
            else $roots[] = $row;
        }
    }

    // Pasangkan Wakil Ketua di samping Ketua; pengurus lain tetap di bawah Ketua.
    $deputyId = null;
    if ($chairId !== null) {
        foreach ($children[$chairId] ?? [] as $child) {
            if (str_starts_with(mb_strtolower(trim((string)$child['position_name']), 'UTF-8'), 'wakil ketua')) {
                $deputyId = (int)$child['id'];
                break;
            }
        }
    }
    $seen = [];
    $renderCard = static function (array $row): void {
        $id = (int)$row['id'];
        echo '<a class="pengurus-card tree-node" href="detail-pengurus.php?id=' . $id . '">';
        echo '<div class="pengurus-icon">';
        if (!empty($row['photo_path'])) echo '<img src="' . e($row['photo_path']) . '" alt="Foto ' . e($row['name']) . '" style="width:100%;height:100%;object-fit:cover">';
        else echo '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 12a4.5 4.5 0 1 0 0-9 4.5 4.5 0 0 0 0 9Zm0 2c-4.1 0-7.5 2.1-7.5 4.7V22h15v-3.3c0-2.6-3.4-4.7-7.5-4.7Z"/></svg>';
        $positionLabel = $row['position_name'];
        echo '</div><div class="pengurus-copy"><div class="pengurus-position">' . e($positionLabel) . '</div><div class="pengurus-name">' . e($row['name']) . '</div>';
        if (!empty($row['period'])) echo '<span class="pengurus-period">' . e($row['period']) . '</span>';
        echo '</div></a>';
    };
    $renderNode = function (array $row) use (&$renderNode, &$seen, $children, $byId, $topId, $chairId, $deputyId, $renderCard): void {
        $id = (int)$row['id'];
        if (isset($seen[$id])) return;
        $seen[$id] = true;
        echo '<li>';
        if ($id === $chairId && $deputyId !== null && isset($byId[$deputyId])) {
            echo '<div class="leadership-pair">';
            $renderCard($row);
            $renderCard($byId[$deputyId]);
            echo '</div>';
            $seen[$deputyId] = true;
        } else {
            $renderCard($row);
        }
        if (!empty($children[$id])) {
            echo '<ul>';
            foreach ($children[$id] as $child) if ((int)$child['id'] !== $deputyId) $renderNode($child);
            echo '</ul>';
        }
        echo '</li>';
    };
    echo '<div class="tree-scroll"><div class="officer-chart">';
    if ($advisors) {
        echo '<div class="advisor-row">';
        foreach ($advisors as $advisor) $renderCard($advisor);
        echo '</div>';
    }
    echo '<div class="officer-tree"><ul>';
    foreach ($roots as $root) $renderNode($root);
    foreach ($rows as $row) if (!isset($seen[(int)$row['id']])) $renderNode($row);
    echo '</ul></div></div></div>';
}

require 'inc/header.php';
?>

<style>

.pengurus-page{
    max-width:1000px;
    margin:0 auto;
}

/* ==============================
   JUDUL
============================== */

.page-header{
    background:linear-gradient(
        135deg,
        #17324d,
        #244e70
    );

    color:white;

    padding:40px 30px;

    border-radius:20px;

    margin-bottom:30px;

    text-align:center;

    box-shadow:0 8px 25px rgba(0,0,0,.12);
}

.page-header-label{
    color:#d8a84e;

    font-size:13px;

    font-weight:bold;

    letter-spacing:2px;

    margin-bottom:10px;
}

.page-header h1{
    margin:0 0 10px;

    color:white;

    font-size:34px;
}

.page-header p{
    margin:0;

    color:rgba(255,255,255,.85);

    line-height:1.7;
}


/* ==============================
   TOMBOL KEMBALI
============================== */

.back-button{
    display:inline-block;

    margin-bottom:25px;

    padding:10px 18px;

    background:#17324d;

    color:white;

    text-decoration:none;

    border-radius:8px;

    font-weight:bold;

    transition:.2s;
}

.back-button:hover{
    background:#d8a84e;

    color:#17324d;
}

/* ==============================
   GRID
============================== */

.pengurus-grid{
    display:grid;
    grid-template-columns:repeat(3, minmax(0, 1fr));
    gap:18px;
    margin-bottom:30px;
}


/* ==============================
   KARTU
============================== */

.pengurus-card{
    background:white;
    color:inherit;
    text-decoration:none;
    min-height:104px;
    border-radius:16px;
    padding:20px;
    display:flex;
    align-items:center;
    gap:16px;
    text-align:left;
    border:1px solid #eee7d9;
    border-left:4px solid #d8a84e;
    box-shadow:0 4px 14px rgba(23,50,77,.06);
    transition:transform .2s, box-shadow .2s;
}

.pengurus-card:hover{
    transform:translateY(-2px);
    box-shadow:0 8px 20px rgba(23,50,77,.12);
}


/* ==============================
   ICON
============================== */

.pengurus-icon{
    width:68px;
    height:64px;
    flex:0 0 68px;
    overflow:hidden;
    border-radius:10px;
    background:#f2eee5;
    display:flex;
    align-items:center;
    justify-content:center;
    color:#17324d;
}

.pengurus-icon svg{
    width:23px;
    height:23px;
    fill:currentColor;
}

.pengurus-copy{
    min-width:0;
}


/* ==============================
   JABATAN
============================== */

.pengurus-position{
    color:#b88925;
    font-size:11px;

    font-weight:bold;

    text-transform:uppercase;

    letter-spacing:.5px;

    margin-bottom:4px;
}


/* ==============================
   NAMA
============================== */

.pengurus-name{
    color:#17324d;
    font-size:16px;

    font-weight:bold;

    margin-bottom:5px;
    line-height:1.4;
}


/* ==============================
   PERIODE
============================== */

.pengurus-period{
    display:inline-block;

    padding:4px 9px;

    background:#f7f4ed;

    color:#666;

    border-radius:20px;

    font-size:11px;
}

.tree-scroll{overflow-x:auto;padding:12px 4px 24px}
.officer-chart{width:max-content;margin:0 auto}
.advisor-row{position:relative;display:flex;width:max-content;justify-content:center;align-items:flex-start;flex-wrap:nowrap;gap:16px;margin:8px auto 0;padding:8px 0 38px}
.advisor-row .tree-node{margin:0}
.advisor-row::before{content:"";position:absolute;left:115px;right:115px;bottom:18px;border-top:2px solid #d8a84e}
.advisor-row::after{content:"";position:absolute;left:50%;bottom:0;height:19px;border-left:2px solid #d8a84e}
.officer-tree{width:100%;min-width:max-content;text-align:center}
.officer-tree ul{display:flex;justify-content:center;position:relative;margin:0;padding:28px 0 0}
.officer-tree>ul{padding-top:0}
.officer-tree li{list-style:none;position:relative;padding:28px 10px 0}
.officer-tree>ul>li{padding-top:0}
.officer-tree li::before,.officer-tree li::after{content:"";position:absolute;top:0;right:50%;width:50%;height:28px;border-top:2px solid #d8a84e}
.officer-tree li::after{right:auto;left:50%;border-left:2px solid #d8a84e}
.officer-tree li:only-child::before,.officer-tree li:only-child::after{display:none}
.officer-tree li:only-child{padding-top:0}
.officer-tree li:first-child::before,.officer-tree li:last-child::after{border:0}
.officer-tree li:last-child::before{border-right:2px solid #d8a84e;border-radius:0 8px 0 0}
.officer-tree li:first-child::after{border-radius:8px 0 0 0}
.officer-tree ul ul::before{content:"";position:absolute;top:0;left:50%;height:28px;border-left:2px solid #d8a84e}
.tree-node{width:230px;min-height:120px;margin:auto;flex-direction:column;justify-content:center;text-align:center;gap:8px;padding:16px}
.leadership-pair{display:flex;justify-content:center;align-items:flex-start;gap:16px}
.leadership-pair .tree-node{margin:0}
.tree-node .pengurus-icon{width:54px;height:54px;flex-basis:54px;border-radius:50%}
.tree-node .pengurus-copy{width:100%}


/* ==============================
   PILIHAN PUSAT / DAERAH
============================== */

.level-grid{
    display:grid;
    grid-template-columns:repeat(3, minmax(0, 1fr));

    gap:25px;
}

.level-card{
    background:white;

    padding:35px 25px;

    border-radius:20px;

    text-align:center;

    text-decoration:none;

    color:#17324d;

    border:1px solid #eee;

    box-shadow:
        0 6px 20px rgba(0,0,0,.07);

    transition:.25s;
}

.level-card:hover{
    transform:translateY(-6px);

    border-color:#d8a84e;

    background:#f7f4ed;

    box-shadow:
        0 12px 28px rgba(0,0,0,.12);
}

.level-icon{
    width:62px;
    height:62px;
    margin:0 auto 15px;
    border-radius:50%;
    background:#f7f4ed;
    color:#17324d;
    display:grid;
    place-items:center;
}

.level-icon svg{
    width:34px;
    height:34px;
    fill:currentColor;
}

.level-card h2{
    margin:0 0 10px;

    color:#17324d;
}

.level-card p{
    margin:0 0 20px;

    color:#666;

    line-height:1.6;
}

.level-button{
    display:inline-block;

    padding:10px 18px;

    background:#17324d;

    color:white;

    border-radius:8px;

    font-weight:bold;
}


/* ==============================
   DAERAH
============================== */

.region-grid{
    display:grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap:18px;
}

.region-card{
    background:white;

    padding:25px 20px;

    border-radius:16px;

    text-align:center;

    border:1px solid #eee;

    box-shadow:
        0 5px 18px rgba(0,0,0,.06);

    transition:.25s;
}

.region-card:hover{
    transform:translateY(-4px);

    border-color:#d8a84e;
}

.region-icon{
    width:50px;
    height:50px;
    margin:0 auto 12px;
    border-radius:50%;
    background:#f7f4ed;
    color:#17324d;
    display:grid;
    place-items:center;
}

.region-icon svg{
    width:25px;
    height:25px;
    fill:currentColor;
}

.region-card h3{
    color:#17324d;

    margin:0 0 15px;
}

.region-button{
    display:inline-block;

    padding:9px 15px;

    background:#17324d;

    color:white;

    text-decoration:none;

    border-radius:7px;

    font-size:14px;

    font-weight:bold;
}

.region-button:hover{
    background:#d8a84e;

    color:#17324d;
}


/* ==============================
   PESAN KOSONG
============================== */

.empty-card{
    background:white;

    padding:35px;

    border-radius:18px;

    text-align:center;

    color:#666;

    box-shadow:
        0 5px 18px rgba(0,0,0,.06);
}


/* ==============================
   RESPONSIVE
============================== */

@media(max-width:850px){

    .level-grid{grid-template-columns:repeat(2, minmax(0, 1fr));}

    .pengurus-grid{
        grid-template-columns:repeat(2, minmax(0, 1fr));
    }

    .region-grid{
        grid-template-columns:
            repeat(2, 1fr);
    }

}

@media(max-width:600px){

    .page-header{
        padding:30px 20px;
    }

    .page-header h1{
        font-size:28px;
    }

    .pengurus-grid{
        grid-template-columns:1fr;
    }

    .level-grid{
        grid-template-columns:1fr;
    }

    .region-grid{
        grid-template-columns:1fr;
    }

    .tree-node{width:200px}

}

</style>

<div class="pengurus-page">

<?php if ($level === ''): ?>


<!-- ==========================================
     HALAMAN UTAMA
=========================================== -->

<section class="page-header">

    <div class="page-header-label">
        POMPARAN MANIK RAJA
    </div>

    <h1>
        Pengurus
    </h1>

    <p>
        Susunan kepengurusan Pomparan Manik Raja
        dari tingkat pusat, regional, hingga daerah.
    </p>

</section>


<div class="level-grid">


    <a
        href="pengurus.php?level=pusat"
        class="level-card"
    >

        <div class="level-icon">
    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 21h18v-2h-2V9l-7-5-7 5v10H3v2Zm4-2v-8h2v8H7Zm4 0v-8h2v8h-2Zm4 0v-8h2v8h-2Zm-8-9.2 7-5 7 5V10H5v-.2Z"/></svg>
</div>

        <h2>
            Pengurus Pusat
        </h2>

        <p>
            Lihat susunan Pengurus Pusat
            Pomparan Manik Raja.
        </p>

        <span class="level-button">
            Lihat Pengurus
        </span>

    </a>

    <a
        href="pengurus.php?level=regional"
        class="level-card"
    >

        <div class="level-icon">
    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2 2 7v2h20V7L12 2Zm-7 9h2v7H5v-7Zm6 0h2v7h-2v-7Zm6 0h2v7h-2v-7ZM3 20h18v2H3v-2Z"/></svg>
</div>

        <h2>Pengurus Regional</h2>

        <p>Lihat susunan pengurus untuk kawasan seperti Jabodetabek atau Sumatera Utara.</p>

        <span class="level-button">Lihat Regional</span>

    </a>


    <a
        href="pengurus.php?level=daerah"
        class="level-card"
    >

        <div class="level-icon">
    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a8 8 0 0 0-8 8c0 5.4 8 12 8 12s8-6.6 8-12a8 8 0 0 0-8-8Zm0 11a3 3 0 1 1 0-6 3 3 0 0 1 0 6Z"/></svg>
</div>

        <h2>
            Pengurus Daerah
        </h2>

        <p>
            Lihat susunan pengurus
            berdasarkan wilayah atau daerah.
        </p>

        <span class="level-button">
            Lihat Daerah
        </span>

    </a>


</div>


<?php elseif ($level === 'pusat'): ?>


<!-- ==========================================
     PENGURUS PUSAT
=========================================== -->

<section class="page-header">

    <div class="page-header-label">
        POMPARAN MANIK RAJA
    </div>

    <h1>
        Pengurus Pusat
    </h1>

    <p>
        Susunan Pengurus Pusat Pomparan Manik Raja.
    </p>

</section>


<a
    href="pengurus.php"
    class="back-button"
>
    Kembali
</a>


<?php

$groupId = (int)$region;
if ($groupId < 1):
    $groups = $pdo->query("SELECT g.id, g.name FROM officer_groups g WHERE g.level = 'pusat' AND EXISTS (SELECT 1 FROM officers o WHERE o.officer_group_id = g.id AND TRIM(o.name) <> '') ORDER BY g.name")->fetchAll();
?>
    <?php if (!$groups): ?>
        <div class="empty-card">Kelompok Pengurus Pusat belum dimasukkan.</div>
    <?php else: ?>
        <div class="region-grid">
            <?php foreach ($groups as $group): ?>
                <div class="region-card">
                    <div class="region-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 21h18v-2h-2V9l-7-5-7 5v10H3v2Zm4-2v-8h2v8H7Zm4 0v-8h2v8h-2Zm4 0v-8h2v8h-2Zm-8-9.2 7-5 7 5V10H5v-.2Z"/></svg></div>
                    <h3><?= e($group['name']) ?></h3>
                    <a href="pengurus.php?level=pusat&amp;region=<?= (int)$group['id'] ?>" class="region-button">Lihat Susunan</a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
<?php else:
    $groupQuery = $pdo->prepare("SELECT * FROM officer_groups WHERE id = ? AND level = 'pusat' LIMIT 1");
    $groupQuery->execute([$groupId]);
    $selectedGroup = $groupQuery->fetch();
    $rows = [];
    if ($selectedGroup) {
        $rowQuery = $pdo->prepare("SELECT * FROM officers WHERE officer_group_id = ? AND TRIM(name) <> '' ORDER BY parent_officer_id IS NOT NULL, CASE WHEN position_name = 'Penasehat' THEN 0 WHEN position_name LIKE '%Ketua%' AND position_name NOT LIKE '%Wakil%' THEN 1 WHEN position_name LIKE '%Wakil Ketua%' THEN 2 WHEN position_name = 'Sekretaris' THEN 3 WHEN position_name LIKE '%Wakil Sekretaris%' THEN 4 WHEN position_name = 'Bendahara' THEN 5 WHEN position_name LIKE '%Wakil Bendahara%' THEN 6 ELSE 7 END, slot_number, id");
        $rowQuery->execute([$groupId]);
        $rows = $rowQuery->fetchAll();
        foreach ($rows as &$row) if (empty($row['period'])) $row['period'] = $selectedGroup['period'];
        unset($row);
    }
?>
    <?php if (!$selectedGroup): ?>
        <div class="empty-card">Kelompok pengurus tidak ditemukan.</div>
    <?php elseif (!$rows): ?>
        <div class="empty-card">Data jabatan kelompok ini belum diisi.</div>
    <?php else: ?>
        <h2><?= e($selectedGroup['name']) ?></h2>
        <?php render_officer_tree($rows); ?>
    <?php endif; ?>
<?php endif; ?>


<?php elseif ($level === 'regional' && $region === ''): ?>

<section class="page-header">
    <div class="page-header-label">POMPARAN MANIK RAJA</div>
    <h1>Pengurus Regional</h1>
    <p>Pilih regional untuk melihat susunan pengurus kawasan.</p>
</section>

<a href="pengurus.php" class="back-button">Kembali</a>

<?php
$regions = $pdo->query("SELECT g.id, g.name AS region FROM officer_groups g WHERE g.level = 'regional' AND EXISTS (SELECT 1 FROM officers o WHERE o.officer_group_id = g.id AND TRIM(o.name) <> '') ORDER BY g.name")->fetchAll();
?>

<?php if (!$regions): ?>
    <div class="empty-card">Belum ada data Pengurus Regional.</div>
<?php else: ?>
    <div class="region-grid">
        <?php foreach ($regions as $r): ?>
            <div class="region-card">
                <div class="region-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a8 8 0 0 0-8 8c0 5.4 8 12 8 12s8-6.6 8-12a8 8 0 0 0-8-8Zm0 11a3 3 0 1 1 0-6 3 3 0 0 1 0 6Z"/></svg></div>
                <h3><?= e($r['region']) ?></h3>
                <a href="pengurus.php?level=regional&amp;region=<?= (int)$r['id'] ?>" class="region-button">Lihat Pengurus</a>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php elseif ($level === 'regional' && $region !== ''): ?>

<?php
$groupId = (int)$region;
$groupQuery = $pdo->prepare("SELECT * FROM officer_groups WHERE id = ? AND level = 'regional' LIMIT 1");
$groupQuery->execute([$groupId]);
$selectedGroup = $groupQuery->fetch();
?>

<section class="page-header">
    <div class="page-header-label">POMPARAN MANIK RAJA</div>
    <h1>Pengurus Regional</h1>
    <p><?= e($selectedGroup['name'] ?? 'Regional tidak ditemukan') ?></p>
</section>

<a href="pengurus.php?level=regional" class="back-button">Kembali ke Daftar Regional</a>

<?php
$rows = [];
if ($selectedGroup) {
    $stmt = $pdo->prepare("SELECT * FROM officers WHERE officer_group_id = ? AND TRIM(name) <> '' ORDER BY parent_officer_id IS NOT NULL, CASE WHEN position_name = 'Penasehat' THEN 0 WHEN position_name LIKE '%Ketua%' AND position_name NOT LIKE '%Wakil%' THEN 1 WHEN position_name LIKE '%Wakil Ketua%' THEN 2 WHEN position_name = 'Sekretaris' THEN 3 WHEN position_name LIKE '%Wakil Sekretaris%' THEN 4 WHEN position_name = 'Bendahara' THEN 5 WHEN position_name LIKE '%Wakil Bendahara%' THEN 6 ELSE 7 END, slot_number, id");
    $stmt->execute([$groupId]);
    $rows = $stmt->fetchAll();
    foreach ($rows as &$row) if (empty($row['period'])) $row['period'] = $selectedGroup['period'];
    unset($row);
}
?>

<?php if (!$selectedGroup): ?>
    <div class="empty-card">Regional tidak ditemukan.</div>
<?php elseif (!$rows): ?>
    <div class="empty-card">Data jabatan regional ini belum diisi.</div>
<?php else: ?>
    <?php render_officer_tree($rows); ?>
<?php endif; ?>


<?php elseif ($level === 'daerah' && $region === ''): ?>

<!-- ==========================================
     DAFTAR DAERAH
=========================================== -->

<section class="page-header">

    <div class="page-header-label">
        POMPARAN MANIK RAJA
    </div>

    <h1>
        Pengurus Daerah
    </h1>

    <p>
        Pilih daerah untuk melihat susunan pengurus.
    </p>

</section>


<a
    href="pengurus.php"
    class="back-button"
>
    Kembali
</a>


<?php

$regions = $pdo->query("SELECT g.id, g.name AS region FROM officer_groups g WHERE g.level = 'daerah' AND EXISTS (SELECT 1 FROM officers o WHERE o.officer_group_id = g.id AND TRIM(o.name) <> '') ORDER BY g.name")->fetchAll();

?>


<?php if (!$regions): ?>

    <div class="empty-card">
        Belum ada data Pengurus Daerah.
    </div>

<?php else: ?>


    <div class="region-grid">


        <?php foreach ($regions as $r): ?>

            <div class="region-card">

                <div class="region-icon">
    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a8 8 0 0 0-8 8c0 5.4 8 12 8 12s8-6.6 8-12a8 8 0 0 0-8-8Zm0 11a3 3 0 1 1 0-6 3 3 0 0 1 0 6Z"/></svg>
</div>

                <h3>
                    <?= e($r['region']) ?>
                </h3>

                <a
                    href="pengurus.php?level=daerah&amp;region=<?= (int)$r['id'] ?>"
                    class="region-button"
                >
                    Lihat Pengurus
                </a>

            </div>

        <?php endforeach; ?>


    </div>


<?php endif; ?>

<?php elseif ($level === 'daerah' && $region !== ''): ?>

<?php
$groupId = (int)$region;
$groupQuery = $pdo->prepare("SELECT * FROM officer_groups WHERE id = ? AND level = 'daerah' LIMIT 1");
$groupQuery->execute([$groupId]);
$selectedGroup = $groupQuery->fetch();
?>


<!-- ==========================================
     PENGURUS DAERAH
=========================================== -->

<section class="page-header">

    <div class="page-header-label">
        POMPARAN MANIK RAJA
    </div>

    <h1>
        Pengurus Daerah
    </h1>

    <p>
        <?= e($selectedGroup['name'] ?? 'Daerah tidak ditemukan') ?>
    </p>

</section>


<a
    href="pengurus.php?level=daerah"
    class="back-button"
>
    Kembali ke Daftar Daerah
</a>


<?php

$rows = [];
if ($selectedGroup) {
    $stmt = $pdo->prepare("SELECT * FROM officers WHERE officer_group_id = ? AND TRIM(name) <> '' ORDER BY parent_officer_id IS NOT NULL, CASE WHEN position_name = 'Penasehat' THEN 0 WHEN position_name LIKE '%Ketua%' AND position_name NOT LIKE '%Wakil%' THEN 1 WHEN position_name LIKE '%Wakil Ketua%' THEN 2 WHEN position_name = 'Sekretaris' THEN 3 WHEN position_name LIKE '%Wakil Sekretaris%' THEN 4 WHEN position_name = 'Bendahara' THEN 5 WHEN position_name LIKE '%Wakil Bendahara%' THEN 6 ELSE 7 END, slot_number, id");
    $stmt->execute([$groupId]);
    $rows = $stmt->fetchAll();
    foreach ($rows as &$row) if (empty($row['period'])) $row['period'] = $selectedGroup['period'];
    unset($row);
}

?>


<?php if (!$selectedGroup): ?>
    <div class="empty-card">Daerah tidak ditemukan.</div>
<?php elseif (!$rows): ?>

    <div class="empty-card">
        Data jabatan daerah ini belum diisi.
    </div>

<?php else: ?>


    <?php render_officer_tree($rows); ?>


<?php endif; ?>


<?php endif; ?>

</div>

<?php require 'footer.php'; ?>
