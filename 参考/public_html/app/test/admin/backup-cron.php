<?php
/**
 * 自動バックアップスクリプト
 * cronジョブで実行する用
 * 
 * 使用例（ConoHaサーバーのcrontab）:
 * # 毎日午前3時にデータバックアップを作成
 * 0 3 * * * /usr/bin/php /home/your-account/public_html/admin/backup-cron.php data
 * 
 * # 毎週日曜日午前4時に完全バックアップを作成  
 * 0 4 * * 0 /usr/bin/php /home/your-account/public_html/admin/backup-cron.php full
 */

// コマンドライン実行のみ許可
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from command line.');
}

// バックアップタイプの取得
$backupType = $argv[1] ?? 'data';
if (!in_array($backupType, ['data', 'full'])) {
    die("Usage: php backup-cron.php [data|full]\n");
}

// バックアップディレクトリ
$backupDir = dirname(__DIR__) . '/assets/backups';
if (!file_exists($backupDir)) {
    mkdir($backupDir, 0755, true);
}

// ログファイル
$logFile = $backupDir . '/backup.log';

function writeLog($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
    echo "[$timestamp] $message\n";
}

// データバックアップ作成
function createDataBackup($backupDir) {
    $timestamp = date('Y-m-d_H-i-s');
    $backupFile = $backupDir . "/backup_auto_data_{$timestamp}.zip";
    
    $zip = new ZipArchive();
    if ($zip->open($backupFile, ZipArchive::CREATE) !== TRUE) {
        return ['success' => false, 'error' => 'ZIPファイルの作成に失敗しました'];
    }
    
    // 重要なデータファイル
    $dataFiles = [
        dirname($backupDir) . '/data/products.json',
        dirname($backupDir) . '/data/shops.json', 
        dirname($backupDir) . '/data/news.json',
        dirname($backupDir) . '/data/menu.json',
        dirname($backupDir) . '/data/sweets-quiz.json',
        dirname($backupDir) . '/data/topics_config.json',
        dirname($backupDir) . '/data/admin_config.json'
    ];
    
    $fileCount = 0;
    foreach ($dataFiles as $file) {
        if (file_exists($file)) {
            $zip->addFile($file, 'data/' . basename($file));
            $fileCount++;
        }
    }
    
    // アップロードファイル
    $uploadDirs = [
        dirname($backupDir) . '/uploads/menus',
        dirname($backupDir) . '/images/products', 
        dirname($backupDir) . '/images/shops',
        dirname($backupDir) . '/images/entertainment'
    ];
    
    foreach ($uploadDirs as $dir) {
        if (is_dir($dir)) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $relativePath = 'uploads/' . str_replace(dirname($backupDir) . '/', '', $file->getPathname());
                    $zip->addFile($file->getPathname(), $relativePath);
                    $fileCount++;
                }
            }
        }
    }
    
    $zip->close();
    
    return [
        'success' => true, 
        'filename' => basename($backupFile),
        'fileCount' => $fileCount,
        'size' => filesize($backupFile)
    ];
}

// 完全バックアップ作成
function createFullBackup($backupDir) {
    $timestamp = date('Y-m-d_H-i-s');
    $backupFile = $backupDir . "/backup_auto_full_{$timestamp}.zip";
    
    $zip = new ZipArchive();
    if ($zip->open($backupFile, ZipArchive::CREATE) !== TRUE) {
        return ['success' => false, 'error' => 'ZIPファイルの作成に失敗しました'];
    }
    
    $rootDir = dirname($backupDir);
    $backupDirs = [
        $rootDir . '/assets' => 'assets',
        dirname($rootDir) . '/admin' => 'admin',
        dirname($rootDir) . '/index.html' => 'index.html'
    ];
    
    $fileCount = 0;
    foreach ($backupDirs as $source => $destination) {
        if (is_file($source)) {
            $zip->addFile($source, $destination);
            $fileCount++;
        } elseif (is_dir($source)) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source));
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $relativePath = $destination . '/' . str_replace($source . '/', '', $file->getPathname());
                    $zip->addFile($file->getPathname(), $relativePath);
                    $fileCount++;
                }
            }
        }
    }
    
    $zip->close();
    
    return [
        'success' => true, 
        'filename' => basename($backupFile),
        'fileCount' => $fileCount,
        'size' => filesize($backupFile)
    ];
}

// 古いバックアップファイルを削除（30日以上前のファイル）
function cleanOldBackups($backupDir) {
    $files = glob($backupDir . '/backup_auto_*.zip');
    $deleted = 0;
    $cutoffTime = time() - (30 * 24 * 60 * 60); // 30日前
    
    foreach ($files as $file) {
        if (filemtime($file) < $cutoffTime) {
            unlink($file);
            $deleted++;
            writeLog("古いバックアップファイルを削除しました: " . basename($file));
        }
    }
    
    return $deleted;
}

// メイン処理
writeLog("自動バックアップを開始します (タイプ: $backupType)");

try {
    if ($backupType === 'data') {
        $result = createDataBackup($backupDir);
    } else {
        $result = createFullBackup($backupDir);
    }
    
    if ($result['success']) {
        $sizeKB = round($result['size'] / 1024, 2);
        writeLog("バックアップが完了しました: {$result['filename']} ({$result['fileCount']}ファイル, {$sizeKB}KB)");
        
        // 古いバックアップファイルをクリーンアップ
        $deleted = cleanOldBackups($backupDir);
        if ($deleted > 0) {
            writeLog("古いバックアップファイルを{$deleted}個削除しました");
        }
        
        exit(0);
    } else {
        writeLog("エラー: " . $result['error']);
        exit(1);
    }
    
} catch (Exception $e) {
    writeLog("例外が発生しました: " . $e->getMessage());
    exit(1);
}
?>