<?php
require 'config.php';

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: berita.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT id, title, body, content, image, created_at
    FROM posts
    WHERE id = ?
      AND category = 'berita'
    LIMIT 1
");

$stmt->execute([$id]);

$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) {
    header('Location: berita.php');
    exit;
}

$pageTitle = $post['title'];

include 'inc/header.php';


/*
|--------------------------------------------------------------------------
| Tentukan isi berita
|--------------------------------------------------------------------------
*/

$isiBerita = '';

if (!empty($post['body'])) {

    $isiBerita = $post['body'];

} elseif (!empty($post['content'])) {

    $isiBerita = $post['content'];

}

?>

<style>

/* =========================================================
   DETAIL BERITA
========================================================= */

.news-detail{
    max-width:900px;
    margin:0 auto;
}

.news-detail-header{
    background:
        linear-gradient(
            135deg,
            #17324d,
            #244e70
        );

    color:white;

    padding:45px 40px;

    border-radius:20px;

    margin-bottom:25px;

    box-shadow:0 8px 25px rgba(0,0,0,.12);
}

.news-detail-label{
    color:#d8a84e;
    font-size:13px;
    font-weight:bold;
    letter-spacing:2px;
    margin-bottom:10px;
}

.news-detail-header h1{
    margin:0 0 15px;
    font-size:36px;
    line-height:1.3;
    color:white;
}

.news-detail-date{
    color:rgba(255,255,255,.75);
    font-size:14px;
}

.news-detail-card{
    background:white;
    border-radius:18px;
    overflow:hidden;
    box-shadow:0 6px 22px rgba(0,0,0,.07);
}

.news-detail-image{
    width:100%;
    max-height:500px;
    object-fit:cover;
    display:block;
}

.news-detail-content{
    padding:40px;
}

.news-detail-content p{
    color:#444;
    font-size:16px;
    line-height:1.9;
    margin:0 0 18px;
}

.news-detail-content img{
    max-width:100%;
    height:auto;
}

.news-back{
    display:inline-block;
    margin-top:25px;
    padding:12px 20px;
    background:#17324d;
    color:white;
    text-decoration:none;
    border-radius:8px;
    font-weight:bold;
    transition:.2s;
}

.news-back:hover{
    background:#d8a84e;
    color:#17324d;
}

@media(max-width:600px){

    .news-detail-header{
        padding:30px 22px;
    }

    .news-detail-header h1{
        font-size:27px;
    }

    .news-detail-content{
        padding:25px 20px;
    }

}

</style>

<div class="news-detail">


<!-- =====================================================
     JUDUL BERITA
====================================================== -->

<section class="news-detail-header">

    <div class="news-detail-label">
        BERITA POMPARAN MANIK RAJA
    </div>

    <h1>
        <?= e($post['title']) ?>
    </h1>

    <div class="news-detail-date">

        Dipublikasikan:
        <?= date(
            'd-m-Y H:i',
            strtotime($post['created_at'])
        ) ?>

    </div>

</section>


<!-- =====================================================
     ISI BERITA
====================================================== -->

<article class="news-detail-card">

    <?php if(!empty($post['image'])): ?>

        <img
            src="<?= e($post['image']) ?>"
            alt="<?= e($post['title']) ?>"
            class="news-detail-image"
        >

    <?php endif; ?>


    <div class="news-detail-content">

        <?php if($isiBerita): ?>

            <?= nl2br(e($isiBerita)) ?>

        <?php else: ?>

            <p>
                Isi berita belum tersedia.
            </p>

        <?php endif; ?>


        <a
            href="berita.php"
            class="news-back"
        >
            ← Kembali ke Berita
        </a>

    </div>

</article>


</div>

<?php include 'inc/footer.php'; ?>
