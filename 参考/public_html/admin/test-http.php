<?php
// HTTP環境テスト
echo "<h2>HTTP環境テスト</h2>";

// cURL テスト
echo "<h3>cURL:</h3>";
if (function_exists('curl_init')) {
    echo "✓ cURL拡張機能は有効<br>";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://httpbin.org/json');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($response && $httpCode === 200) {
        echo "✓ cURLでHTTPSリクエスト成功<br>";
    } else {
        echo "✗ cURLでHTTPSリクエスト失敗: HTTP{$httpCode}, Error: {$error}<br>";
    }
} else {
    echo "✗ cURL拡張機能が無効<br>";
}

// stream wrapper テスト
echo "<h3>Stream Wrapper:</h3>";
$wrappers = stream_get_wrappers();
echo "利用可能なラッパー: " . implode(', ', $wrappers) . "<br>";

if (in_array('https', $wrappers)) {
    echo "✓ HTTPSラッパーは有効<br>";
    
    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'ignore_errors' => true
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false
        ]
    ]);
    
    $response = @file_get_contents('https://httpbin.org/json', false, $context);
    
    if ($response) {
        echo "✓ file_get_contentsでHTTPSリクエスト成功<br>";
    } else {
        echo "✗ file_get_contentsでHTTPSリクエスト失敗<br>";
        if (isset($http_response_header)) {
            echo "レスポンスヘッダー: " . print_r($http_response_header, true) . "<br>";
        }
    }
} else {
    echo "✗ HTTPSラッパーが無効<br>";
}

// PHP設定確認
echo "<h3>PHP設定:</h3>";
echo "allow_url_fopen: " . (ini_get('allow_url_fopen') ? '有効' : '無効') . "<br>";
echo "user_agent: " . ini_get('user_agent') . "<br>";
echo "auto_detect_line_endings: " . (ini_get('auto_detect_line_endings') ? '有効' : '無効') . "<br>";

// OpenSSL確認
echo "<h3>OpenSSL:</h3>";
if (extension_loaded('openssl')) {
    echo "✓ OpenSSL拡張機能は有効<br>";
    echo "OpenSSLバージョン: " . OPENSSL_VERSION_TEXT . "<br>";
} else {
    echo "✗ OpenSSL拡張機能が無効<br>";
}

// 簡単なテスト実行
echo "<h3>実際のFacebook APIテスト:</h3>";
$test_url = "https://graph.facebook.com/v18.0/2811824335645782?fields=instagram_business_account&access_token=EAAZAOZC9ZBsmF4BPKe0ZAylBMykE5gvsZBuR0ltgoHBI7I1YBTmXJe0vRW6A2f2VfB2h1DhwZCnPqemMIgx3sio8rVcbk5ErW4ZCoB3yOk4iy6eatZB5K3fGZCRV1MQgaBUDqVcUJ8UxZAObyGpDf05rDLUrPJ26K1Ads8TMk3IFXy8udpDshq6vmcpE94Ubo6aC99kLJcUoh8evorbMFnHFFXUa74eS0I3bFYpqMX";

echo "テストURL: " . htmlspecialchars($test_url) . "<br><br>";

// cURLでテスト
if (function_exists('curl_init')) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $test_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    echo "<strong>cURL結果:</strong><br>";
    echo "HTTPコード: {$httpCode}<br>";
    if ($error) {
        echo "エラー: {$error}<br>";
    }
    if ($response) {
        echo "レスポンス: " . htmlspecialchars($response) . "<br>";
    }
}
?>