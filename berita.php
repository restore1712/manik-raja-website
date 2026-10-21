<?php
require 'config.php';

$pageTitle = 'Berita';

$rows = $pdo->query("
    SELECT id, title, body, image, created_at
    FROM posts
    WHERE category = 'berita'
    ORDER BY created_at DESC
")->fetchAll();

require 'inc/header.php';
?>

<style>
.news-list{
    max-width:950px;
    margin:0 auto;
}

.news-list h1{
    margin-bottom:25px;
}

.news-card{
    background:#fff;
    border-radius:18px;
    overflow:hidden;
    margin-bottom:25px;
    box-shadow:0 6px 22px rgba(0,0,0,.08);
    transition:.25s ease;
}

.news-card:hover{
    transform:translateY(-3px);
    box-shadow:0 10px 28px rgba(0,0,0,.12);
}

.news-link{
    display:block;
    color:inherit;
    text-decoration:none;
}

.news-image{
    width:100%;
    height:320px;
    object-fit:cover;
    display:block;
}

.news-content{
    padding:25px 28px 28px;
}

.news-date{
    color:#777;
    font-size:13px;
    margin-bottom:10px;
}

.news-title{
    color:#17324d;
    margin:0 0 12px;
    font-size:25px;
    line-height:1.3;
}

.news-card:hover .news-title{
    color:#b88925;
}

.news-excerpt{
    color:#555;
    line-height:1.7;
    margin:0;
}

.read-more{
    display:inline-block;
    margin-top:16px;
    color:#17324d;
    font-size:14px;
    font-weight:bold;
}

@media(max-width:600px){

    .news-image{
        height:220px;
    }

    .news-content{
        padding:20px;
    }

    .news-title{
        font-size:21px;
    }
}
</style>

<div class="news-list">


<h1>Berita dan Kegiatan</h1>

<?php if(!$rows): ?>

    <p>Belum ada berita yang diterbitkan.</p>

<?php endif; ?>

<?php foreach($rows as $r): ?>

    <article class="news-card">

        <a
            href="detail-berita.php?id=<?= (int)$r['id'] ?>"
            class="news-link"
        >

            <?php if(!empty($r['image'])): ?>

                <img
                    src="<?= e($r['image']) ?>"
                    alt="<?= e($r['title']) ?>"
                    class="news-image"
                >

            <?php endif; ?>

            <div class="news-content">

                <div class="news-date">
                    <?= date(
                        'd-m-Y H:i',
                        strtotime($r['created_at'])
                    ) ?>
                </div>

                <h2 class="news-title">
                    <?= e($r['title']) ?>
                </h2>

                <p class="news-excerpt">
                    <?= nl2br(
                        e(
                            mb_strimwidth(
                                $r['body'],
                                0,
                                220,
                                '...'
                            )
                        )
                    ) ?>
                </p>

                <span class="read-more">
                    Baca Selengkapnya →
                </span>

            </div>

        </a>

    </article>

<?php endforeach; ?>


</div>

<?php require 'footer.php'; ?>
