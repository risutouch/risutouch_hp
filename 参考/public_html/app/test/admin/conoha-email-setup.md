# ConoHaメールサーバー設定ガイド

ConoHaレンタルサーバーでメール送信機能を設定する方法を説明します。

## 📧 ConoHaメール設定情報

**提供された情報**：
- **SMTPサーバー**: mail37.conoha.ne.jp
- **送信メールアドレス**: info@risutouch.com
- **プロトコル**: SMTP, POP, IMAP対応

## 🔧 設定内容

### 自動設定（実装済み）
システムは以下の順序でメール送信を試行します：

1. **ConoHaメールサーバー** (本番環境推奨)
   - SMTP: mail37.conoha.ne.jp:587
   - From: info@risutouch.com

2. **標準mail()関数** (レンタルサーバー標準)
   - サーバー内蔵のメール機能を使用

3. **MailHog** (ローカル開発用)
   - 開発環境でのテスト用

## 🚀 本番環境への配置手順

### 1. ファイルアップロード
```bash
# admin/フォルダを丸ごとConoHaサーバーにアップロード
/public_html/admin/
├── index.php
├── security.php
├── products.php
├── shops.php
├── menu.php
├── topics.php
├── style.css
├── script.php.js
├── totp-helper.php
└── (その他ファイル)
```

### 2. メールアドレス設定確認
ConoHaコントロールパネルで以下を確認：

1. **メールアドレス作成**
   - `info@risutouch.com` が作成済みであること
   - パスワードが設定されていること

2. **DNS設定**
   - MXレコードが正しく設定されていること
   - SPFレコードの設定（推奨）

### 3. PHPメール設定
ConoHaサーバーでは通常、追加設定は不要です。

必要に応じて `.htaccess` で設定：
```apache
# メール送信の設定（通常は不要）
php_value SMTP mail37.conoha.ne.jp
php_value smtp_port 587
php_value sendmail_from info@risutouch.com
```

## 🧪 テスト方法

### 本番環境でのテスト
1. **管理画面にアクセス**: https://risutouch.com/admin/
2. **ログイン試行**: risutouch@gmail.com + admin2025
3. **メール確認**: メールクライアントまたはWebメールで確認
4. **認証コード入力**: 6桁コードを入力
5. **ログイン完了**: 管理画面にアクセス確認

### デバッグ情報確認
PHPエラーログを確認してメール送信状況をチェック：
```bash
tail -f /path/to/php/error.log
```

ログ出力例：
```
=== メール認証コード送信 ===
送信先: risutouch@gmail.com
認証コード: 123456
Mail sent successfully via ConoHa SMTP
```

## 🔒 セキュリティ設定

### 1. ファイル権限設定
```bash
chmod 644 admin/*.php
chmod 644 admin/*.css
chmod 644 admin/*.js
chmod 600 assets/data/*.json  # データファイルは読み取り専用
```

### 2. .htaccess設定
`admin/.htaccess` で管理画面を保護：
```apache
# 管理画面へのアクセス制限
<Files "*.json">
    Order allow,deny
    Deny from all
</Files>

# セキュリティヘッダー
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options DENY
Header always set X-XSS-Protection "1; mode=block"
```

## 📋 トラブルシューティング

### よくある問題

1. **メール送信エラー**
   ```
   ConoHa SMTP mail sending failed
   ```
   **解決策**:
   - ConoHaコントロールパネルでメールアドレス設定を確認
   - SMTPサーバー名を確認（mail37.conoha.ne.jp）
   - ポート番号を25に変更してテスト

2. **メールが届かない**
   **解決策**:
   - 迷惑メールフォルダを確認
   - SPFレコードの設定を確認
   - メールアドレスのタイポを確認

3. **認証エラー**
   **解決策**:
   - info@risutouch.com のパスワードを確認
   - SMTP認証が必要な場合は設定を追加

### 高度な設定（必要時）

SMTP認証が必要な場合の設定例：
```php
function sendViaConoHaSMTPAuth($email, $subject, $message) {
    // SMTP認証が必要な場合の設定
    ini_set('SMTP', 'mail37.conoha.ne.jp');
    ini_set('smtp_port', '587');
    ini_set('sendmail_from', 'info@risutouch.com');
    
    // 認証情報（必要に応じて）
    ini_set('smtp_username', 'info@risutouch.com');
    ini_set('smtp_password', 'your-email-password');
    
    $headers = "From: info@risutouch.com\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    
    return mail($email, $subject, $message, $headers);
}
```

## ✅ 配置チェックリスト

- [ ] ファイルをConoHaサーバーにアップロード
- [ ] info@risutouch.com メールアドレス作成確認
- [ ] DNS設定（MX, SPF）確認
- [ ] ファイル権限設定
- [ ] .htaccess設定
- [ ] テストログイン実行
- [ ] メール送信テスト
- [ ] エラーログ確認

ConoHaの標準メール機能を使用するため、特別な設定は不要で簡単に動作するはずです！