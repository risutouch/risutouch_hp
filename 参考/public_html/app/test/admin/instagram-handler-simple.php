<?php
require_once 'instagram-simple.php';

header('Content-Type: application/json');

$instagram = new SimpleInstagramManager();

// アクション別の処理
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'auth_url':
        // 認証URL（ダミー）
        echo json_encode([
            'success' => true,
            'auth_url' => '#'
        ]);
        break;
        
    case 'profile':
        // プロフィール情報取得
        $profile = $instagram->getAccountInfo();
        echo json_encode([
            'success' => true,
            'profile' => $profile
        ]);
        break;
        
    case 'media':
        // メディア一覧取得
        $limit = $_GET['limit'] ?? 25;
        $posts = $instagram->getPosts($limit);
        
        echo json_encode([
            'success' => true,
            'posts' => $posts
        ]);
        break;
        
    case 'status':
        // 認証状態確認
        echo json_encode([
            'success' => true,
            'authenticated' => $instagram->isConnected()
        ]);
        break;
        
    case 'connect':
        // 接続（サンプルデータを更新）
        $result = $instagram->updatePosts();
        echo json_encode([
            'success' => $result,
            'message' => $result ? 'Instagram投稿を取得しました' : '投稿の取得に失敗しました'
        ]);
        break;
        
    case 'logout':
        // ログアウト
        $instagram->disconnect();
        echo json_encode([
            'success' => true,
            'message' => 'Instagram連携を解除しました'
        ]);
        break;
        
    default:
        echo json_encode([
            'success' => false,
            'error' => '無効なアクションです'
        ]);
        break;
}
?>