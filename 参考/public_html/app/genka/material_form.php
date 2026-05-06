<?php
// JSONファイルのパス
$jsonFile = __DIR__ . '/db/materials.json';

// フォームが送信された場合の処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 送信されたデータを取得
    $newMaterial = array(
        'name' => $_POST['name'],
        'unit' => $_POST['unit'],
        'price' => (float)$_POST['price']  // float型に変更して少数対応
    );

    // 既存のデータを取得してデコード
    $materials = array();
    if (file_exists($jsonFile)) {
        $jsonData = file_get_contents($jsonFile);
        $materials = json_decode($jsonData, true);
    }

    // 新しい材料をデータに追加
    $materials[] = $newMaterial;

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
    <title>材料登録</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

    <div class="container">
        <h1>材料登録</h1>

        <form method="POST" action="material_form.php">
            <label for="name">材料名:</label>
            <input type="text" id="name" name="name" required><br><br>

            <label for="unit">単位:</label>
            <input type="text" id="unit" name="unit" required><br><br>

            <label for="price">価格:</label>
            <input type="number" id="price" name="price" step="0.01" required><br><br> <!-- step="0.01"で少数対応 -->

            <button type="submit">登録</button>
        </form>

        <!-- 材料一覧に戻るボタン -->
        <button onclick="window.location.href='materials_list.php'">材料一覧に戻る</button>
    </div>

</body>
</html>
