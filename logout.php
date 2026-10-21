<?php
require 'config.php';
$timedOut = isset($_GET['timeout']) && $_GET['timeout'] === '1';
clear_current_session();
header('Location: login.php' . ($timedOut ? '?timeout=1' : ''));
exit;
