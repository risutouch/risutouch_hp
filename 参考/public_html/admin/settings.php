<?php
// 直接アクセス防止
if (!defined('ADMIN_ACCESS') || !isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    http_response_code(403);
    die('Access Denied');
}

// エラー表示（デバッグ用）
ini_set('display_errors', 1);
error_reporting(E_ALL);

// CSRFトークンを取得（親から）
$csrf_token = $_SESSION['csrf_token'] ?? '';
if (empty($csrf_token) && function_exists('generateCSRFToken')) {
    $csrf_token = generateCSRFToken();
}

// 設定ファイルを読み込み
$configFile = '../assets/data/site_config.json';
$config = [];
if (file_exists($configFile)) {
    $jsonContent = file_get_contents($configFile);
    if ($jsonContent === false) {
        error_log("Failed to read config file: $configFile");
        $config = [];
    } else {
        $config = json_decode($jsonContent, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("JSON decode error: " . json_last_error_msg());
            $config = [];
        }
        $config = $config ?: [];
    }
} else {
    error_log("Config file not found: $configFile");
}

// デフォルト値を設定
$defaultConfig = [
    'site' => [
        'title' => '焼き菓子.りすたっち',
        'subtitle' => '山口県長門市の手作り焼き菓子専門店',
        'description' => '旬の素材を使った季節の焼き菓子を心を込めて手作り。委託販売・ギフト対応も承ります。',
        'keywords' => '焼き菓子,りすたっち,山口県,長門市,手作り,委託販売,クッキー,フロランタン,ギフト,お菓子,スイーツ',
        'url' => 'https://risutouch.com',
        'theme_color' => '#4a3c2a',
        'background_color' => '#ffffff'
    ],
    'contact' => [
        'email' => 'info@risutouch.com',
        'phone' => '',
        'address' => '山口県長門市',
        'instagram' => 'risutouch_official'
    ],
    'seo' => [
        'og_image' => 'assets/images/og-image.jpg',
        'twitter_card' => 'summary_large_image',
        'robots' => 'index, follow'
    ],
    'favicon' => [
        'enabled' => true,
        'favicon_ico' => 'assets/favicons/favicon.ico',
        'favicon_png' => 'assets/favicons/favicon.png',
        'apple_touch_icon' => 'assets/favicons/apple-touch-icon.png'
    ],
    'manifest' => [
        'name' => '焼き菓子.りすたっち',
        'short_name' => 'りすたっち',
        'display' => 'standalone'
    ]
];

// 設定をデフォルト値とマージ（安全版）
function safe_array_merge($default, $config) {
    $result = $default;
    foreach ($config as $key => $value) {
        if (is_array($value) && isset($result[$key]) && is_array($result[$key])) {
            $result[$key] = safe_array_merge($result[$key], $value);
        } else {
            $result[$key] = $value;
        }
    }
    return $result;
}

$config = safe_array_merge($defaultConfig, $config);
?>

<!-- Settings Tab -->
<div class="tab-header">
    <h2>基本設定</h2>
    <p class="tab-description">サイトの基本情報、SEO設定、favicon等を管理します</p>
</div>

<form method="POST" enctype="multipart/form-data" class="settings-form">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
    
    <!-- サイト基本情報 -->
    <div class="settings-section">
        <h3>🌐 サイト基本情報</h3>
        <div class="settings-grid">
            <div class="form-group">
                <label for="site_title">サイトタイトル</label>
                <input type="text" id="site_title" name="site[title]" value="<?php echo htmlspecialchars($config['site']['title']); ?>" required>
                <small>ブラウザのタイトルバーに表示されます</small>
            </div>
            
            <div class="form-group">
                <label for="site_subtitle">サブタイトル</label>
                <input type="text" id="site_subtitle" name="site[subtitle]" value="<?php echo htmlspecialchars($config['site']['subtitle']); ?>">
                <small>サイトの説明文として使用されます</small>
            </div>
            
            <div class="form-group full-width">
                <label for="site_description">サイト説明文</label>
                <textarea id="site_description" name="site[description]" rows="3" required><?php echo htmlspecialchars($config['site']['description']); ?></textarea>
                <small>検索エンジンの説明文として表示されます</small>
            </div>
            
            <div class="form-group full-width">
                <label for="site_keywords">キーワード</label>
                <input type="text" id="site_keywords" name="site[keywords]" value="<?php echo htmlspecialchars($config['site']['keywords']); ?>">
                <small>カンマ区切りで入力してください（例：焼き菓子,クッキー,ギフト）</small>
            </div>
            
            <div class="form-group">
                <label for="site_url">サイトURL</label>
                <input type="url" id="site_url" name="site[url]" value="<?php echo htmlspecialchars($config['site']['url']); ?>" required>
                <small>https://から始まる完全なURL</small>
            </div>
        </div>
    </div>

    <!-- カラー設定 -->
    <div class="settings-section">
        <h3>🎨 デザイン設定</h3>
        <div class="settings-grid">
            <div class="form-group">
                <label for="theme_color">テーマカラー</label>
                <div class="color-input-group">
                    <input type="color" id="theme_color" name="site[theme_color]" value="<?php echo htmlspecialchars($config['site']['theme_color']); ?>">
                    <input type="text" id="theme_color_text" value="<?php echo htmlspecialchars($config['site']['theme_color']); ?>" placeholder="#4a3c2a">
                </div>
                <small>スマートフォンのアドレスバーの色など</small>
            </div>
            
            <div class="form-group">
                <label for="background_color">背景色</label>
                <div class="color-input-group">
                    <input type="color" id="background_color" name="site[background_color]" value="<?php echo htmlspecialchars($config['site']['background_color']); ?>">
                    <input type="text" id="background_color_text" value="<?php echo htmlspecialchars($config['site']['background_color']); ?>" placeholder="#ffffff">
                </div>
                <small>PWAアプリの背景色</small>
            </div>
        </div>
    </div>

    <!-- 連絡先情報 -->
    <div class="settings-section">
        <h3>📞 連絡先情報</h3>
        <div class="alert alert-info" style="margin-bottom: 20px;">
            <strong>📧 メールアドレスについて</strong><br>
            ここで設定するのは<strong>お客様向けの連絡先メール</strong>です（ウェブサイトに表示）。<br>
            管理者認証用メール（risutouch@gmail.com）とは別のものです。
        </div>
        <div class="settings-grid">
            <div class="form-group">
                <label for="contact_email">メールアドレス（お客様向け連絡先）</label>
                <input type="email" id="contact_email" name="contact[email]" value="<?php echo htmlspecialchars($config['contact']['email']); ?>">
                <small>ウェブサイトの「お問い合わせ」に表示されます</small>
            </div>
            
            <div class="form-group">
                <label for="contact_phone">電話番号</label>
                <input type="tel" id="contact_phone" name="contact[phone]" value="<?php echo htmlspecialchars($config['contact']['phone']); ?>">
            </div>
            
            <div class="form-group">
                <label for="contact_address">住所</label>
                <input type="text" id="contact_address" name="contact[address]" value="<?php echo htmlspecialchars($config['contact']['address']); ?>">
            </div>
            
            <div class="form-group">
                <label for="contact_instagram">Instagram</label>
                <input type="text" id="contact_instagram" name="contact[instagram]" value="<?php echo htmlspecialchars($config['contact']['instagram']); ?>" placeholder="@アカウント名 または アカウント名">
            </div>
        </div>
    </div>

    <!-- SEO設定 -->
    <div class="settings-section">
        <h3>🔍 SEO設定</h3>
        <div class="settings-grid">
            <div class="form-group">
                <label for="og_image">OG画像</label>
                <input type="file" id="og_image" name="og_image" accept="image/*">
                <small>現在: <?php echo htmlspecialchars($config['seo']['og_image']); ?></small>
                <small>SNSでシェアされた時に表示される画像（推奨: 1200x630px）</small>
            </div>
            
            <div class="form-group">
                <label for="twitter_card">Twitterカードタイプ</label>
                <select id="twitter_card" name="seo[twitter_card]">
                    <option value="summary" <?php echo $config['seo']['twitter_card'] === 'summary' ? 'selected' : ''; ?>>通常</option>
                    <option value="summary_large_image" <?php echo $config['seo']['twitter_card'] === 'summary_large_image' ? 'selected' : ''; ?>>大きな画像</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="robots">検索エンジン設定</label>
                <select id="robots" name="seo[robots]">
                    <option value="index, follow" <?php echo $config['seo']['robots'] === 'index, follow' ? 'selected' : ''; ?>>インデックス許可</option>
                    <option value="noindex, nofollow" <?php echo $config['seo']['robots'] === 'noindex, nofollow' ? 'selected' : ''; ?>>インデックス拒否</option>
                </select>
            </div>
            
            <div class="form-group full-width">
                <label for="google_verification">Google Search Console認証コード</label>
                <input type="text" id="google_verification" name="seo[google_verification]" value="<?php echo htmlspecialchars($config['seo']['google_verification'] ?? ''); ?>" placeholder="google12345678901234567">
                <small>Google Search Consoleの「HTMLタグ」方式で表示されるcontent値を入力</small>
            </div>
        </div>
    </div>

    <!-- Favicon設定 -->
    <div class="settings-section">
        <h3>🎯 Favicon設定</h3>
        <div class="favicon-section">
            <div class="form-group">
                <label>
                    <input type="checkbox" name="favicon[enabled]" value="1" <?php echo $config['favicon']['enabled'] ? 'checked' : ''; ?>>
                    Faviconを有効にする
                </label>
            </div>
            
            <div class="favicon-uploads">
                <div class="form-group">
                    <label for="favicon_file">Faviconファイル</label>
                    <input type="file" id="favicon_file" name="favicon_file" accept="image/*">
                    <small>現在: <?php echo htmlspecialchars($config['favicon']['favicon_png']); ?></small>
                    <small>推奨: 正方形の画像（PNG/ICO形式、32x32px以上）</small>
                </div>
                
                <div class="form-group">
                    <label for="apple_icon_file">Apple Touch Icon</label>
                    <input type="file" id="apple_icon_file" name="apple_icon_file" accept="image/*">
                    <small>現在: <?php echo htmlspecialchars($config['favicon']['apple_touch_icon']); ?></small>
                    <small>推奨: 180x180pxのPNG画像</small>
                </div>
            </div>
            
            <div class="favicon-preview">
                <h4>現在のFavicon</h4>
                <div class="favicon-display">
                    <img src="../<?php echo htmlspecialchars($config['favicon']['favicon_png']); ?>" alt="Favicon" style="width: 32px; height: 32px;" onerror="this.style.display='none'">
                    <span>ブラウザタブでの表示イメージ</span>
                </div>
            </div>
        </div>
    </div>

    <!-- PWA設定 -->
    <div class="settings-section">
        <h3>📱 PWA設定</h3>
        <div class="settings-grid">
            <div class="form-group">
                <label for="manifest_name">アプリ名</label>
                <input type="text" id="manifest_name" name="manifest[name]" value="<?php echo htmlspecialchars($config['manifest']['name']); ?>">
                <small>フルネーム（ホーム画面に追加時の表示名）</small>
            </div>
            
            <div class="form-group">
                <label for="manifest_short_name">短縮名</label>
                <input type="text" id="manifest_short_name" name="manifest[short_name]" value="<?php echo htmlspecialchars($config['manifest']['short_name']); ?>">
                <small>アイコン下の表示名（12文字以下推奨）</small>
            </div>
            
            <div class="form-group">
                <label for="manifest_display">表示モード</label>
                <select id="manifest_display" name="manifest[display]">
                    <option value="standalone" <?php echo $config['manifest']['display'] === 'standalone' ? 'selected' : ''; ?>>アプリライク</option>
                    <option value="browser" <?php echo $config['manifest']['display'] === 'browser' ? 'selected' : ''; ?>>ブラウザ</option>
                    <option value="minimal-ui" <?php echo $config['manifest']['display'] === 'minimal-ui' ? 'selected' : ''; ?>>最小UI</option>
                </select>
            </div>
        </div>
    </div>

    <!-- 保存ボタン -->
    <div class="form-actions">
        <button type="submit" name="save_settings" class="btn btn-primary">
            💾 設定を保存
        </button>
        <button type="button" onclick="resetToDefaults()" class="btn btn-secondary">
            🔄 デフォルトに戻す
        </button>
    </div>
</form>

<style>
.settings-form {
    max-width: 1000px;
}

.settings-section {
    background: white;
    padding: 24px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    margin-bottom: 24px;
}

.settings-section h3 {
    color: var(--primary-color);
    margin-bottom: 20px;
    font-size: 1.4rem;
    border-bottom: 2px solid #f0f0f0;
    padding-bottom: 8px;
}

.settings-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

.settings-grid .form-group.full-width {
    grid-column: 1 / -1;
}

.color-input-group {
    display: flex;
    gap: 8px;
    align-items: center;
}

.color-input-group input[type="color"] {
    width: 40px;
    height: 40px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

.color-input-group input[type="text"] {
    flex: 1;
}

.favicon-section .favicon-uploads {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.favicon-preview {
    background: #f8f9fa;
    padding: 16px;
    border-radius: 6px;
    margin-top: 16px;
}

.favicon-display {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-top: 8px;
}

.favicon-display img {
    border: 1px solid #ddd;
    border-radius: 2px;
}

.form-actions {
    background: white;
    padding: 24px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    display: flex;
    gap: 16px;
    justify-content: center;
}

@media (max-width: 768px) {
    .settings-grid {
        grid-template-columns: 1fr;
    }
    
    .form-actions {
        flex-direction: column;
    }
}
</style>

<script>
// カラーピッカーとテキスト入力の同期
document.getElementById('theme_color').addEventListener('input', function() {
    document.getElementById('theme_color_text').value = this.value;
});

document.getElementById('theme_color_text').addEventListener('input', function() {
    document.getElementById('theme_color').value = this.value;
});

document.getElementById('background_color').addEventListener('input', function() {
    document.getElementById('background_color_text').value = this.value;
});

document.getElementById('background_color_text').addEventListener('input', function() {
    document.getElementById('background_color').value = this.value;
});

// デフォルト値に戻す
function resetToDefaults() {
    if (confirm('設定をデフォルト値に戻しますか？未保存の変更は失われます。')) {
        location.reload();
    }
}

// フォーム送信前の確認
document.querySelector('.settings-form').addEventListener('submit', function(e) {
    // 特に確認不要、そのまま送信
});
</script>