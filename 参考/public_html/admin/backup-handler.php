<?php
session_start();

// 直接アクセス防止とログイン状態チェック
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Access Denied - Login Required']);
    exit;
}

// CSRF対策
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// バックアップディレクトリ
$backupDir = '../assets/backups';
if (!file_exists($backupDir)) {
    mkdir($backupDir, 0755, true);
}

// データバックアップ作成
function createDataBackup($backupDir) {
    $timestamp = date('Y-m-d_H-i-s');
    $backupFile = $backupDir . "/backup_data_{$timestamp}.zip";
    
    $zip = new ZipArchive();
    if ($zip->open($backupFile, ZipArchive::CREATE) !== TRUE) {
        return ['success' => false, 'error' => 'ZIPファイルの作成に失敗しました'];
    }
    
    // 重要なデータファイル
    $dataFiles = [
        '../assets/data/products.json',
        '../assets/data/shops.json', 
        '../assets/data/news.json',
        '../assets/data/menu.json',
        '../assets/data/sweets-quiz.json',
        '../assets/data/topics_config.json',
        '../assets/data/admin_config.json'
    ];
    
    // JSONデータファイルを追加
    foreach ($dataFiles as $file) {
        if (file_exists($file)) {
            $zip->addFile($file, 'data/' . basename($file));
        }
    }
    
    // アップロードファイルを追加
    $uploadDirs = [
        '../assets/uploads/menus',
        '../assets/images/products', 
        '../assets/images/shops',
        '../assets/images/entertainment'
    ];
    
    foreach ($uploadDirs as $dir) {
        if (is_dir($dir)) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $relativePath = 'uploads/' . str_replace('../assets/', '', $file->getPathname());
                    $zip->addFile($file->getPathname(), $relativePath);
                }
            }
        }
    }
    
    $zip->close();
    
    return ['success' => true, 'filename' => basename($backupFile)];
}

// 完全バックアップ作成
function createFullBackup($backupDir) {
    $timestamp = date('Y-m-d_H-i-s');
    $backupFile = $backupDir . "/backup_full_{$timestamp}.zip";
    
    $zip = new ZipArchive();
    if ($zip->open($backupFile, ZipArchive::CREATE) !== TRUE) {
        return ['success' => false, 'error' => 'ZIPファイルの作成に失敗しました'];
    }
    
    // システム全体をバックアップ（adminフォルダとassetsフォルダ）
    $backupDirs = [
        '../assets' => 'assets',
        '../admin' => 'admin',
        '../index.html' => 'index.html'
    ];
    
    foreach ($backupDirs as $source => $destination) {
        if (is_file($source)) {
            $zip->addFile($source, $destination);
        } elseif (is_dir($source)) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source));
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $relativePath = $destination . '/' . str_replace($source . '/', '', $file->getPathname());
                    $zip->addFile($file->getPathname(), $relativePath);
                }
            }
        }
    }
    
    $zip->close();
    
    return ['success' => true, 'filename' => basename($backupFile)];
}

// バックアップダウンロード
if (isset($_GET['download']) && isset($_GET['csrf_token'])) {
    if (!validateCSRFToken($_GET['csrf_token'])) {
        http_response_code(403);
        die('CSRF token validation failed');
    }
    
    $filename = $_GET['download'];
    $filepath = $backupDir . '/' . $filename;
    
    // ファイル名の検証（セキュリティ対策）
    if (!preg_match('/^backup_[a-zA-Z0-9_\-]+\.zip$/', $filename) || !file_exists($filepath)) {
        http_response_code(404);
        die('ファイルが見つかりません');
    }
    
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($filepath));
    
    readfile($filepath);
    exit;
}

// POST処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $_SESSION['message'] = 'セキュリティトークンが無効です';
        header('Location: index.php?tab=backup');
        exit;
    }
    
    // データバックアップ作成
    if (isset($_POST['create_data_backup'])) {
        $result = createDataBackup($backupDir);
        if ($result['success']) {
            $_SESSION['message'] = 'データバックアップを作成しました: ' . $result['filename'];
        } else {
            $_SESSION['message'] = 'バックアップの作成に失敗しました: ' . $result['error'];
        }
        header('Location: index.php?tab=backup');
        exit;
    }
    
    // 完全バックアップ作成
    if (isset($_POST['create_full_backup'])) {
        $result = createFullBackup($backupDir);
        if ($result['success']) {
            $_SESSION['message'] = '完全バックアップを作成しました: ' . $result['filename'];
        } else {
            $_SESSION['message'] = 'バックアップの作成に失敗しました: ' . $result['error'];
        }
        header('Location: index.php?tab=backup');
        exit;
    }
    
    // バックアップ削除
    if (isset($_POST['delete_backup'])) {
        $filename = $_POST['delete_backup'];
        $filepath = $backupDir . '/' . $filename;
        
        if (preg_match('/^backup_[a-zA-Z0-9_\-]+\.zip$/', $filename) && file_exists($filepath)) {
            if (unlink($filepath)) {
                $_SESSION['message'] = 'バックアップファイルを削除しました: ' . $filename;
            } else {
                $_SESSION['message'] = 'バックアップファイルの削除に失敗しました';
            }
        } else {
            $_SESSION['message'] = '無効なファイル名です';
        }
        header('Location: index.php?tab=backup');
        exit;
    }
    
    // バックアップ復元
    if (isset($_POST['restore_backup']) && isset($_FILES['restore_file'])) {
        if ($_FILES['restore_file']['error'] === UPLOAD_ERR_OK) {
            $uploadedFile = $_FILES['restore_file']['tmp_name'];
            $filename = $_FILES['restore_file']['name'];
            
            // ファイルタイプチェック
            if (pathinfo($filename, PATHINFO_EXTENSION) !== 'zip') {
                $_SESSION['message'] = 'ZIPファイルのみアップロード可能です';
                header('Location: index.php?tab=backup');
                exit;
            }
            
            $zip = new ZipArchive();
            if ($zip->open($uploadedFile) === TRUE) {
                // 復元処理（簡単な実装）
                $extractPath = '../temp_restore';
                if (!file_exists($extractPath)) {
                    mkdir($extractPath, 0755, true);
                }
                
                $zip->extractTo($extractPath);
                $zip->close();
                
                // データファイルの復元
                if (file_exists($extractPath . '/data')) {
                    $files = glob($extractPath . '/data/*.json');
                    foreach ($files as $file) {
                        $dest = '../assets/data/' . basename($file);
                        copy($file, $dest);
                    }
                }
                
                // 一時ディレクトリを削除
                function deleteDirectory($dir) {
                    if (is_dir($dir)) {
                        $files = array_diff(scandir($dir), array('.', '..'));
                        foreach ($files as $file) {
                            (is_dir("$dir/$file")) ? deleteDirectory("$dir/$file") : unlink("$dir/$file");
                        }
                        rmdir($dir);
                    }
                }
                deleteDirectory($extractPath);
                
                $_SESSION['message'] = 'バックアップからデータを復元しました';
            } else {
                $_SESSION['message'] = 'ZIPファイルの展開に失敗しました';
            }
        } else {
            $_SESSION['message'] = 'ファイルのアップロードに失敗しました';
        }
        header('Location: index.php?tab=backup');
        exit;
    }
}

// 不明なリクエスト
http_response_code(400);
echo 'Bad Request';
?>