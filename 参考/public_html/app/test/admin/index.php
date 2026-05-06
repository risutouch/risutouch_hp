<?php
session_start();

// 管理画面アクセス許可フラグ
define('ADMIN_ACCESS', true);


// 本番環境設定（セキュリティ強化）
error_reporting(E_ALL);
ini_set('display_errors', 0); // 本番環境ではエラー表示を無効
ini_set('log_errors', 1);

// 設定
// 新しいパスワード: admin2025
$ADMIN_PASSWORD_HASH = '$argon2id$v=19$m=65536,t=4,p=1$Q2dENklHbU15eWhUS3hTMg$tYNUneT2ii3DriWy3tT1Ek6gdKzv2ZIpUikOZlDPu74';
$ADMIN_EMAIL = 'risutouch@gmail.com';
$DATA_DIR = '../assets/data/';
$UPLOAD_DIR = '../assets/images/';
$LOGIN_ATTEMPTS_FILE = '../assets/data/login_attempts.json';
$PASSWORD_CONFIG_FILE = '../assets/data/admin_config.json';


// CSRF トークン生成
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// ブルートフォース攻撃対策
function checkLoginAttempts($ip) {
    global $LOGIN_ATTEMPTS_FILE;
    
    if (!file_exists($LOGIN_ATTEMPTS_FILE)) {
        file_put_contents($LOGIN_ATTEMPTS_FILE, json_encode([]));
        return true;
    }
    
    $attempts = json_decode(file_get_contents($LOGIN_ATTEMPTS_FILE), true) ?: [];
    
    if (isset($attempts[$ip])) {
        // 5回失敗で15分間ロック
        if ($attempts[$ip]['count'] >= 5) {
            if (time() - $attempts[$ip]['time'] < 900) { // 15分
                return false;
            }
        }
    }
    return true;
}

function recordLoginAttempt($ip, $success) {
    global $LOGIN_ATTEMPTS_FILE;
    
    $attempts = [];
    if (file_exists($LOGIN_ATTEMPTS_FILE)) {
        $attempts = json_decode(file_get_contents($LOGIN_ATTEMPTS_FILE), true) ?: [];
    }
    
    if ($success) {
        // 成功時は記録を削除
        unset($attempts[$ip]);
    } else {
        // 失敗時は回数を増加
        if (!isset($attempts[$ip])) {
            $attempts[$ip] = ['count' => 0, 'time' => time()];
        }
        $attempts[$ip]['count']++;
        $attempts[$ip]['time'] = time();
    }
    
    file_put_contents($LOGIN_ATTEMPTS_FILE, json_encode($attempts));
}

// セッションタイムアウトチェック
function checkSessionTimeout() {
    if (isset($_SESSION['admin_logged_in']) && isset($_SESSION['login_time'])) {
        // 2時間でタイムアウト
        if (time() - $_SESSION['login_time'] > 7200) {
            session_destroy();
            return false;
        }
    }
    return true;
}

// パスワード管理関数
function getCurrentPasswordHash() {
    global $PASSWORD_CONFIG_FILE, $ADMIN_PASSWORD_HASH;
    
    if (file_exists($PASSWORD_CONFIG_FILE)) {
        $config = json_decode(file_get_contents($PASSWORD_CONFIG_FILE), true);
        if (isset($config['password_hash'])) {
            return $config['password_hash'];
        }
    }
    return $ADMIN_PASSWORD_HASH; // デフォルトパスワード
}

function saveNewPasswordHash($new_hash) {
    global $PASSWORD_CONFIG_FILE;
    
    $config = [];
    if (file_exists($PASSWORD_CONFIG_FILE)) {
        $config = json_decode(file_get_contents($PASSWORD_CONFIG_FILE), true) ?: [];
    }
    
    $config['password_hash'] = $new_hash;
    $config['updated'] = date('Y-m-d H:i:s');
    
    return file_put_contents($PASSWORD_CONFIG_FILE, json_encode($config, JSON_PRETTY_PRINT));
}

function generatePasswordResetToken() {
    $token = bin2hex(random_bytes(32));
    $expires = time() + 1800; // 30分有効
    
    global $PASSWORD_CONFIG_FILE;
    $config = [];
    if (file_exists($PASSWORD_CONFIG_FILE)) {
        $config = json_decode(file_get_contents($PASSWORD_CONFIG_FILE), true) ?: [];
    }
    
    $config['reset_token'] = $token;
    $config['reset_expires'] = $expires;
    $config['reset_requested'] = date('Y-m-d H:i:s');
    
    file_put_contents($PASSWORD_CONFIG_FILE, json_encode($config, JSON_PRETTY_PRINT));
    
    return $token;
}

function validatePasswordResetToken($token) {
    global $PASSWORD_CONFIG_FILE;
    
    if (!file_exists($PASSWORD_CONFIG_FILE)) {
        return false;
    }
    
    $config = json_decode(file_get_contents($PASSWORD_CONFIG_FILE), true);
    
    if (!isset($config['reset_token']) || !isset($config['reset_expires'])) {
        return false;
    }
    
    if ($config['reset_token'] !== $token || time() > $config['reset_expires']) {
        return false;
    }
    
    return true;
}

function clearPasswordResetToken() {
    global $PASSWORD_CONFIG_FILE;
    
    if (file_exists($PASSWORD_CONFIG_FILE)) {
        $config = json_decode(file_get_contents($PASSWORD_CONFIG_FILE), true) ?: [];
        unset($config['reset_token']);
        unset($config['reset_expires']);
        unset($config['reset_requested']);
        file_put_contents($PASSWORD_CONFIG_FILE, json_encode($config, JSON_PRETTY_PRINT));
    }
}

function sendPasswordResetEmail($email, $token) {
    $reset_url = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/index.php?reset_token=" . $token;
    
    $subject = "りすたっち管理画面 - パスワードリセット";
    $message = "
パスワードリセットが要求されました。

以下のリンクをクリックして新しいパスワードを設定してください：
{$reset_url}

このリンクは30分間有効です。

もしこの要求をしていない場合は、このメールを無視してください。

りすたっち管理システム
";
    
    $headers = "From: info@risutouch.com\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $headers .= "Reply-To: info@risutouch.com\r\n";
    
    // ログに出力（開発・デバッグ用）
    error_log("=== パスワードリセットメール送信 ===");
    error_log("送信先: $email");
    error_log("リセットURL: $reset_url");
    error_log("トークン: $token");
    
    // ConoHaメールサーバーで送信を試行
    $conoha_result = sendViaConoHaSMTP($email, $subject, $message);
    if ($conoha_result) {
        return true;
    }
    
    // 標準mail()関数で送信を試行
    $standard_result = sendViaStandardMail($email, $subject, $message);
    if ($standard_result) {
        return true;
    }
    
    // 開発環境：コンソールにリセットURLを表示
    echo "<script>console.log('パスワードリセットURL: $reset_url');</script>";
    
    return true; // 開発環境では常に成功
}

function sendEmailVerification($email, $code) {
    $subject = "りすたっち管理画面 - ログイン認証コード";
    $message = "
ログイン認証コードです。

認証コード: {$code}

このコードは10分間有効です。
第三者に教えないでください。

もしこのリクエストをしていない場合は、このメールを無視してください。

りすたっち管理システム
";
    
    $headers = "From: info@risutouch.com\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $headers .= "Reply-To: info@risutouch.com\r\n";
    
    // ログに出力（デバッグ用）
    error_log("=== メール認証コード送信開始 ===");
    error_log("送信先: $email");
    error_log("認証コード: $code");
    
    // ConoHaメールサーバー経由で送信
    error_log("ConoHaメールサーバーを試行");
    $conoha_result = sendViaConoHaSMTP($email, $subject, $message);
    if ($conoha_result) {
        return true;
    }
    
    // レンタルサーバーの標準mail()関数
    error_log("標準mail()関数を試行");
    $standard_result = sendViaStandardMail($email, $subject, $message);
    if ($standard_result) {
        return true;
    }
    
    // すべて失敗した場合はfalseを返す
    error_log("All email sending methods failed");
    return false;
}



function sendViaConoHaSMTP($email, $subject, $message) {
    // ConoHaメールサーバーのSMTP設定
    error_log("ConoHa SMTP設定開始: mail37.conoha.ne.jp:587");
    ini_set('SMTP', 'mail37.conoha.ne.jp');
    ini_set('smtp_port', '587'); // または '25'
    ini_set('sendmail_from', 'info@risutouch.com');
    
    $headers = "From: info@risutouch.com\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $headers .= "Reply-To: info@risutouch.com\r\n";
    
    error_log("ConoHa mail()関数実行: " . $email);
    $result = @mail($email, $subject, $message, $headers);
    
    if ($result) {
        error_log("✓ Mail sent successfully via ConoHa SMTP");
        return true;
    }
    
    $last_error = error_get_last();
    error_log("✗ ConoHa SMTP mail sending failed. Last error: " . ($last_error ? $last_error['message'] : 'Unknown'));
    return false;
}

function sendViaStandardMail($email, $subject, $message) {
    // レンタルサーバーの標準mail()関数を使用
    $headers = "From: info@risutouch.com\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $headers .= "Reply-To: info@risutouch.com\r\n";
    
    $result = @mail($email, $subject, $message, $headers);
    
    if ($result) {
        error_log("Mail sent successfully via standard mail() function");
        return true;
    }
    
    error_log("Standard mail() function failed");
    return false;
}



// TOTP関連関数
function getTOTPSecret() {
    global $PASSWORD_CONFIG_FILE;
    
    if (!file_exists($PASSWORD_CONFIG_FILE)) {
        return null;
    }
    
    $config = json_decode(file_get_contents($PASSWORD_CONFIG_FILE), true);
    return $config['totp_secret'] ?? null;
}

function saveTOTPSecret($secret) {
    global $PASSWORD_CONFIG_FILE;
    
    $config = [];
    if (file_exists($PASSWORD_CONFIG_FILE)) {
        $config = json_decode(file_get_contents($PASSWORD_CONFIG_FILE), true) ?: [];
    }
    
    $config['totp_secret'] = $secret;
    $config['totp_setup_date'] = date('Y-m-d H:i:s');
    
    return file_put_contents($PASSWORD_CONFIG_FILE, json_encode($config, JSON_PRETTY_PRINT));
}

function isTOTPEnabled() {
    return getTOTPSecret() !== null;
}

function setupTOTP() {
    $secret = TOTPHelper::generateSecret();
    saveTOTPSecret($secret);
    return $secret;
}

function verifyTOTP($code) {
    $secret = getTOTPSecret();
    if (!$secret) {
        return false;
    }
    
    return TOTPHelper::verifyTOTP($secret, $code);
}

function getTOTPQRCode($secret) {
    return TOTPHelper::getQRCodeURL('りすたっち管理者', $secret, 'りすたっち管理画面');
}



$csrf_token = generateCSRFToken();


// ログイン処理
if (isset($_POST['login'])) {
    $client_ip = $_SERVER['REMOTE_ADDR'] ?? '';
    
    // CSRF トークン検証
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'セキュリティトークンが無効です';
    }
    // ブルートフォース攻撃チェック
    elseif (!checkLoginAttempts($client_ip)) {
        $error = '試行回数が上限に達しました。15分後に再試行してください';
    }
    // メールアドレスとパスワード検証
    elseif ($_POST['email'] !== $ADMIN_EMAIL) {
        $error = 'メールアドレスが正しくありません';
        recordLoginAttempt($client_ip, false);
        error_log("Invalid email attempted: " . ($_POST['email'] ?? 'empty'));
    }
    elseif (password_verify($_POST['password'], getCurrentPasswordHash())) {
        // メール認証コードを送信
        $email_code = sprintf('%06d', random_int(0, 999999));
        $_SESSION['email_verification_code'] = $email_code;
        $_SESSION['email_verification_expires'] = time() + 600; // 10分有効
        $_SESSION['email_verification_email'] = $_POST['email'];
        
        // メール送信（開発環境用）
        $sent = sendEmailVerification($_POST['email'], $email_code);
        
        if ($sent) {
            $_SESSION['message'] = 'メールアドレスに認証コードを送信しました。メールをご確認ください。';
            error_log("Email verification code sent to: " . $_POST['email']);
            
            // 認証コード入力画面にリダイレクト
            header('Location: index.php?verify_email=1');
            exit;
        } else {
            // メール送信失敗時
            $_SESSION['message'] = 'メール送信に失敗しました。サーバー管理者にお問い合わせください。<br><small>【緊急用】認証コード: ' . $email_code . '</small>';
            error_log("Email sending failed - showing code directly: " . $email_code);
            
            header('Location: index.php?verify_email=1');
            exit;
        }
    } else {
        $error = 'パスワードが間違っています';
        recordLoginAttempt($client_ip, false);
        
        // ログ記録
        error_log("Admin login failed from IP: " . $client_ip);
    }
}

// メール認証コード検証処理
if (isset($_POST['verify_email_code'])) {
    $client_ip = $_SERVER['REMOTE_ADDR'] ?? '';
    
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'セキュリティトークンが無効です';
    } elseif (!isset($_SESSION['email_verification_code']) || !isset($_SESSION['email_verification_expires'])) {
        $error = '認証セッションが見つかりません。最初からやり直してください';
    } elseif (time() > $_SESSION['email_verification_expires']) {
        $error = '認証コードの有効期限が切れました。再度ログインしてください';
        unset($_SESSION['email_verification_code']);
        unset($_SESSION['email_verification_expires']);
        unset($_SESSION['email_verification_email']);
    } elseif ($_POST['email_code'] !== $_SESSION['email_verification_code']) {
        $error = '認証コードが正しくありません';
        recordLoginAttempt($client_ip, false);
        error_log("Email verification failed from IP: " . $client_ip);
    } else {
        // メール認証成功 - ログイン完了
        session_regenerate_id(true);
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['login_time'] = time();
        $_SESSION['user_ip'] = $client_ip;
        $_SESSION['user_email'] = $_SESSION['email_verification_email'];
        $_SESSION['login_method'] = 'email';
        
        // 認証情報をクリア
        unset($_SESSION['email_verification_code']);
        unset($_SESSION['email_verification_expires']);
        unset($_SESSION['email_verification_email']);
        
        recordLoginAttempt($client_ip, true);
        error_log("Email verification successful from IP: " . $client_ip);
        
        header('Location: index.php');
        exit;
    }
}

// パスワードリセット要求処理
if (isset($_POST['request_reset'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'セキュリティトークンが無効です';
    } elseif (isset($_POST['email']) && $_POST['email'] === $ADMIN_EMAIL) {
        $token = generatePasswordResetToken();
        if (sendPasswordResetEmail($ADMIN_EMAIL, $token)) {
            $reset_url = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/index.php?reset_token=" . $token;
            $_SESSION['message'] = '開発環境：パスワードリセットリンクを生成しました。<br><a href="' . htmlspecialchars($reset_url) . '" target="_blank">リセットリンクはこちら</a><br><small>※本番環境ではメールで送信されます</small>';
            error_log("Password reset requested for email: " . $ADMIN_EMAIL);
            
            // POST-Redirect-GET パターンでブラウザ更新時の重複送信を防止
            header('Location: index.php?forgot=1');
            exit;
        } else {
            $error = 'メール送信に失敗しました';
        }
    } else {
        $error = 'メールアドレスが正しくありません';
        error_log("Invalid email for password reset: " . ($_POST['email'] ?? 'empty'));
    }
}

// パスワードリセット実行処理
if (isset($_POST['reset_password']) && isset($_GET['reset_token'])) {
    $token = $_GET['reset_token'];
    
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'セキュリティトークンが無効です';
    } elseif (!validatePasswordResetToken($token)) {
        $error = 'リセットトークンが無効または期限切れです';
    } elseif (empty($_POST['new_password']) || strlen($_POST['new_password']) < 8) {
        $error = 'パスワードは8文字以上である必要があります';
    } elseif ($_POST['new_password'] !== $_POST['confirm_password']) {
        $error = 'パスワードが一致しません';
    } else {
        $new_hash = password_hash($_POST['new_password'], PASSWORD_ARGON2ID);
        if (saveNewPasswordHash($new_hash)) {
            clearPasswordResetToken();
            $message = 'パスワードが正常に変更されました';
            error_log("Password successfully changed");
            
            // リセット後はリダイレクト
            $_SESSION['message'] = $message;
            header('Location: index.php');
            exit;
        } else {
            $error = 'パスワードの保存に失敗しました';
        }
    }
}




// ログアウト処理
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

// ログイン状態チェック（セッションタイムアウト含む）
$isLoggedIn = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] && checkSessionTimeout();

// セッションハイジャック対策（IPアドレスチェック）
if ($isLoggedIn && isset($_SESSION['user_ip'])) {
    $current_ip = $_SERVER['REMOTE_ADDR'] ?? '';
    if ($_SESSION['user_ip'] !== $current_ip) {
        session_destroy();
        $isLoggedIn = false;
        $error = 'セッションが無効になりました。再度ログインしてください';
        error_log("Session hijacking attempt detected. Session IP: " . $_SESSION['user_ip'] . ", Current IP: " . $current_ip);
    }
}

// セッションからメッセージを取得（POSTリクエストの直後のみ表示）
$message = '';
$show_message = false;

// リダイレクト後の最初のGETリクエストでのみメッセージを表示
if (isset($_SESSION['message']) && !empty(trim($_SESSION['message']))) {
    // 前回のリクエストがPOSTで、今回がGETの場合のみ表示
    if (isset($_SESSION['last_was_post']) && $_SESSION['last_was_post'] && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $message = $_SESSION['message'];
        $show_message = true;
        unset($_SESSION['message']);
        unset($_SESSION['last_was_post']);
    } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // 既にGETリクエストが済んでいる場合はクリア
        unset($_SESSION['message']);
        unset($_SESSION['last_was_post']);
    }
}

// POSTリクエストの場合はフラグを設定
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['last_was_post'] = true;
}

// データ処理
if ($isLoggedIn) {
    // 商品データ処理
    if (isset($_POST['save_product'])) {
        $result = saveProduct($_POST, $_FILES);
        $message = $result['success'] ? '商品を保存しました' : '保存に失敗しました: ' . $result['error'];
        
        // POST-Redirect-GET パターンでブラウザ更新時の重複送信を防止
        $_SESSION['message'] = $message;
        header('Location: index.php?tab=products');
        exit;
    }
    
    // 店舗データ処理
    if (isset($_POST['save_shop'])) {
        $result = saveShop($_POST, $_FILES);
        $message = $result['success'] ? '店舗を保存しました' : '保存に失敗しました: ' . $result['error'];
        
        // POST-Redirect-GET パターンでブラウザ更新時の重複送信を防止
        $_SESSION['message'] = $message;
        header('Location: index.php?tab=shops');
        exit;
    }
    
    // データ削除処理
    if (isset($_GET['delete'])) {
        $result = deleteItem($_GET['delete'], $_GET['type']);
        $message = $result['success'] ? '削除しました' : '削除に失敗しました: ' . $result['error'];
        
        // リダイレクトで重複削除を防止
        $_SESSION['message'] = $message;
        $currentTab = $_GET['tab'] ?? 'products';
        header("Location: index.php?tab=$currentTab");
        exit;
    }
    
    
    // お知らせ保存
    if (isset($_POST['save_news'])) {
        $result = saveNews($_POST);
        $message = $result['success'] ? 'お知らせを保存しました' : '保存に失敗しました: ' . $result['error'];
    }
    
    // メニュー表保存
    if (isset($_POST['save_menu'])) {
        $result = saveMenu($_POST, $_FILES);
        $message = $result['success'] ? 'メニュー表を保存しました' : '保存に失敗しました: ' . $result['error'];
    }
    
    // 商品表示/非表示切り替え
    if (isset($_POST['action']) && $_POST['action'] === 'toggle_product_visible') {
        $result = toggleProductVisible($_POST['product_name'], $_POST['visible'] === '1');
        if (!$result['success']) {
            http_response_code(500);
        }
        exit;
    }
    
    // 店舗表示/非表示切り替え
    if (isset($_POST['action']) && $_POST['action'] === 'toggle_shop_visible') {
        $result = toggleShopVisible($_POST['shop_id'], $_POST['visible'] === '1');
        if (!$result['success']) {
            http_response_code(500);
        }
        exit;
    }
    
    // 商品順序変更（AJAX）
    if (isset($_POST['action']) && $_POST['action'] === 'update_product_order') {
        $result = updateProductOrder($_POST['product_name'], (int)$_POST['new_order']);
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }
    
    // 店舗順序変更（AJAX）
    if (isset($_POST['action']) && $_POST['action'] === 'update_shop_order') {
        $result = updateShopOrder($_POST['shop_id'], (int)$_POST['new_order']);
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }
    
    // データ読み込み（order初期化含む）
    $products = initializeProductOrder();
    $shops = initializeShopOrder();
    $news = loadNews();
    $menu = loadMenu();
}

// 関数定義
function loadProducts() {
    global $DATA_DIR;
    $file = $DATA_DIR . 'products.json';
    if (file_exists($file)) {
        return json_decode(file_get_contents($file), true);
    }
    return ['products' => []];
}

function loadShops() {
    global $DATA_DIR;
    $file = $DATA_DIR . 'shops.json';
    if (file_exists($file)) {
        return json_decode(file_get_contents($file), true);
    }
    return ['shops' => []];
}

function loadNews() {
    global $DATA_DIR;
    $file = $DATA_DIR . 'news.json';
    if (file_exists($file)) {
        return json_decode(file_get_contents($file), true);
    }
    return ['news' => []];
}

function loadMenu() {
    global $DATA_DIR;
    $file = $DATA_DIR . 'menu.json';
    if (file_exists($file)) {
        return json_decode(file_get_contents($file), true);
    }
    return ['menu' => ['image' => '', 'lastUpdated' => '', 'note' => '']];
}

function saveProduct($data, $files) {
    global $DATA_DIR, $UPLOAD_DIR;
    
    try {
        $products = loadProducts();
        
        // 画像処理
        $images = [];
        $hasNewImages = false;
        
        // 新しい画像がアップロードされているかチェック
        if (isset($files['images']) && is_array($files['images']['tmp_name'])) {
            foreach ($files['images']['tmp_name'] as $key => $tmp_name) {
                if ($files['images']['error'][$key] === 0 && !empty($tmp_name) && $files['images']['size'][$key] > 0) {
                    $hasNewImages = true;
                    $file = [
                        'name' => $files['images']['name'][$key] ?? '',
                        'tmp_name' => $tmp_name,
                        'error' => $files['images']['error'][$key] ?? UPLOAD_ERR_NO_FILE,
                        'size' => $files['images']['size'][$key] ?? 0,
                        'type' => $files['images']['type'][$key] ?? ''
                    ];
                    $imagePath = uploadImage($file, 'products');
                    if ($imagePath) {
                        $images[] = $imagePath;
                        error_log("Product image uploaded: " . $imagePath);
                    } else {
                        error_log("Failed to upload product image: " . $file['name']);
                    }
                }
            }
        }
        
        // デバッグ用ログ（商品画像）
        error_log("Product - Has new images: " . ($hasNewImages ? 'yes' : 'no'));
        error_log("Product - Existing images data: " . ($data['existing_images'] ?? 'empty'));
        
        // 新しい画像がない場合は既存画像を保持
        if (!$hasNewImages && isset($data['existing_images'])) {
            // HTMLエスケープを解除してからJSONデコード
            $decodedExistingImages = htmlspecialchars_decode($data['existing_images']);
            $existingImages = json_decode($decodedExistingImages, true);
            error_log("Product - Decoded existing images: " . json_encode($existingImages));
            if (is_array($existingImages)) {
                $images = $existingImages;
            } else if ($decodedExistingImages === '[]') {
                // 空配列の場合も適切に処理
                $images = [];
            }
        } else if ($hasNewImages && isset($data['existing_images'])) {
            // 新しい画像がある場合、古い画像ファイルを削除
            $decodedExistingImages = htmlspecialchars_decode($data['existing_images']);
            $existingImages = json_decode($decodedExistingImages, true);
            if (is_array($existingImages)) {
                foreach ($existingImages as $oldImage) {
                    $oldImagePath = '../' . $oldImage;
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                        error_log("Deleted old image: " . $oldImagePath);
                    }
                }
            }
        }
        
        error_log("Product - Final images: " . json_encode($images));
        
        $product = [
            'name' => $data['name'],
            'description' => $data['description'],
            'images' => $images,
            'seasonal' => isset($data['seasonal'])
        ];
        
        // 既存商品の更新または新規追加
        $index = -1;
        if (isset($data['edit_mode']) && $data['edit_mode'] === '1' && isset($data['original_name'])) {
            // 編集モード：元の商品名で識別
            foreach ($products['products'] as $i => $existingProduct) {
                if ($existingProduct['name'] === $data['original_name']) {
                    $index = $i;
                    break;
                }
            }
        } else {
            // 新規追加モード：同じ名前の商品がないか確認
            foreach ($products['products'] as $i => $existingProduct) {
                if ($existingProduct['name'] === $data['name']) {
                    $index = $i;
                    break;
                }
            }
        }
        
        if ($index !== -1) {
            // 既存商品の場合、visibleフィールドを保持
            if (isset($products['products'][$index]['visible'])) {
                $product['visible'] = $products['products'][$index]['visible'];
            }
            $products['products'][$index] = $product;
        } else {
            // 新規商品の場合、デフォルトで表示
            $product['visible'] = true;
            $products['products'][] = $product;
        }
        
        // JSONファイルに保存
        $result = file_put_contents($DATA_DIR . 'products.json', json_encode($products, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        return ['success' => $result !== false];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function saveShop($data, $files) {
    global $DATA_DIR;
    
    try {
        // フォームから送信された全データをログ出力
        error_log("=== SHOP SAVE DEBUG ===");
        error_log("POST data: " . json_encode($data));
        error_log("FILES data: " . json_encode($files));
        
        $shops = loadShops();
        
        // ロゴ画像アップロード処理
        $logoPath = '';
        if (isset($files['logo']) && $files['logo']['error'] === 0 && $files['logo']['size'] > 0) {
            // ロゴファイルのフィールドを確認
            $logoFile = $files['logo'];
            if (!isset($logoFile['type'])) {
                $logoFile['type'] = '';
            }
            $logoPath = uploadImage($logoFile, 'shops');
            if (!$logoPath) {
                return ['success' => false, 'error' => 'ロゴ画像のアップロードに失敗しました'];
            }
        } else if (isset($data['existing_logo'])) {
            $logoPath = $data['existing_logo'];
        }
        
        // 店舗画像アップロード処理
        $shopImages = [];
        $hasNewShopImages = false;
        
        if (isset($files['shopImages'])) {
            foreach ($files['shopImages']['tmp_name'] as $key => $tmp_name) {
                if ($files['shopImages']['error'][$key] === 0 && !empty($tmp_name) && $files['shopImages']['size'][$key] > 0) {
                    $hasNewShopImages = true;
                    $file = [
                        'name' => $files['shopImages']['name'][$key] ?? '',
                        'tmp_name' => $tmp_name,
                        'error' => $files['shopImages']['error'][$key] ?? UPLOAD_ERR_NO_FILE,
                        'size' => $files['shopImages']['size'][$key] ?? 0,
                        'type' => $files['shopImages']['type'][$key] ?? ''
                    ];
                    $imagePath = uploadImage($file, 'shops');
                    if ($imagePath) {
                        $shopImages[] = $imagePath;
                    }
                }
            }
        }
        
        // デバッグ用ログ
        error_log("Has new shop images: " . ($hasNewShopImages ? 'yes' : 'no'));
        error_log("Existing shop images data: " . ($data['existing_shop_images'] ?? 'empty'));
        
        // 新しい画像がない場合は既存画像を保持
        if (!$hasNewShopImages && isset($data['existing_shop_images'])) {
            // HTMLエスケープを解除してからJSONデコード
            $decodedExistingImages = htmlspecialchars_decode($data['existing_shop_images']);
            $existingShopImages = json_decode($decodedExistingImages, true);
            error_log("Decoded existing shop images: " . json_encode($existingShopImages));
            if (is_array($existingShopImages)) {
                $shopImages = $existingShopImages;
            } else if ($decodedExistingImages === '[]') {
                // 空配列の場合も適切に処理
                $shopImages = [];
            }
        } else if ($hasNewShopImages && isset($data['existing_shop_images'])) {
            // 新しい画像がある場合は既存画像と結合
            $decodedExistingImages = htmlspecialchars_decode($data['existing_shop_images']);
            $existingShopImages = json_decode($decodedExistingImages, true);
            if (is_array($existingShopImages)) {
                $shopImages = array_merge($existingShopImages, $shopImages);
            }
        }
        
        error_log("Final shop images: " . json_encode($shopImages));
        
        $shop = [
            'id' => $data['id'] ?: generateId(),
            'name' => $data['name'],
            'address' => $data['address'],
            'hours' => $data['hours'],
            'closed' => $data['closed'],
            'description' => $data['description'],
            'logo' => $logoPath,
            'shopImages' => $shopImages,
            'social' => [
                'instagram' => $data['instagram'] ?? ''
            ]
        ];
        
        // 既存店舗の更新または新規追加
        if ($data['id']) {
            $index = array_search($data['id'], array_column($shops['shops'], 'id'));
            if ($index !== false) {
                // 既存店舗の場合、visibleフィールドを保持
                if (isset($shops['shops'][$index]['visible'])) {
                    $shop['visible'] = $shops['shops'][$index]['visible'];
                }
                $shops['shops'][$index] = $shop;
            }
        } else {
            // 新規店舗の場合、デフォルトで表示
            $shop['visible'] = true;
            $shops['shops'][] = $shop;
        }
        
        // JSONファイルに保存
        $result = file_put_contents($DATA_DIR . 'shops.json', json_encode($shops, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        return ['success' => $result !== false];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function saveNews($data) {
    global $DATA_DIR;
    
    try {
        $news = loadNews();
        
        $newsItem = [
            'id' => $data['id'] ?: generateId(),
            'title' => $data['title'],
            'description' => $data['description'],
            'image' => $data['image'],
            'date' => $data['date'],
            'published' => isset($data['published']),
            'source' => $data['source'] ?? 'manual',
            'sourceUrl' => $data['sourceUrl'] ?? ''
        ];
        
        // 既存ニュースの更新または新規追加
        if ($data['id']) {
            $index = array_search($data['id'], array_column($news['news'], 'id'));
            if ($index !== false) {
                $news['news'][$index] = $newsItem;
            }
        } else {
            array_unshift($news['news'], $newsItem);
        }
        
        // JSONファイルに保存
        $result = file_put_contents($DATA_DIR . 'news.json', json_encode($news, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        return ['success' => $result !== false];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function saveMenu($data, $files) {
    global $DATA_DIR;
    
    try {
        $menu = loadMenu();
        
        // 画像アップロード処理
        if (isset($files['menu_image']) && $files['menu_image']['error'] === 0) {
            $imagePath = uploadImage($files['menu_image'], 'menu');
            if (!$imagePath) {
                return ['success' => false, 'error' => '画像のアップロードに失敗しました'];
            }
            $menu['menu']['image'] = $imagePath;
        }
        
        $menu['menu']['lastUpdated'] = date('Y-m-d H:i:s');
        $menu['menu']['note'] = $data['note'] ?? '価格は変更される場合があります';
        
        // JSONファイルに保存
        $result = file_put_contents($DATA_DIR . 'menu.json', json_encode($menu, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        return ['success' => $result !== false];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function deleteItem($identifier, $type) {
    global $DATA_DIR;
    
    try {
        $file = $DATA_DIR . $type . '.json';
        $data = json_decode(file_get_contents($file), true);
        
        $key = $type === 'products' ? 'products' : ($type === 'shops' ? 'shops' : 'news');
        
        $index = -1;
        if ($type === 'products') {
            // 商品は名前で削除
            foreach ($data[$key] as $i => $item) {
                if ($item['name'] === $identifier) {
                    $index = $i;
                    break;
                }
            }
        } else {
            // 他はIDで削除
            $index = array_search($identifier, array_column($data[$key], 'id'));
        }
        
        if ($index !== false && $index !== -1) {
            array_splice($data[$key], $index, 1);
            $result = file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            return ['success' => $result !== false];
        }
        
        return ['success' => false, 'error' => 'アイテムが見つかりません'];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function uploadImage($file, $folder) {
    global $UPLOAD_DIR;
    
    error_log("uploadImage called with file: " . json_encode($file) . ", folder: " . $folder);
    
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    // ファイルの必須フィールドをチェック
    if (!isset($file['type']) || !isset($file['size']) || !isset($file['tmp_name']) || !isset($file['name'])) {
        error_log("Missing required file fields: " . json_encode(array_keys($file)));
        return false;
    }
    
    if (!in_array($file['type'], $allowedTypes)) {
        error_log("File type not allowed: " . $file['type']);
        return false;
    }
    
    if ($file['size'] > $maxSize) {
        error_log("File size too large: " . $file['size']);
        return false;
    }
    
    $uploadDir = $UPLOAD_DIR . $folder . '/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
        error_log("Created directory: " . $uploadDir);
    }
    
    $filename = generateId() . '_' . basename($file['name']);
    $uploadPath = $uploadDir . $filename;
    
    error_log("Attempting to move file from " . $file['tmp_name'] . " to " . $uploadPath);
    
    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        $relativePath = 'assets/images/' . $folder . '/' . $filename;
        error_log("Upload successful: " . $relativePath);
        return $relativePath;
    }
    
    error_log("Upload failed");
    return false;
}

function fetchInstagramPosts($url) {
    // 簡単なInstagram投稿取得（実際の実装では適切なAPIまたはスクレイピングを使用）
    // この例では模擬データを返します
    $posts = [
        [
            'id' => 'post_' . time() . '_1',
            'image' => 'https://picsum.photos/400/400?random=1',
            'caption' => '新作のシフォンケーキができました！ふわふわで美味しいです 🍰',
            'date' => date('Y-m-d'),
            'url' => $url . '/p/example1'
        ],
        [
            'id' => 'post_' . time() . '_2',
            'image' => 'https://picsum.photos/400/400?random=2',
            'caption' => '本日のおすすめははちみつクッキーです 🍪',
            'date' => date('Y-m-d', strtotime('-1 day')),
            'url' => $url . '/p/example2'
        ],
        [
            'id' => 'post_' . time() . '_3',
            'image' => 'https://picsum.photos/400/400?random=3',
            'caption' => '冬限定のフロランタンが人気です ❄️',
            'date' => date('Y-m-d', strtotime('-2 days')),
            'url' => $url . '/p/example3'
        ]
    ];
    
    return ['success' => true, 'posts' => $posts];
}

function generateId() {
    return substr(md5(uniqid(rand(), true)), 0, 8);
}

function toggleProductVisible($productName, $isVisible) {
    global $DATA_DIR;
    
    try {
        $products = loadProducts();
        
        // 商品を検索して visible フィールドを更新
        foreach ($products['products'] as &$product) {
            if ($product['name'] === $productName) {
                $product['visible'] = $isVisible;
                break;
            }
        }
        
        // JSONファイルに保存
        $result = file_put_contents($DATA_DIR . 'products.json', json_encode($products, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        return ['success' => $result !== false];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function toggleShopVisible($shopId, $isVisible) {
    global $DATA_DIR;
    
    try {
        $shops = loadShops();
        
        // 店舗を検索して visible フィールドを更新
        foreach ($shops['shops'] as &$shop) {
            if ($shop['id'] === $shopId) {
                $shop['visible'] = $isVisible;
                break;
            }
        }
        
        // JSONファイルに保存
        $result = file_put_contents($DATA_DIR . 'shops.json', json_encode($shops, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        return ['success' => $result !== false];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// 商品順序初期化（orderフィールドがない場合に追加）
function initializeProductOrder() {
    global $DATA_DIR;
    $products = loadProducts();
    $needsSave = false;
    
    foreach ($products['products'] as $index => &$product) {
        if (!isset($product['order'])) {
            $product['order'] = $index;
            $needsSave = true;
        }
    }
    
    if ($needsSave) {
        file_put_contents($DATA_DIR . 'products.json', json_encode($products, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    
    return $products;
}

// 店舗順序初期化
function initializeShopOrder() {
    global $DATA_DIR;
    $shops = loadShops();
    $needsSave = false;
    
    foreach ($shops['shops'] as $index => &$shop) {
        if (!isset($shop['order'])) {
            $shop['order'] = $index;
            $needsSave = true;
        }
    }
    
    if ($needsSave) {
        file_put_contents($DATA_DIR . 'shops.json', json_encode($shops, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    
    return $shops;
}

// 商品順序更新
function updateProductOrder($productName, $newOrder) {
    global $DATA_DIR;
    
    try {
        $products = loadProducts();
        
        // 範囲チェック
        if ($newOrder < 1 || $newOrder > count($products['products'])) {
            return ['success' => false, 'error' => '無効な順序番号です'];
        }
        
        // 対象商品を取得
        $targetProduct = null;
        $currentIndex = -1;
        foreach ($products['products'] as $index => $product) {
            if ($product['name'] === $productName) {
                $targetProduct = $product;
                $currentIndex = $index;
                break;
            }
        }
        
        if (!$targetProduct) {
            return ['success' => false, 'error' => '商品が見つかりません'];
        }
        
        // 商品を配列から削除
        array_splice($products['products'], $currentIndex, 1);
        
        // 新しい位置に挿入（1ベースから0ベースに変換）
        array_splice($products['products'], $newOrder - 1, 0, [$targetProduct]);
        
        // orderフィールドを更新
        foreach ($products['products'] as $index => &$product) {
            $product['order'] = $index;
        }
        
        // 保存
        $result = file_put_contents($DATA_DIR . 'products.json', json_encode($products, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        return ['success' => $result !== false, 'products' => $products['products']];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// 店舗順序更新
function updateShopOrder($shopId, $newOrder) {
    global $DATA_DIR;
    
    try {
        $shops = loadShops();
        
        // 範囲チェック
        if ($newOrder < 1 || $newOrder > count($shops['shops'])) {
            return ['success' => false, 'error' => '無効な順序番号です'];
        }
        
        // 対象店舗を取得
        $targetShop = null;
        $currentIndex = -1;
        foreach ($shops['shops'] as $index => $shop) {
            if ($shop['id'] === $shopId) {
                $targetShop = $shop;
                $currentIndex = $index;
                break;
            }
        }
        
        if (!$targetShop) {
            return ['success' => false, 'error' => '店舗が見つかりません'];
        }
        
        // 店舗を配列から削除
        array_splice($shops['shops'], $currentIndex, 1);
        
        // 新しい位置に挿入（1ベースから0ベースに変換）
        array_splice($shops['shops'], $newOrder - 1, 0, [$targetShop]);
        
        // orderフィールドを更新
        foreach ($shops['shops'] as $index => &$shop) {
            $shop['order'] = $index;
        }
        
        // 保存
        $result = file_put_contents($DATA_DIR . 'shops.json', json_encode($shops, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        return ['success' => $result !== false, 'shops' => $shops['shops']];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// 現在のタブを取得
$currentTab = $_GET['tab'] ?? 'products';
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>りすたっち管理画面</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Shippori+Mincho+B1:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <?php if (!$isLoggedIn): ?>
        <!-- Login Screen -->
        <div class="login-screen">
            <div class="login-container">
                <h1>りすたっち管理画面</h1>
                <?php if (isset($error)): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>
                <?php if ($show_message && isset($message) && !empty(trim($message))): ?>
                    <div class="alert alert-success"><?php echo $message; /* HTMLリンクを含む場合があるためエスケープしない */ ?></div>
                <?php endif; ?>
                <?php if (isset($_SESSION['oauth_error'])): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($_SESSION['oauth_error']); ?></div>
                    <?php unset($_SESSION['oauth_error']); ?>
                <?php endif; ?>
                
                <?php if (isset($_GET['reset_token']) && validatePasswordResetToken($_GET['reset_token'])): ?>
                    <!-- パスワードリセットフォーム -->
                    <form method="POST" class="login-form">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                        <h2>新しいパスワードを設定</h2>
                        <div class="form-group">
                            <label for="new_password">新しいパスワード</label>
                            <input type="password" id="new_password" name="new_password" required minlength="8" autocomplete="new-password">
                            <small>8文字以上で入力してください</small>
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">パスワード確認</label>
                            <input type="password" id="confirm_password" name="confirm_password" required minlength="8" autocomplete="new-password">
                        </div>
                        <button type="submit" name="reset_password" class="btn btn-primary">パスワードを変更</button>
                        <a href="index.php" class="btn btn-secondary">キャンセル</a>
                    </form>
                    
                <?php elseif (isset($_GET['forgot'])): ?>
                    <!-- パスワードリセット要求フォーム -->
                    <form method="POST" class="login-form">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                        <h2>パスワードリセット</h2>
                        <div class="form-group">
                            <label for="email">メールアドレス</label>
                            <input type="email" id="email" name="email" required autocomplete="email" placeholder="メールアドレスを入力">
                        </div>
                        <button type="submit" name="request_reset" class="btn btn-primary">リセットメールを送信</button>
                        <a href="index.php" class="btn btn-secondary">ログイン画面に戻る</a>
                    </form>
                    
                <?php elseif (isset($_GET['verify_email'])): ?>
                    <!-- メール認証コード入力フォーム -->
                    <form method="POST" class="login-form">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                        <h2>📧 メール認証</h2>
                        <p>メールアドレスに送信された6桁の認証コードを入力してください。</p>
                        <div class="form-group">
                            <label for="email_code">認証コード</label>
                            <input type="text" id="email_code" name="email_code" maxlength="6" pattern="[0-9]{6}" required autocomplete="one-time-code" placeholder="123456">
                            <small>10分以内に入力してください</small>
                        </div>
                        <button type="submit" name="verify_email_code" class="btn btn-primary">認証する</button>
                        <a href="index.php" class="btn btn-secondary">ログイン画面に戻る</a>
                    </form>
                    
                <?php else: ?>
                    <!-- ログインフォーム -->
                    <form method="POST" class="login-form">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                        <div class="form-group">
                            <label for="email">メールアドレス</label>
                            <input type="email" id="email" name="email" required autocomplete="email" placeholder="メールアドレスを入力">
                        </div>
                        <div class="form-group">
                            <label for="password">パスワード</label>
                            <input type="password" id="password" name="password" required autocomplete="current-password">
                        </div>
                        <button type="submit" name="login" class="btn btn-primary">ログイン</button>
                        <a href="index.php?forgot=1" class="forgot-password-link">パスワードを忘れた場合</a>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <!-- Main Admin Panel -->
        <div class="admin-panel">
            <header class="admin-header">
                <h1>りすたっち管理画面</h1>
                <a href="?logout=1" class="btn btn-secondary">ログアウト</a>
            </header>

            <?php if ($show_message && isset($message) && !empty(trim($message))): ?>
                <div class="alert alert-success"><?php echo $message; /* HTMLリンクを含む場合があるためエスケープしない */ ?></div>
            <?php endif; ?>

            <nav class="admin-nav">
                <a href="?tab=products" class="nav-btn <?php echo $currentTab === 'products' ? 'active' : ''; ?>">焼き菓子管理</a>
                <a href="?tab=shops" class="nav-btn <?php echo $currentTab === 'shops' ? 'active' : ''; ?>">販売店管理</a>
                <a href="?tab=menu" class="nav-btn <?php echo $currentTab === 'menu' ? 'active' : ''; ?>">メニュー表</a>
                <a href="?tab=topics" class="nav-btn <?php echo $currentTab === 'topics' ? 'active' : ''; ?>">トピック管理</a>
                <a href="?tab=backup" class="nav-btn <?php echo $currentTab === 'backup' ? 'active' : ''; ?>">バックアップ</a>
                <a href="?tab=security" class="nav-btn <?php echo $currentTab === 'security' ? 'active' : ''; ?>">セキュリティ設定</a>
            </nav>

            <main class="admin-main">
                <?php if ($currentTab === 'products'): ?>
                    <?php include 'products.php'; ?>
                <?php elseif ($currentTab === 'shops'): ?>
                    <?php include 'shops.php'; ?>
                <?php elseif ($currentTab === 'menu'): ?>
                    <?php include 'menu.php'; ?>
                <?php elseif ($currentTab === 'topics'): ?>
                    <?php include 'topics.php'; ?>
                <?php elseif ($currentTab === 'backup'): ?>
                    <?php include 'backup.php'; ?>
                <?php elseif ($currentTab === 'security'): ?>
                    <?php include 'security.php'; ?>
                <?php endif; ?>
            </main>
        </div>
    <?php endif; ?>

    <script src="script.php.js"></script>
</body>
</html>
<?php
// ページ表示後にセッションメッセージを確実にクリア
if (isset($_SESSION['message'])) {
    unset($_SESSION['message']);
}
?>