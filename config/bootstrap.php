<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/database.php';

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $config = db_config();
    $dsn = 'mysql:host=' . $config['host'] . ';dbname=' . $config['name'] . ';charset=utf8mb4';

    $pdo = new PDO($dsn, $config['user'], $config['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
}

function h(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect_to(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function is_admin_logged_in(): bool
{
    return !empty($_SESSION['admin_id']);
}

function require_admin(): void
{
    if (!is_admin_logged_in()) {
        redirect_to('login.php');
    }
}

function flash_set(string $key, string $message): void
{
    $_SESSION['flash_messages'][$key] = $message;
}

function flash_get(string $key): ?string
{
    if (!isset($_SESSION['flash_messages'][$key])) {
        return null;
    }

    $message = (string) $_SESSION['flash_messages'][$key];
    unset($_SESSION['flash_messages'][$key]);

    return $message;
}

function slugify(string $value): string
{
    $value = preg_replace('/[^a-zA-Z0-9]+/', '-', strtolower(trim($value))) ?? '';
    return trim($value, '-') ?: 'item';
}

function upload_url(string $relativePath): string
{
    return str_replace('\\', '/', $relativePath);
}