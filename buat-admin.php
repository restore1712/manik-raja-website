<?php

// File helper lama yang berisi kredensial bawaan; jangan layani melalui web.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require 'config.php';

$username = 'admin';
$password = 'admin123';

$hash = password_hash($password, PASSWORD_DEFAULT);

echo "Username: " . $username . "<br>";
echo "Password: " . $password . "<br><br>";
echo "Password Hash:<br>";
echo "<textarea style='width:700px;height:100px;'>" . htmlspecialchars($hash) . "</textarea>";
