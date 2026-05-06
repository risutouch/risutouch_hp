<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/json_storage.php';

function users_file_path(): string
{
    return data_path('users.json');
}

function load_users(): array
{
    $raw = read_json_file(users_file_path(), []);
    if (!is_array($raw)) {
        return [];
    }
    $indexed = [];
    foreach ($raw as $user) {
        if (!is_array($user) || !isset($user['id'])) {
            continue;
        }
        $indexed[$user['id']] = $user;
    }
    return $indexed;
}

function save_users(array $users): void
{
    write_json_file(users_file_path(), array_values($users));
}

function find_user_by_login(string $login): ?array
{
    $users = load_users();
    foreach ($users as $user) {
        if (isset($user['login']) && strcasecmp($user['login'], $login) === 0) {
            return $user;
        }
    }
    return null;
}

function find_user(string $userId): ?array
{
    $users = load_users();
    return $users[$userId] ?? null;
}

function login_user(array $user): void
{
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role'] = $user['role'] ?? null;
    session_regenerate_id(true);
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function current_user(): ?array
{
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    return find_user($_SESSION['user_id']);
}

function require_login(): void
{
    if (!current_user()) {
        redirect('login.php');
    }
}

function current_user_id(): ?string
{
    return $_SESSION['user_id'] ?? null;
}

function current_user_role(): ?string
{
    return $_SESSION['role'] ?? null;
}

function is_supplier(): bool
{
    return current_user_role() === 'supplier';
}

function is_receiver(): bool
{
    return current_user_role() === 'receiver';
}

