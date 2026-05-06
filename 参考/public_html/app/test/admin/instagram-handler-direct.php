<?php
require_once 'instagram-direct.php';

header('Content-Type: application/json');

$instagram = new DirectInstagramAPI();

// 設定されたトークンとアカウントIDを確認
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'set_token':
        // トークンとアカウントIDを設定
        $token = $_POST['token'] ?? '';
        $account_id = $_POST['account_id'] ?? '';
        
        if ($token && $account_id) {
            // セッションに保存
            session_start();
            $_SESSION['instagram_token'] = $token;
            $_SESSION['instagram_account_id'] = $account_id;
            
            $instagram->setAccessToken($token);
            $instagram->setInstagramAccountId($account_id);
            
            // トークンの有効性をテスト
            if ($instagram->validateToken()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Instagram APIの設定が完了しました'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => 'トークンまたはアカウントIDが無効です'
                ]);
            }
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'トークンとアカウントIDを入力してください'
            ]);
        }
        break;
        
    case 'status':
        // 設定状態確認
        session_start();
        $token = $_SESSION['instagram_token'] ?? null;
        $account_id = $_SESSION['instagram_account_id'] ?? null;
        
        if ($token && $account_id) {
            $instagram->setAccessToken($token);
            $instagram->setInstagramAccountId($account_id);
            echo json_encode([
                'success' => true,
                'authenticated' => $instagram->validateToken()
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'authenticated' => false
            ]);
        }
        break;
        
    case 'profile':
        // プロフィール情報取得
        session_start();
        $token = $_SESSION['instagram_token'] ?? null;
        $account_id = $_SESSION['instagram_account_id'] ?? null;
        
        if ($token && $account_id) {
            $instagram->setAccessToken($token);
            $instagram->setInstagramAccountId($account_id);
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
        } else {
            echo json_encode([
                'success' => false,
                'error' => '認証情報が設定されていません'
            ]);
        }
        break;
        
    case 'media':
        // メディア一覧取得
        session_start();
        $token = $_SESSION['instagram_token'] ?? null;
        $account_id = $_SESSION['instagram_account_id'] ?? null;
        $limit = $_GET['limit'] ?? 25;
        
        if ($token && $account_id) {
            $instagram->setAccessToken($token);
            $instagram->setInstagramAccountId($account_id);
            $result = $instagram->getUserMedia($limit);
            
            if (isset($result['error'])) {
                echo json_encode([
                    'success' => false,
                    'error' => $result['error']
                ]);
            } else {
                echo json_encode($result);
            }
        } else {
            echo json_encode([
                'success' => false,
                'error' => '認証情報が設定されていません'
            ]);
        }
        break;
        
    case 'logout':
        // セッションクリア
        session_start();
        unset($_SESSION['instagram_token']);
        unset($_SESSION['instagram_account_id']);
        
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