<?php
// 直接アクセス防止
if (!defined('ADMIN_ACCESS') || !isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    http_response_code(403);
    die('Access Denied');
}

// セキュリティ設定画面
?>
<div class="tab-content active">
    <div class="tab-header">
        <div>
            <h2>セキュリティ設定</h2>
            <p class="tab-description">管理画面のセキュリティ設定を管理します</p>
        </div>
    </div>

    
    <!-- メール設定セクション -->
    <div class="security-section">
        <h3>メール認証設定</h3>
        
        <div class="alert alert-info">
            <strong>📧 メール二段階認証が有効です</strong><br>
            ログイン時にメールアドレスに送信される認証コードが必要です。
        </div>
        
        <div class="email-settings">
            <h4>現在の設定</h4>
            <p><strong>送信元メールアドレス:</strong> info@risutouch.com</p>
            <p><strong>対象メールアドレス:</strong> <?php echo htmlspecialchars($ADMIN_EMAIL); ?></p>
            <p><strong>認証コード有効期限:</strong> 10分間</p>
            <p><strong>メール送信方法:</strong> ConoHaメールサーバー (mail37.conoha.ne.jp)</p>
            <p><strong>SMTPサーバー:</strong> mail37.conoha.ne.jp:587
            </p>
        </div>
        
    </div>
    
    <!-- その他のセキュリティ情報 -->
    <div class="security-info">
        <h3>その他のセキュリティ機能</h3>
        <div class="security-features">
            <div class="feature">
                <h4>🔒 ブルートフォース攻撃対策</h4>
                <p>5回連続でログインに失敗すると、15分間アクセスがブロックされます</p>
            </div>
            <div class="feature">
                <h4>⏰ セッションタイムアウト</h4>
                <p>2時間操作がない場合、自動的にログアウトされます</p>
            </div>
            <div class="feature">
                <h4>📧 メール二段階認証</h4>
                <p>メールアドレスに送信される認証コードによる安全なログイン</p>
            </div>
            <div class="feature">
                <h4>🛡️ CSRF対策</h4>
                <p>すべてのフォーム送信でCSRFトークンによる検証を実施しています</p>
            </div>
            <div class="feature">
                <h4>🔐 パスワードリセット</h4>
                <p>安全なパスワードリセット機能でアカウント復旧が可能</p>
            </div>
        </div>
    </div>
</div>

<style>
.security-section {
    background: white;
    padding: 24px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    margin-bottom: 24px;
}

.security-section h3 {
    color: var(--primary-color);
    margin-bottom: 16px;
    font-size: 1.4rem;
}


.security-info {
    background: white;
    padding: 24px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.security-info h3 {
    color: var(--primary-color);
    margin-bottom: 16px;
    font-size: 1.4rem;
}

.security-features {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 16px;
    margin-top: 16px;
}

.feature {
    background: #f8f9fa;
    padding: 16px;
    border-radius: 6px;
    border-left: 4px solid var(--primary-color);
}

.feature h4 {
    color: var(--gray-dark);
    margin-bottom: 8px;
    font-size: 1.1rem;
}

.feature p {
    color: var(--gray);
    font-size: 0.9rem;
    margin: 0;
}

code {
    background: #f1f3f4;
    padding: 2px 6px;
    border-radius: 3px;
    font-family: monospace;
    font-size: 0.9em;
    color: #d63384;
}
</style>