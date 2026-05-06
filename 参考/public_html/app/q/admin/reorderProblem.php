<?php
header("Content-Type: application/json");

$setId = trim($_POST['setId']);
$problemIndex = intval($_POST['problemIndex']);
$direction = trim($_POST['direction']);

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

if($direction === "up" && $problemIndex > 0){
  // 前の問題と入れ替え
  $temp = $problems[$problemIndex - 1];
  $problems[$problemIndex - 1] = $problems[$problemIndex];
  $problems[$problemIndex] = $temp;
} elseif($direction === "down" && $problemIndex < count($problems)-1){
  // 次の問題と入れ替え
  $temp = $problems[$problemIndex + 1];
  $problems[$problemIndex + 1] = $problems[$problemIndex];
  $problems[$problemIndex] = $temp;
} else {
  echo json_encode(["success" => false, "error" => "これ以上並び替えできません。"]);
  exit;
}

file_put_contents($problemFile, json_encode($problems, JSON_PRETTY_PRINT));
echo json_encode(["success" => true]);
exit;
?>
