<?php
// Jalankan sekali untuk membuat admin pertama, lalu HAPUS file setup_admin.php.
require 'config.php';
$count=(int)$pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn();
$message='';
if($count>0){exit('Admin sudah ada. Demi keamanan, hapus file setup_admin.php sekarang.');}
if($_SERVER['REQUEST_METHOD']==='POST'){
 if(!isset($_POST['csrf']) || !hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'])) $message='Permintaan tidak valid.';
 else {
  $u=trim($_POST['username']??''); $p=$_POST['password']??'';
  if(strlen($u)<4 || strlen($p)<12) $message='Username minimal 4 karakter dan password minimal 12 karakter.';
  else {
   $stmt=$pdo->prepare('INSERT INTO admins(username,password_hash) VALUES(?,?)');
   $stmt->execute([$u,password_hash($p,PASSWORD_DEFAULT)]);
   $message='Admin berhasil dibuat. HAPUS file setup_admin.php dari folder website, lalu login melalui login.php.';
  }
 }
}
if(empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(32));
$pageTitle='Setup Admin'; require 'header.php';
?>
<h1>Buat Admin Pertama</h1><div class="notice danger">Halaman setup ini hanya untuk instalasi awal. Hapus file setup_admin.php setelah admin dibuat.</div>
<?php if($message): ?><div class="notice"><?= e($message) ?></div><?php endif; ?>
<form method="post" class="card" style="max-width:480px"><input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
<label>Username (min. 4 karakter)</label><input name="username" required minlength="4">
<label>Password (min. 12 karakter)</label><input type="password" name="password" required minlength="12">
<button type="submit">Buat Admin</button></form>
<?php require 'footer.php'; ?>
