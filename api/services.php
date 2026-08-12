<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

$services = [];

try {
    $statement = db()->query('SELECT id, title, description, icon_class, features_json, sort_order FROM service_items ORDER BY sort_order ASC, id DESC');
    foreach ($statement->fetchAll() as $row) {
        $features = [];
        if (!empty($row['features_json'])) {
            $decoded = json_decode((string) $row['features_json'], true);
            if (is_array($decoded)) {
                $features = array_values(array_filter(array_map('strval', $decoded)));
            }
        }

        $services[] = [
            'id' => (int) $row['id'],
            'title' => (string) $row['title'],
            'description' => (string) $row['description'],
            'icon_class' => (string) $row['icon_class'],
            'features' => $features,
            'sort_order' => (int) $row['sort_order'],
        ];
    }
} catch (Throwable $throwable) {
    $services = [];
}

echo json_encode([
    'success' => true,
    'services' => $services,
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);