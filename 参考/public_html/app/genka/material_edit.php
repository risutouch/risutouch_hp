<?php
// キャッシュ無効化ヘッダーを設定
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1
header("Pragma: no-cache"); // HTTP 1.0
header("Expires: 0"); // Proxies

// materials.jsonファイルのパス
$jsonFile = __DIR__ . '/db/materials.json';

// JSONファイルから材料データを取得
$materials = array();
if (file_exists($jsonFile)) {
    $jsonData = file_get_contents($jsonFile);
    $materials = json_decode($jsonData, true);
}

// 編集する材料のIDを取得
$id = isset($_GET['id']) ? (int)$_GET['id'] : -1;

// 該当する材料データが存在するか確認
if ($id < 0 || $id >= count($materials)) {
    echo "無効な材料IDです。";
    exit;
}

// 編集対象の材料データ
$material = $materials[$id];

// フォームが送信された場合の処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 送信されたデータで材料を更新
    $materials[$id]['name'] = $_POST['name'];
    $materials[$id]['unit'] = $_POST['unit'];
    $materials[$id]['price'] = (float)$_POST['price'];  // float型に変更して少数対応

    // JSONファイルに保存
    file_put_contents($jsonFile, json_encode($materials, JSON_PRETTY_PRINT));

    // 成功メッセージとリダイレクト
    header('Location: materials_list.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>材料編集</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

    <div class="container">
        <h1>材料編集</h1>

        <form method="POST" action="material_edit.php?id=<?php echo $id; ?>">
            <label for="name">材料名:</label>
            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($material['name'], ENT_QUOTES, 'UTF-8'); ?>" required><br><br>

            <label for="unit">単位:</label>
            <input type="text" id="unit" name="unit" value="<?php echo htmlspecialchars($material['unit'], ENT_QUOTES, 'UTF-8'); ?>" required><br><br>

            <label for="price">価格:</label>
            <input type="number" id="price" name="price" step="0.01" value="<?php echo htmlspecialchars($material['price'], ENT_QUOTES, 'UTF-8'); ?>" required><br><br> <!-- step="0.01"で少数対応 -->

            <button type="submit">保存</button>
        </form>

        <!-- 材料一覧に戻るボタン -->
        <button onclick="window.location.href='materials_list.php'">材料一覧に戻る</button>
    </div>

</body>
</html>
