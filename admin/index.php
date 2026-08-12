<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';
require_admin();

$galleryCount = null;

try {
    $galleryCount = (int) db()->query('SELECT COUNT(*) FROM gallery_items')->fetchColumn();
} catch (Throwable $throwable) {
    $galleryCount = null;
}

$success = flash_get('success');
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChestherTech Admin</title>
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
                    <a class="admin-nav-link admin-nav-link-active" href="index.php">Dashboard</a>
                    <a class="admin-nav-link" href="content.php">Content</a>
                    <a class="admin-nav-link" href="services.php">Services</a>
                    <a class="admin-nav-link" href="about.php">About</a>
                    <a class="admin-nav-link" href="upload-image.php">Gallery</a>
                    <a class="admin-nav-link admin-nav-link-button" href="logout.php">Logout</a>
                </nav>
            </div>
        </header>

        <main class="admin-page">
            <div class="admin-page-hero">
                <div>
                    <p class="admin-kicker">Operations center</p>
                    <h1 class="admin-title">Admin Dashboard</h1>
                    <p class="admin-subtitle">Manage landing-page content from here.</p>
                </div>
            </div>

            <?php if ($success): ?>
                <div class="admin-notice admin-notice-success"><?php echo h($success); ?></div>
            <?php endif; ?>

            <div class="admin-grid">
                <section class="admin-card">
                    <p class="admin-muted">Gallery items</p>
                    <div class="admin-stat"><?php echo $galleryCount === null ? 'Not ready' : h((string) $galleryCount); ?></div>
                    <p class="admin-muted">Add, edit, delete, and upload project images.</p>
                    <div class="admin-actions">
                        <a class="admin-btn admin-btn-primary" href="upload-image.php">Manage Gallery</a>
                    </div>
                </section>

                <section class="admin-card">
                    <p class="admin-muted">Service cards</p>
                    <div class="admin-stat">Editable</div>
                    <p class="admin-muted">Change the service titles, icons, feature lists, and ordering.</p>
                    <div class="admin-actions">
                        <a class="admin-btn admin-btn-primary" href="services.php">Manage Services</a>
                    </div>
                </section>

                <section class="admin-card">
                    <p class="admin-muted">About section</p>
                    <div class="admin-stat">Editable</div>
                    <p class="admin-muted">Edit the feature bullets and highlight boxes shown in the about section.</p>
                    <div class="admin-actions">
                        <a class="admin-btn admin-btn-primary" href="about.php">Manage About</a>
                    </div>
                </section>

                <section class="admin-card">
                    <p class="admin-muted">Homepage content</p>
                    <h2 style="margin:0.35rem 0 0;">Edit landing-page copy</h2>
                    <p class="admin-muted">Update hero, about, services, gallery labels, contact details, and footer text from one place.</p>
                    <div class="admin-actions">
                        <a class="admin-btn admin-btn-primary" href="content.php">Edit Content</a>
                        <a class="admin-btn admin-btn-secondary" href="../index.html">View Homepage</a>
                    </div>
                </section>
            </div>
        </main>
    </div>
</body>
</html>