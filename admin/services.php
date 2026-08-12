<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

require_admin();

$error = null;
$success = flash_get('success');
$editItem = null;

if (isset($_GET['edit'])) {
    try {
        $statement = db()->prepare('SELECT * FROM service_items WHERE id = :id LIMIT 1');
        $statement->execute(['id' => (int) $_GET['edit']]);
        $editItem = $statement->fetch() ?: null;
    } catch (Throwable $throwable) {
        $editItem = null;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = (string) ($_POST['action'] ?? 'save');
        $itemId = (int) ($_POST['id'] ?? 0);

        if ($action === 'delete' && $itemId > 0) {
            $statement = db()->prepare('DELETE FROM service_items WHERE id = :id');
            $statement->execute(['id' => $itemId]);
            flash_set('success', 'Service item deleted.');
            redirect_to('services.php');
        }

        $title = trim((string) ($_POST['title'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $iconClass = trim((string) ($_POST['icon_class'] ?? ''));
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);
        $features = preg_split('/\r\n|\r|\n/', trim((string) ($_POST['features'] ?? ''))) ?: [];
        $features = array_values(array_filter(array_map('trim', $features)));

        if ($title === '' || $description === '' || $iconClass === '') {
            throw new RuntimeException('Title, description, and icon class are required.');
        }

        if ($itemId > 0) {
            $statement = db()->prepare('UPDATE service_items SET title = :title, description = :description, icon_class = :icon_class, features_json = :features_json, sort_order = :sort_order WHERE id = :id');
            $statement->execute([
                'title' => $title,
                'description' => $description,
                'icon_class' => $iconClass,
                'features_json' => json_encode($features, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'sort_order' => $sortOrder,
                'id' => $itemId,
            ]);
            flash_set('success', 'Service item updated.');
        } else {
            $statement = db()->prepare('INSERT INTO service_items (title, description, icon_class, features_json, sort_order) VALUES (:title, :description, :icon_class, :features_json, :sort_order)');
            $statement->execute([
                'title' => $title,
                'description' => $description,
                'icon_class' => $iconClass,
                'features_json' => json_encode($features, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'sort_order' => $sortOrder,
            ]);
            flash_set('success', 'Service item added.');
        }

        redirect_to('services.php');
    } catch (Throwable $throwable) {
        $error = $throwable->getMessage();
    }
}

$items = [];
try {
    $items = db()->query('SELECT id, title, description, icon_class, features_json, sort_order, updated_at FROM service_items ORDER BY sort_order ASC, id DESC')->fetchAll();
} catch (Throwable $throwable) {
    $items = [];
}

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Services</title>
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
                    <a class="admin-nav-link admin-nav-link-active" href="services.php">Services</a>
                    <a class="admin-nav-link" href="about.php">About</a>
                    <a class="admin-nav-link" href="upload-image.php">Gallery</a>
                    <a class="admin-nav-link admin-nav-link-button" href="logout.php">Logout</a>
                </nav>
            </div>
        </header>

        <main class="admin-page">
            <div class="admin-page-hero">
                <div>
                    <p class="admin-kicker">Service catalog</p>
                    <h1 class="admin-title"><?php echo $editItem ? 'Edit service item' : 'Add service item'; ?></h1>
                    <p class="admin-subtitle">Manage the service cards shown in the homepage section.</p>
                </div>
                <div class="admin-page-actions">
                    <a class="admin-btn admin-btn-secondary" href="index.php">Dashboard</a>
                    <a class="admin-btn admin-btn-secondary" href="content.php">Content</a>
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
                <form class="admin-form" method="POST">
                    <input type="hidden" name="id" value="<?php echo h((string) ($editItem['id'] ?? 0)); ?>">
                    <label for="title">Title</label>
                    <input id="title" name="title" required value="<?php echo h($editItem['title'] ?? ''); ?>">

                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="4" required><?php echo h($editItem['description'] ?? ''); ?></textarea>

                    <label for="icon_class">Icon class</label>
                    <input id="icon_class" name="icon_class" required value="<?php echo h($editItem['icon_class'] ?? 'fa-solid fa-shield-halved'); ?>" placeholder="fa-solid fa-video">

                    <div class="row">
                        <div>
                            <label for="sort_order">Sort order</label>
                            <input id="sort_order" name="sort_order" type="number" value="<?php echo h((string) ($editItem['sort_order'] ?? 0)); ?>">
                        </div>
                        <div>
                            <label>Preview</label>
                            <div class="icon-preview"><i class="<?php echo h($editItem['icon_class'] ?? 'fa-solid fa-shield-halved'); ?>"></i></div>
                        </div>
                    </div>

                    <label for="features">Features, one per line</label>
                    <textarea id="features" name="features" rows="5" placeholder="Mobile access\nMotion alerts\nNight vision"><?php
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
                            <a class="admin-btn admin-btn-secondary" href="services.php">Cancel edit</a>
                        <?php endif; ?>
                    </div>
                </form>
            </section>

                <section class="admin-panel admin-stack">
                    <h2 style="margin:0;">Current items</h2>
                <?php if (!$items): ?>
                    <p class="admin-muted">No service items yet.</p>
                <?php else: ?>
                    <div class="admin-table-wrap">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Icon</th>
                                    <th>Title</th>
                                    <th>Sort</th>
                                    <th>Updated</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td><i class="<?php echo h($item['icon_class']); ?>"></i></td>
                                        <td><?php echo h($item['title']); ?><div class="admin-small admin-muted"><?php echo h($item['description']); ?></div></td>
                                        <td><?php echo h((string) $item['sort_order']); ?></td>
                                        <td class="small muted"><?php echo h((string) $item['updated_at']); ?></td>
                                        <td>
                                            <div class="admin-actions" style="margin:0;">
                                                <a class="admin-btn admin-btn-secondary" href="services.php?edit=<?php echo h((string) $item['id']); ?>">Edit</a>
                                                <form class="inline-form" method="POST" onsubmit="return confirm('Delete this service item?');">
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