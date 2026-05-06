<?php
// キャッシュ無効化ヘッダーを設定
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// JSONファイルのパス
$productsFile = __DIR__ . '/db/products.json';
$materialsFile = __DIR__ . '/db/materials.json';

// バックアップフォルダのパス
$backupDir = __DIR__ . '/db/backup';

// バックアップを作成する関数
function createBackup($jsonFile, $backupDir, $fileName) {
    if (!file_exists($backupDir)) {
        mkdir($backupDir, 0777, true); // フォルダがなければ作成
    }

    // 今日の日付を取得して、バックアップファイル名を作成
    $backupFile = $backupDir . '/' . date('Ymd') . '_' . $fileName;

    // JSONファイルのバックアップを作成
    if (file_exists($jsonFile)) {
        copy($jsonFile, $backupFile);
    }
}

// バックアップボタンが押された時の処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // products.jsonのバックアップを作成
    createBackup($productsFile, $backupDir, 'products.json');

    // materials.jsonのバックアップを作成
    createBackup($materialsFile, $backupDir, 'materials.json');

    // バックアップ成功メッセージを設定
    $message = "バックアップが正常に作成されました。";
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>設定</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

    <div class="container">
        <h1>設定</h1>

        <!-- バックアップボタン -->
        <form method="POST" action="setting.php">
            <button type="submit">JSONファイルのバックアップを作成</button>
        </form>

        <!-- バックアップ成功メッセージを表示 -->
        <?php if (!empty($message)): ?>
            <p><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>

        <!-- 管理メニューに戻るボタン -->
        <button onclick="window.location.href='index.html'">管理メニューに戻る</button>
    </div>

</body>
</html>
