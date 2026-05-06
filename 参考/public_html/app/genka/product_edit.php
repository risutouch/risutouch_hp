<?php
// キャッシュ無効化ヘッダーを設定
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// products.jsonファイルのパス
$productsFile = __DIR__ . '/db/products.json';
$products = array();
if (file_exists($productsFile)) {
    $jsonData = file_get_contents($productsFile);
    $products = json_decode($jsonData, true);
}

// materials.jsonファイルのパス
$materialsFile = __DIR__ . '/db/materials.json';
$materials = array();
if (file_exists($materialsFile)) {
    $jsonData = file_get_contents($materialsFile);
    $materials = json_decode($jsonData, true);
}

// 編集する商品のIDを取得
$id = isset($_GET['id']) ? (int)$_GET['id'] : -1;

// 該当する商品データを取得
if ($id < 0 || $id >= count($products)) {
    echo "無効な商品IDです。";
    exit;
}
$product = $products[$id];

// フォームが送信された場合の処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 商品データを更新
    $products[$id]['name'] = $_POST['name'];
    $products[$id]['quantity'] = (int)$_POST['quantity'];
    $products[$id]['materials'] = array();

    // 各材料の量を取得
    foreach ($_POST['materials'] as $index => $quantity) {
        if ($quantity !== '' && isset($_POST['material_name'][$index])) {
            $products[$id]['materials'][] = array(
                'material' => $_POST['material_name'][$index],
                'quantity' => (int)$quantity
            );
        }
    }

    // JSONファイルに保存
    file_put_contents($productsFile, json_encode($products, JSON_PRETTY_PRINT));

    // 成功メッセージとリダイレクト
    header('Location: products_list.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>商品編集</title>
    <link rel="stylesheet" href="styles.css">
    <script>
        // 材料ごとの単価と単位を取得
        const materialsData = <?php echo json_encode($materials); ?>;

        // 合計金額を計算する関数
        function calculateTotal() {
            let total = 0;
            let quantity = parseFloat(document.getElementById('quantity').value) || 1;
            for (let i = 0; i < 20; i++) {
                const quantityInput = parseFloat(document.getElementById('quantity_' + i).value) || 0;
                const materialSelect = document.getElementById('material_' + i);
                const selectedMaterial = materialSelect.value;

                const materialInfo = materialsData.find(material => material.name === selectedMaterial);
                if (materialInfo) {
                    const materialCost = quantityInput * materialInfo.price;
                    total += materialCost;

                    // 材料ごとの金額を表示
                    document.getElementById('material_cost_' + i).textContent = materialCost.toFixed(2) + ' 円';

                    // 単位を表示
                    document.getElementById('unit_' + i).textContent = materialInfo.unit;
                } else {
                    document.getElementById('material_cost_' + i).textContent = '';
                    document.getElementById('unit_' + i).textContent = '';
                }
            }
            document.getElementById('totalPrice').textContent = '合計金額: ' + total.toFixed(2) + ' 円';
            document.getElementById('unitPrice').textContent = '1個当たりの原価: ' + (total / quantity).toFixed(2) + ' 円';
        }

        // ページ読み込み時に合計金額を計算
        window.onload = function() {
            calculateTotal();
        };
    </script>
</head>
<body>

    <div class="container">
        <h1>商品編集</h1>

        <form method="POST" action="product_edit.php?id=<?php echo $id; ?>">
            <label for="name">商品名:</label>
            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?>" required><br><br>

            <label for="quantity">何個分:</label>
            <input type="number" id="quantity" name="quantity" value="<?php echo htmlspecialchars($product['quantity'], ENT_QUOTES, 'UTF-8'); ?>" required oninput="calculateTotal()"><br><br>

            <h2>使用する材料</h2>

            <table>
                <tr>
                    <th>材料名</th>
                    <th>数量</th>
                    <th>単位</th>
                    <th>金額</th>
                </tr>

                <?php for ($i = 0; $i < 20; $i++): ?>
                <tr>
                    <td>
                        <!-- セレクトボックスで材料を選択 -->
                        <select id="material_<?php echo $i; ?>" name="material_name[]" oninput="calculateTotal()">
                            <option value="">-- 材料を選択 --</option>
                            <?php foreach ($materials as $material): ?>
                                <option value="<?php echo htmlspecialchars($material['name'], ENT_QUOTES, 'UTF-8'); ?>"
                                    <?php echo isset($product['materials'][$i]['material']) && $product['materials'][$i]['material'] === $material['name'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($material['name'], ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    
                    <td><input type="number" id="quantity_<?php echo $i; ?>" name="materials[]" step="0.01" placeholder="数量を入力" value="<?php echo isset($product['materials'][$i]['quantity']) ? htmlspecialchars($product['materials'][$i]['quantity'], ENT_QUOTES, 'UTF-8') : ''; ?>" oninput="calculateTotal()"></td>
                    
                    <td><span id="unit_<?php echo $i; ?>"></span></td>
                    <td><span id="material_cost_<?php echo $i; ?>"></span></td>
                </tr>
                <?php endfor; ?>
            </table>

            <h3 id="totalPrice">合計金額: 0 円</h3>
            <h3 id="unitPrice">1個当たりの原価: 0 円</h3>

            <button type="submit">保存</button>
        </form>

        <!-- 商品一覧に戻るボタン -->
        <button onclick="window.location.href='products_list.php'">商品一覧に戻る</button>
    </div>

</body>
</html>
