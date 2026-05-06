<?php
// キャッシュ無効化ヘッダーを設定
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// materials.jsonファイルのパス
$jsonFile = __DIR__ . '/db/materials.json';

// JSONデータを取得
$materials = array();
if (file_exists($jsonFile)) {
    $jsonData = file_get_contents($jsonFile);
    $materials = json_decode($jsonData, true);
}

// 削除処理
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if ($id >= 0 && $id < count($materials)) {
        array_splice($materials, $id, 1);
        file_put_contents($jsonFile, json_encode($materials, JSON_PRETTY_PRINT));
        header('Location: materials_list.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>材料一覧</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        /* コンテナー内に収まる「参考資料」リンク */
        .reference-link {
            text-align: right;  /* コンテナー内の右側に配置 */
            margin-bottom: 10px;
        }
    </style>
    <script>
        function confirmDelete(id) {
            if (confirm('この材料を本当に削除しますか？')) {
                window.location.href = 'materials_list.php?delete=' + id;
            }
        }
    </script>
</head>
<body>

    <div class="container">
        <h1>材料一覧</h1>

        <!-- 参考資料リンクを右側に配置 -->
        <div class="reference-link">
            <a href="https://docs.google.com/spreadsheets/d/15BwOy8_DO34TrSwNsbuND_rvphke-cqeZvKXnSA3Bh0/edit?gid=0#gid=0" target="_blank">
                参考資料
            </a>
        </div>
        
        <!-- 登録件数の表示 -->
        <p>登録されている材料件数: <?php echo count($materials); ?> 件</p>

        <!-- ボタンを横に並べるためのコンテナ -->
        <div class="button-container">
            <!-- 材料登録ページへのリンクボタン -->
            <button onclick="window.location.href='material_form.php'">材料登録</button>
            <!-- 管理メニューに戻るボタン -->
            <button onclick="window.location.href='index.html'">管理メニューに戻る</button>
        </div>

        <table>
            <thead>
                <tr>
                    <th>材料名</th>
                    <th>単位</th>
                    <th>価格</th>
                    <th>編集</th>
                    <th>削除</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($materials)): ?>
                    <?php foreach ($materials as $index => $material): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($material['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($material['unit'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($material['price'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <a href="material_edit.php?id=<?php echo $index; ?>">編集</a>
                        </td>
                        <td>
                            <button onclick="confirmDelete(<?php echo $index; ?>)">削除</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5">材料データがありません。</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</body>
</html>
