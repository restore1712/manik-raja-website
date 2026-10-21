<?php
$pageTitle = $pageTitle ?? 'Pomparan Manik Raja';
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?> | Pomparan Manik Raja</title>

<style>
:root{
    --navy:#17324d;
    --navy-dark:#102538;
    --gold:#d8a84e;
    --cream:#f7f4ed;
    --ink:#24313d;
    --white:#ffffff;
}

*{
    box-sizing:border-box;
}

html{
    width:100%;
    max-width:100%;
}

body{
    margin:0;
    font-family:Arial,Helvetica,sans-serif;
    color:var(--ink);
    background:#fff;
    line-height:1.6;
    min-width:0;
    overflow-wrap:anywhere;
}

img,
video,
iframe{
    max-width:100%;
}

/* HEADER */
header{
    background:var(--navy);
    color:white;
}

.top{
    max-width:1100px;
    margin:auto;
    padding:18px 20px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:18px;
}

.brand{
    display:flex;
    align-items:center;
    gap:12px;
    font-size:16px;
    font-weight:700;
    line-height:1.35;
}

.brand-logo{
    display:block;
    width:42px;
    height:42px;
    flex:0 0 42px;
    object-fit:contain;
    background:var(--navy-dark);
    border:2px solid #ddb04f;
    border-radius:50%;
}

/* NAVIGATION */
nav{
    background:var(--navy-dark);
}

.nav-inner{
    max-width:1100px;
    margin:auto;
    padding:0 20px;
    display:flex;
    flex-wrap:wrap;
    gap:4px;
}

nav a{
    color:white;
    text-decoration:none;
    padding:12px 13px;
    display:block;
}

nav a:hover{
    background:#284962;
}

/* CONTENT */
main{
    max-width:1100px;
    margin:28px auto;
    padding:0 20px;
    min-height:55vh;
}

main > *,
.grid > *,
.card{
    min-width:0;
}

/* HERO */
.hero{
    background:linear-gradient(120deg,#17324d,#315c70);
    color:white;
    padding:55px 30px;
    border-radius:14px;
    margin-bottom:28px;
}

.hero h1{
    font-size:clamp(28px,5vw,44px);
    line-height:1.15;
    margin:0 0 12px;
}

.hero p{
    max-width:700px;
}

/* BUTTON */
.btn,
button{
    display:inline-block;
    background:var(--gold);
    color:#172b3b;
    border:0;
    padding:11px 16px;
    border-radius:7px;
    text-decoration:none;
    font-weight:700;
    cursor:pointer;
}

.btn:hover{
    opacity:.9;
}

/* GRID */
.grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(230px,1fr));
    gap:18px;
}

/* CARD */
.card{
    border:1px solid #e4e7ea;
    border-radius:10px;
    padding:20px;
    background:#fff;
}

.muted{
    color:#65717b;
    font-size:14px;
}

/* FORM */
label{
    display:block;
    font-weight:700;
    margin:12px 0 5px;
}

input,
textarea,
select{
    width:100%;
    padding:11px;
    border:1px solid #ccd3d9;
    border-radius:6px;
    font:inherit;
}

textarea{
    min-height:130px;
}

/* TABLE */
table{
    width:100%;
    border-collapse:collapse;
    margin:15px 0;
    display:block;
    max-width:100%;
    overflow-x:auto;
}

th,
td{
    text-align:left;
    padding:10px;
    border-bottom:1px solid #e2e6e9;
    vertical-align:top;
}

th{
    background:var(--cream);
}

/* NOTICE */
.notice{
    padding:12px 15px;
    background:#edf7ee;
    border-radius:7px;
    margin:12px 0;
}

.danger{
    background:#fff0ef;
}

.actions{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
}

/* FOOTER */
footer{
    background:var(--navy-dark);
    color:#e9eef1;
    padding:25px 20px;
    text-align:center;
    margin-top:35px;
}

/* MOBILE */
@media(max-width:600px){

    .top{
        align-items:flex-start;
        flex-direction:column;
    }

    .nav-inner{
        padding:0 10px;
    }

    nav a{
        padding:10px;
        font-size:14px;
    }

    .hero{
        padding:35px 20px;
    }

    main{
        padding:0 14px;
    }

    table{
        font-size:14px;
    }
}
</style>
</head>

<body>

<header>
    <div class="top">
        <div class="brand">
            <img class="brand-logo" src="<?= e(site_logo_url()) ?>" alt="">
            <span>POMPARAN MANIK RAJA,<br>BORU, BERE DOHOT IBEBERE</span>
        </div>

        
    </div>
</header>

<nav>
    <div class="nav-inner">
        <a href="index.php">Beranda</a>
        <a href="sejarah.php">Sejarah</a>
        <a href="pengurus.php">Pengurus</a>
        <a href="berita.php">Berita</a>
        <a href="galeri.php">Galeri</a>
        <a href="daftar.php">Pendaftaran</a>
        <a href="kontak.php">Kontak</a>
    </div>
</nav>

<main>
