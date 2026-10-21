<?php
if (!isset($pageTitle)) {
    $pageTitle = 'Pomparan Manik Raja';
}
?>

<!DOCTYPE html>

<html lang="id">
<head>


<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title><?= htmlspecialchars($pageTitle) ?> - Pomparan Manik Raja</title>

<style>

    *{
        box-sizing:border-box;
    }

    html{
        width:100%;
        max-width:100%;
    }

    body{
        margin:0;
        font-family:Arial, Helvetica, sans-serif;
        background:#f7f4ed;
        color:#24313d;
        min-width:0;
        overflow-wrap:anywhere;
    }

    img,
    video,
    iframe{
        max-width:100%;
    }

    a{
        color:inherit;
    }

    .site-header{
        background:#17324d;
        color:white;
        padding:0;
        position:relative;
        z-index:1000;
    }

    .header-inner{
        max-width:1100px;
        margin:auto;
        padding:18px 20px;
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:20px;
        position:relative;
    }

    .brand{
        display:flex;
        align-items:center;
        gap:12px;
        text-decoration:none;
        color:white;
        font-size:16px;
        font-weight:bold;
        line-height:1.2;
        flex:0 0 auto;
        white-space:nowrap;
    }

    .brand-logo{
        display:block;
        width:42px;
        height:42px;
        flex:0 0 42px;
        object-fit:contain;
        background:#102538;
        border:2px solid #ddb04f;
        border-radius:50%;
    }

    .nav{
        display:flex;
        gap:6px;
        align-items:center;
        flex-wrap:wrap;
    }

    .nav a{
        color:white;
        text-decoration:none;
        padding:9px 12px;
        border-radius:6px;
        font-size:14px;
    }

    .nav a:hover{
        background:#d8a84e;
        color:#17324d;
    }

    .menu-toggle{
        display:none;
        background:none;
        border:0;
        color:white;
        font-size:28px;
        cursor:pointer;
        padding:4px 8px;
        line-height:1;
    }

    main{
        max-width:1100px;
        margin:30px auto;
        padding:0 20px;
    }

    main > *,
    main .card,
    main .grid > *{
        min-width:0;
    }

    footer{
        margin-top:50px;
        background:#17324d;
        color:white;
        text-align:center;
        padding:25px 20px;
    }


    /* =========================
       TAMPILAN HP
       ========================= */

  @media(max-width:800px){

    .header-inner{
        position:relative !important;
        padding:15px 16px !important;
    }

    .brand{
        font-size:16px !important;
        white-space:normal;
    }

    .site-header .menu-toggle{
        display:block !important;
        visibility:visible !important;
        opacity:1 !important;
        margin-left:auto !important;
        background:none !important;
        border:0 !important;
        color:white !important;
        font-size:28px !important;
        cursor:pointer !important;
        padding:4px 8px !important;
        line-height:1 !important;
        position:relative !important;
        z-index:10001 !important;
    }

    .site-header .nav{
        display:none !important;
        position:absolute !important;
        top:100% !important;
        left:0 !important;
        right:0 !important;
        width:100% !important;
        background:#17324d !important;
        padding:10px 16px 18px !important;
        flex-direction:column !important;
        align-items:stretch !important;
        gap:4px !important;
        z-index:10000 !important;
        box-shadow:0 4px 8px rgba(0,0,0,0.20) !important;
    }

    .site-header .nav.show{
        display:flex !important;
    }

    .site-header .nav a{
        display:block !important;
        width:100% !important;
        padding:12px 14px !important;
        border-radius:6px !important;
        font-size:15px !important;
    }

    main{
        margin:25px auto !important;
        padding:0 15px !important;
    }

}

</style>


</head>

<body>

<header class="site-header">


<div class="header-inner">

    <a href="index.php" class="brand">
        <img class="brand-logo" src="<?= htmlspecialchars(site_logo_url(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" alt="">
        <span>POMPARAN MANIK RAJA,<br>BORU, BERE DOHOT IBEBERE</span>
    </a>

    <button
        type="button"
        class="menu-toggle"
        id="menuToggle"
        aria-label="Buka menu"
        aria-expanded="false"
    >
        ☰
    </button>

    <nav class="nav" id="mainNav">

        <a href="index.php">Beranda</a>

        <a href="sejarah.php">Sejarah</a>

        <a href="pengurus.php">Pengurus</a>

        <a href="berita.php">Berita</a>

        <a href="galeri.php">Galeri</a>

        <a href="daftar.php">Pendaftaran</a>

        <a href="kontak.php">Kontak</a>

    </nav>

</div>


</header>

<main>

<script>

document.addEventListener('DOMContentLoaded', function(){

    const menuToggle = document.getElementById('menuToggle');
    const mainNav = document.getElementById('mainNav');

    if(!menuToggle || !mainNav){
        return;
    }

    menuToggle.addEventListener('click', function(){

        mainNav.classList.toggle('show');

        const isOpen = mainNav.classList.contains('show');

        menuToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');

    });

    /* Tutup menu setelah memilih salah satu menu */

    const navLinks = mainNav.querySelectorAll('a');

    navLinks.forEach(function(link){

        link.addEventListener('click', function(){

            mainNav.classList.remove('show');

            menuToggle.setAttribute('aria-expanded', 'false');

        });

    });

});

</script>
