<?php
// 直接アクセス防止
if (!defined('ADMIN_ACCESS') || !isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    http_response_code(403);
    die('Access Denied');
}

// CSRF トークン生成
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// バックアップディレクトリ
$backupDir = dirname(__DIR__) . '/assets/backups';
if (!file_exists($backupDir)) {
    mkdir($backupDir, 0755, true);
}

// POST処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_data_backup'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $message = 'CSRF トークンが無効です';
        $message_type = 'error';
    } else {
        // バックアップ作成テスト
        $timestamp = date('Y-m-d_H-i-s');
        $backupFile = $backupDir . "/backup_data_{$timestamp}.zip";
        
        if (class_exists('ZipArchive')) {
            $zip = new ZipArchive();
            if ($zip->open($backupFile, ZipArchive::CREATE) === TRUE) {
                // データファイルを追加
                $baseDir = dirname(__DIR__);
                $dataFiles = [
                    $baseDir . '/assets/data/products.json',
                    $baseDir . '/assets/data/shops.json', 
                    $baseDir . '/assets/data/news.json',
                    $baseDir . '/assets/data/site_config.json'
                ];
                
                $fileCount = 0;
                foreach ($dataFiles as $file) {
                    if (file_exists($file)) {
                        $zip->addFile($file, 'data/' . basename($file));
                        $fileCount++;
                    }
                }
                
                $zip->close();
                $message = "データバックアップを作成しました ({$fileCount}ファイル)";
                $message_type = 'success';
            } else {
                $message = 'ZIPファイルの作成に失敗しました';
                $message_type = 'error';
            }
        } else {
            $message = 'ZipArchive拡張が利用できません';
            $message_type = 'error';
        }
    }
}

echo "<h1>バックアップページ</h1>";
if (isset($message)) {
    $class = $message_type === 'success' ? 'alert-success' : 'alert-error';
    echo "<div class='alert $class'>$message</div>";
}
?>

<div class="backup-section">
    <h2>バックアップ管理</h2>
    
    <div class="backup-actions">
        <div class="backup-option">
            <h3>📦 データバックアップ</h3>
            <p>JSONデータファイル（商品、販売店、お知らせなど）をバックアップします</p>
            <form method="POST" style="display: inline;">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <button type="submit" name="create_data_backup" class="btn btn-primary">
                    📦 データバックアップを作成
                </button>
            </form>
        </div>
    </div>
    
    <div class="backup-history">
        <h3>バックアップ履歴</h3>
        <?php
        // バックアップファイル一覧を取得
        $backups = [];
        if (is_dir($backupDir)) {
            $files = glob($backupDir . '/backup_*.zip');
            if ($files) {
                foreach ($files as $file) {
                    $filename = basename($file);
                    $backups[] = [
                        'filename' => $filename,
                        'date' => date('Y年m月d日 H:i:s', filemtime($file)),
                        'size' => round(filesize($file) / 1024, 2) . ' KB'
                    ];
                }
                // 新しい順にソート
                usort($backups, function($a, $b) {
                    return strcmp($b['filename'], $a['filename']);
                });
            }
        }
        
        if (empty($backups)): ?>
            <p>バックアップファイルがありません。</p>
        <?php else: ?>
            <div class="backup-list">
                <?php foreach ($backups as $backup): ?>
                    <div class="backup-item">
                        <div class="backup-info">
                            <span class="backup-filename"><?php echo htmlspecialchars($backup['filename']); ?></span>
                            <span class="backup-date"><?php echo htmlspecialchars($backup['date']); ?></span>
                            <span class="backup-size"><?php echo htmlspecialchars($backup['size']); ?></span>
                        </div>
                        <div class="backup-actions">
                            <a href="../assets/backups/<?php echo htmlspecialchars($backup['filename']); ?>" 
                               class="btn btn-small" download>
                                📥 ダウンロード
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.backup-section {
    padding: 20px;
}

.backup-actions {
    margin: 20px 0;
}

.backup-option {
    border: 1px solid #ddd;
    padding: 15px;
    margin: 10px 0;
    border-radius: 5px;
}

.btn {
    background: #007bff;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
}

.btn-primary {
    background: #007bff;
}

.btn:hover {
    opacity: 0.8;
}

.alert {
    padding: 10px;
    margin: 10px 0;
    border-radius: 5px;
}

.alert-success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-error {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.backup-list {
    border: 1px solid #ddd;
    border-radius: 5px;
    margin-top: 10px;
}

.backup-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 15px;
    border-bottom: 1px solid #eee;
}

.backup-item:last-child {
    border-bottom: none;
}

.backup-info {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.backup-filename {
    font-weight: bold;
}

.backup-date, .backup-size {
    font-size: 0.9em;
    color: #666;
}

.btn-small {
    padding: 4px 8px;
    font-size: 0.9em;
}
</style>