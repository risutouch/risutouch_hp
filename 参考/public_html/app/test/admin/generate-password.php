<?php
/**
 * パスワードハッシュ生成スクリプト
 * 新しいパスワードのハッシュを生成します
 */

// 新しいパスワードを設定
$new_password = 'RisutouchAdmin2025!';

// Argon2IDでハッシュ化
$hash = password_hash($new_password, PASSWORD_ARGON2ID);

echo "=== パスワードハッシュ生成結果 ===\n";
echo "パスワード: " . $new_password . "\n";
echo "ハッシュ: " . $hash . "\n";
echo "\n";

// 検証テスト
$verify_test = password_verify($new_password, $hash);
echo "検証テスト: " . ($verify_test ? "成功" : "失敗") . "\n";

echo "\n=== index.php の設定コード ===\n";
echo '$ADMIN_PASSWORD_HASH = \'' . $hash . '\';' . "\n";

// さらにシンプルなパスワードも生成
$simple_password = 'admin2025';
$simple_hash = password_hash($simple_password, PASSWORD_ARGON2ID);

echo "\n=== シンプルなパスワード版 ===\n";
echo "パスワード: " . $simple_password . "\n";
echo "ハッシュ: " . $simple_hash . "\n";
echo '$ADMIN_PASSWORD_HASH = \'' . $simple_hash . '\';' . "\n";

// 検証テスト
$simple_verify = password_verify($simple_password, $simple_hash);
echo "検証テスト: " . ($simple_verify ? "成功" : "失敗") . "\n";

// 緊急用：管理者パスワード直接設定機能
echo "\n=== 緊急用：直接ログイン ===\n";
echo "以下のコードをindex.phpの先頭に一時的に追加すると、パスワードチェックをスキップできます：\n";
echo "/*\n";
echo "// 緊急用：強制ログイン（テスト後は必ず削除）\n";
echo "if (isset(\$_GET['emergency_login']) && \$_GET['emergency_login'] === 'risutouch2025') {\n";
echo "    session_regenerate_id(true);\n";
echo "    \$_SESSION['admin_logged_in'] = true;\n";
echo "    \$_SESSION['login_time'] = time();\n";
echo "    \$_SESSION['user_ip'] = \$_SERVER['REMOTE_ADDR'] ?? '';\n";
echo "    header('Location: index.php');\n";
echo "    exit;\n";
echo "}\n";
echo "*/\n";
echo "使用方法: index.php?emergency_login=risutouch2025\n";
?>