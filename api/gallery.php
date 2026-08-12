<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

$images = [];

try {
    $statement = db()->query('SELECT title, description, use_case, alt_text, features_json, image_path FROM gallery_items ORDER BY sort_order ASC, id DESC');
    foreach ($statement->fetchAll() as $row) {
        $features = [];
        if (!empty($row['features_json'])) {
            $decoded = json_decode((string) $row['features_json'], true);
            if (is_array($decoded)) {
                $features = array_values(array_filter(array_map('strval', $decoded)));
            }
        }

        $images[] = [
            'title' => (string) $row['title'],
            'description' => (string) $row['description'],
            'use' => (string) $row['use_case'],
            'alt' => (string) $row['alt_text'],
            'features' => $features,
            'image' => upload_url((string) $row['image_path']),
        ];
    }
} catch (Throwable $throwable) {
    $images = [];
}

echo json_encode([
    'success' => true,
    'images' => $images,
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);