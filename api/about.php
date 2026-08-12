<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

$items = [];
$highlights = [];

try {
    $statement = db()->query('SELECT id, item_type, label, title, body, icon_class, sort_order FROM about_items ORDER BY sort_order ASC, id ASC');
    foreach ($statement->fetchAll() as $row) {
        $entry = [
            'id' => (int) $row['id'],
            'label' => (string) $row['label'],
            'title' => (string) $row['title'],
            'body' => (string) $row['body'],
            'icon_class' => (string) ($row['icon_class'] ?? ''),
            'sort_order' => (int) $row['sort_order'],
        ];

        if ((string) $row['item_type'] === 'highlight') {
            $highlights[] = $entry;
        } else {
            $items[] = $entry;
        }
    }
} catch (Throwable $throwable) {
    $items = [];
    $highlights = [];
}

echo json_encode([
    'success' => true,
    'items' => $items,
    'highlights' => $highlights,
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);