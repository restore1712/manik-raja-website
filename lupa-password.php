<?php
require 'config.php';

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

$message = 'Jika akun memiliki email pemulihan yang terverifikasi, instruksi reset akan dikirim.';
$csrf = $_SESSION['csrf'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf']) || !hash_equals($csrf, (string)$_POST['csrf'])) {
        $message = 'Permintaan tidak valid. Muat ulang halaman lalu coba kembali.';
    } else {
        try {
            if (recovery_email_is_configured() && !recovery_request_is_limited($pdo)) {
                $email = strtolower(trim((string)($_POST['email'] ?? '')));
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $find = $pdo->prepare('SELECT id, username, email FROM admins WHERE email = ? AND email_verified_at IS NOT NULL LIMIT 1');
                    $find->execute([$email]);
                    $admin = $find->fetch();
                    $urlBase = recovery_url('reset-password.php', 'placeholder');
                    if ($admin && $urlBase !== null && filter_var(MAIL_FROM, FILTER_VALIDATE_EMAIL)) {
                        $token = bin2hex(random_bytes(32));
                        $pdo->beginTransaction();
                        $invalidate = $pdo->prepare('UPDATE admin_password_resets SET used_at = NOW() WHERE admin_id = ? AND used_at IS NULL');
                        $invalidate->execute([(int)$admin['id']]);
                        $save = $pdo->prepare('INSERT INTO admin_password_resets (admin_id, token_hash, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 30 MINUTE))');
                        $save->execute([(int)$admin['id'], hash('sha256', $token)]);
                        $pdo->commit();

                        $url = recovery_url('reset-password.php', $token);
                        $sent = $url !== null && send_security_email(
                            $admin['email'],
                            'Reset password admin Pomparan Manik Raja',
                            "Halo {$admin['username']},\n\n" .
                            "Gunakan tautan berikut untuk membuat password baru. Tautan berlaku 30 menit dan hanya dapat digunakan sekali:\n\n" .
                            $url . "\n\nJika Anda tidak meminta reset password, abaikan email ini.\n"
                        );
                        if (!$sent) {
                            error_log('Email reset password admin tidak dapat dikirim. Periksa konfigurasi mail server.');
                        }
                    }
                }
            }
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('Permintaan reset password admin gagal: ' . $exception->getMessage());
        }
    }
}

$pageTitle = 'Lupa Password Admin';
require 'header.php';
?>
<section class="card" style="max-width:560px;margin:35px auto">
    <h1>Lupa Password Admin</h1>
    <p><?= e($message) ?></p>
    <form method="post">
        <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
        <label for="email">Email pemulihan terverifikasi</label>
        <input id="email" type="email" name="email" maxlength="190" required autocomplete="email">
        <br><br><button type="submit">Kirim tautan reset</button>
    </form>
    <p><a href="login.php">Kembali ke login</a></p>
</section>
<?php require 'footer.php'; ?>
