<?php
require 'config.php';

$pageTitle = 'Kontak';

require 'inc/header.php';
?>

<style>
.kontak-page{
    max-width:950px;
    margin:0 auto;
}

.kontak-header{
    background:linear-gradient(135deg,#17324d,#244e70);
    color:white;
    padding:50px 35px;
    border-radius:22px;
    text-align:center;
    margin-bottom:35px;
    box-shadow:0 10px 30px rgba(0,0,0,.12);
}

.kontak-label{
    color:#d8a84e;
    font-size:13px;
    font-weight:bold;
    letter-spacing:2px;
    margin-bottom:12px;
}

.kontak-header h1{
    color:white;
    margin:0 0 15px;
    font-size:38px;
}

.kontak-header p{
    max-width:700px;
    margin:0 auto;
    color:rgba(255,255,255,.88);
    line-height:1.8;
}

.kontak-grid{
    display:grid;
    grid-template-columns:repeat(2,1fr);
    gap:22px;
}

.kontak-card{
    background:white;
    padding:30px;
    border-radius:18px;
    border:1px solid #eee;
    box-shadow:0 6px 22px rgba(0,0,0,.07);
}

.kontak-icon{
    font-size:35px;
    margin-bottom:15px;
}

.kontak-card h2{
    color:#17324d;
    margin:0 0 10px;
    font-size:21px;
}

.kontak-card p{
    color:#666;
    line-height:1.7;
    margin:0;
}

.kontak-card strong{
    color:#17324d;
}

.kontak-info{
    margin-top:35px;
    background:#f7f4ed;
    border-left:5px solid #d8a84e;
    padding:25px 30px;
    border-radius:15px;
}

.kontak-info h3{
    margin:0 0 10px;
    color:#17324d;
}

.kontak-info p{
    margin:0;
    color:#666;
    line-height:1.7;
}

.kontak-wa{
    display:inline-block;
    margin-top:18px;
    background:#17324d;
    color:white;
    text-decoration:none;
    padding:12px 20px;
    border-radius:8px;
    font-weight:bold;
    transition:.2s;
}

.kontak-wa:hover{
    background:#d8a84e;
    color:#17324d;
}

@media(max-width:650px){
    .kontak-header{
        padding:35px 22px;
    }

    .kontak-header h1{
        font-size:29px;
    }

    .kontak-grid{
        grid-template-columns:1fr;
    }

    .kontak-card{
        padding:25px;
    }
}
</style>

<div class="kontak-page">


<section class="kontak-header">
    <div class="kontak-label">POMPARAN MANIK RAJA</div>

    <h1>Kontak Pengurus</h1>

    <p>
        Hubungi pengurus Pomparan Manik Raja untuk mendapatkan
        informasi mengenai kegiatan, administrasi, pendaftaran,
        dan informasi keluarga besar Pomparan Manik Raja.
    </p>
</section>


<div class="kontak-grid">

    <div class="kontak-card">
        <div class="kontak-icon">📱</div>

        <h2>WhatsApp</h2>

        <p>
            Untuk informasi dan komunikasi dengan pengurus,
            silakan hubungi nomor WhatsApp resmi komunitas.
        </p>

        <p style="margin-top:12px;">
            <strong>Nomor:</strong>
            Belum diisi
        </p>

        <!--
        Jika nomor sudah tersedia, nanti ubah menjadi:

        <a href="https://wa.me/628xxxxxxxxxx"
           class="kontak-wa"
           target="_blank">
            Chat WhatsApp
        </a>
        -->
    </div>


    <div class="kontak-card">
        <div class="kontak-icon">✉️</div>

        <h2>Email</h2>

        <p>
            Untuk keperluan administrasi atau informasi resmi,
            silakan gunakan email komunitas.
        </p>

        <p style="margin-top:12px;">
            <strong>Email:</strong>
            Belum diisi
        </p>
    </div>


    <div class="kontak-card">
        <div class="kontak-icon">📍</div>

        <h2>Alamat Sekretariat</h2>

        <p>
            Alamat sekretariat resmi Pomparan Manik Raja
            dapat dicantumkan di bagian ini.
        </p>

        <p style="margin-top:12px;">
            <strong>Alamat:</strong>
            Belum diisi
        </p>
    </div>


    <div class="kontak-card">
        <div class="kontak-icon">🤝</div>

        <h2>Informasi Pengurus</h2>

        <p>
            Untuk mengetahui susunan pengurus pusat maupun
            pengurus daerah, silakan lihat halaman Pengurus.
        </p>

        <a href="pengurus.php" class="kontak-wa">
            Lihat Pengurus
        </a>
    </div>

</div>


<div class="kontak-info">

    <h3>📜 Informasi Penting</h3>

    <p>
        Gunakan hanya kontak resmi yang telah mendapat
        persetujuan dari pengurus Pomparan Manik Raja.
        Informasi kontak akan diperbarui apabila data resmi
        sudah tersedia.
    </p>

</div>


</div>

<?php require 'footer.php'; ?>
