<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/json_storage.php';

function rate_limit_file_path(): string
{
    return data_path('rate_limits.json');
}

function load_rate_limits(): array
{
    return read_json_file(rate_limit_file_path(), []);
}

function save_rate_limits(array $limits): void
{
    write_json_file(rate_limit_file_path(), $limits);
}

function get_client_identifier(): string
{
    // IPアドレスベースの識別
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    // プロキシ経由の場合は実際のIPを取得
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($ips[0]);
    } elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    }

    return $ip;
}

function clean_expired_rate_limits(array &$limits, int $currentTime): void
{
    foreach ($limits as $key => $data) {
        if (isset($data['unlock_at']) && $data['unlock_at'] <= $currentTime) {
            unset($limits[$key]);
        } elseif (isset($data['attempts']) && is_array($data['attempts'])) {
            // 1時間以上前の試行記録を削除
            $data['attempts'] = array_filter($data['attempts'], function($timestamp) use ($currentTime) {
                return $timestamp > ($currentTime - 3600);
            });
            if (empty($data['attempts'])) {
                unset($limits[$key]);
            } else {
                $limits[$key] = $data;
            }
        }
    }
}

function check_rate_limit(string $login, int $maxAttempts = 5, int $lockoutMinutes = 15): array
{
    $limits = load_rate_limits();
    $currentTime = time();
    $identifier = get_client_identifier();
    $key = $identifier . ':' . $login;

    // 期限切れのレコードをクリーンアップ
    clean_expired_rate_limits($limits, $currentTime);

    // ロックアウト中かチェック
    if (isset($limits[$key]['unlock_at']) && $limits[$key]['unlock_at'] > $currentTime) {
        $remainingMinutes = ceil(($limits[$key]['unlock_at'] - $currentTime) / 60);
        return [
            'allowed' => false,
            'locked_until' => $limits[$key]['unlock_at'],
            'message' => "アカウントがロックされています。{$remainingMinutes}分後に再試行してください。"
        ];
    }

    // 試行回数をチェック
    $attempts = $limits[$key]['attempts'] ?? [];
    $recentAttempts = array_filter($attempts, function($timestamp) use ($currentTime) {
        return $timestamp > ($currentTime - 900); // 15分以内
    });

    if (count($recentAttempts) >= $maxAttempts) {
        // ロックアウト
        $unlockAt = $currentTime + ($lockoutMinutes * 60);
        $limits[$key] = [
            'attempts' => $recentAttempts,
            'unlock_at' => $unlockAt,
            'locked_at' => $currentTime
        ];
        save_rate_limits($limits);

        return [
            'allowed' => false,
            'locked_until' => $unlockAt,
            'message' => "試行回数が上限に達しました。{$lockoutMinutes}分後に再試行してください。"
        ];
    }

    return [
        'allowed' => true,
        'remaining_attempts' => $maxAttempts - count($recentAttempts)
    ];
}

function record_failed_login(string $login): void
{
    $limits = load_rate_limits();
    $currentTime = time();
    $identifier = get_client_identifier();
    $key = $identifier . ':' . $login;

    clean_expired_rate_limits($limits, $currentTime);

    if (!isset($limits[$key])) {
        $limits[$key] = ['attempts' => []];
    }

    $limits[$key]['attempts'][] = $currentTime;
    save_rate_limits($limits);

    // ログファイルに記録
    log_security_event('login_failed', [
        'login' => $login,
        'ip' => $identifier,
        'timestamp' => date('Y-m-d H:i:s', $currentTime)
    ]);
}

function reset_login_attempts(string $login): void
{
    $limits = load_rate_limits();
    $identifier = get_client_identifier();
    $key = $identifier . ':' . $login;

    if (isset($limits[$key])) {
        unset($limits[$key]);
        save_rate_limits($limits);
    }
}

function log_security_event(string $event, array $data): void
{
    ensure_data_dir();
    $logFile = data_path('security.log');
    $logEntry = date('Y-m-d H:i:s') . " [{$event}] " . json_encode($data, JSON_UNESCAPED_UNICODE) . PHP_EOL;
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}
