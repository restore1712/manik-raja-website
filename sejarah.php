<?php
require 'config.php';

$pageTitle = 'Sejarah';

$items = $pdo->query("
    SELECT id, title, body, content, created_at
    FROM posts
    WHERE category = 'sejarah'
    ORDER BY created_at ASC
")->fetchAll();

require 'inc/header.php';
?>

<style>

/* =========================================
   HALAMAN SEJARAH
========================================= */

.sejarah-page{
    max-width:950px;
    margin:0 auto;
}


/* =========================================
   HEADER
========================================= */

.sejarah-header{
    position:relative;

    background:
        linear-gradient(
            135deg,
            #17324d,
            #244e70
        );

    color:white;

    padding:50px 35px;

    border-radius:22px;

    text-align:center;

    margin-bottom:35px;

    overflow:hidden;

    box-shadow:
        0 10px 30px rgba(0,0,0,.12);
}

.sejarah-header::before{
    content:"";

    position:absolute;

    width:250px;
    height:250px;

    border-radius:50%;

    background:
        rgba(216,168,78,.12);

    top:-120px;
    right:-80px;
}

.sejarah-header::after{
    content:"";

    position:absolute;

    width:180px;
    height:180px;

    border-radius:50%;

    background:
        rgba(255,255,255,.05);

    bottom:-90px;
    left:-60px;
}

.sejarah-header-content{
    position:relative;

    z-index:2;
}

.sejarah-label{
    color:#d8a84e;

    font-size:13px;

    font-weight:bold;

    letter-spacing:2px;

    margin-bottom:12px;
}

.sejarah-header h1{
    color:white;

    margin:0 0 15px;

    font-size:38px;

    line-height:1.2;
}

.sejarah-header p{
    max-width:750px;

    margin:0 auto;

    color:
        rgba(255,255,255,.87);

    line-height:1.8;

    font-size:16px;
}


/* =========================================
   ISI SEJARAH
========================================= */

.sejarah-list{
    display:flex;

    flex-direction:column;

    gap:25px;
}

.sejarah-card{
    position:relative;

    background:white;

    padding:35px 40px;

    border-radius:18px;

    border:1px solid #eee;

    box-shadow:
        0 6px 22px rgba(0,0,0,.07);

    overflow:hidden;
}

.sejarah-card::before{
    content:"";

    position:absolute;

    left:0;
    top:0;
    bottom:0;

    width:6px;

    background:#d8a84e;
}

.sejarah-card h2{
    margin:0 0 18px;

    color:#17324d;

    font-size:27px;

    line-height:1.4;
}

.sejarah-card-body{
    color:#555;

    font-size:16px;

    line-height:1.9;
}

.sejarah-card-body p{
    margin:0 0 15px;
}

.sejarah-date{
    display:inline-block;

    margin-bottom:15px;

    padding:6px 12px;

    border-radius:20px;

    background:#f7f4ed;

    color:#777;

    font-size:12px;
}


/* =========================================
   KOSONG
========================================= */

.sejarah-empty{
    background:white;

    padding:45px 30px;

    border-radius:18px;

    text-align:center;

    box-shadow:
        0 6px 22px rgba(0,0,0,.07);
}

.sejarah-empty-icon{
    font-size:50px;

    margin-bottom:15px;
}

.sejarah-empty h3{
    color:#17324d;

    margin:0 0 10px;
}

.sejarah-empty p{
    color:#666;

    line-height:1.7;

    max-width:650px;

    margin:0 auto;
}


/* =========================================
   CATATAN
========================================= */

.sejarah-note{
    margin-top:35px;

    background:#f7f4ed;

    border-radius:18px;

    padding:25px 30px;

    border-left:5px solid #d8a84e;
}

.sejarah-note h3{
    margin:0 0 8px;

    color:#17324d;

    font-size:18px;
}

.sejarah-note p{
    margin:0;

    color:#666;

    line-height:1.7;

    font-size:14px;
}


/* =========================================
   RESPONSIVE
========================================= */

@media(max-width:600px){

    .sejarah-header{
        padding:35px 22px;
    }

    .sejarah-header h1{
        font-size:29px;
    }

    .sejarah-header p{
        font-size:14px;
    }

    .sejarah-card{
        padding:28px 25px;
    }

    .sejarah-card h2{
        font-size:23px;
    }

    .sejarah-card-body{
        font-size:15px;
    }

    .sejarah-note{
        padding:22px;
    }

}

</style>

<div class="sejarah-page">


<!-- ======================================
     HEADER
======================================= -->

<section class="sejarah-header">

    <div class="sejarah-header-content">

        <div class="sejarah-label">
            POMPARAN MANIK RAJA
        </div>

        <h1>
            Sejarah Manik Raja
        </h1>

        <p>
            Mengenal sejarah, kisah leluhur,
            perjalanan keluarga besar,
            serta informasi tarombo Pomparan Manik Raja.
        </p>

    </div>

</section>


<!-- ======================================
     ISI SEJARAH
======================================= -->

<?php if(!$items): ?>

    <div class="sejarah-empty">

        <div class="sejarah-empty-icon">
            📖
        </div>

        <h3>
            Sejarah akan segera dilengkapi
        </h3>

        <p>
            Silakan masukkan naskah sejarah yang
            sudah diverifikasi melalui dashboard admin.
        </p>

    </div>


<?php else: ?>


    <div class="sejarah-list">

        <?php foreach($items as $item): ?>

            <article class="sejarah-card">

                <div class="sejarah-date">

                    <?= date(
                        'd-m-Y',
                        strtotime($item['created_at'])
                    ) ?>

                </div>


                <h2>
                    <?= e($item['title']) ?>
                </h2>


                <div class="sejarah-card-body">

                    <?php

                    $isiSejarah = '';

                    if(!empty($item['body'])){

                        $isiSejarah = $item['body'];

                    } elseif(!empty($item['content'])){

                        $isiSejarah = $item['content'];

                    }

                    ?>


                    <?php if($isiSejarah): ?>

                        <?= nl2br(e($isiSejarah)) ?>

                    <?php else: ?>

                        <p>
                            Isi sejarah belum tersedia.
                        </p>

                    <?php endif; ?>


                </div>

            </article>

        <?php endforeach; ?>

    </div>


<?php endif; ?>


<!-- ======================================
     CATATAN
======================================= -->

<div class="sejarah-note">

    <h3>
        📜 Catatan Sejarah
    </h3>

    <p>
        Informasi sejarah, kisah leluhur, dan tarombo
        yang ditampilkan di website ini diharapkan
        telah diperiksa dan disepakati oleh pengurus
        atau keluarga yang berwenang.
    </p>

</div>


</div>

<?php require 'footer.php'; ?>
