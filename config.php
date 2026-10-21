<?php
declare(strict_types=1);

const ADMIN_IDLE_TIMEOUT = 900; // 15 menit
const SITE_LOGO_PATH = 'img/monumen-beranda.png';

if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');

    $isHttps = isset($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off';
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
date_default_timezone_set('Asia/Jakarta');

/* Set these in the web server environment before enabling recovery email. */
define('APP_BASE_URL', rtrim((string)(getenv('APP_BASE_URL') ?: ''), '/'));
define('MAIL_FROM', trim((string)(getenv('MAIL_FROM') ?: '')));

$dbHost = 'localhost';
$dbName = 'marga_manik';
$dbUser = 'root';
$dbPass = ''; // Default XAMPP biasanya kosong. Ubah jika MySQL Anda memakai password.

try {
    $pdo = new PDO(
        "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4",
        $dbUser,
        $dbPass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
         PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (PDOException $e) {
    http_response_code(500);
    exit('Database belum terhubung. Periksa config.php dan pastikan database marga_manik sudah diimpor.');
}

function e(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function site_logo_url(): string {
    $filePath = __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, SITE_LOGO_PATH);
    $version = is_file($filePath) ? (string)filemtime($filePath) : '';
    return SITE_LOGO_PATH . ($version !== '' ? '?v=' . rawurlencode($version) : '');
}
function is_admin(): bool {
    return isset($_SESSION['admin_id']);
}
function require_admin(): void {
    if (!is_admin()) {
        header('Location: login.php');
        exit;
    }

    $lastActivity = (int)($_SESSION['admin_last_activity'] ?? 0);
    if ($lastActivity === 0 || (time() - $lastActivity) >= ADMIN_IDLE_TIMEOUT) {
        clear_current_session();
        header('Location: login.php?timeout=1');
        exit;
    }

    $versionQuery = $GLOBALS['pdo']->prepare('SELECT session_version FROM admins WHERE id = ? LIMIT 1');
    $versionQuery->execute([(int)$_SESSION['admin_id']]);
    $currentVersion = $versionQuery->fetchColumn();
    if ($currentVersion === false ||
        !isset($_SESSION['admin_session_version']) ||
        (int)$currentVersion !== (int)$_SESSION['admin_session_version']) {
        clear_current_session();
        header('Location: login.php?reauth=1');
        exit;
    }

    $_SESSION['admin_last_activity'] = time();
}

function recovery_url(string $script, string $token): ?string {
    $parts = parse_url(APP_BASE_URL);
    if (!$parts || strtolower($parts['scheme'] ?? '') !== 'https' || empty($parts['host'])) {
        return null;
    }
    return APP_BASE_URL . '/' . ltrim($script, '/') . '?token=' . rawurlencode($token);
}

function recovery_email_is_configured(): bool {
    $parts = parse_url(APP_BASE_URL);
    $appKey = (string)(getenv('APP_KEY') ?: '');
    return $parts !== false && strtolower($parts['scheme'] ?? '') === 'https' && !empty($parts['host']) &&
        filter_var(MAIL_FROM, FILTER_VALIDATE_EMAIL) !== false && strlen($appKey) >= 32 && function_exists('mail');
}

function send_security_email(string $to, string $subject, string $body): bool {
    if (!filter_var($to, FILTER_VALIDATE_EMAIL) || !filter_var(MAIL_FROM, FILTER_VALIDATE_EMAIL) || !function_exists('mail')) {
        return false;
    }
    $headers = "From: " . MAIL_FROM . "\r\n" .
        "Content-Type: text/plain; charset=UTF-8\r\n" .
        "X-Content-Type-Options: nosniff\r\n";
    return @mail($to, $subject, $body, $headers);
}

function recovery_request_is_limited(PDO $pdo): bool {
    $ip = (string)($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    $key = (string)(getenv('APP_KEY') ?: '');
    if (strlen($key) < 32) return true;
    $fingerprint = hash_hmac('sha256', $ip, $key);
    $count = $pdo->prepare('SELECT COUNT(*) FROM admin_recovery_requests WHERE request_ip_hash = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)');
    $count->execute([$fingerprint]);
    if ((int)$count->fetchColumn() >= 5) {
        return true;
    }
    $insert = $pdo->prepare('INSERT INTO admin_recovery_requests (request_ip_hash) VALUES (?)');
    $insert->execute([$fingerprint]);
    return false;
}

function clear_current_session(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $params['path'] ?: '/',
            'domain' => $params['domain'],
            'secure' => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => $params['samesite'] ?? 'Lax',
        ]);
    }
    session_destroy();
}

/** Simpan upload gambar yang benar-benar terdeteksi sebagai gambar. */
function save_uploaded_image(array $file, string $subdirectory): ?string {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK ||
        empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name']) ||
        (int)($file['size'] ?? 0) > 5 * 1024 * 1024) {
        throw new RuntimeException('Unggahan gagal atau melebihi batas 5 MB.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];
    if (!isset($extensions[$mime]) || @getimagesize($file['tmp_name']) === false) {
        throw new RuntimeException('File harus berupa gambar JPG, PNG, atau WEBP yang valid.');
    }

    $relativeDirectory = 'uploads/' . trim($subdirectory, '/\\');
    $directory = __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativeDirectory);
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        throw new RuntimeException('Folder upload tidak dapat dibuat.');
    }
    $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
    if (!move_uploaded_file($file['tmp_name'], $directory . DIRECTORY_SEPARATOR . $filename)) {
        throw new RuntimeException('Gambar gagal disimpan.');
    }
    return $relativeDirectory . '/' . $filename;
}

/** Hapus foto pengurus hanya dari folder upload khusus pengurus. */
function delete_officer_upload(?string $relativePath): void {
    if (!$relativePath || strpos($relativePath, 'uploads/officers/') !== 0) {
        return;
    }

    $uploadRoot = realpath(__DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'officers');
    $filePath = realpath(__DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath));
    if (!$uploadRoot || !$filePath || strpos($filePath, $uploadRoot . DIRECTORY_SEPARATOR) !== 0 || !is_file($filePath)) {
        return;
    }

    @unlink($filePath);
}

/** Simpan video MP4/WebM dengan nama unik di folder upload galeri. */
function save_uploaded_video(array $file): ?string {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    $maxBytes = 35 * 1024 * 1024;
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK ||
        empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name']) ||
        (int)($file['size'] ?? 0) > $maxBytes) {
        throw new RuntimeException('Upload video gagal atau melebihi batas 35 MB.');
    }

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $extensions = [
        'video/mp4' => 'mp4',
        'video/webm' => 'webm',
    ];
    if (!isset($extensions[$mime])) {
        throw new RuntimeException('Video harus berformat MP4 atau WebM.');
    }

    $relativeDirectory = 'uploads/gallery/videos';
    $directory = __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativeDirectory);
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        throw new RuntimeException('Folder upload video tidak dapat dibuat.');
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
    if (!move_uploaded_file($file['tmp_name'], $directory . DIRECTORY_SEPARATOR . $filename)) {
        throw new RuntimeException('Video gagal disimpan.');
    }

    return $relativeDirectory . '/' . $filename;
}

/** Hapus file media galeri hanya jika lokasinya benar-benar di folder galeri. */
function delete_gallery_upload(?string $relativePath): void {
    if (!$relativePath || strpos($relativePath, 'uploads/gallery/') !== 0) {
        return;
    }

    $galleryRoot = realpath(__DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'gallery');
    $filePath = realpath(__DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath));
    if (!$galleryRoot || !$filePath || strpos($filePath, $galleryRoot . DIRECTORY_SEPARATOR) !== 0 || !is_file($filePath)) {
        return;
    }

    @unlink($filePath);
}

function valid_video_url(string $url): bool {
    $parts = parse_url($url);
    if (!$parts || strtolower($parts['scheme'] ?? '') !== 'https') {
        return false;
    }
    $host = strtolower($parts['host'] ?? '');
    if (!in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com', 'youtu.be', 'www.youtu.be'], true)) {
        return false;
    }
    if (in_array($host, ['youtu.be', 'www.youtu.be'], true)) {
        return preg_match('~^/[A-Za-z0-9_-]{11}/?$~', $parts['path']) === 1;
    }
    if (($parts['path'] ?? '') === '/watch') {
        parse_str($parts['query'] ?? '', $query);
        return preg_match('/^[A-Za-z0-9_-]{11}$/', $query['v'] ?? '') === 1;
    }
    return preg_match('~^/(?:embed|shorts)/[A-Za-z0-9_-]{11}/?$~', $parts['path'] ?? '') === 1;
}
