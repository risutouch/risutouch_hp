<?php
// キャッシュ無効化ヘッダーを設定
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// products.jsonファイルのパス
$jsonFile = __DIR__ . '/db/products.json';
$materialsFile = __DIR__ . '/db/materials.json';

// JSONデータを取得
$products = array();
$materials = array();
if (file_exists($jsonFile)) {
    $jsonData = file_get_contents($jsonFile);
    $products = json_decode($jsonData, true);
}
if (file_exists($materialsFile)) {
    $materialsData = file_get_contents($materialsFile);
    $materials = json_decode($materialsData, true);
}

// 削除処理を追加
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if ($id >= 0 && $id < count($products)) {
        array_splice($products, $id, 1);  // 指定されたIDの商品を削除
        file_put_contents($jsonFile, json_encode($products, JSON_PRETTY_PRINT));  // 削除後にJSONを保存
        header('Location: products_list.php');  // 削除後に一覧ページにリダイレクト
        exit;
    }
}

// 材料の価格情報を取得する関数
function getMaterialPrice($materialName, $materials) {
    foreach ($materials as $material) {
        if ($material['name'] === $materialName) {
            return $material['price'];
        }
    }
    return 0; // 見つからない場合は0を返す
}

// 合計金額を計算する関数
function calculateTotal($product, $materials) {
    $total = 0;
    foreach ($product['materials'] as $material) {
        $price = getMaterialPrice($material['material'], $materials);
        $total += $price * $material['quantity'];
    }
    return $total;
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>商品一覧</title>
    <link rel="stylesheet" href="styles.css">
    <script>
        function confirmDelete(id) {
            if (confirm('この商品を本当に削除しますか？')) {
                window.location.href = 'products_list.php?delete=' + id;
            }
        }
    </script>
</head>
<body>

    <div class="container">
        <h1>商品一覧</h1>
        
        <!-- 登録件数を表示 -->
        <p>登録商品件数: <?php echo count($products); ?> 件</p>

        <div class="button-container">
            <button onclick="window.location.href='product_form.php'">商品登録</button>
            <button onclick="window.location.href='index.html'">管理メニューに戻る</button>
        </div>

        <table>
            <thead>
                <tr>
                    <th>商品名</th>
                    <th>何個分</th>
                    <th>合計金額</th>
                    <th>1個当たり金額</th>
                    <th>編集</th>
                    <th>削除</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($products)): ?>
                    <?php foreach ($products as $index => $product): ?>
                        <?php
                        // 合計金額を計算
                        $total = calculateTotal($product, $materials);
                        // 1個当たりの金額を計算
                        $unitPrice = $product['quantity'] > 0 ? $total / $product['quantity'] : 0;
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($product['quantity'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo number_format($total, 2) . ' 円'; ?></td>
                            <td><?php echo number_format($unitPrice, 2) . ' 円'; ?></td>
                            <td>
                                <a href="product_edit.php?id=<?php echo $index; ?>">編集</a>
                            </td>
                            <td>
                                <button onclick="confirmDelete(<?php echo $index; ?>)">削除</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6">商品データがありません。</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</body>
</html>
