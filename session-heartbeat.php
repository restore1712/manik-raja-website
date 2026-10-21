<?php
require 'config.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$sentToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !is_admin() || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $sentToken)) {
    http_response_code(401);
    echo json_encode(['ok' => false]);
    exit;
}

$lastActivity = (int)($_SESSION['admin_last_activity'] ?? 0);
if ($lastActivity === 0 || (time() - $lastActivity) >= ADMIN_IDLE_TIMEOUT) {
    clear_current_session();
    http_response_code(401);
    echo json_encode(['ok' => false, 'timeout' => true]);
    exit;
}

$_SESSION['admin_last_activity'] = time();
echo json_encode(['ok' => true]);
