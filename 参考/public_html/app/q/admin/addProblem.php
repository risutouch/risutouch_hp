<?php
header("Content-Type: application/json");
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 問題集IDのチェック
$setId = trim($_POST['setId']);
if (!$setId) {
    echo json_encode(["success" => false, "error" => "問題集IDがありません。"]);
    exit;
}

// 画像アップロード先ディレクトリの指定
// ※実際の保存先は、PHPファイルから見て一個手前の階層にある img フォルダ
$uploadDir = "../img/" . $setId . "/";
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// 問題文と問題画像の処理
$question = trim($_POST['question']);
$questionImagePath = "";
if (isset($_FILES['questionImage']) && $_FILES['questionImage']['error'] === UPLOAD_ERR_OK) {
    $ext = pathinfo($_FILES['questionImage']['name'], PATHINFO_EXTENSION);
    $newFileName = "question_" . time() . "." . $ext;
    $destination = $uploadDir . $newFileName;
    if (move_uploaded_file($_FILES['questionImage']['tmp_name'], $destination)) {
        // JSONに書き込むパスは、上位階層指定（../）を除いたパス
        $questionImagePath = "img/" . $setId . "/" . $newFileName;
    }
}

// 各解答の処理（テキストと画像の両方を処理）
// ※JSONに書き込むパスは "../" を除いた形にする
function processAnswer($textField, $fileField, $uploadDir, $setId) {
    $text = trim($_POST[$textField]);
    $imagePath = "";
    if (isset($_FILES[$fileField]) && $_FILES[$fileField]['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES[$fileField]['name'], PATHINFO_EXTENSION);
        $newFileName = $fileField . "_" . time() . "." . $ext;
        $destination = $uploadDir . $newFileName;
        if (move_uploaded_file($_FILES[$fileField]['tmp_name'], $destination)) {
            $imagePath = "img/" . $setId . "/" . $newFileName;
        }
    }
    return ["text" => $text, "image" => $imagePath];
}

$answer1 = processAnswer('answer1Text', 'answer1Image', $uploadDir, $setId);
$answer2 = processAnswer('answer2Text', 'answer2Image', $uploadDir, $setId);
$answer3 = processAnswer('answer3Text', 'answer3Image', $uploadDir, $setId);

// 各解答について、テキストまたは画像のどちらかが入力されているかチェック
if (empty($answer1['text']) && empty($answer1['image'])) {
    echo json_encode(["success" => false, "error" => "正解（解答1）のテキストまたは画像を入力してください。"]);
    exit;
}
if (empty($answer2['text']) && empty($answer2['image'])) {
    echo json_encode(["success" => false, "error" => "解答2のテキストまたは画像を入力してください。"]);
    exit;
}
if (empty($answer3['text']) && empty($answer3['image'])) {
    echo json_encode(["success" => false, "error" => "解答3のテキストまたは画像を入力してください。"]);
    exit;
}

// 新しい問題データの作成
$newProblem = [
    "question"      => $question,
    "questionImage" => $questionImagePath,
    "answers"       => [
                         $answer1,
                         $answer2,
                         $answer3
                       ]
];

// 問題集ファイル（JSON）は、一個手前の階層にある db フォルダ内に保存
$problemFile = "../db/" . $setId . ".json";
$problems = [];
if (file_exists($problemFile)) {
    $problems = json_decode(file_get_contents($problemFile), true);
}
$problems[] = $newProblem;
file_put_contents($problemFile, json_encode($problems, JSON_PRETTY_PRINT));

// 成功レスポンスの返却
echo json_encode(["success" => true, "problems" => $problems]);
exit;
?>
