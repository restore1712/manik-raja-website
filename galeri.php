<?php
require 'config.php';

$pageTitle = 'Galeri';

/*
|--------------------------------------------------------------------------
| PILIH MENU
|--------------------------------------------------------------------------
| mode kosong       = halaman utama Galeri
| mode foto         = pilihan Foto Beranda / Foto Album
| mode beranda      = foto yang tampil di Beranda
| mode album        = daftar album
| mode album_view   = isi salah satu album
| mode video        = daftar video
*/

$mode = $_GET['mode'] ?? '';

/*
|--------------------------------------------------------------------------
| FOTO BERANDA
|--------------------------------------------------------------------------
*/
$fotoBeranda = [];

if ($mode === 'beranda') {

    $stmt = $pdo->query("
        SELECT id, title, image_path, description, created_at
        FROM gallery
        WHERE type = 'foto'
          AND category = 'beranda'
        ORDER BY created_at DESC
    ");

    $fotoBeranda = $stmt->fetchAll();
}


/*
|--------------------------------------------------------------------------
| DAFTAR ALBUM
|--------------------------------------------------------------------------
*/
$albums = [];

if ($mode === 'album') {

    $stmt = $pdo->query("
        SELECT
            album_name,
            COUNT(*) AS jumlah_foto,
            MAX(image_path) AS cover_image,
            MAX(created_at) AS latest_date
        FROM gallery
        WHERE type = 'foto'
          AND category = 'album'
          AND album_name IS NOT NULL
          AND album_name <> ''
        GROUP BY album_name
        ORDER BY latest_date DESC
    ");

    $albums = $stmt->fetchAll();
}


/*
|--------------------------------------------------------------------------
| FOTO DALAM ALBUM
|--------------------------------------------------------------------------
*/
$albumName = $_GET['album'] ?? '';
$albumPhotos = [];

if ($mode === 'album_view' && $albumName !== '') {

    $stmt = $pdo->prepare("
        SELECT id, title, image_path, description, created_at
        FROM gallery
        WHERE type = 'foto'
          AND category = 'album'
          AND album_name = ?
        ORDER BY created_at DESC
    ");

    $stmt->execute([$albumName]);

    $albumPhotos = $stmt->fetchAll();
}


/*
|--------------------------------------------------------------------------
| VIDEO
|--------------------------------------------------------------------------
*/
$videos = [];

if ($mode === 'video') {

    $stmt = $pdo->query("
        SELECT id, title, video_url, description, created_at
        FROM gallery
        WHERE type = 'video'
        ORDER BY created_at DESC
    ");

    $videos = $stmt->fetchAll();
}


require 'inc/header.php';
?>


<!-- =========================================================
     JUDUL GALERI
========================================================= -->

<section class="gallery-header">

    <div class="section-heading">

        <span>
            DOKUMENTASI
        </span>

        <h1>
            Galeri
        </h1>

        <p>
            Dokumentasi kegiatan dan kebersamaan
            Pomparan Manik Raja.
        </p>

    </div>

</section>



<!-- =========================================================
     MENU UTAMA GALERI
========================================================= -->

<?php if ($mode === ''): ?>
<div class="gallery-menu">

    <a
        href="galeri.php?mode=foto"
        class="gallery-menu-card"
    >

        <div class="gallery-menu-icon">
            📷
        </div>

        <h3>
            Foto
        </h3>

        <p>
            Lihat dokumentasi foto kegiatan
            Pomparan Manik Raja.
        </p>

    </a>


    <a
        href="galeri.php?mode=video"
        class="gallery-menu-card"
    >

        <div class="gallery-menu-icon">
            🎥
        </div>

        <h3>
            Video
        </h3>

        <p>
            Lihat kumpulan video kegiatan
            Pomparan Manik Raja.
        </p>

    </a>

</div>
<?php endif; ?>



<!-- =========================================================
     PILIHAN FOTO
========================================================= -->

<?php if ($mode === 'foto'): ?>

<section class="gallery-section">

    <div class="gallery-top">

      <div class="section-heading">

        <span>
            FOTO
        </span>

        <h2>
            Pilih Kategori Foto
        </h2>

        <p class="gallery-heading-note">Pilih koleksi foto yang ingin Anda lihat.</p>

      </div>

      <a href="galeri.php" class="back-button">Kembali ke Galeri</a>

    </div>


    <div class="gallery-category-grid">

        <a
            href="galeri.php?mode=beranda"
            class="category-card"
        >

            <div class="category-icon">
                🏠
            </div>

            <h3>
                Foto Beranda
            </h3>

            <p>
                Foto yang ditampilkan pada
                halaman Beranda.
            </p>

        </a>


        <a
            href="galeri.php?mode=album"
            class="category-card"
        >

            <div class="category-icon">
                🖼️
            </div>

            <h3>
                Foto Album
            </h3>

            <p>
                Kumpulan foto berdasarkan
                album kegiatan.
            </p>

        </a>

    </div>

</section>

<?php endif; ?>



<!-- =========================================================
     FOTO BERANDA
========================================================= -->

<?php if ($mode === 'beranda'): ?>

<section class="gallery-section">

    <div class="gallery-top">

        <div>

            <div class="section-heading">

                <span>
                    DOKUMENTASI
                </span>

                <h2>
                    Foto Beranda
                </h2>

            </div>

        </div>


        <a
            href="galeri.php?mode=foto"
            class="back-button"
        >
            ← Kembali
        </a>

    </div>


    <?php if (!$fotoBeranda): ?>

        <div class="empty-card">

            <div class="empty-icon">
                📷
            </div>

            <h3>
                Belum Ada Foto
            </h3>

            <p>
                Belum ada foto yang ditambahkan
                sebagai Foto Beranda.
            </p>

        </div>

    <?php else: ?>


        <div class="photo-grid">

            <?php foreach ($fotoBeranda as $photo): ?>

                <div class="photo-card">

                    <div
                        class="photo-image"
                        onclick="bukaLightbox(
                            '<?= e($photo['image_path']) ?>',
                            '<?= e($photo['title']) ?>'
                        )"
                    >

                        <img
                            src="<?= e($photo['image_path']) ?>"
                            alt="<?= e($photo['title']) ?>"
                            loading="lazy"
                        >

                        <div class="photo-overlay">
                            <span>
                                🔍 Lihat Foto
                            </span>
                        </div>

                    </div>


                    <div class="photo-info">

                        <h3>
                            <?= e($photo['title']) ?>
                        </h3>

                        <?php if (!empty($photo['description'])): ?>

                            <p>
                                <?= nl2br(
                                    e($photo['description'])
                                ) ?>
                            </p>

                        <?php endif; ?>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</section>

<?php endif; ?>



<!-- =========================================================
     DAFTAR ALBUM
========================================================= -->

<?php if ($mode === 'album'): ?>

<section class="gallery-section">

    <div class="gallery-top">

        <div>

            <div class="section-heading">

                <span>
                    FOTO
                </span>

                <h2>
                    Album Foto
                </h2>

            </div>

        </div>


        <a
            href="galeri.php?mode=foto"
            class="back-button"
        >
            ← Kembali
        </a>

    </div>


    <?php if (!$albums): ?>

        <div class="empty-card">

            <div class="empty-icon">
                🖼️
            </div>

            <h3>
                Belum Ada Album
            </h3>

            <p>
                Belum ada album foto yang dibuat.
            </p>

        </div>

    <?php else: ?>


        <div class="album-grid">

            <?php foreach ($albums as $album): ?>

                <a
                    href="galeri.php?mode=album_view&album=<?= urlencode($album['album_name']) ?>"
                    class="album-card"
                >

                    <div class="album-cover">

                        <?php if (!empty($album['cover_image'])): ?>

                            <img
                                src="<?= e($album['cover_image']) ?>"
                                alt="<?= e($album['album_name']) ?>"
                                loading="lazy"
                            >

                        <?php else: ?>

                            <div class="album-no-image">
                                🖼️
                            </div>

                        <?php endif; ?>

                        <div class="album-count">

                            <?= (int)$album['jumlah_foto'] ?>
                            Foto

                        </div>

                    </div>


                    <div class="album-info">

                        <h3>
                            <?= e($album['album_name']) ?>
                        </h3>

                        <p>
                            Lihat album →
                        </p>

                    </div>

                </a>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</section>

<?php endif; ?>



<!-- =========================================================
     ISI ALBUM
========================================================= -->

<?php if ($mode === 'album_view'): ?>

<section class="gallery-section">

    <div class="gallery-top">

        <div>

            <div class="section-heading">

                <span>
                    ALBUM FOTO
                </span>

                <h2>
                    <?= e($albumName) ?>
                </h2>

            </div>

        </div>


        <a
            href="galeri.php?mode=album"
            class="back-button"
        >
            ← Daftar Album
        </a>

    </div>


    <?php if (!$albumPhotos): ?>

        <div class="empty-card">

            <div class="empty-icon">
                📷
            </div>

            <h3>
                Album Kosong
            </h3>

            <p>
                Belum ada foto dalam album ini.
            </p>

        </div>

    <?php else: ?>


        <div class="photo-grid">

            <?php foreach ($albumPhotos as $photo): ?>

                <div class="photo-card">

                    <div
                        class="photo-image"
                        onclick="bukaLightbox(
                            '<?= e($photo['image_path']) ?>',
                            '<?= e($photo['title']) ?>'
                        )"
                    >

                        <img
                            src="<?= e($photo['image_path']) ?>"
                            alt="<?= e($photo['title']) ?>"
                            loading="lazy"
                        >

                        <div class="photo-overlay">
                            <span>
                                🔍 Lihat Foto
                            </span>
                        </div>

                    </div>


                    <div class="photo-info">

                        <h3>
                            <?= e($photo['title']) ?>
                        </h3>

                        <?php if (!empty($photo['description'])): ?>

                            <p>
                                <?= nl2br(
                                    e($photo['description'])
                                ) ?>
                            </p>

                        <?php endif; ?>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</section>

<?php endif; ?>



<!-- =========================================================
     VIDEO
========================================================= -->

<?php if ($mode === 'video'): ?>

<section class="gallery-section">

    <div class="gallery-top">

        <div>

            <div class="section-heading">

                <span>
                    DOKUMENTASI
                </span>

                <h2>
                    Video Kegiatan
                </h2>

            </div>

        </div>

        <a href="galeri.php" class="back-button">Kembali ke Galeri</a>

    </div>


    <?php if (!$videos): ?>

        <div class="empty-card">

            <div class="empty-icon">
                🎥
            </div>

            <h3>
                    Belum Ada Video
            </h3>

            <p>
                Belum ada video yang ditambahkan.
            </p>

        </div>

    <?php else: ?>


        <div class="video-grid">

            <?php foreach ($videos as $video): ?>

                <div class="video-card">

                    <div class="video-frame">

                        <?php

                        $url = trim($video['video_url']);

                        /*
                        |---------------------------------------------
                        | Jika URL YouTube
                        |---------------------------------------------
                        */

                        $youtubeId = '';

                        if (
                            strpos($url, 'youtube.com/watch?v=') !== false
                        ) {

                            parse_str(
                                parse_url($url, PHP_URL_QUERY),
                                $query
                            );

                            $youtubeId = $query['v'] ?? '';

                        } elseif (
                            strpos($url, 'youtu.be/') !== false
                        ) {

                            $youtubeId = basename(
                                parse_url(
                                    $url,
                                    PHP_URL_PATH
                                )
                            );

                        }


                        $isLocalUpload = strpos($url, 'uploads/gallery/videos/') === 0;
                        $videoExtension = strtolower(pathinfo((string)parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));

                        if ($isLocalUpload):

                        ?>

                            <video class="video-player" controls playsinline preload="metadata" aria-label="<?= e($video['title']) ?>">
                                <source src="<?= e($url) ?>" type="video/<?= $videoExtension === 'webm' ? 'webm' : 'mp4' ?>">
                                Browser Anda tidak mendukung pemutaran video.
                            </video>

                        <?php elseif ($youtubeId):

                        ?>

                            <iframe
                                src="https://www.youtube.com/embed/<?= e($youtubeId) ?>"
                                title="<?= e($video['title']) ?>"
                                frameborder="0"
                                allowfullscreen
                            ></iframe>

                        <?php else: ?>

                            <div class="video-link-box">

                                <div class="video-big-icon">
                                    🎥
                                </div>

                                <a
                                    href="<?= e($url) ?>"
                                    target="_blank"
                                    rel="noopener"
                                >
                                    Buka Video
                                </a>

                            </div>

                        <?php endif; ?>

                    </div>


                    <div class="video-info">

                        <h3>
                            <?= e($video['title']) ?>
                        </h3>


                        <?php if (!empty($video['description'])): ?>

                            <p>
                                <?= nl2br(
                                    e($video['description'])
                                ) ?>
                            </p>

                        <?php endif; ?>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</section>

<?php endif; ?>



<!-- =========================================================
     LIGHTBOX FOTO
========================================================= -->

<div
    id="photoLightbox"
    class="photo-lightbox"
    onclick="tutupLightbox(event)"
>

    <button
        type="button"
        class="lightbox-close"
        onclick="tutupLightbox()"
    >
        ×
    </button>


    <button
        type="button"
        class="lightbox-prev"
        onclick="fotoSebelumnya(event)"
    >
        ❮
    </button>


    <div class="lightbox-content">

        <img
            id="lightboxImage"
            src=""
            alt=""
        >

        <div
            id="lightboxTitle"
            class="lightbox-title"
        ></div>

    </div>


    <button
        type="button"
        class="lightbox-next"
        onclick="fotoBerikutnya(event)"
    >
        ❯
    </button>

</div>



<style>

/* =========================================================
   HEADER GALERI
========================================================= */

.gallery-header{
    margin-bottom:35px;
}


.gallery-header h1{
    color:#17324d;

    font-size:40px;

    margin:0 0 8px;
}


.gallery-header p{
    color:#68737c;

    font-size:16px;
}



/* =========================================================
   MENU GALERI
========================================================= */

.gallery-menu{
    display:grid;

    grid-template-columns:
        repeat(2,1fr);

    gap:20px;

    margin-bottom:60px;
}


.gallery-menu-card{
    display:block;

    text-decoration:none;

    background:white;

    border:1px solid #e5e8ea;

    border-radius:16px;

    padding:35px;

    color:#24313d;

    transition:.25s;
}


.gallery-menu-card:hover{
    transform:translateY(-5px);

    box-shadow:
        0 12px 30px
        rgba(0,0,0,.08);

    border-color:#d8a84e;
}


.gallery-menu-icon{
    font-size:45px;

    margin-bottom:15px;
}


.gallery-menu-card h3{
    color:#17324d;

    font-size:25px;

    margin:0 0 8px;
}


.gallery-menu-card p{
    color:#68737c;

    margin:0;

    line-height:1.6;
}



/* =========================================================
   SECTION
========================================================= */

.gallery-section{
    margin-bottom:60px;
}


.gallery-top{
    display:flex;

    align-items:flex-start;

    justify-content:space-between;

    gap:20px;

    margin-bottom:25px;
}


.back-button{
    display:inline-block;

    padding:10px 18px;

    border-radius:8px;

    background:#17324d;

    color:white;

    text-decoration:none;

    font-weight:bold;

    white-space:nowrap;
}


.back-button:hover{
    background:#244d70;
}



/* =========================================================
   KATEGORI FOTO
========================================================= */

.gallery-category-grid{
    display:grid;

    grid-template-columns:
        repeat(2,1fr);

    gap:20px;
}


.category-card{
    display:block;

    text-decoration:none;

    background:#f7f4ed;

    border-radius:16px;

    padding:30px;

    color:#24313d;

    transition:.25s;
}


.category-card:hover{
    transform:translateY(-4px);

    box-shadow:
        0 10px 25px
        rgba(0,0,0,.08);
}


.category-icon{
    font-size:42px;

    margin-bottom:12px;
}


.category-card h3{
    color:#17324d;

    margin:0 0 8px;
}


.category-card p{
    color:#68737c;

    margin:0;
}



/* =========================================================
   GRID FOTO
========================================================= */

.photo-grid{
    display:grid;

    grid-template-columns:
        repeat(3,1fr);

    gap:22px;
}


.photo-card{
    background:white;

    border-radius:14px;

    overflow:hidden;

    border:1px solid #e5e8ea;

    box-shadow:
        0 4px 15px
        rgba(0,0,0,.04);
}


.photo-image{
    position:relative;

    width:100%;

    height:240px;

    overflow:hidden;

    cursor:pointer;

    background:#17324d;
}


.photo-image img{
    width:100%;
    height:100%;

    object-fit:cover;

    display:block;

    transition:
        transform .35s ease;
}


.photo-image:hover img{
    transform:scale(1.06);
}


.photo-overlay{
    position:absolute;

    inset:0;

    display:flex;

    align-items:center;

    justify-content:center;

    background:rgba(0,0,0,.45);

    color:white;

    opacity:0;

    transition:.25s;
}


.photo-image:hover .photo-overlay{
    opacity:1;
}


.photo-overlay span{
    padding:10px 16px;

    background:rgba(0,0,0,.65);

    border-radius:30px;

    font-size:14px;

    font-weight:bold;
}


.photo-info{
    padding:18px;
}


.photo-info h3{
    color:#17324d;

    margin:0 0 8px;

    font-size:18px;
}


.photo-info p{
    color:#68737c;

    font-size:14px;

    line-height:1.6;

    margin:0;
}



/* =========================================================
   ALBUM
========================================================= */

.album-grid{
    display:grid;

    grid-template-columns:
        repeat(3,1fr);

    gap:22px;
}


.album-card{
    display:block;

    text-decoration:none;

    color:#24313d;

    background:white;

    border:1px solid #e5e8ea;

    border-radius:14px;

    overflow:hidden;

    transition:.25s;
}


.album-card:hover{
    transform:translateY(-5px);

    box-shadow:
        0 12px 30px
        rgba(0,0,0,.08);
}


.album-cover{
    position:relative;

    width:100%;

    height:230px;

    background:#17324d;

    overflow:hidden;
}


.album-cover img{
    width:100%;
    height:100%;

    object-fit:cover;

    display:block;
}


.album-no-image{
    width:100%;
    height:100%;

    display:flex;

    align-items:center;

    justify-content:center;

    font-size:55px;
}


.album-count{
    position:absolute;

    right:12px;
    bottom:12px;

    background:rgba(0,0,0,.7);

    color:white;

    padding:6px 11px;

    border-radius:20px;

    font-size:13px;

    font-weight:bold;
}


.album-info{
    padding:18px;
}


.album-info h3{
    color:#17324d;

    margin:0 0 6px;
}


.album-info p{
    color:#c29137;

    margin:0;

    font-weight:bold;

    font-size:14px;
}



/* =========================================================
   VIDEO
========================================================= */

.video-grid{
    display:grid;

    grid-template-columns:
        repeat(2,1fr);

    gap:25px;
}


.video-card{
    background:white;

    border:1px solid #e5e8ea;

    border-radius:14px;

    overflow:hidden;

    box-shadow:
        0 4px 15px
        rgba(0,0,0,.04);
}


.video-frame{
    width:100%;

    aspect-ratio:16/9;

    background:#17324d;
}


.video-frame iframe{
    width:100%;
    height:100%;

    border:0;

    display:block;
}

.video-frame .video-player{
    width:100%;
    height:100%;
    display:block;
    background:#102538;
    object-fit:contain;
}


.video-link-box{
    width:100%;
    height:100%;

    display:flex;

    flex-direction:column;

    align-items:center;

    justify-content:center;

    gap:15px;
}


.video-big-icon{
    font-size:50px;
}


.video-link-box a{
    color:white;

    background:#c29137;

    padding:10px 18px;

    border-radius:7px;

    text-decoration:none;

    font-weight:bold;
}


.video-info{
    padding:18px;
}


.video-info h3{
    color:#17324d;

    margin:0 0 8px;
}


.video-info p{
    color:#68737c;

    margin:0;

    line-height:1.6;
}



/* =========================================================
   TIDAK ADA DATA
========================================================= */

.empty-card{
    text-align:center;

    background:#f7f4ed;

    border-radius:15px;

    padding:50px 25px;
}


.empty-icon{
    font-size:50px;

    margin-bottom:10px;
}


.empty-card h3{
    color:#17324d;

    margin:10px 0;
}


.empty-card p{
    color:#68737c;
}



/* =========================================================
   LIGHTBOX
========================================================= */

.photo-lightbox{
    position:fixed;

    z-index:99999;

    inset:0;

    display:none;

    align-items:center;

    justify-content:center;

    background:rgba(0,0,0,.92);

    padding:30px;

}


.photo-lightbox.show{
    display:flex;
}


.lightbox-content{
    position:relative;

    max-width:90vw;

    max-height:90vh;

    display:flex;

    flex-direction:column;

    align-items:center;

    justify-content:center;
}


.lightbox-content img{
    max-width:90vw;

    max-height:82vh;

    width:auto;
    height:auto;

    object-fit:contain;

    border-radius:8px;

    box-shadow:
        0 10px 50px
        rgba(0,0,0,.5);
}


.lightbox-title{
    color:white;

    font-size:17px;

    font-weight:bold;

    margin-top:15px;

    text-align:center;
}



/* TOMBOL CLOSE */

.lightbox-close{
    position:fixed;

    top:20px;
    right:25px;

    width:48px;
    height:48px;

    border:none;

    border-radius:50%;

    background:rgba(255,255,255,.15);

    color:white;

    font-size:34px;

    line-height:40px;

    cursor:pointer;

    z-index:100000;
}


.lightbox-close:hover{
    background:rgba(255,255,255,.3);
}



/* TOMBOL SEBELUMNYA */

.lightbox-prev,
.lightbox-next{
    position:fixed;

    top:50%;

    transform:translateY(-50%);

    width:50px;
    height:50px;

    border:none;

    border-radius:50%;

    background:rgba(255,255,255,.15);

    color:white;

    font-size:25px;

    cursor:pointer;

    z-index:100000;
}


.lightbox-prev{
    left:25px;
}


.lightbox-next{
    right:25px;
}


.lightbox-prev:hover,
.lightbox-next:hover{
    background:rgba(255,255,255,.3);
}



/* =========================================================
   MOBILE
========================================================= */

@media(max-width:900px){

    .photo-grid{
        grid-template-columns:
            repeat(2,1fr);
    }


    .album-grid{
        grid-template-columns:
            repeat(2,1fr);
    }

}


@media(max-width:700px){

    .gallery-menu{
        grid-template-columns:1fr;
    }


    .gallery-category-grid{
        grid-template-columns:1fr;
    }


    .video-grid{
        grid-template-columns:1fr;
    }


    .gallery-top{
        flex-direction:column;
    }


    .back-button{
        align-self:flex-start;
    }

}


@media(max-width:600px){

    .photo-grid{
        grid-template-columns:1fr;
    }


    .album-grid{
        grid-template-columns:1fr;
    }


    .photo-image{
        height:250px;
    }


    .lightbox-prev,
    .lightbox-next{
        width:40px;
        height:40px;

        font-size:20px;
    }


    .lightbox-prev{
        left:10px;
    }


    .lightbox-next{
        right:10px;
    }


    .lightbox-close{
        top:10px;
        right:10px;
    }


    .photo-lightbox{
        padding:15px;
    }

}

/* Polesan tampilan galeri */
.gallery-header{
    position:relative;
    overflow:hidden;
    isolation:isolate;
    margin:8px 0 28px;
    padding:clamp(28px,5vw,48px);
    border-radius:22px;
    color:#fff;
    background:linear-gradient(125deg,#17324d 0%,#214a66 58%,#356978 100%);
    box-shadow:0 16px 38px rgba(23,50,77,.16);
}
.gallery-header::after{
    content:"";
    position:absolute;
    z-index:-1;
    width:300px;
    height:300px;
    right:-90px;
    bottom:-190px;
    border:1px solid rgba(255,255,255,.14);
    border-radius:50%;
    box-shadow:0 0 0 32px rgba(255,255,255,.035),0 0 0 68px rgba(255,255,255,.025);
}
.gallery-header .section-heading{position:relative;z-index:1;max-width:650px}
.gallery-header .section-heading span,
.gallery-header .section-heading h1,
.gallery-header .section-heading p{color:#fff}
.gallery-header .section-heading span{color:#f0c76e;letter-spacing:.16em;font-weight:700;font-size:12px}
.gallery-header h1{font-size:clamp(34px,5vw,48px);line-height:1.08;margin:6px 0 12px}
.gallery-header p{font-size:16px;line-height:1.7;margin:0;opacity:.88}
.gallery-menu{grid-template-columns:repeat(2,minmax(0,1fr));gap:20px;margin-bottom:48px}
.gallery-menu-card{
    position:relative;
    min-height:190px;
    padding:28px 30px;
    border:1px solid #e8e5dd;
    border-radius:18px;
    box-shadow:0 8px 22px rgba(30,48,62,.06);
}
.gallery-menu-card::after{content:"Lihat koleksi  →";display:block;margin-top:18px;color:#a87620;font-size:13px;font-weight:700}
.gallery-menu-card:hover{transform:translateY(-5px);box-shadow:0 18px 34px rgba(23,50,77,.13);border-color:#d8a84e}
.gallery-menu-icon,.category-icon{
    display:grid;
    place-items:center;
    width:58px;
    height:58px;
    margin:0 0 18px;
    border-radius:17px;
    background:linear-gradient(145deg,#fff3d7,#f5dfae);
    font-size:29px;
    box-shadow:inset 0 0 0 1px rgba(216,168,78,.2);
}
.gallery-menu-card h3{font-size:23px;margin-bottom:5px}
.gallery-menu-card p,.category-card p{font-size:14px;line-height:1.65}
.gallery-section{margin:0 0 54px;padding:30px;border:1px solid #ebe7de;border-radius:20px;background:#fff;box-shadow:0 10px 28px rgba(30,48,62,.045)}
.gallery-section .section-heading span{color:#b07b20;letter-spacing:.14em;font-weight:700;font-size:12px}
.gallery-section .section-heading h2{margin:4px 0 0;color:#17324d;font-size:clamp(22px,3vw,29px)}
.gallery-heading-note{margin:8px 0 0;color:#68737c;font-size:14px}
.gallery-category-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;margin-top:22px}
.category-card{background:#fbfaf7;border:1px solid #ece8df;border-radius:16px;padding:24px;min-height:175px}
.category-card:hover{background:#fff;transform:translateY(-4px);border-color:#d8a84e;box-shadow:0 12px 26px rgba(23,50,77,.09)}
.category-card h3{font-size:18px;margin-bottom:5px}
.category-icon{width:48px;height:48px;margin-bottom:14px;font-size:25px}
.back-button{border-radius:999px;padding:10px 17px;box-shadow:0 5px 14px rgba(23,50,77,.14)}
.empty-card{border:1px dashed #d9d3c7;background:#fbfaf7}
.gallery-menu-card:focus-visible,.category-card:focus-visible,.back-button:focus-visible{outline:3px solid #d8a84e;outline-offset:3px}
@media(max-width:700px){
    .gallery-header{border-radius:17px;padding:28px 23px}
    .gallery-menu{grid-template-columns:1fr;gap:14px;margin-bottom:32px}
    .gallery-menu-card{min-height:0;padding:23px}
    .gallery-section{padding:22px 17px;border-radius:16px}
    .gallery-category-grid{grid-template-columns:1fr}
}

</style>



<!-- =========================================================
     JAVASCRIPT LIGHTBOX
========================================================= -->

<script>

/*
|--------------------------------------------------------------------------
| KUMPULKAN SEMUA FOTO DI HALAMAN
|--------------------------------------------------------------------------
*/

let daftarFoto = [];

let posisiFoto = 0;


/*
|--------------------------------------------------------------------------
| AMBIL DATA FOTO DARI ELEMEN GAMBAR
|--------------------------------------------------------------------------
*/

document.addEventListener(
    'DOMContentLoaded',
    function(){

        const fotoElements =
            document.querySelectorAll(
                '.photo-image'
            );


        fotoElements.forEach(
            function(element){

                const image =
                    element.querySelector('img');


                if(image){

                    daftarFoto.push({

                        src: image.src,

                        title: image.alt

                    });

                }

            }
        );

    }
);



/*
|--------------------------------------------------------------------------
| BUKA LIGHTBOX
|--------------------------------------------------------------------------
*/

function bukaLightbox(src,title){

    const lightbox =
        document.getElementById(
            'photoLightbox'
        );


    const image =
        document.getElementById(
            'lightboxImage'
        );


    const titleElement =
        document.getElementById(
            'lightboxTitle'
        );


    /*
    | Cari posisi foto yang diklik
    */

    for(
        let i = 0;
        i < daftarFoto.length;
        i++
    ){

        if(
            daftarFoto[i].src ===
            new URL(src,window.location.href).href
        ){

            posisiFoto = i;

            break;

        }

    }


    image.src = src;

    image.alt = title;

    titleElement.textContent = title;


    lightbox.classList.add('show');


    document.body.style.overflow =
        'hidden';

}



/*
|--------------------------------------------------------------------------
| TUTUP LIGHTBOX
|--------------------------------------------------------------------------
*/

function tutupLightbox(event){

    /*
    | Jangan tutup jika klik pada gambar
    */

    if(
        event &&
        event.target &&
        (
            event.target.id ===
            'lightboxImage'
            ||
            event.target.closest(
                '.lightbox-content'
            )
        )
    ){

        return;

    }


    const lightbox =
        document.getElementById(
            'photoLightbox'
        );


    lightbox.classList.remove('show');


    document.body.style.overflow =
        '';

}



/*
|--------------------------------------------------------------------------
| FOTO SEBELUMNYA
|--------------------------------------------------------------------------
*/

function fotoSebelumnya(event){

    if(event){

        event.stopPropagation();

    }


    if(daftarFoto.length <= 1){

        return;

    }


    posisiFoto--;


    if(posisiFoto < 0){

        posisiFoto =
            daftarFoto.length - 1;

    }


    tampilkanFotoLightbox();

}



/*
|--------------------------------------------------------------------------
| FOTO BERIKUTNYA
|--------------------------------------------------------------------------
*/

function fotoBerikutnya(event){

    if(event){

        event.stopPropagation();

    }


    if(daftarFoto.length <= 1){

        return;

    }


    posisiFoto++;


    if(
        posisiFoto >=
        daftarFoto.length
    ){

        posisiFoto = 0;

    }


    tampilkanFotoLightbox();

}



/*
|--------------------------------------------------------------------------
| TAMPILKAN FOTO
|--------------------------------------------------------------------------
*/

function tampilkanFotoLightbox(){

    const image =
        document.getElementById(
            'lightboxImage'
        );


    const title =
        document.getElementById(
            'lightboxTitle'
        );


    image.src =
        daftarFoto[posisiFoto].src;


    image.alt =
        daftarFoto[posisiFoto].title;


    title.textContent =
        daftarFoto[posisiFoto].title;

}



/*
|--------------------------------------------------------------------------
| KEYBOARD
|--------------------------------------------------------------------------
*/

document.addEventListener(
    'keydown',
    function(event){

        const lightbox =
            document.getElementById(
                'photoLightbox'
            );


        if(
            !lightbox.classList.contains(
                'show'
            )
        ){

            return;

        }


        if(event.key === 'Escape'){

            tutupLightbox();

        }


        if(event.key === 'ArrowLeft'){

            fotoSebelumnya();

        }


        if(event.key === 'ArrowRight'){

            fotoBerikutnya();

        }

    }
);

</script>



<?php require 'footer.php'; ?>
