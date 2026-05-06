<?php
session_start();

// 直接アクセス防止とログイン状態チェック
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Access Denied - Login Required']);
    exit;
}

header('Content-Type: application/json');

// 定数定義
define('ALLOWED_TOPICS', ['sweets-quiz', 'seasonal-calendar']);
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('CONFIG_FILE', '../assets/data/topics_config.json');

// CSRF対策関数
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// ファイルアップロード検証強化
function validateImageFileSecurity($file) {
    // 基本チェック
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return false;
    }
    
    // 拡張子チェック（ホワイトリスト方式）
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowed_extensions)) {
        return false;
    }
    
    // 実際の画像ファイルかチェック
    $image_info = getimagesize($file['tmp_name']);
    if ($image_info === false) {
        return false;
    }
    
    // MIMEタイプの二重チェック
    $allowed_mimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($image_info['mime'], $allowed_mimes)) {
        return false;
    }
    
    // ファイル内容の危険なコード検証
    $content = file_get_contents($file['tmp_name']);
    if (preg_match('/(<\?php|<\?=|<script|javascript:|data:|vbscript:)/i', $content)) {
        return false;
    }
    
    // ファイルサイズチェック
    if ($file['size'] > MAX_FILE_SIZE) {
        return false;
    }
    
    return true;
}

// CSRF トークン検証（GETリクエスト以外）
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!validateCSRFToken($csrf_token)) {
        echo json_encode([
            'success' => false,
            'error' => 'セキュリティトークンが無効です'
        ]);
        exit;
    }
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'save_topic':
        $topic_id = $_POST['topic_id'] ?? '';
        $content = $_POST['content'] ?? '';
        $title = $_POST['title'] ?? '';
        $description = $_POST['description'] ?? '';
        
        if (empty($topic_id) || empty($content)) {
            echo json_encode([
                'success' => false,
                'error' => 'トピックIDまたは内容が指定されていません'
            ]);
            break;
        }
        
        // 許可されたトピックIDのみ処理
        if (!in_array($topic_id, ALLOWED_TOPICS)) {
            echo json_encode([
                'success' => false,
                'error' => '許可されていないトピックIDです'
            ]);
            break;
        }
        
        $file_path = "../assets/topics/{$topic_id}.html";
        $thumbnail_path = null;
        
        // サムネイル画像の処理
        if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['thumbnail'];
            
            // セキュリティ強化された検証
            if (!validateImageFileSecurity($file)) {
                echo json_encode([
                    'success' => false,
                    'error' => 'アップロードされたファイルは安全ではありません'
                ]);
                break;
            }
            
            // アップロードディレクトリ
            $uploadDir = '../assets/images/entertainment/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // ファイル名生成
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = $topic_id . '.' . $extension;
            $uploadPath = $uploadDir . $filename;
            
            // ファイル移動
            if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                $thumbnail_path = 'assets/images/entertainment/' . $filename;
            }
        }
        
        // ファイルに書き込み
        $result = file_put_contents($file_path, $content);
        
        if ($result !== false) {
            // トピック設定ファイルにサムネイル情報を保存
            $configFile = CONFIG_FILE;
            $config = [];
            if (file_exists($configFile)) {
                $config = json_decode(file_get_contents($configFile), true) ?: [];
            }
            
            if (!isset($config[$topic_id])) {
                $config[$topic_id] = [];
            }
            
            $config[$topic_id]['updated'] = date('Y-m-d H:i:s');
            $config[$topic_id]['title'] = $title;
            $config[$topic_id]['description'] = $description;
            
            if ($thumbnail_path) {
                $config[$topic_id]['thumbnail'] = $thumbnail_path;
            }
            
            file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            
            $response = [
                'success' => true,
                'message' => 'トピックを保存しました'
            ];
            
            if ($thumbnail_path) {
                $response['thumbnail_path'] = $thumbnail_path;
            }
            
            echo json_encode($response);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'ファイルの書き込みに失敗しました'
            ]);
        }
        break;
        
    case 'save_thumbnail':
        $topic_id = $_POST['topic_id'] ?? '';
        
        if (empty($topic_id)) {
            echo json_encode([
                'success' => false,
                'error' => 'トピックIDが指定されていません'
            ]);
            break;
        }
        
        // 許可されたトピックIDのみ処理
        if (!in_array($topic_id, ALLOWED_TOPICS)) {
            echo json_encode([
                'success' => false,
                'error' => '許可されていないトピックIDです'
            ]);
            break;
        }
        
        // ファイルアップロード処理
        if (!isset($_FILES['thumbnail']) || $_FILES['thumbnail']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode([
                'success' => false,
                'error' => 'ファイルのアップロードに失敗しました'
            ]);
            break;
        }
        
        $file = $_FILES['thumbnail'];
        
        // セキュリティ強化された検証
        if (!validateImageFileSecurity($file)) {
            echo json_encode([
                'success' => false,
                'error' => 'アップロードされたファイルは安全ではありません'
            ]);
            break;
        }
        
        // アップロードディレクトリ
        $uploadDir = '../assets/images/entertainment/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // ファイル名生成
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = $topic_id . '.' . $extension;
        $uploadPath = $uploadDir . $filename;
        
        // ファイル移動
        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            echo json_encode([
                'success' => true,
                'message' => 'サムネイル画像を保存しました',
                'thumbnail_path' => 'assets/images/entertainment/' . $filename
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'ファイルの保存に失敗しました'
            ]);
        }
        break;
        
    case 'toggle_published':
        $topic_id = $_POST['topic_id'] ?? '';
        $published = $_POST['published'] ?? '0';
        
        if (empty($topic_id)) {
            echo json_encode([
                'success' => false,
                'error' => 'トピックIDが指定されていません'
            ]);
            break;
        }
        
        // 許可されたトピックIDのみ処理
        if (!in_array($topic_id, ALLOWED_TOPICS)) {
            echo json_encode([
                'success' => false,
                'error' => '許可されていないトピックIDです'
            ]);
            break;
        }
        
        // 設定ファイルに保存（簡易実装）
        $configFile = CONFIG_FILE;
        $config = [];
        if (file_exists($configFile)) {
            $config = json_decode(file_get_contents($configFile), true) ?: [];
        }
        
        if (!isset($config[$topic_id])) {
            $config[$topic_id] = [];
        }
        
        $config[$topic_id]['published'] = $published === '1';
        $config[$topic_id]['updated'] = date('Y-m-d H:i:s');
        
        if (file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
            echo json_encode([
                'success' => true,
                'message' => '公開設定を更新しました'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => '設定の保存に失敗しました'
            ]);
        }
        break;
        
    case 'update_order':
        $topic_id = $_POST['topic_id'] ?? '';
        $order = $_POST['order'] ?? '';
        
        if (empty($topic_id) || empty($order)) {
            echo json_encode([
                'success' => false,
                'error' => 'トピックIDまたは順序が指定されていません'
            ]);
            break;
        }
        
        // 許可されたトピックIDのみ処理
        if (!in_array($topic_id, ALLOWED_TOPICS)) {
            echo json_encode([
                'success' => false,
                'error' => '許可されていないトピックIDです'
            ]);
            break;
        }
        
        // 設定ファイルを読み込み
        $configFile = CONFIG_FILE;
        $config = [];
        if (file_exists($configFile)) {
            $config = json_decode(file_get_contents($configFile), true) ?: [];
        }
        
        if (!isset($config[$topic_id])) {
            $config[$topic_id] = [];
        }
        
        $config[$topic_id]['order'] = intval($order);
        $config[$topic_id]['updated'] = date('Y-m-d H:i:s');
        
        if (file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
            echo json_encode([
                'success' => true,
                'message' => 'トピック順序を更新しました'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => '設定の保存に失敗しました'
            ]);
        }
        break;
        
    default:
        echo json_encode([
            'success' => false,
            'error' => '無効なアクションです'
        ]);
        break;
}
?>