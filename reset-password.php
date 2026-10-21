<?php
require 'config.php';
header('Referrer-Policy: no-referrer');
header('Cache-Control: no-store, max-age=0');

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

$token = trim((string)($_POST['token'] ?? $_GET['token'] ?? ''));
$tokenValid = preg_match('/^[a-f0-9]{64}$/', $token) === 1;
$message = '';
$done = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = (string)($_POST['password'] ?? '');
    $confirm = (string)($_POST['password_confirm'] ?? '');
    if (!isset($_POST['csrf']) || !hash_equals($_SESSION['csrf'], (string)$_POST['csrf'])) {
        $message = 'Permintaan tidak valid. Muat ulang halaman lalu coba kembali.';
    } elseif (strlen($password) < 12) {
        $message = 'Password baru minimal 12 karakter.';
    } elseif (!hash_equals($password, $confirm)) {
        $message = 'Konfirmasi password tidak sama.';
    } elseif ($tokenValid) {
        try {
            $pdo->beginTransaction();
            $find = $pdo->prepare('SELECT id, admin_id FROM admin_password_resets WHERE token_hash = ? AND used_at IS NULL AND expires_at > NOW() LIMIT 1 FOR UPDATE');
            $find->execute([hash('sha256', $token)]);
            $reset = $find->fetch();
            if (!$reset) {
                $pdo->rollBack();
                $message = 'Tautan reset tidak valid atau sudah kedaluwarsa. Minta tautan baru.';
            } else {
                $adminId = (int)$reset['admin_id'];
                $update = $pdo->prepare('UPDATE admins SET password_hash = ?, session_version = session_version + 1 WHERE id = ?');
                $update->execute([password_hash($password, PASSWORD_DEFAULT), $adminId]);
                $consume = $pdo->prepare('UPDATE admin_password_resets SET used_at = NOW() WHERE admin_id = ? AND used_at IS NULL');
                $consume->execute([$adminId]);
                $pdo->commit();
                $done = true;
                $message = 'Password berhasil diubah. Semua sesi admin lama sudah dikeluarkan. Silakan login dengan password baru.';
            }
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('Reset password admin gagal: ' . $exception->getMessage());
            $message = 'Reset password belum berhasil. Silakan coba kembali.';
        }
    } else {
        $message = 'Tautan reset tidak valid atau sudah kedaluwarsa. Minta tautan baru.';
    }
}

if (!$done && $message === '' && $tokenValid) {
    $check = $pdo->prepare('SELECT id FROM admin_password_resets WHERE token_hash = ? AND used_at IS NULL AND expires_at > NOW() LIMIT 1');
    $check->execute([hash('sha256', $token)]);
    $tokenValid = (bool)$check->fetchColumn();
}
if (!$done && $message === '' && !$tokenValid) {
    $message = 'Tautan reset tidak valid atau sudah kedaluwarsa. Minta tautan baru.';
}

$pageTitle = 'Reset Password Admin';
require 'header.php';
?>
<section class="card" style="max-width:560px;margin:35px auto">
    <h1>Reset Password Admin</h1>
    <?php if ($message !== ''): ?><p><?= e($message) ?></p><?php endif; ?>
    <?php if (!$done && $tokenValid): ?>
        <form method="post">
            <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
            <input type="hidden" name="token" value="<?= e($token) ?>">
            <label for="password">Password baru (minimal 12 karakter)</label>
            <input id="password" type="password" name="password" required minlength="12" autocomplete="new-password">
            <label for="password_confirm">Ulangi password baru</label>
            <input id="password_confirm" type="password" name="password_confirm" required minlength="12" autocomplete="new-password">
            <br><br><button type="submit">Simpan password baru</button>
        </form>
    <?php endif; ?>
    <?php if ($done || !$tokenValid): ?><p><a href="login.php">Ke halaman login</a></p><?php endif; ?>
</section>
<?php require 'footer.php'; ?>
