<?php

declare(strict_types=1);

function data_path(string $relative): string
{
    return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . $relative;
}

function ensure_data_dir(string $relativeDir = ''): void
{
    $base = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'data';
    if ($relativeDir !== '') {
        $base .= DIRECTORY_SEPARATOR . $relativeDir;
    }
    if (!is_dir($base)) {
        mkdir($base, 0775, true);
    }
}

function current_year_month(): string
{
    return date('Y-m');
}

function normalize_year_month(string $yearMonth): string
{
    if (preg_match('/^(\d{4})[-\/]?(\d{2})$/', $yearMonth, $m)) {
        return $m[1] . '-' . $m[2];
    }
    $timestamp = strtotime($yearMonth);
    if ($timestamp === false) {
        return current_year_month();
    }
    return date('Y-m', $timestamp);
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function request_post(string $key, $default = null)
{
    return $_POST[$key] ?? $default;
}

function request_get(string $key, $default = null)
{
    return $_GET[$key] ?? $default;
}

function escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function csrf_token(): string
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token(?string $token): bool
{
    return is_string($token) && isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function format_currency(float $value): string
{
    return number_format($value, 0, '.', ',');
}

function parse_float($value): float
{
    if (is_numeric($value)) {
        return (float) $value;
    }
    $value = str_replace([',', ' '], '', (string) $value);
    return is_numeric($value) ? (float) $value : 0.0;
}

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function default_commission_rate(): float
{
    return 0.15;
}

function user_display_name(?string $userId, array $users): string
{
    if (!$userId) {
        return '-';
    }
    if (isset($users[$userId])) {
        $user = $users[$userId];
        if (isset($user['role']) && $user['role'] !== '') {
            $label = role_label((string) $user['role']);
            if ($label !== '') {
                return $label;
            }
        }
        if (isset($user['name']) && trim((string) $user['name']) !== '') {
            return (string) $user['name'];
        }
        if (isset($user['login']) && trim((string) $user['login']) !== '') {
            return (string) $user['login'];
        }
    }
    foreach ($users as $user) {
        $matchesId = ($user['id'] ?? null) === $userId;
        $matchesLogin = ($user['login'] ?? null) === $userId;
        if ($matchesId || $matchesLogin) {
            if (isset($user['role']) && $user['role'] !== '') {
                $label = role_label((string) $user['role']);
                if ($label !== '') {
                    return $label;
                }
            }
            return $user['name'] ?? ($user['login'] ?? $userId);
        }
    }
    return $userId;
}


