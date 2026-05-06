<?php
// deleteQuizSet.php
header("Content-Type: application/json");

/**
 * 指定されたディレクトリを再帰的に削除する関数
 */
function rrmdir($dir) {
    if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != "." && $object != "..") {
                $path = $dir . DIRECTORY_SEPARATOR . $object;
                if (is_dir($path) && !is_link($path)) {
                    rrmdir($path);
                } else {
                    unlink($path);
                }
            }
        }
        rmdir($dir);
    }
}

// POSTで送られてくる問題集IDを取得
$setId = isset($_POST['setId']) ? trim($_POST['setId']) : '';
if (!$setId) {
    echo json_encode(["success" => false, "error" => "問題集IDがありません。"]);
    exit;
}

// 1. ../db/quizSets.json から該当する問題集を削除
$quizSetsFile = "../db/quizSets.json";
$quizSets = [];
if (file_exists($quizSetsFile)) {
    $quizSets = json_decode(file_get_contents($quizSetsFile), true);
}
$found = false;
foreach ($quizSets as $key => $set) {
    if ($set['id'] === $setId) {
        $found = true;
        unset($quizSets[$key]);
        break;
    }
}
if (!$found) {
    echo json_encode(["success" => false, "error" => "指定された問題集が見つかりません。"]);
    exit;
}
$quizSets = array_values($quizSets);
file_put_contents($quizSetsFile, json_encode($quizSets, JSON_PRETTY_PRINT));

// 2. ../db/[setId].json も削除
$problemFile = "../db/" . $setId . ".json";
if (file_exists($problemFile)) {
    unlink($problemFile);
}

// 3. ../img フォルダ内の該当問題集フォルダも削除（例：img/{setId}）
$imgDir = "../img/" . $setId;
if (is_dir($imgDir)) {
    rrmdir($imgDir);
}

echo json_encode(["success" => true]);
exit;
?>
