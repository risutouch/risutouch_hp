<?php
// 直接アクセス防止
if (!defined('ADMIN_ACCESS') || !isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    http_response_code(403);
    die('Access Denied');
}

// バックアップディレクトリ
$backupDir = '../assets/backups';
if (!file_exists($backupDir)) {
    mkdir($backupDir, 0755, true);
}

// 利用可能なバックアップファイルを取得
function getAvailableBackups($backupDir) {
    $backups = [];
    if (is_dir($backupDir)) {
        $files = glob($backupDir . '/backup_*.zip');
        foreach ($files as $file) {
            $filename = basename($file);
            $timestamp = str_replace(['backup_', '.zip'], '', $filename);
            $date = DateTime::createFromFormat('Y-m-d_H-i-s', $timestamp);
            $backups[] = [
                'filename' => $filename,
                'filepath' => $file,
                'date' => $date ? $date->format('Y年m月d日 H:i:s') : $timestamp,
                'size' => formatBytes(filesize($file))
            ];
        }
        // 新しい順にソート
        usort($backups, function($a, $b) {
            return strcmp($b['filename'], $a['filename']);
        });
    }
    return $backups;
}

// ファイルサイズを読みやすい形式に変換
function formatBytes($size, $precision = 2) {
    $base = log($size, 1024);
    $suffixes = ['B', 'KB', 'MB', 'GB', 'TB'];
    return round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[floor($base)];
}

$availableBackups = getAvailableBackups($backupDir);
?>

<!-- Backup Tab -->
<div class="tab-header">
    <h2>バックアップ管理</h2>
    <p class="tab-description">データのバックアップ・復元を管理します</p>
</div>

<!-- バックアップ作成セクション -->
<div class="backup-section">
    <h3>🛡️ バックアップ作成</h3>
    <div class="backup-options">
        <div class="backup-type">
            <h4>重要データバックアップ</h4>
            <p>JSONデータファイル（商品、販売店、お知らせなど）とアップロードファイルをバックアップします</p>
            <form method="POST" style="display: inline;">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <button type="submit" name="create_data_backup" class="btn btn-primary">
                    📦 データバックアップを作成
                </button>
            </form>
        </div>

        <div class="backup-type">
            <h4>完全バックアップ</h4>
            <p>システム全体（PHPファイル、画像、データ等すべて）をバックアップします</p>
            <form method="POST" style="display: inline;">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <button type="submit" name="create_full_backup" class="btn btn-secondary">
                    🗂️ 完全バックアップを作成
                </button>
            </form>
        </div>
    </div>
</div>

<!-- 自動バックアップ設定 -->
<div class="backup-section">
    <h3>⏰ 自動バックアップ設定</h3>
    <div class="auto-backup-info">
        <p><strong>現在の設定:</strong> 手動バックアップのみ</p>
        <div class="alert alert-info">
            <strong>💡 定期バックアップのご提案</strong><br>
            ConoHaサーバーでは、以下の方法で定期バックアップが可能です：
            <ul>
                <li><strong>ConoHa管理画面</strong>: 自動バックアップ機能を有効化</li>
                <li><strong>cronジョブ</strong>: 毎日決まった時間にバックアップスクリプトを実行</li>
                <li><strong>手動実行</strong>: 重要な更新前に手動でバックアップ作成</li>
            </ul>
        </div>
    </div>
</div>

<!-- バックアップ一覧 -->
<div class="backup-section">
    <h3>📋 バックアップ履歴</h3>
    <?php if (empty($availableBackups)): ?>
        <div class="no-backups">
            <p>まだバックアップが作成されていません。</p>
        </div>
    <?php else: ?>
        <div class="backup-list">
            <?php foreach ($availableBackups as $backup): ?>
                <div class="backup-item">
                    <div class="backup-info">
                        <h4><?php echo htmlspecialchars($backup['filename']); ?></h4>
                        <p>作成日時: <?php echo htmlspecialchars($backup['date']); ?></p>
                        <p>ファイルサイズ: <?php echo htmlspecialchars($backup['size']); ?></p>
                    </div>
                    <div class="backup-actions">
                        <a href="backup-handler.php?download=<?php echo urlencode($backup['filename']); ?>&csrf_token=<?php echo urlencode($csrf_token); ?>" 
                           class="btn btn-primary btn-small">
                            💾 ダウンロード
                        </a>
                        <form method="POST" style="display: inline;" onsubmit="return confirm('このバックアップを削除しますか？')">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                            <input type="hidden" name="delete_backup" value="<?php echo htmlspecialchars($backup['filename']); ?>">
                            <button type="submit" class="btn btn-danger btn-small">
                                🗑️ 削除
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- 復元機能 -->
<div class="backup-section">
    <h3>🔄 データ復元</h3>
    <div class="restore-options">
        <div class="alert alert-warning">
            <strong>⚠️ 注意</strong><br>
            データ復元は慎重に行ってください。現在のデータは上書きされます。
        </div>
        
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            <div class="form-group">
                <label for="restore_file">バックアップファイル (.zip)</label>
                <input type="file" id="restore_file" name="restore_file" accept=".zip" required>
                <small>バックアップZIPファイルを選択してください</small>
            </div>
            <button type="submit" name="restore_backup" class="btn btn-warning" 
                    onclick="return confirm('本当にデータを復元しますか？現在のデータは失われます。')">
                🔄 バックアップから復元
            </button>
        </form>
    </div>
</div>

<style>
.backup-section {
    background: white;
    padding: 24px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    margin-bottom: 24px;
}

.backup-section h3 {
    color: var(--primary-color);
    margin-bottom: 16px;
    font-size: 1.4rem;
}

.backup-options {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 24px;
    margin-top: 16px;
}

.backup-type {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    border-left: 4px solid var(--primary-color);
}

.backup-type h4 {
    color: var(--gray-dark);
    margin-bottom: 8px;
    font-size: 1.1rem;
}

.backup-type p {
    color: var(--gray);
    font-size: 0.9rem;
    margin-bottom: 16px;
}

.auto-backup-info {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
}

.backup-list {
    display: grid;
    gap: 16px;
}

.backup-item {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.backup-info h4 {
    color: var(--gray-dark);
    margin-bottom: 4px;
    font-size: 1.1rem;
}

.backup-info p {
    color: var(--gray);
    font-size: 0.9rem;
    margin: 2px 0;
}

.backup-actions {
    display: flex;
    gap: 8px;
}

.restore-options {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
}

.no-backups {
    background: #f8f9fa;
    padding: 40px;
    border-radius: 8px;
    text-align: center;
    color: var(--gray);
}

@media (max-width: 768px) {
    .backup-item {
        flex-direction: column;
        gap: 16px;
        align-items: stretch;
    }
    
    .backup-actions {
        justify-content: center;
    }
}
</style>