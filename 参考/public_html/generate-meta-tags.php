<?php
// 設定ファイルから動的にメタタグを生成
function generateMetaTags() {
    $configFile = 'assets/data/site_config.json';
    if (!file_exists($configFile)) {
        return '';
    }
    
    $config = json_decode(file_get_contents($configFile), true);
    if (!$config) {
        return '';
    }
    
    $metaTags = '';
    
    // Google Search Console認証タグ
    if (!empty($config['seo']['google_verification'])) {
        $metaTags .= '<meta name="google-site-verification" content="' . htmlspecialchars($config['seo']['google_verification']) . '">' . "\n    ";
    }
    
    // その他の動的メタタグをここに追加可能
    
    return $metaTags;
}

// HTMLファイルの更新（管理画面から呼び出し用）
function updateHtmlMetaTags() {
    $htmlFile = 'index.html';
    if (!file_exists($htmlFile)) {
        return false;
    }
    
    $html = file_get_contents($htmlFile);
    $metaTags = generateMetaTags();
    
    // Google認証タグのプレースホルダーを置換
    $pattern = '/<!-- google-site-verification メタタグは設定で追加されます -->/';
    $replacement = trim($metaTags);
    
    if (empty($replacement)) {
        $replacement = '<!-- google-site-verification メタタグは設定で追加されます -->';
    }
    
    $html = preg_replace($pattern, $replacement, $html);
    
    return file_put_contents($htmlFile, $html) !== false;
}
?>