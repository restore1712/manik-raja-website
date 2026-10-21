<?php

require 'config.php';

$pageTitle = 'Beranda';

/* =========================================================
   GALERI FOTO BERANDA - UNTUK SLIDESHOW SAJA
   ========================================================= */

$stmtGallery = $pdo->query("
    SELECT title, image_path
    FROM gallery
    WHERE type = 'foto'
      AND category = 'beranda'
      AND image_path IS NOT NULL
      AND image_path <> ''
    ORDER BY created_at DESC
");

$homeGallery = $stmtGallery->fetchAll(PDO::FETCH_ASSOC);


/* =========================================================
   BERITA TERKINI
   ========================================================= */

$stmtNews = $pdo->query("
    SELECT id, title, body, content, image, created_at
    FROM posts
    WHERE category = 'berita'
    ORDER BY created_at DESC
    LIMIT 3
");

$latestNews = $stmtNews->fetchAll(PDO::FETCH_ASSOC);


/* Jumlah anggota aktif untuk ringkasan di beranda. */
$memberCount = (int)$pdo->query("
    SELECT COUNT(*)
    FROM members
    WHERE status = 'active'
      AND deleted_at IS NULL
      AND panggoaran IS NOT NULL
      AND TRIM(panggoaran) <> ''
")->fetchColumn();


/* =========================================================
   HEADER
   ========================================================= */

require 'inc/header.php';

?>

<style>

/* =========================================================
   UMUM
   ========================================================= */

.hero,
.home-slideshow,
.section,
.cta-box {
    animation: fadeUp .55s ease both;
}

@keyframes fadeUp {
    from {
        opacity: 0;
        transform: translateY(12px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.section {
    margin-bottom: 65px;
}

.section-title {
    text-align: center;
    margin-bottom: 30px;
}

.section-title h2 {
    position: relative;
    display: inline-block;
    color: #17324d;
    margin: 0 0 12px;
    font-size: 30px;
    line-height: 1.3;
}

.section-title h2::after {
    content: "";
    display: block;
    width: 55px;
    height: 3px;
    background: #d8a84e;
    margin: 12px auto 0;
    border-radius: 10px;
}

.section-title p {
    color: #777;
    margin: 0;
    line-height: 1.7;
}


/* =========================================================
   HERO
   ========================================================= */

.hero {
    position: relative;
    overflow: hidden;
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    align-items: center;
    width: auto;
    min-height: 430px;
    box-sizing: border-box;
    background: linear-gradient(135deg, #17324d, #244b6b);
    color: white;
    padding: 55px 30px;
    border-radius: 22px;
    text-align: center;
    margin-bottom: 45px;
    margin-left: auto;
    margin-right: auto;

    box-shadow:
        0 12px 35px rgba(23,50,77,.18);
}

.hero-background {
    position: absolute;
    inset: 0;
    z-index: 0;
    overflow: hidden;
}

.hero-background::before {
    content: "";
    position: absolute;
    inset: -18px;
    background-image: var(--hero-image);
    background-size: cover;
    background-position: center;
    filter: blur(12px) brightness(.72);
    transform: scale(1.04);
}

.hero-background img {
    width: 100%;
    height: 100%;
    max-width: none;
    object-fit: contain;
    display: block;
    margin: 0;
    position: relative;
    z-index: 0;
    filter: brightness(.95);
}

.hero::after {
    content: "";
    position: absolute;
    inset: 0;
    z-index: 1;
    background: linear-gradient(90deg, rgba(15,37,56,.24), rgba(15,37,56,.38));
}

.hero::before {
    content: "";
    position: absolute;
    width: 230px;
    height: 230px;
    border-radius: 50%;
    background: rgba(255,255,255,.035);
    left: -100px;
    bottom: -100px;
    z-index: 1;
}

.hero-content {
    position: relative;
    z-index: 2;
    width: min(100%, 900px);
    margin: 0 auto;
    top: 80px;
}

.hero h1 {
    position: relative;
    z-index: 1;
    margin: 0 0 15px;
    font-size: 40px;
    line-height: 1.2;
}

.hero p {
    position: relative;
    z-index: 1;
    max-width: 760px;
    margin: 0 auto;
    line-height: 1.8;
    font-size: 16px;
    color: rgba(255,255,255,.9);
}


/* =========================================================
   TOMBOL
   ========================================================= */

.btn {
    display: inline-block;
    padding: 11px 19px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: bold;
    margin: 6px 4px;

    transition:
        transform .2s ease,
        box-shadow .2s ease,
        background .2s ease;
}

.btn:hover {
    transform: translateY(-2px);
}

.btn-primary {
    background: #d8a84e;
    color: #17324d;

    box-shadow:
        0 5px 14px rgba(216,168,78,.22);
}

.btn-primary:hover {
    background: #c89435;

    box-shadow:
        0 7px 18px rgba(216,168,78,.30);
}

.btn-secondary {
    background: white;
    color: #17324d;
    border: 1px solid #d8a84e;
}

.btn-secondary:hover {
    background: #f7f4ed;
}


/* =========================================================
   SLIDESHOW BERANDA
   ========================================================= */

.home-slideshow {
    margin-bottom: 60px;
}

.slideshow-container {
    position: relative;
    width: 100%;
    height: 430px;
    overflow: hidden;
    border-radius: 20px;
    background: #17324d;

    box-shadow:
        0 10px 30px rgba(0,0,0,.14);
}

.slide {
    display: none;
    position: relative;
    width: 100%;
    height: 100%;
}

.slide.active {
    display: block;
}

.slide img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.slide::after {
    content: "";
    position: absolute;
    left: 0;
    right: 0;
    bottom: 0;
    height: 50%;

    background:
        linear-gradient(
            transparent,
            rgba(0,0,0,.72)
        );
}

.slide-caption {
    position: absolute;
    left: 30px;
    right: 30px;
    bottom: 25px;
    z-index: 2;
    color: white;
}

.slide-caption h2 {
    margin: 0;
    font-size: 25px;
    line-height: 1.35;

    text-shadow:
        0 2px 6px rgba(0,0,0,.6);
}

.slide-prev,
.slide-next {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    z-index: 5;

    width: 45px;
    height: 45px;

    border: none;
    border-radius: 50%;

    background: rgba(0,0,0,.42);
    color: white;

    font-size: 32px;
    line-height: 40px;

    cursor: pointer;

    transition:
        background .2s ease,
        transform .2s ease;
}

.slide-prev {
    left: 15px;
}

.slide-next {
    right: 15px;
}

.slide-prev:hover,
.slide-next:hover {
    background: rgba(0,0,0,.72);

    transform:
        translateY(-50%)
        scale(1.05);
}

.slide-dots {
    text-align: center;
    margin-top: 16px;
}

.slide-dot {
    width: 10px;
    height: 10px;
    padding: 0;
    margin: 0 4px;

    border: none;
    border-radius: 50%;

    background: #ccc;
    cursor: pointer;

    transition:
        transform .2s ease,
        background .2s ease;
}

.slide-dot.active {
    background: #d8a84e;
    transform: scale(1.25);
}


/* =========================================================
   BERITA
   ========================================================= */

.news-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(min(100%, 300px), 1fr));
    max-width: 1000px;
    margin: 0 auto;
    gap: 26px;
    align-items: stretch;
}

.news-card {
    display: flex;
    flex-direction: column;
    background: white;
    border-radius: 18px;
    overflow: hidden;

    box-shadow:
        0 8px 24px rgba(23,50,77,.09);

    border: 1px solid rgba(23,50,77,.07);

    transition:
        transform .25s ease,
        box-shadow .25s ease;
}

.news-card:hover {
    transform: translateY(-5px);

    box-shadow:
        0 12px 28px rgba(0,0,0,.13);
}

.news-card img {
    width: 100%;
    height: 220px;
    object-fit: cover;
    display: block;
    background: #e9e4d9;
}

.news-no-image {
    height: 220px;

    background:
        linear-gradient(
            135deg,
            #e9e4d9,
            #f5f1e8
        );

    display: flex;
    align-items: center;
    justify-content: center;

    color: #687684;
    font-size: 14px;
    letter-spacing: .2px;
}

.news-content {
    display: flex;
    flex: 1;
    flex-direction: column;
    padding: 23px 24px 25px;
}

.news-content h3 {
    margin: 0 0 8px;
    color: #17324d;
    font-size: 21px;
    line-height: 1.4;
}

.news-date {
    font-size: 13px;
    color: #8a7350;
    margin-bottom: 13px;
}

.news-excerpt {
    color: #555;
    line-height: 1.7;
    font-size: 14px;
}

.news-link {
    display: inline-block;
    align-self: flex-start;
    margin-top: auto;
    padding-top: 16px;

    color: #17324d;
    font-weight: bold;
    text-decoration: none;

    border-bottom: 2px solid #d8a84e;
    padding-bottom: 3px;
}

.news-link:hover {
    color: #d8a84e;
}


/* =========================================================
   TENTANG
   ========================================================= */

.about-box {
    background: white;
    padding: 34px 38px;
    border-radius: 18px;

    box-shadow:
        0 10px 28px rgba(23,50,77,.07);

    line-height: 1.8;

    border: 1px solid rgba(23,50,77,.055);
}

.about-box p {
    margin-top: 0;
}

.about-box p:last-child {
    margin-bottom: 0;
}


/* =========================================================
   MENU CEPAT
   ========================================================= */

.quick-menu {
    display: grid;
    grid-template-columns:
        repeat(4, minmax(0, 1fr));

    gap: 20px;
}

.quick-card {
    display: block;
    background: white;

    padding: 30px 20px;
    min-height: 205px;

    border-radius: 18px;
    text-align: center;

    text-decoration: none;
    color: #17324d;

    border: 1px solid rgba(23,50,77,.055);

    box-shadow:
        0 5px 18px rgba(0,0,0,.07);

    transition:
        transform .25s ease,
        box-shadow .25s ease,
        border-color .25s ease;
}

/* Penyempurnaan bagian informasi di beranda */
.section-title h2 {
    letter-spacing: -.35px;
}

.home-about .about-box {
    max-width: 980px;
    margin: 0 auto;
    padding: 26px 30px;
    border-left: 4px solid #d8a84e;
    background: #fffaf0;
}

.home-about {
    padding: 30px 24px 34px;
    border-radius: 24px;
    background: #f0eadd;
}

.home-news {
    padding: 30px 24px 34px;
    border-radius: 24px;
    background: #eaf0f3;
}

.home-about .about-box p {
    max-width: 900px;
    margin: 0;
    color: #465463;
    line-height: 1.85;
}

.home-about .about-box p + p {
    margin-top: 12px;
}

.home-members .member-summary {
    max-width: 980px;
    margin: 0 auto;
    padding: 28px 24px;
    background: linear-gradient(135deg, #fff 0%, #fffdf8 100%);
}

.home-members {
    padding: 30px 24px 34px;
    border-radius: 24px;
    background: #eaf0f3;
}

.member-summary .member-count {
    display: inline-flex;
    align-items: baseline;
    gap: 7px;
    margin: 0 0 12px;
    color: #526171;
}

.member-summary .member-count strong {
    color: #17324d;
    font-size: 28px;
    line-height: 1;
}

.member-summary > p:not(.member-count) {
    margin: 0 0 16px;
    color: #64707b;
}

.member-summary .btn {
    margin: 0;
    padding: 12px 21px;
    border-radius: 10px;
}

.home-links .quick-card {
    min-height: 200px;
    padding: 26px 20px;
}

.home-links {
    padding: 30px 24px 34px;
    border-radius: 24px;
    background: #eaf0f3;
}

.home-info {
    padding: 30px 16px 34px;
    border-radius: 24px;
    background: #eaf0f3;
}

.home-links .quick-icon {
    width: 60px;
    height: 60px;
    margin-bottom: 14px;
    color: #17324d;
    box-shadow: inset 0 0 0 1px rgba(216,168,78,.12);
}

.home-links .quick-card h3 {
    margin-bottom: 8px;
}

.home-links .quick-card p {
    max-width: 220px;
    margin: 0 auto;
}

.quick-card:hover {
    transform: translateY(-6px);

    box-shadow:
        0 12px 28px rgba(0,0,0,.13);

    border-color: #d8a84e;
}

.quick-icon {
    width: 64px;
    height: 64px;

    display: flex;
    align-items: center;
    justify-content: center;

    margin: 0 auto 15px;

    border-radius: 50%;
    background: #f7f4ed;

    font-size: 32px;
}

.quick-card h3 {
    margin: 0 0 9px;
    font-size: 19px;
}

.quick-card p {
    margin: 0;
    color: #777;
    font-size: 14px;
    line-height: 1.6;
}


/* =========================================================
   FILTER ANGGOTA
   ========================================================= */

.member-filter {
    background: white;
    padding: 24px;
    border-radius: 16px;

    box-shadow:
        0 5px 18px rgba(0,0,0,.07);

    margin-bottom: 25px;

    border: 1px solid rgba(0,0,0,.04);
}

.filter-row {
    display: flex;
    gap: 14px;
    flex-wrap: wrap;
    align-items: end;
}

.filter-group {
    flex: 1;
    min-width: 220px;
}

.filter-group label {
    display: block;
    font-weight: bold;
    color: #17324d;

    margin-bottom: 8px;
    font-size: 14px;
}

.filter-group select {
    width: 100%;
    padding: 12px;

    border: 1px solid #d5d5d5;
    border-radius: 8px;

    background: white;
    color: #333;

    outline: none;

    transition:
        border-color .2s ease,
        box-shadow .2s ease;
}

.filter-group select:focus {
    border-color: #d8a84e;

    box-shadow:
        0 0 0 3px rgba(216,168,78,.15);
}


/* =========================================================
   TABEL ANGGOTA
   ========================================================= */

.member-table-wrap {
    overflow-x: auto;
    background: white;
    border-radius: 16px;

    box-shadow:
        0 5px 18px rgba(0,0,0,.07);

    border: 1px solid rgba(0,0,0,.04);
}

.member-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 800px;
}

.member-table th {
    background: #17324d;
    color: white;

    padding: 14px 13px;
    text-align: left;

    font-size: 14px;
}

.member-table td {
    padding: 14px 13px;
    border-bottom: 1px solid #eee;

    vertical-align: middle;
    font-size: 14px;
}

.member-table tbody tr:last-child td {
    border-bottom: none;
}

.member-table tbody tr:hover td {
    background: #faf8f2;
}

.member-name-link {
    color: #17324d;
    text-decoration: none;
    font-weight: bold;
}

.member-name-link:hover {
    color: #d8a84e;
    text-decoration: underline;
}

.family-deceased {
    color: #721c24;
    font-weight: bold;
}


/* =========================================================
   CTA
   ========================================================= */

.cta-box {
    position: relative;
    overflow: hidden;

    background:
        radial-gradient(
            circle at top right,
            rgba(216,168,78,.18),
            transparent 35%
        ),
        #17324d;

    color: white;

    padding: 45px 25px;
    border-radius: 20px;

    text-align: center;

    box-shadow:
        0 10px 30px rgba(23,50,77,.16);
}

.cta-box h2 {
    margin-top: 0;
    font-size: 28px;
}

.cta-box p {
    line-height: 1.7;
    max-width: 700px;

    margin: 0 auto 18px;

    color: rgba(255,255,255,.88);
}


/* =========================================================
   RESPONSIVE TABLET / HP
   ========================================================= */

@media (max-width: 800px) {

    .hero {
        display: block;
        padding: 40px 20px;
        border-radius: 17px;
    }

    .hero {
        min-height: 400px;
    }

    .hero h1 {
        font-size: 30px;
    }

    .hero p {
        font-size: 15px;
    }

    .section {
        margin-bottom: 50px;
    }

    .section-title {
        margin-bottom: 24px;
    }

    .section-title h2 {
        font-size: 26px;
    }

    .slideshow-container {
        height: 280px;
        border-radius: 14px;
    }

    .slide-caption {
        left: 18px;
        right: 18px;
        bottom: 18px;
    }

    .slide-caption h2 {
        font-size: 19px;
    }

    .slide-prev,
    .slide-next {
        width: 38px;
        height: 38px;
        font-size: 27px;
        line-height: 33px;
    }

    .news-grid {
        grid-template-columns: 1fr;
    }

    .quick-menu {
        grid-template-columns:
            repeat(2, minmax(0, 1fr));

        gap: 14px;
    }

    .quick-card {
        min-height: 185px;
        padding: 25px 15px;
    }

    .filter-row {
        flex-direction: column;
        align-items: stretch;
    }

    .filter-group {
        width: 100%;
        min-width: 100%;
    }

    .filter-row > div:last-child {
        width: 100%;
    }

    .filter-row .btn {
        margin-left: 0;
        margin-right: 6px;
    }

    .about-box {
        padding: 24px;
    }

    .cta-box {
        padding: 35px 20px;
        border-radius: 16px;
    }
}


/* =========================================================
   HP KECIL
   ========================================================= */

@media (max-width: 500px) {

    .hero {
        padding: 32px 16px;
    }

    .hero {
        min-height: 380px;
    }

    .hero h1 {
        font-size: 26px;
        line-height: 1.3;
    }

    .hero p {
        font-size: 14px;
        line-height: 1.7;
    }

    .hero .btn {
        display: block;
        width: 100%;
        margin: 8px 0;
    }

    .slideshow-container {
        height: 230px;
        border-radius: 12px;
    }

    .slide-caption h2 {
        font-size: 16px;
    }

    .slide-prev {
        left: 9px;
    }

    .slide-next {
        right: 9px;
    }

    .news-card img,
    .news-no-image {
        height: 190px;
    }

    .news-content {
        padding: 18px;
    }

    .quick-menu {
        grid-template-columns: 1fr;
    }

    .quick-card {
        min-height: auto;
        padding: 25px 20px;
    }

    .member-filter {
        padding: 17px;
    }

    .member-table th,
    .member-table td {
        padding: 12px 10px;
    }

    .cta-box h2 {
        font-size: 23px;
    }

    .cta-box .btn {
        display: block;
        width: 100%;
        margin: 8px 0;
    }
}

</style>


<!-- =========================================================
     HERO
     ========================================================= -->

<section class="hero">

    <?php if (file_exists(__DIR__ . '/' . SITE_LOGO_PATH)): ?>
        <div class="hero-background" aria-hidden="true" style="--hero-image: url('<?= e(site_logo_url()) ?>')">
            <img src="<?= e(site_logo_url()) ?>" alt="">
        </div>

    <?php endif; ?>

    <div class="hero-content">
    <h1>
        Horas, Pomparan Manik Raja, Boru, Bere, Ibebere !
    </h1>

    <p>
        Selamat datang di ruang informasi dan kebersamaan
        keluarga besar Pomparan Manik Raja.
    </p>

    <div style="margin-top:25px;">

        <a
            href="daftar.php"
            class="btn btn-primary"
        >
            Daftar Anggota
        </a>

        <a
            href="sejarah.php"
            class="btn btn-secondary"
        >
            Kenali Sejarah
        </a>

    </div>
    </div>

</section>


<!-- =========================================================
     SLIDESHOW FOTO BERANDA
     ========================================================= -->

<?php if (!empty($homeGallery)): ?>

<section class="home-slideshow">

    <div class="slideshow-container">

        <?php foreach ($homeGallery as $index => $photo): ?>

            <?php

            $slideImage = trim($photo['image_path']);

            if (
                strpos($slideImage, 'http://') !== 0 &&
                strpos($slideImage, 'https://') !== 0 &&
                strpos($slideImage, '/') !== 0 &&
                strpos($slideImage, 'uploads/') !== 0
            ) {
                $slideImage = 'uploads/' . $slideImage;
            }

            ?>

            <div
                class="slide <?= $index === 0 ? 'active' : '' ?>"
            >

                <img
                    src="<?= htmlspecialchars($slideImage) ?>"
                    alt="<?= htmlspecialchars($photo['title']) ?>"
                >

                <div class="slide-caption">

                    <h2>
                        <?= htmlspecialchars($photo['title']) ?>
                    </h2>

                </div>

            </div>

        <?php endforeach; ?>


        <?php if (count($homeGallery) > 1): ?>

            <button
                type="button"
                class="slide-prev"
                onclick="changeSlide(-1)"
                aria-label="Foto sebelumnya"
            >
                ‹
            </button>

            <button
                type="button"
                class="slide-next"
                onclick="changeSlide(1)"
                aria-label="Foto berikutnya"
            >
                ›
            </button>

        <?php endif; ?>

    </div>


    <?php if (count($homeGallery) > 1): ?>

        <div class="slide-dots">

            <?php foreach ($homeGallery as $index => $photo): ?>

                <button
                    type="button"
                    class="slide-dot <?= $index === 0 ? 'active' : '' ?>"
                    onclick="showSlide(<?= $index ?>)"
                    aria-label="Foto <?= $index + 1 ?>"
                ></button>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</section>

<?php endif; ?>


<!-- =========================================================
     BERITA TERKINI
     ========================================================= -->

<section class="section home-news">

    <div class="section-title">

        <h2>
            Berita Terkini
        </h2>

        <p>
            Informasi dan kabar terbaru Pomparan Manik Raja
        </p>

    </div>


    <?php if (!empty($latestNews)): ?>

        <div class="news-grid">

            <?php foreach ($latestNews as $news): ?>

                <?php

                $newsImage = '';

                if (!empty($news['image'])) {

                    $newsImage = trim($news['image']);

                    if (
                        strpos($newsImage, 'http://') !== 0 &&
                        strpos($newsImage, 'https://') !== 0 &&
                        strpos($newsImage, '/') !== 0 &&
                        strpos($newsImage, 'uploads/') !== 0
                    ) {
                        $newsImage = 'uploads/' . $newsImage;
                    }
                }


                $newsText = '';

                if (!empty($news['body'])) {

                    $newsText = $news['body'];

                } elseif (!empty($news['content'])) {

                    $newsText = $news['content'];

                }

                $newsText = strip_tags($newsText);

                if (mb_strlen($newsText) > 140) {

                    $newsText =
                        mb_substr($newsText, 0, 140) . '...';

                }

                ?>

                <article class="news-card">

                    <?php if ($newsImage !== ''): ?>

                        <img
                            src="<?= htmlspecialchars($newsImage) ?>"
                            alt="<?= htmlspecialchars($news['title']) ?>"
                            onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                        >

                        <div
                            class="news-no-image"
                            style="display:none;"
                        >
                            Foto berita tidak ditemukan
                        </div>

                    <?php else: ?>

                        <div class="news-no-image">
                            Tidak ada foto
                        </div>

                    <?php endif; ?>


                    <div class="news-content">

                        <h3>
                            <?= htmlspecialchars($news['title']) ?>
                        </h3>

                        <div class="news-date">

                            <?= date(
                                'd M Y',
                                strtotime($news['created_at'])
                            ) ?>

                        </div>


                        <?php if ($newsText !== ''): ?>

                            <div class="news-excerpt">

                                <?= htmlspecialchars($newsText) ?>

                            </div>

                        <?php endif; ?>


                        <a
                            href="berita.php?id=<?= (int) $news['id'] ?>"
                            class="news-link"
                        >
                            Baca Selengkapnya →
                        </a>

                    </div>

                </article>

            <?php endforeach; ?>

        </div>

    <?php else: ?>

        <div
            class="about-box"
            style="text-align:center;"
        >
            Belum ada berita terbaru.
        </div>

    <?php endif; ?>

</section>


<!-- =========================================================
     TENTANG
     ========================================================= -->

<section class="section home-members">

    <div class="section-title">

        <h2>
            Tentang Pomparan Manik Raja
        </h2>

    </div>


    <div class="about-box">

        <p>
            Website ini menjadi ruang informasi dan kebersamaan
            bagi keluarga besar Pomparan Manik Raja.
        </p>

        <p>
            Melalui website ini, anggota keluarga dapat mengetahui
            sejarah, pengurus, berita, galeri kegiatan, serta
            informasi keluarga Pomparan Manik Raja.
        </p>

    </div>

</section>


<!-- =========================================================
     DATA ANGGOTA
     ========================================================= -->

<section class="section home-links">
    <div class="section-title">
        <h2>Data Anggota</h2>
        <p>Direktori publik menampilkan nama dan wilayah anggota aktif.</p>
    </div>

    <div class="about-box member-summary" style="text-align:center;">
        <p class="member-count">
            <strong><?= number_format($memberCount, 0, ',', '.') ?></strong>
            <span>anggota aktif</span>
        </p>
        <p>Informasi keluarga yang lebih lengkap hanya tersedia untuk pengurus.</p>
        <a href="anggota.php" class="btn btn-primary">Lihat Daftar Anggota</a>
    </div>
</section>

<!-- =========================================================
     MENU CEPAT
     ========================================================= -->

<section class="section home-info">

    <div class="section-title">

        <h2>
            Informasi Pomparan Manik Raja
        </h2>

        <p>
            Pilih informasi yang ingin Anda lihat
        </p>

    </div>


    <div class="quick-menu">

        <a
            href="sejarah.php"
            class="quick-card"
        >

            <div class="quick-icon">
                📖
            </div>

            <h3>
                Sejarah
            </h3>

            <p>
                Kenali sejarah dan asal-usul
                Pomparan Manik Raja.
            </p>

        </a>


        <a
            href="berita.php"
            class="quick-card"
        >

            <div class="quick-icon">
                📰
            </div>

            <h3>
                Berita
            </h3>

            <p>
                Informasi dan kabar terbaru
                Pomparan Manik Raja.
            </p>

        </a>


        <a
            href="galeri.php"
            class="quick-card"
        >

            <div class="quick-icon">
                🖼️
            </div>

            <h3>
                Galeri
            </h3>

            <p>
                Lihat dokumentasi foto dan
                video kegiatan keluarga.
            </p>

        </a>


        <a
            href="daftar.php"
            class="quick-card"
        >

            <div class="quick-icon">
                📝
            </div>

            <h3>
                Pendaftaran
            </h3>

            <p>
                Daftarkan diri sebagai bagian
                dari Pomparan Manik Raja.
            </p>

        </a>

    </div>

</section>


<!-- =========================================================
     CTA
     ========================================================= -->

<section class="section">

    <div class="cta-box">

        <h2>
            Mari Pererat Kebersamaan
        </h2>

        <p>
            Mari bersama-sama menjaga hubungan kekeluargaan
            dan melestarikan sejarah Pomparan Manik Raja.
        </p>

        <a
            href="daftar.php"
            class="btn btn-primary"
        >
            Daftar Sebagai Anggota
        </a>

    </div>

</section>


<!-- =========================================================
     JAVASCRIPT SLIDESHOW + FILTER
     ========================================================= -->

<script>

let currentSlide = 0;
let slideTimer;


/* Tampilkan slide */

function showSlide(index) {

    const slides =
        document.querySelectorAll('.slide');

    const dots =
        document.querySelectorAll('.slide-dot');

    if (slides.length === 0) {
        return;
    }


    if (index >= slides.length) {

        currentSlide = 0;

    } else if (index < 0) {

        currentSlide =
            slides.length - 1;

    } else {

        currentSlide = index;

    }


    slides.forEach(function(slide) {

        slide.classList.remove('active');

    });


    dots.forEach(function(dot) {

        dot.classList.remove('active');

    });


    slides[currentSlide]
        .classList
        .add('active');


    if (dots[currentSlide]) {

        dots[currentSlide]
            .classList
            .add('active');

    }

}


/* Tombol kiri / kanan */

function changeSlide(direction) {

    showSlide(
        currentSlide + direction
    );

    restartSlideTimer();

}


/* Slideshow otomatis */

function startSlideTimer() {

    const slides =
        document.querySelectorAll('.slide');

    if (slides.length <= 1) {
        return;
    }


    slideTimer = setInterval(
        function() {

            showSlide(
                currentSlide + 1
            );

        },
        5000
    );

}


/* Restart timer */

function restartSlideTimer() {

    clearInterval(slideTimer);

    startSlideTimer();

}


/* Filter Kota / Kabupaten */

document.addEventListener(
    'DOMContentLoaded',
    function() {

        /* Slideshow */

        showSlide(0);

        startSlideTimer();


        /* Filter wilayah */

        const wilayahSelect =
            document.getElementById('wilayah');

        const daerahSelect =
            document.getElementById('daerah');


        if (
            !wilayahSelect ||
            !daerahSelect
        ) {
            return;
        }


        function filterDaerah() {

            const wilayahId =
                wilayahSelect.value;

            const options =
                daerahSelect.querySelectorAll(
                    'option'
                );


            options.forEach(
                function(option, index) {

                    if (index === 0) {

                        option.style.display = '';

                        return;

                    }


                    const optionWilayah =
                        option.getAttribute(
                            'data-wilayah'
                        );


                    if (
                        wilayahId === '' ||
                        optionWilayah === wilayahId
                    ) {

                        option.style.display = '';

                    } else {

                        option.style.display = 'none';


                        if (option.selected) {

                            option.selected = false;

                            daerahSelect.value = '';

                        }

                    }

                }
            );

        }


        wilayahSelect.addEventListener(
            'change',
            filterDaerah
        );


        filterDaerah();

    }
);

</script>


<?php

require 'inc/footer.php';

?>
