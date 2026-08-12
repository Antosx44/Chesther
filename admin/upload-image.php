<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

require_admin();

$uploadDir = realpath(__DIR__ . '/../uploads/gallery');
if ($uploadDir === false) {
    $uploadDir = __DIR__ . '/../uploads/gallery';
}

$error = null;
$success = flash_get('success');
$editItem = null;

if (isset($_GET['edit'])) {
    try {
        $statement = db()->prepare('SELECT * FROM gallery_items WHERE id = :id LIMIT 1');
        $statement->execute(['id' => (int) $_GET['edit']]);
        $editItem = $statement->fetch() ?: null;
    } catch (Throwable $throwable) {
        $editItem = null;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? 'save');
    $itemId = (int) ($_POST['id'] ?? 0);

    try {
        if ($action === 'delete' && $itemId > 0) {
            $statement = db()->prepare('SELECT image_path FROM gallery_items WHERE id = :id LIMIT 1');
            $statement->execute(['id' => $itemId]);
            $row = $statement->fetch();

            $deleteStatement = db()->prepare('DELETE FROM gallery_items WHERE id = :id');
            $deleteStatement->execute(['id' => $itemId]);

            if ($row && !empty($row['image_path'])) {
                $filePath = __DIR__ . '/../' . ltrim((string) $row['image_path'], '/\\');
                if (is_file($filePath)) {
                    @unlink($filePath);
                }
            }

            flash_set('success', 'Gallery item deleted.');
            redirect_to('upload-image.php');
        }

        $title = trim((string) ($_POST['title'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $useCase = trim((string) ($_POST['use_case'] ?? ''));
        $altText = trim((string) ($_POST['alt_text'] ?? ''));
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);
        $features = preg_split('/\r\n|\r|\n/', trim((string) ($_POST['features'] ?? ''))) ?: [];
        $features = array_values(array_filter(array_map('trim', $features)));

        if ($title === '' || $description === '' || $useCase === '' || $altText === '') {
            throw new RuntimeException('Fill in all required fields.');
        }

        $imagePath = null;
        $existingImagePath = null;

        if ($itemId > 0) {
            $statement = db()->prepare('SELECT image_path FROM gallery_items WHERE id = :id LIMIT 1');
            $statement->execute(['id' => $itemId]);
            $existing = $statement->fetch();
            $existingImagePath = $existing ? (string) $existing['image_path'] : null;
        }

        if (!empty($_FILES['image']['name'])) {
            $upload = $_FILES['image'];

            if (($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                throw new RuntimeException('Image upload failed.');
            }

            $fileInfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $fileInfo->file((string) $upload['tmp_name']);
            $allowedMimeTypes = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
            ];

            if (!isset($allowedMimeTypes[$mimeType])) {
                throw new RuntimeException('Use JPG, PNG, or WebP images only.');
            }

            $extension = $allowedMimeTypes[$mimeType];
            $safeName = slugify($title) . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(3)) . '.' . $extension;
            $targetRelative = 'uploads/gallery/' . $safeName;
            $targetAbsolute = __DIR__ . '/../' . $targetRelative;

            if (!move_uploaded_file((string) $upload['tmp_name'], $targetAbsolute)) {
                throw new RuntimeException('Could not save the uploaded image.');
            }

            $imagePath = $targetRelative;

            if ($existingImagePath && $itemId > 0) {
                $oldFile = __DIR__ . '/../' . ltrim($existingImagePath, '/\\');
                if (is_file($oldFile)) {
                    @unlink($oldFile);
                }
            }
        } elseif ($itemId === 0) {
            throw new RuntimeException('Please upload an image for the new gallery item.');
        }

        if ($itemId > 0) {
            $fields = [
                'title' => $title,
                'description' => $description,
                'use_case' => $useCase,
                'alt_text' => $altText,
                'features_json' => json_encode($features, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'sort_order' => $sortOrder,
                'id' => $itemId,
            ];

            $sql = 'UPDATE gallery_items SET title = :title, description = :description, use_case = :use_case, alt_text = :alt_text, features_json = :features_json, sort_order = :sort_order';
            if ($imagePath !== null) {
                $sql .= ', image_path = :image_path';
                $fields['image_path'] = $imagePath;
            }
            $sql .= ' WHERE id = :id';

            $statement = db()->prepare($sql);
            $statement->execute($fields);
            flash_set('success', 'Gallery item updated.');
        } else {
            $statement = db()->prepare('INSERT INTO gallery_items (title, description, use_case, alt_text, features_json, image_path, sort_order) VALUES (:title, :description, :use_case, :alt_text, :features_json, :image_path, :sort_order)');
            $statement->execute([
                'title' => $title,
                'description' => $description,
                'use_case' => $useCase,
                'alt_text' => $altText,
                'features_json' => json_encode($features, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'image_path' => $imagePath,
                'sort_order' => $sortOrder,
            ]);
            flash_set('success', 'Gallery item added.');
        }

        redirect_to('upload-image.php');
    } catch (Throwable $throwable) {
        $error = $throwable->getMessage();
    }
}

$items = [];
try {
    $items = db()->query('SELECT id, title, use_case, sort_order, image_path, updated_at FROM gallery_items ORDER BY sort_order ASC, id DESC')->fetchAll();
} catch (Throwable $throwable) {
    $items = [];
}

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Gallery</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin.css">
</head>
<body>
    <div class="admin-shell">
        <header class="admin-nav">
            <div class="admin-nav-inner">
                <div class="admin-brand">
                    <div class="admin-brand-mark"><i class="fa-solid fa-shield-halved"></i></div>
                    <div class="admin-brand-text">
                        <div class="admin-brand-name">ChestherTech</div>
                        <div class="admin-brand-subtitle">Infra Admin Console</div>
                    </div>
                </div>
                <nav class="admin-nav-links">
                    <a class="admin-nav-link" href="index.php">Dashboard</a>
                    <a class="admin-nav-link" href="content.php">Content</a>
                    <a class="admin-nav-link" href="services.php">Services</a>
                    <a class="admin-nav-link" href="about.php">About</a>
                    <a class="admin-nav-link admin-nav-link-active" href="upload-image.php">Gallery</a>
                    <a class="admin-nav-link admin-nav-link-button" href="logout.php">Logout</a>
                </nav>
            </div>
        </header>

        <main class="admin-page">
            <div class="admin-page-hero">
                <div>
                    <p class="admin-kicker">Media library</p>
                    <h1 class="admin-title"><?php echo $editItem ? 'Edit gallery item' : 'Add gallery item'; ?></h1>
                    <p class="admin-subtitle">Use this page to add, edit, and delete the project cards shown on the homepage.</p>
                </div>
                <div class="admin-page-actions">
                    <a class="admin-btn admin-btn-secondary" href="index.php">Dashboard</a>
                    <a class="admin-btn admin-btn-secondary" href="logout.php">Logout</a>
                </div>
            </div>

        <?php if ($error): ?>
            <div class="admin-notice admin-notice-error"><?php echo h($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="admin-notice admin-notice-success"><?php echo h($success); ?></div>
        <?php endif; ?>

            <div class="admin-grid-wide">
                <section class="admin-panel">
                    <h2 style="margin:0;"><?php echo $editItem ? 'Update item' : 'Create item'; ?></h2>
                <form class="admin-form" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="id" value="<?php echo h((string) ($editItem['id'] ?? 0)); ?>">
                    <label for="title">Title</label>
                    <input id="title" name="title" required value="<?php echo h($editItem['title'] ?? ''); ?>">

                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="4" required><?php echo h($editItem['description'] ?? ''); ?></textarea>

                    <label for="use_case">Best fit / use case</label>
                    <input id="use_case" name="use_case" required value="<?php echo h($editItem['use_case'] ?? ''); ?>">

                    <label for="alt_text">Alt text</label>
                    <input id="alt_text" name="alt_text" required value="<?php echo h($editItem['alt_text'] ?? ''); ?>">

                    <div class="row">
                        <div>
                            <label for="sort_order">Sort order</label>
                            <input id="sort_order" name="sort_order" type="number" value="<?php echo h((string) ($editItem['sort_order'] ?? 0)); ?>">
                        </div>
                        <div>
                            <label for="image">Image <?php echo $editItem ? '(leave empty to keep current)' : ''; ?></label>
                            <input id="image" name="image" type="file" accept="image/png,image/jpeg,image/webp">
                        </div>
                    </div>

                    <label for="features">Features, one per line</label>
                    <textarea id="features" name="features" rows="5" placeholder="Dome cameras\nMotion alerts\nMobile app viewing"><?php
                        $featureValues = [];
                        if (!empty($editItem['features_json'])) {
                            $decoded = json_decode((string) $editItem['features_json'], true);
                            if (is_array($decoded)) {
                                $featureValues = array_map('strval', $decoded);
                            }
                        }
                        echo h(implode("\n", $featureValues));
                    ?></textarea>

                    <div class="admin-actions">
                        <button class="admin-btn admin-btn-primary" type="submit" name="action" value="save"><?php echo $editItem ? 'Save changes' : 'Add item'; ?></button>
                        <?php if ($editItem): ?>
                            <a class="admin-btn admin-btn-secondary" href="upload-image.php">Cancel edit</a>
                        <?php endif; ?>
                    </div>
                </form>
            </section>

                <section class="admin-panel admin-stack">
                    <h2 style="margin:0;">Current items</h2>
                <?php if (!$items): ?>
                    <p class="admin-muted">No gallery items yet.</p>
                <?php else: ?>
                    <div class="admin-table-wrap">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Preview</th>
                                    <th>Title</th>
                                    <th>Use case</th>
                                    <th>Sort</th>
                                    <th>Updated</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td><img class="thumb" src="<?php echo h(upload_url((string) $item['image_path'])); ?>" alt=""></td>
                                        <td><?php echo h($item['title']); ?></td>
                                        <td class="small muted"><?php echo h($item['use_case']); ?></td>
                                        <td><?php echo h((string) $item['sort_order']); ?></td>
                                        <td class="admin-small admin-muted"><?php echo h((string) $item['updated_at']); ?></td>
                                        <td>
                                            <div class="admin-actions" style="margin:0;">
                                                <a class="admin-btn admin-btn-secondary" href="upload-image.php?edit=<?php echo h((string) $item['id']); ?>">Edit</a>
                                                <form class="inline-form" method="POST" onsubmit="return confirm('Delete this gallery item?');">
                                                    <input type="hidden" name="id" value="<?php echo h((string) $item['id']); ?>">
                                                    <button class="admin-btn admin-btn-danger" type="submit" name="action" value="delete">Delete</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>
            </div>
        </main>
    </div>
</body>
</html>