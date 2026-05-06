<?php
/**
 * Favicon生成スクリプト
 * logo.pngから各サイズのfaviconを生成します
 */

// 直接実行のみ許可
if (php_sapi_name() !== 'cli') {
    // Webブラウザからのアクセスの場合
    session_start();
    if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
        die('Access Denied');
    }
}

$sourceImage = '../assets/images/logo.png';
$faviconDir = '../assets/favicons';

// ディレクトリ作成
if (!file_exists($faviconDir)) {
    mkdir($faviconDir, 0755, true);
}

// ソース画像が存在するかチェック
if (!file_exists($sourceImage)) {
    die("ソース画像が見つかりません: $sourceImage\n");
}

// GDライブラリが利用可能かチェック
if (!extension_loaded('gd')) {
    die("GDライブラリが必要です\n");
}

// 画像を読み込み
$source = imagecreatefrompng($sourceImage);
if (!$source) {
    die("画像の読み込みに失敗しました\n");
}

// ソース画像のサイズを取得
$sourceWidth = imagesx($source);
$sourceHeight = imagesy($source);

echo "ソース画像: {$sourceWidth}x{$sourceHeight}\n";

// 生成するfaviconサイズ
$sizes = [
    16 => 'favicon-16x16.png',
    32 => 'favicon-32x32.png', 
    48 => 'favicon-48x48.png',
    64 => 'favicon-64x64.png',
    96 => 'favicon-96x96.png',
    128 => 'favicon-128x128.png',
    180 => 'apple-touch-icon.png',
    192 => 'android-chrome-192x192.png',
    512 => 'android-chrome-512x512.png'
];

$generatedFiles = [];

foreach ($sizes as $size => $filename) {
    // 新しい画像を作成
    $resized = imagecreatetruecolor($size, $size);
    
    // 透明度を保持
    imagealphablending($resized, false);
    imagesavealpha($resized, true);
    $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
    imagefill($resized, 0, 0, $transparent);
    
    // リサイズ
    imagecopyresampled(
        $resized, $source,
        0, 0, 0, 0,
        $size, $size, $sourceWidth, $sourceHeight
    );
    
    // 保存
    $outputPath = $faviconDir . '/' . $filename;
    if (imagepng($resized, $outputPath)) {
        echo "生成: {$filename} ({$size}x{$size})\n";
        $generatedFiles[] = $filename;
    } else {
        echo "エラー: {$filename} の生成に失敗\n";
    }
    
    imagedestroy($resized);
}

// favicon.icoを生成（16x16と32x32を含む）
$icoContent = '';

// 簡易的なICOファイル生成（実際にはより複雑な処理が必要）
// ここでは16x16のPNGをfavicon.icoとしてコピー
if (file_exists($faviconDir . '/favicon-16x16.png')) {
    copy($faviconDir . '/favicon-16x16.png', $faviconDir . '/favicon.ico');
    echo "生成: favicon.ico\n";
    $generatedFiles[] = 'favicon.ico';
}

// Web App Manifestファイルを生成
$manifest = [
    'name' => '焼き菓子.りすたっち',
    'short_name' => 'りすたっち',
    'description' => '山口県長門市の手作り焼き菓子専門店',
    'start_url' => '/',
    'display' => 'standalone',
    'background_color' => '#ffffff',
    'theme_color' => '#4a3c2a',
    'icons' => [
        [
            'src' => 'assets/favicons/android-chrome-192x192.png',
            'sizes' => '192x192',
            'type' => 'image/png'
        ],
        [
            'src' => 'assets/favicons/android-chrome-512x512.png', 
            'sizes' => '512x512',
            'type' => 'image/png'
        ]
    ]
];

file_put_contents('../manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "生成: manifest.json\n";

// ブラウザ設定ファイルを生成
$browserConfig = '<?xml version="1.0" encoding="utf-8"?>
<browserconfig>
    <msapplication>
        <tile>
            <square150x150logo src="assets/favicons/favicon-128x128.png"/>
            <TileColor>#4a3c2a</TileColor>
        </tile>
    </msapplication>
</browserconfig>';

file_put_contents('../browserconfig.xml', $browserConfig);
echo "生成: browserconfig.xml\n";

// HTMLのheadタグに追加するコードを生成
$htmlCode = '<!-- Favicon -->
<link rel="icon" type="image/x-icon" href="assets/favicons/favicon.ico">
<link rel="icon" type="image/png" sizes="16x16" href="assets/favicons/favicon-16x16.png">
<link rel="icon" type="image/png" sizes="32x32" href="assets/favicons/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="48x48" href="assets/favicons/favicon-48x48.png">
<link rel="icon" type="image/png" sizes="64x64" href="assets/favicons/favicon-64x64.png">
<link rel="icon" type="image/png" sizes="96x96" href="assets/favicons/favicon-96x96.png">
<link rel="icon" type="image/png" sizes="128x128" href="assets/favicons/favicon-128x128.png">
<link rel="apple-touch-icon" sizes="180x180" href="assets/favicons/apple-touch-icon.png">
<link rel="manifest" href="manifest.json">
<meta name="msapplication-config" content="browserconfig.xml">
<meta name="theme-color" content="#4a3c2a">';

file_put_contents($faviconDir . '/favicon-html.txt', $htmlCode);
echo "生成: favicon-html.txt（HTMLに追加するコード）\n";

// メモリ解放
imagedestroy($source);

echo "\nfavicon生成完了！\n";
echo "生成されたファイル数: " . count($generatedFiles) . "\n";
echo "\n=== 次の手順 ===\n";
echo "1. favicon-html.txt の内容をindex.htmlとadmin/index.phpの<head>タグに追加\n";
echo "2. manifest.jsonとbrowserconfig.xmlがルートディレクトリに配置されていることを確認\n";
echo "3. ブラウザでサイトを確認\n";

if (php_sapi_name() !== 'cli') {
    echo "<br><br><strong>favicon-html.txt の内容:</strong><br>";
    echo "<pre>" . htmlspecialchars($htmlCode) . "</pre>";
}
?>