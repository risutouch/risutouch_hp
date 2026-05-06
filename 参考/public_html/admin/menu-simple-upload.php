<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('POSTリクエストが必要です');
    }
    
    // ファイルアップロードの確認
    if (!isset($_FILES['menu_file']) || $_FILES['menu_file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('ファイルのアップロードに失敗しました');
    }
    
    $file = $_FILES['menu_file'];
    
    // ファイルタイプの確認
    $allowed_types = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    if (!in_array($file['type'], $allowed_types)) {
        throw new Exception('対応していないファイル形式です。JPG、PNG、WebP、GIFのみサポートしています');
    }
    
    // ファイルサイズの確認 (最大10MB)
    if ($file['size'] > 10 * 1024 * 1024) {
        throw new Exception('ファイルサイズが大きすぎます。最大10MBまでです');
    }
    
    // アップロードディレクトリの作成
    $upload_dir = '../assets/uploads/menus/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // ファイル名の生成（タイムスタンプ + ランダム）
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'menu_' . date('YmdHis') . '_' . substr(md5(uniqid()), 0, 10) . '.' . $extension;
    $file_path = $upload_dir . $filename;
    $relative_path = 'assets/uploads/menus/' . $filename;
    
    // ファイルの移動
    if (!move_uploaded_file($file['tmp_name'], $file_path)) {
        throw new Exception('ファイルの保存に失敗しました');
    }
    
    // menu.jsonの更新
    $menu_json_path = '../assets/data/menu.json';
    $menu_data = [
        'menu' => [
            'image' => $relative_path,
            'lastUpdated' => date('Y-m-d H:i:s'),
            'note' => 'アップロードされた最新のメニュー表'
        ]
    ];
    
    if (!file_put_contents($menu_json_path, json_encode($menu_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
        throw new Exception('メニュー設定の保存に失敗しました');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'メニュー表をアップロードしました',
        'file_path' => $relative_path
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>