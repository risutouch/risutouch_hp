<?php
require_once 'instagram-api.php';

// OAuth認証のコールバック処理
if (isset($_GET['code'])) {
    $instagram = new InstagramAPI();
    $result = $instagram->getAccessToken($_GET['code']);
    
    if ($result) {
        // 認証成功
        header('Location: index.php?instagram_connected=1');
        exit;
    } else {
        // 認証失敗
        header('Location: index.php?instagram_error=1');
        exit;
    }
} else if (isset($_GET['error'])) {
    // ユーザーが認証を拒否した場合
    header('Location: index.php?instagram_cancelled=1');
    exit;
} else {
    // 不正なアクセス
    header('Location: index.php');
    exit;
}
?>