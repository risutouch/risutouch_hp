<?php
header("Content-Type: application/json");

$setId = trim($_POST['setId']);
$problemIndex = intval($_POST['problemIndex']);
$newQuestion = trim($_POST['question']);

if(!$setId){
  echo json_encode(["success" => false, "error" => "問題集IDがありません。"]);
  exit;
}

$problemFile = "db/" . $setId . ".json";
if(!file_exists($problemFile)){
  echo json_encode(["success" => false, "error" => "問題ファイルが見つかりません。"]);
  exit;
}

$problems = json_decode(file_get_contents($problemFile), true);
if(!isset($problems[$problemIndex])){
  echo json_encode(["success" => false, "error" => "指定された問題が存在しません。"]);
  exit;
}

// 更新（ここでは問題文のみ）
$problems[$problemIndex]['question'] = $newQuestion;

file_put_contents($problemFile, json_encode($problems, JSON_PRETTY_PRINT));
echo json_encode(["success" => true]);
exit;
?>
