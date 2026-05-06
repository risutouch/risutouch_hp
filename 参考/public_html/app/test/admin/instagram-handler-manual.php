<?php
require_once 'instagram-manual.php';

header('Content-Type: application/json');

$instagram = new ManualInstagramManager();
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'set_username':
        // ユーザー名を設定
        $username = $_POST['username'] ?? '';
        
        if (empty($username)) {
            echo json_encode([
                'success' => false,
                'error' => 'ユーザー名を入力してください'
            ]);
            break;
        }
        
        $username = ltrim($username, '@');
        $result = $instagram->setUsername($username);
        
        echo json_encode([
            'success' => $result,
            'message' => $result ? 'ユーザー名を設定しました' : '設定の保存に失敗しました'
        ]);
        break;
        
    case 'add_post':
        // 単一投稿を追加
        $post_data = [
            'caption' => $_POST['caption'] ?? '',
            'image' => $_POST['image'] ?? '',
            'url' => $_POST['url'] ?? '',
            'date' => $_POST['date'] ?? date('Y-m-d')
        ];
        
        if (empty($post_data['caption']) && empty($post_data['image'])) {
            echo json_encode([
                'success' => false,
                'error' => 'キャプションまたは画像URLを入力してください'
            ]);
            break;
        }
        
        $result = $instagram->addPost($post_data);
        
        if (isset($result['error'])) {
            echo json_encode([
                'success' => false,
                'error' => $result['error']
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'message' => '投稿を追加しました',
                'post' => $result['post']
            ]);
        }
        break;
        
    case 'add_multiple':
        // 複数投稿を一括追加
        $posts_json = $_POST['posts_json'] ?? '';
        
        if (empty($posts_json)) {
            echo json_encode([
                'success' => false,
                'error' => '投稿データを入力してください'
            ]);
            break;
        }
        
        $result = $instagram->addMultiplePosts($posts_json);
        
        if (isset($result['error'])) {
            echo json_encode([
                'success' => false,
                'error' => $result['error']
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'message' => "{$result['added_count']}件の投稿を追加しました（合計{$result['total_count']}件）",
                'added_count' => $result['added_count'],
                'total_count' => $result['total_count']
            ]);
        }
        break;
        
    case 'generate_sample':
        // サンプルデータを生成
        $result = $instagram->generateSampleData();
        
        if (isset($result['error'])) {
            echo json_encode([
                'success' => false,
                'error' => $result['error']
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'message' => "サンプル投稿を{$result['added_count']}件追加しました",
                'added_count' => $result['added_count'],
                'total_count' => $result['total_count']
            ]);
        }
        break;
        
    case 'delete_post':
        // 投稿を削除
        $post_id = $_POST['post_id'] ?? '';
        
        if (empty($post_id)) {
            echo json_encode([
                'success' => false,
                'error' => '投稿IDが指定されていません'
            ]);
            break;
        }
        
        $result = $instagram->deletePost($post_id);
        
        if (isset($result['error'])) {
            echo json_encode([
                'success' => false,
                'error' => $result['error']
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'message' => '投稿を削除しました'
            ]);
        }
        break;
        
    case 'status':
        // 設定状態確認
        $account_info = $instagram->getAccountInfo();
        echo json_encode([
            'success' => true,
            'authenticated' => !empty($account_info['username']),
            'username' => $account_info['username'],
            'last_updated' => $account_info['last_updated']
        ]);
        break;
        
    case 'profile':
        // プロフィール情報
        $account_info = $instagram->getAccountInfo();
        echo json_encode([
            'success' => true,
            'profile' => $account_info
        ]);
        break;
        
    case 'media':
        // 投稿一覧を取得
        $limit = $_GET['limit'] ?? 25;
        $result = $instagram->getPosts($limit);
        echo json_encode($result);
        break;
        
    case 'logout':
        // データクリア
        $result = $instagram->clearData();
        echo json_encode([
            'success' => $result,
            'message' => $result ? 'データをクリアしました' : 'データのクリアに失敗しました'
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