<?php


mb_internal_encoding('UTF-8');
date_default_timezone_set('Asia/Tokyo');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$autoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
}

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/json_storage.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/deliveries.php';
require_once __DIR__ . '/invoices.php';
require_once __DIR__ . '/settings.php';

