<?php
/** @var string $pageTitle */
/** @var string $ogTitle */
/** @var string $ogDescription */
if (!isset($pageTitle)) {
    $pageTitle = '納品管理システム';
}
if (!isset($ogTitle)) {
    $ogTitle = $pageTitle;
}
if (!isset($ogDescription)) {
    $ogDescription = '納品データと請求書を簡単に管理できるシステムです';
}
$user = current_user();
$displayName = null;
if ($user) {
    $role = $user['role'] ?? null;
    if ($role) {
        $displayName = role_label((string) $role);
    }
    if (!$displayName) {
        $displayName = $user['name'] ?? ($user['login'] ?? ($user['id'] ?? 'ユーザー'));
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, user-scalable=yes">
    <meta name="robots" content="noindex, nofollow">
    <meta name="format-detection" content="telephone=no, email=no, address=no">
    <title><?= escape($pageTitle) ?></title>
    <meta property="og:title" content="<?= escape($ogTitle) ?>">
    <meta property="og:description" content="<?= escape($ogDescription) ?>">
    <meta property="og:type" content="website">
    <meta name="description" content="<?= escape($ogDescription) ?>">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime(__DIR__ . '/../assets/css/style.css') ?>">
</head>
<body>
<header class="site-header">
    <div class="container header-content">
        <div class="brand">納品・請求管理</div>
        <?php if ($user): ?>
            <nav class="main-nav">
                <a href="index.php">納品一覧</a>
                <a href="invoice.php">請求書</a>
                <?php if (is_supplier()): ?>
                    <a href="settings.php">設定</a>
                <?php endif; ?>
                <a href="logout.php">ログアウト</a>
            </nav>
            <div class="user-info">
                ログイン中: <?= escape($displayName) ?>
            </div>
        <?php endif; ?>
    </div>
</header>
<main class="container main-content">
