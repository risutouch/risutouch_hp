<?php
require_once 'instagram-easy.php';

header('Content-Type: application/json');

$instagram = new EasyInstagramAPI();
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'save_config':
        // トークンとアカウントIDを保存
        $token = $_POST['token'] ?? '';
        $account_id = $_POST['account_id'] ?? '';
        
        if (empty($token) || empty($account_id)) {
            echo json_encode([
                'success' => false,
                'error' => 'アクセストークンとアカウントIDを入力してください'
            ]);
            break;
        }
        
        $result = $instagram->saveConfig($token, $account_id);
        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Instagram設定を保存しました'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => '設定の保存に失敗しました'
            ]);
        }
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
        $profile = $instagram->getProfile();
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
        // メディア取得
        $limit = $_GET['limit'] ?? 25;
        $result = $instagram->getPosts($limit);
        
        if (isset($result['error'])) {
            echo json_encode([
                'success' => false,
                'error' => $result['error'],
                'debug' => $result['debug'] ?? null
            ]);
        } else {
            echo json_encode($result);
        }
        break;
        
    case 'logout':
        // 設定クリア
        $result = $instagram->clearConfig();
        echo json_encode([
            'success' => $result,
            'message' => $result ? 'Instagram設定をクリアしました' : '設定のクリアに失敗しました'
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