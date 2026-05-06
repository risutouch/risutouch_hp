<?php
require_once __DIR__ . '/lib/bootstrap.php';
require_once __DIR__ . '/lib/rate_limit.php';

if (current_user()) {
    redirect('index.php');
}

$error = null;
$loginValue = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = request_post('csrf_token');
    if (!verify_csrf_token($token)) {
        $error = '不正なリクエストです。もう一度お試しください。';
    } else {
        $loginValue = trim((string) request_post('login', ''));
        $password = (string) request_post('password', '');

        // レート制限チェック
        $rateLimitCheck = check_rate_limit($loginValue);
        if (!$rateLimitCheck['allowed']) {
            $error = $rateLimitCheck['message'];
        } else {
            $user = $loginValue !== '' ? find_user_by_login($loginValue) : null;

            if (!$user || !isset($user['password_hash']) || !password_verify($password, $user['password_hash'])) {
                // ログイン失敗を記録
                record_failed_login($loginValue);

                $remaining = $rateLimitCheck['remaining_attempts'] - 1;
                if ($remaining > 0) {
                    $error = "ユーザーIDまたはパスワードが正しくありません。(残り試行回数: {$remaining}回)";
                } else {
                    $error = 'ユーザーIDまたはパスワードが正しくありません。';
                }
            } else {
                // ログイン成功 - 試行回数をリセット
                reset_login_attempts($loginValue);
                log_security_event('login_success', [
                    'login' => $loginValue,
                    'ip' => get_client_identifier(),
                    'timestamp' => date('Y-m-d H:i:s')
                ]);
                login_user($user);
                redirect('index.php');
            }
        }
    }
}

$pageTitle = 'ログイン';
include __DIR__ . '/partials/header.php';
?>

<div class="card" style="max-width: 420px; margin: 3rem auto;">
    <h1 style="margin-top: 0; font-size: 1.4rem;">ログイン</h1>
    <?php if ($error): ?>
        <div class="alert" style="background: #ffe3e3; color: #a40000; padding: 0.75rem 1rem; border-radius: 4px; margin-bottom: 1rem;">
            <?= escape($error) ?>
        </div>
    <?php endif; ?>
    <form method="post" action="login.php">
        <input type="hidden" name="csrf_token" value="<?= escape(csrf_token()) ?>">
        <div class="form-row" style="flex-direction: column;">
            <label>ユーザーID
                <input type="text" name="login" value="<?= escape($loginValue) ?>" required autofocus>
            </label>
            <label>パスワード
                <input type="password" name="password" required>
            </label>
        </div>
        <div class="actions" style="justify-content: flex-end;">
            <button type="submit" class="primary">ログイン</button>
        </div>
    </form>
</div>

<?php include __DIR__ . '/partials/footer.php'; ?>
