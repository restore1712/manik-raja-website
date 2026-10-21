<?php
require 'config.php';
if(is_admin()){header('Location: admin.php');exit;}
$error = !empty($_GET['timeout'])
    ? 'Sesi berakhir setelah 15 menit tidak aktif. Silakan masuk kembali.'
    : (!empty($_GET['reauth']) ? 'Password admin berubah. Silakan login kembali.' : '');
if($_SERVER['REQUEST_METHOD']==='POST'){
 if(!isset($_POST['csrf']) || !hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'])) $error='Permintaan tidak valid.';
 else {
  $stmt=$pdo->prepare('SELECT id,username,password_hash,session_version FROM admins WHERE username=? LIMIT 1');
  $stmt->execute([trim($_POST['username']??'')]); $admin=$stmt->fetch();
  if($admin && password_verify($_POST['password']??'', $admin['password_hash'])){
    session_regenerate_id(true);
    $_SESSION['admin_id'] = $admin['id'];
    $_SESSION['admin_name'] = $admin['username'];
    $_SESSION['admin_session_version'] = (int)$admin['session_version'];
    $_SESSION['admin_last_activity'] = time();
    header('Location: admin.php');
    exit;
  } else $error='Username atau password salah.';
 }
}
if(empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(32));
$pageTitle='Login Admin'; require 'header.php';
?>
<h1>Login Admin</h1><?php if($error): ?><div class="notice danger"><?= e($error) ?></div><?php endif; ?>
<form class="card" method="post" style="max-width:480px"><input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
<label>Username</label><input name="username" required autocomplete="username">
<label>Password</label><input type="password" name="password" required autocomplete="current-password">
<br><br><button type="submit">Masuk</button>
<p><a href="lupa-password.php">Lupa password?</a></p></form>
<?php require 'footer.php'; ?>
