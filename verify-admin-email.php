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
$verified = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf']) || !hash_equals($_SESSION['csrf'], (string)$_POST['csrf'])) {
        $message = 'Permintaan tidak valid. Muat ulang halaman lalu coba kembali.';
    } elseif ($tokenValid) {
        try {
            $pdo->beginTransaction();
            $find = $pdo->prepare('SELECT id, admin_id, email FROM admin_email_verifications WHERE token_hash = ? AND used_at IS NULL AND expires_at > NOW() LIMIT 1 FOR UPDATE');
            $find->execute([hash('sha256', $token)]);
            $verification = $find->fetch();
            if (!$verification) {
                $pdo->rollBack();
                $message = 'Tautan verifikasi tidak valid atau sudah kedaluwarsa.';
            } else {
                $update = $pdo->prepare('UPDATE admins SET email = ?, email_verified_at = NOW() WHERE id = ?');
                $update->execute([$verification['email'], (int)$verification['admin_id']]);
                if ($update->rowCount() !== 1) {
                    $pdo->rollBack();
                    $message = 'Alamat email sudah berubah. Atur dan verifikasi ulang email pemulihan.';
                } else {
                    $consume = $pdo->prepare('UPDATE admin_email_verifications SET used_at = NOW() WHERE admin_id = ? AND used_at IS NULL');
                    $consume->execute([(int)$verification['admin_id']]);
                    $pdo->commit();
                    $verified = true;
                    $message = 'Email pemulihan berhasil diverifikasi.';
                }
            }
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('Verifikasi email admin gagal: ' . $exception->getMessage());
            $message = 'Verifikasi email belum berhasil. Silakan coba kembali.';
        }
    } else {
        $message = 'Tautan verifikasi tidak valid atau sudah kedaluwarsa.';
    }
}

if (!$verified && $message === '' && $tokenValid) {
    $check = $pdo->prepare('SELECT id FROM admin_email_verifications WHERE token_hash = ? AND used_at IS NULL AND expires_at > NOW() LIMIT 1');
    $check->execute([hash('sha256', $token)]);
    $tokenValid = (bool)$check->fetchColumn();
}
if (!$verified && $message === '' && !$tokenValid) {
    $message = 'Tautan verifikasi tidak valid atau sudah kedaluwarsa.';
}

$pageTitle = 'Verifikasi Email Admin';
require 'header.php';
?>
<section class="card" style="max-width:560px;margin:35px auto">
    <h1>Verifikasi Email Pemulihan</h1>
    <?php if ($message !== ''): ?><p><?= e($message) ?></p><?php endif; ?>
    <?php if (!$verified && $tokenValid): ?>
        <p>Konfirmasi alamat email ini sebagai email pemulihan akun admin.</p>
        <form method="post">
            <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
            <input type="hidden" name="token" value="<?= e($token) ?>">
            <button type="submit">Verifikasi email</button>
        </form>
    <?php endif; ?>
    <?php if ($verified): ?><p><a href="login.php">Ke halaman login</a></p><?php endif; ?>
</section>
<?php require 'footer.php'; ?>
