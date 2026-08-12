<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

$blocks = [];
$contact = [];

try {
    $statement = db()->query('SELECT block_key, title, body FROM content_blocks');
    foreach ($statement->fetchAll() as $row) {
        $blocks[(string) $row['block_key']] = [
            'title' => (string) ($row['title'] ?? ''),
            'body' => (string) ($row['body'] ?? ''),
        ];
    }
} catch (Throwable $throwable) {
    $blocks = [];
}

try {
    $statement = db()->query('SELECT phone, email, coverage, whatsapp_url, messenger_url FROM contact_settings ORDER BY id DESC LIMIT 1');
    $contact = $statement->fetch() ?: [];
} catch (Throwable $throwable) {
    $contact = [];
}

echo json_encode([
    'success' => true,
    'blocks' => $blocks,
    'contact' => $contact,
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);