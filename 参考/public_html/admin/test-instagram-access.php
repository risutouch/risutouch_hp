<?php
// Instagram アクセステスト
echo "<h2>Instagram アクセステスト</h2>";

$username = 'risutouch';
$test_urls = [
    'Instagram公式' => "https://www.instagram.com/{$username}/",
    'RSSHub' => "https://rsshub.app/instagram/user/{$username}",
    'Bibliogram' => "https://bibliogram.art/u/{$username}/rss.xml",
    'HTTPBin（テスト用）' => "https://httpbin.org/json"
];

echo "<h3>1. URL アクセステスト</h3>";

foreach ($test_urls as $name => $url) {
    echo "<p><strong>{$name}:</strong><br>";
    echo "URL: {$url}<br>";
    
    // file_get_contents でテスト
    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'ignore_errors' => true
        ]
    ]);
    
    $start_time = microtime(true);
    $content = @file_get_contents($url, false, $context);
    $end_time = microtime(true);
    
    if ($content !== false) {
        $size = strlen($content);
        $time = round(($end_time - $start_time) * 1000, 2);
        echo "✅ 成功 - サイズ: {$size} bytes, 時間: {$time}ms<br>";
        echo "レスポンス先頭: " . htmlspecialchars(substr($content, 0, 200)) . "...<br>";
    } else {
        echo "❌ 失敗<br>";
        if (isset($http_response_header)) {
            echo "レスポンスヘッダー: " . implode(' | ', $http_response_header) . "<br>";
        }
    }
    echo "</p>";
}

echo "<h3>2. PHP設定確認</h3>";
echo "<p>";
echo "allow_url_fopen: " . (ini_get('allow_url_fopen') ? '✅ 有効' : '❌ 無効') . "<br>";
echo "user_agent: " . (ini_get('user_agent') ?: 'デフォルト') . "<br>";
echo "default_socket_timeout: " . ini_get('default_socket_timeout') . "秒<br>";
echo "auto_detect_line_endings: " . (ini_get('auto_detect_line_endings') ? '有効' : '無効') . "<br>";
echo "</p>";

echo "<h3>3. ネットワーク確認</h3>";
echo "<p>";

// 簡単な DNS 確認
$domains = ['www.instagram.com', 'rsshub.app', 'httpbin.org'];
foreach ($domains as $domain) {
    $ip = gethostbyname($domain);
    if ($ip !== $domain) {
        echo "✅ {$domain} → {$ip}<br>";
    } else {
        echo "❌ {$domain} → DNS解決失敗<br>";
    }
}

echo "</p>";

echo "<h3>4. Instagram ページ詳細チェック</h3>";
$instagram_url = "https://www.instagram.com/{$username}/";
$context = stream_context_create([
    'http' => [
        'timeout' => 15,
        'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
        'ignore_errors' => true,
        'follow_location' => true
    ]
]);

echo "<p>";
echo "URL: {$instagram_url}<br>";
$content = @file_get_contents($instagram_url, false, $context);

if ($content !== false) {
    echo "✅ Instagram ページアクセス成功<br>";
    echo "コンテンツサイズ: " . strlen($content) . " bytes<br>";
    
    // メタタグチェック
    if (preg_match('/<title>([^<]+)<\/title>/', $content, $matches)) {
        echo "ページタイトル: " . htmlspecialchars($matches[1]) . "<br>";
    }
    
    // sharedData チェック
    if (strpos($content, 'window._sharedData') !== false) {
        echo "✅ _sharedData 発見<br>";
    } else {
        echo "❌ _sharedData なし（新形式かログイン要求）<br>";
    }
    
    // エラーメッセージチェック
    if (strpos($content, 'Sorry, this page isn\'t available') !== false) {
        echo "❌ ページが利用できません（プライベートまたは存在しない）<br>";
    } elseif (strpos($content, 'Login') !== false || strpos($content, 'Sign up') !== false) {
        echo "⚠️ ログインが必要な可能性<br>";
    }
    
} else {
    echo "❌ Instagram ページアクセス失敗<br>";
    if (isset($http_response_header)) {
        echo "レスポンスヘッダー: " . implode(' | ', array_slice($http_response_header, 0, 3)) . "<br>";
    }
}
echo "</p>";

echo "<h3>5. 推奨事項</h3>";
echo "<ul>";
echo "<li>HTTPSアクセスが全て失敗する場合：OpenSSL設定やファイアウォールを確認</li>";
echo "<li>Instagramのみ失敗する場合：User-Agentやアクセス制限が原因</li>";
echo "<li>すべて成功するが投稿が取得できない場合：HTMLパースの問題</li>";
echo "</ul>";
?>