<?php
require 'config.php';

$pageTitle = 'Tentang Kami';

require 'inc/header.php';
?>

<style>
.about-page{
    max-width:900px;
    margin:auto;
}

.about-hero{
    background:linear-gradient(135deg,#17324d,#244c6d);
    color:white;
    padding:45px 35px;
    border-radius:20px;
    margin-bottom:30px;
}

.about-hero .label{
    color:#d8a84e;
    font-size:13px;
    font-weight:bold;
    letter-spacing:2px;
    margin-bottom:10px;
}

.about-hero h1{
    margin:0 0 15px;
    font-size:36px;
}

.about-hero p{
    margin:0;
    line-height:1.8;
    color:#f1f1f1;
}

.about-card{
    background:white;
    padding:35px;
    border-radius:18px;
    margin-bottom:25px;
    box-shadow:0 6px 22px rgba(0,0,0,.07);
}

.about-card h2{
    margin-top:0;
    color:#17324d;
    border-left:5px solid #d8a84e;
    padding-left:15px;
}

.about-card p{
    line-height:1.9;
    color:#555;
}

.about-list{
    padding-left:20px;
    line-height:2;
    color:#555;
}

.back-button{
    display:inline-block;
    background:#17324d;
    color:white;
    text-decoration:none;
    padding:12px 20px;
    border-radius:8px;
    font-weight:bold;
}

.back-button:hover{
    background:#d8a84e;
    color:#17324d;
}

@media(max-width:600px){
    .about-hero{
        padding:30px 22px;
    }

    .about-hero h1{
        font-size:28px;
    }

    .about-card{
        padding:25px 20px;
    }
}
</style>

<div class="about-page">


<section class="about-hero">
    <div class="label">TENTANG KAMI</div>

    <h1>Pomparan Manik Raja</h1>

    <p>
        Ruang informasi dan kebersamaan keluarga besar
        Pomparan Manik Raja.
    </p>
</section>


<section class="about-card">

    <h2>Mengenal Pomparan Manik Raja</h2>

    <p>
        Punguan Marga Manik merupakan wadah untuk menghimpun
        dan mempererat hubungan seluruh pomparan Manik
        di mana pun berada.
    </p>

    <p>
        Website ini hadir sebagai media informasi,
        komunikasi, dan dokumentasi bagi keluarga besar
        Pomparan Manik Raja.
    </p>

    <p>
        Melalui website ini, kita dapat mengenal sejarah,
        menjaga tarombo, mendapatkan informasi kegiatan,
        serta mempererat tali persaudaraan antar keluarga
        besar Pomparan Manik Raja.
    </p>

</section>


<section class="about-card">

    <h2>Tujuan</h2>

    <ul class="about-list">
        <li>Mempererat hubungan kekeluargaan Pomparan Manik Raja.</li>
        <li>Melestarikan sejarah dan silsilah keluarga.</li>
        <li>Menyediakan informasi kegiatan punguan.</li>
        <li>Mendokumentasikan kegiatan dan kebersamaan keluarga.</li>
        <li>Membangun komunikasi antar pomparan di berbagai daerah.</li>
    </ul>

</section>


<section class="about-card">

    <h2>Kebersamaan dan Persaudaraan</h2>

    <p>
        Pomparan Manik Raja diharapkan dapat terus menjaga
        semangat persaudaraan, saling membantu, dan menjaga
        hubungan kekeluargaan di mana pun berada.
    </p>

    <p>
        Horas! Salam persaudaraan untuk seluruh Pomparan
        Manik Raja.
    </p>

</section>


<a href="index.php" class="back-button">
    ← Kembali ke Beranda
</a>


</div>

<?php require 'inc/footer.php'; ?>
