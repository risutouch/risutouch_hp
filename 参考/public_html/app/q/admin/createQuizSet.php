<?php
// エラー表示（開発中のみ）
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json");

$setName = trim($_POST['setName']);
if(!$setName) {
  echo json_encode(["success" => false, "error" => "問題集名がありません。"]);
  exit;
}

// 追加項目：URL の取得
$url = trim($_POST['url']);
if(!$url) {
  echo json_encode(["success" => false, "error" => "URLがありません。"]);
  exit;
}

$setId = time();  // 簡易なユニークID

// 保存先ディレクトリ（PHPファイルから見て、上の階層にあるものとする）
$dbDir  = "../db";  // JSONファイル用のディレクトリ
$imgDir = "../img"; // 画像保存用のディレクトリ

// dbディレクトリが存在しなければ作成
if (!is_dir($dbDir)) {
    mkdir($dbDir, 0777, true);
}

// アイコン画像の処理
if(isset($_FILES['setIcon']) && $_FILES['setIcon']['error'] === UPLOAD_ERR_OK) {
    // 画像は ../img/{$setId}/ に保存（実際の保存先には ../ を付与）
    $uploadDir = $imgDir . "/" . $setId . "/";
    if(!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    $ext = pathinfo($_FILES['setIcon']['name'], PATHINFO_EXTENSION);
    $newFileName = "icon_" . $setId . "." . $ext;
    $destination = $uploadDir . $newFileName;
    if(move_uploaded_file($_FILES['setIcon']['tmp_name'], $destination)) {
        // JSONに書き込むパスでは、../ を除いた "img/..." の形式にする
        $iconPath = "img/" . $setId . "/" . $newFileName;
    } else {
        echo json_encode(["success" => false, "error" => "アイコン画像の保存に失敗しました。"]);
        exit;
    }
} else {
    echo json_encode(["success" => false, "error" => "アイコン画像が選択されていません。"]);
    exit;
}

// db/quizSets.json の読み込み・追記
$quizSetsFile = $dbDir . "/quizSets.json";
$quizSets = [];
if(file_exists($quizSetsFile)) {
    $quizSets = json_decode(file_get_contents($quizSetsFile), true);
}
$newSet = [
    "id"   => (string)$setId,
    "name" => $setName,
    "icon" => $iconPath, // JSONに書き込むパス（../は含まない）
    "url"  => $url       // 追加項目：URL
];
$quizSets[] = $newSet;
file_put_contents($quizSetsFile, json_encode($quizSets, JSON_PRETTY_PRINT));

// 空の問題ファイル作成：db/[setId].json
$problemFile = $dbDir . "/" . $setId . ".json";
file_put_contents($problemFile, json_encode([], JSON_PRETTY_PRINT));

echo json_encode(["success" => true, "setId" => (string)$setId, "setName" => $setName, "url" => $url]);
exit;
?>
