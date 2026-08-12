<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

require_admin();

$error = null;
$success = flash_get('success');
$editItem = null;

if (isset($_GET['edit'])) {
    try {
        $statement = db()->prepare('SELECT * FROM about_items WHERE id = :id LIMIT 1');
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
            $statement = db()->prepare('DELETE FROM about_items WHERE id = :id');
            $statement->execute(['id' => $itemId]);
            flash_set('success', 'About item deleted.');
            redirect_to('about.php');
        }

        $itemType = (string) ($_POST['item_type'] ?? 'feature');
        $label = trim((string) ($_POST['label'] ?? ''));
        $title = trim((string) ($_POST['title'] ?? ''));
        $body = trim((string) ($_POST['body'] ?? ''));
        $iconClass = trim((string) ($_POST['icon_class'] ?? ''));
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);

        if (!in_array($itemType, ['feature', 'highlight'], true)) {
            throw new RuntimeException('Invalid item type.');
        }

        if ($label === '' || $title === '' || $body === '') {
            throw new RuntimeException('Label, title, and body are required.');
        }

        if ($itemId > 0) {
            $statement = db()->prepare('UPDATE about_items SET item_type = :item_type, label = :label, title = :title, body = :body, icon_class = :icon_class, sort_order = :sort_order WHERE id = :id');
            $statement->execute([
                'item_type' => $itemType,
                'label' => $label,
                'title' => $title,
                'body' => $body,
                'icon_class' => $iconClass !== '' ? $iconClass : null,
                'sort_order' => $sortOrder,
                'id' => $itemId,
            ]);
            flash_set('success', 'About item updated.');
        } else {
            $statement = db()->prepare('INSERT INTO about_items (item_type, label, title, body, icon_class, sort_order) VALUES (:item_type, :label, :title, :body, :icon_class, :sort_order)');
            $statement->execute([
                'item_type' => $itemType,
                'label' => $label,
                'title' => $title,
                'body' => $body,
                'icon_class' => $iconClass !== '' ? $iconClass : null,
                'sort_order' => $sortOrder,
            ]);
            flash_set('success', 'About item added.');
        }

        redirect_to('about.php');
    } catch (Throwable $throwable) {
        $error = $throwable->getMessage();
    }
}

$items = [];
try {
    $items = db()->query('SELECT id, item_type, label, title, body, icon_class, sort_order, updated_at FROM about_items ORDER BY sort_order ASC, id ASC')->fetchAll();
} catch (Throwable $throwable) {
    $items = [];
}

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage About Section</title>
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
                    <a class="admin-nav-link admin-nav-link-active" href="about.php">About</a>
                    <a class="admin-nav-link" href="upload-image.php">Gallery</a>
                    <a class="admin-nav-link admin-nav-link-button" href="logout.php">Logout</a>
                </nav>
            </div>
        </header>

        <main class="admin-page">
            <div class="admin-page-hero">
                <div>
                    <p class="admin-kicker">About section</p>
                    <h1 class="admin-title"><?php echo $editItem ? 'Edit about item' : 'Add about item'; ?></h1>
                    <p class="admin-subtitle">Manage the feature bullets and right-side highlights in the about section.</p>
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
                    <label for="item_type">Item type</label>
                    <select id="item_type" name="item_type">
                        <option value="feature" <?php echo (($editItem['item_type'] ?? 'feature') === 'feature') ? 'selected' : ''; ?>>Feature item</option>
                        <option value="highlight" <?php echo (($editItem['item_type'] ?? '') === 'highlight') ? 'selected' : ''; ?>>Highlight box</option>
                    </select>

                    <label for="label">Label</label>
                    <input id="label" name="label" required value="<?php echo h($editItem['label'] ?? ''); ?>" placeholder="What we install">

                    <label for="title">Title</label>
                    <input id="title" name="title" required value="<?php echo h($editItem['title'] ?? ''); ?>" placeholder="CCTV, NVR/DVR, IP cameras">

                    <label for="body">Body</label>
                    <textarea id="body" name="body" rows="4" required><?php echo h($editItem['body'] ?? ''); ?></textarea>

                    <label for="icon_class">Icon class</label>
                    <input id="icon_class" name="icon_class" value="<?php echo h($editItem['icon_class'] ?? ''); ?>" placeholder="fa-solid fa-video">

                    <div class="row">
                        <div>
                            <label for="sort_order">Sort order</label>
                            <input id="sort_order" name="sort_order" type="number" value="<?php echo h((string) ($editItem['sort_order'] ?? 0)); ?>">
                        </div>
                        <div>
                            <label for="item_type_note">Use</label>
                            <input id="item_type_note" type="text" value="Feature items populate the left side, highlights populate the right side." readonly>
                        </div>
                    </div>

                    <div class="admin-actions">
                        <button class="admin-btn admin-btn-primary" type="submit" name="action" value="save"><?php echo $editItem ? 'Save changes' : 'Add item'; ?></button>
                        <?php if ($editItem): ?>
                            <a class="admin-btn admin-btn-secondary" href="about.php">Cancel edit</a>
                        <?php endif; ?>
                    </div>
                </form>
            </section>

                <section class="admin-panel admin-stack">
                    <h2 style="margin:0;">Current items</h2>
                <?php if (!$items): ?>
                    <p class="admin-muted">No about items yet.</p>
                <?php else: ?>
                    <div class="admin-table-wrap">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Label</th>
                                    <th>Title</th>
                                    <th>Sort</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td><?php echo h($item['item_type']); ?></td>
                                        <td><?php echo h($item['label']); ?></td>
                                        <td><?php echo h($item['title']); ?><div class="admin-small admin-muted"><?php echo h($item['body']); ?></div></td>
                                        <td><?php echo h((string) $item['sort_order']); ?></td>
                                        <td>
                                            <div class="admin-actions" style="margin:0;">
                                                <a class="admin-btn admin-btn-secondary" href="about.php?edit=<?php echo h((string) $item['id']); ?>">Edit</a>
                                                <form method="POST" onsubmit="return confirm('Delete this about item?');">
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