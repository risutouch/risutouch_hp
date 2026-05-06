<?php
require_once 'instagram-browser.php';

header('Content-Type: application/json');

$instagram = new BrowserInstagramManager();
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'save_config':
        // 設定保存
        $token = $_POST['token'] ?? '';
        $page_id = $_POST['page_id'] ?? '';
        
        if (empty($token) || empty($page_id)) {
            echo json_encode([
                'success' => false,
                'error' => 'アクセストークンとページIDを入力してください'
            ]);
            break;
        }
        
        $result = $instagram->saveConfig($token, $page_id);
        echo json_encode([
            'success' => $result,
            'message' => $result ? '設定を保存しました' : '設定の保存に失敗しました'
        ]);
        break;
        
    case 'save_posts':
        // ブラウザから送信された投稿データを保存
        $posts_json = $_POST['posts'] ?? '';
        
        if (empty($posts_json)) {
            echo json_encode([
                'success' => false,
                'error' => '投稿データが送信されていません'
            ]);
            break;
        }
        
        $posts_data = json_decode($posts_json, true);
        if (!$posts_data) {
            echo json_encode([
                'success' => false,
                'error' => '投稿データの形式が正しくありません'
            ]);
            break;
        }
        
        $result = $instagram->savePosts($posts_data);
        echo json_encode($result);
        break;
        
    case 'save_account_info':
        // アカウント情報保存
        $account_json = $_POST['account_info'] ?? '';
        
        if (empty($account_json)) {
            echo json_encode([
                'success' => false,
                'error' => 'アカウント情報が送信されていません'
            ]);
            break;
        }
        
        $account_info = json_decode($account_json, true);
        if (!$account_info) {
            echo json_encode([
                'success' => false,
                'error' => 'アカウント情報の形式が正しくありません'
            ]);
            break;
        }
        
        $result = $instagram->saveAccountInfo($account_info);
        echo json_encode([
            'success' => $result,
            'message' => $result ? 'アカウント情報を保存しました' : 'アカウント情報の保存に失敗しました'
        ]);
        break;
        
    case 'status':
        // 設定状態確認
        echo json_encode([
            'success' => true,
            'authenticated' => $instagram->isConfigured()
        ]);
        break;
        
    case 'profile':
        // プロフィール情報取得
        $profile = $instagram->getAccountInfo();
        if (isset($profile['error'])) {
            echo json_encode([
                'success' => false,
                'error' => $profile['error']
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'profile' => $profile
            ]);
        }
        break;
        
    case 'media':
        // 保存された投稿を取得
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