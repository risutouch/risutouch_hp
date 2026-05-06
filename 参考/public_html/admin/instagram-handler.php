<?php
require_once 'instagram-api.php';

header('Content-Type: application/json');

$instagram = new InstagramAPI();

// アクション別の処理
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'auth_url':
        // 認証URL取得
        echo json_encode([
            'success' => true,
            'auth_url' => $instagram->getAuthUrl()
        ]);
        break;
        
    case 'profile':
        // プロフィール情報取得
        $profile = $instagram->getUserProfile();
        if ($profile) {
            echo json_encode([
                'success' => true,
                'profile' => $profile
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => '認証が必要です'
            ]);
        }
        break;
        
    case 'media':
        // メディア一覧取得
        $limit = $_GET['limit'] ?? 25;
        $media = $instagram->getUserMedia($limit);
        
        if ($media && isset($media['data'])) {
            // Instagram APIのデータを管理画面用に変換
            $posts = [];
            foreach ($media['data'] as $item) {
                // 画像・動画のみを対象
                if (in_array($item['media_type'], ['IMAGE', 'VIDEO', 'CAROUSEL_ALBUM'])) {
                    $posts[] = [
                        'id' => $item['id'],
                        'caption' => $item['caption'] ?? '',
                        'image' => $item['media_url'] ?? $item['thumbnail_url'] ?? '',
                        'url' => $item['permalink'],
                        'date' => date('Y-m-d', strtotime($item['timestamp'])),
                        'type' => strtolower($item['media_type'])
                    ];
                }
            }
            
            echo json_encode([
                'success' => true,
                'posts' => $posts
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'メディアの取得に失敗しました'
            ]);
        }
        break;
        
    case 'status':
        // 認証状態確認
        echo json_encode([
            'success' => true,
            'authenticated' => $instagram->isTokenValid()
        ]);
        break;
        
    case 'logout':
        // ログアウト
        $instagram->logout();
        echo json_encode([
            'success' => true,
            'message' => 'Instagramアカウントの連携を解除しました'
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