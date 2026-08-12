<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

require_admin();

$defaults = [
    'hero_badge' => 'Smart security for homes and businesses',
    'hero_title' => 'Security systems that look premium without feeling crowded.',
    'hero_description' => 'ChestherTech designs and installs CCTV, access control, alarm systems, structured cabling, and remote monitoring setups with a clean finish and practical coverage.',
    'about_label' => 'About ChestherTech',
    'about_title' => 'A security partner built for real-world properties.',
    'about_description' => 'ChestherTech helps property owners protect people, assets, and daily operations with practical systems that are easy to use and built to last.',
    'services_label' => 'Services',
    'services_title' => 'Everything you need to secure a property properly.',
    'services_description' => 'We do more than install cameras. We help design a complete security plan, then deliver the equipment, wiring, setup, and support to keep it working day after day.',
    'portfolio_label' => 'Sample gallery',
    'portfolio_title' => 'A few sample pictures to show the kind of work we do.',
    'portfolio_description' => 'These are sample visuals you can use right away. If you already have real project photos, swap them in later for an even stronger portfolio.',
    'contact_label' => 'Contact',
    'contact_title' => 'Let\'s secure your space.',
    'contact_description' => 'Send us an inquiry today. We provide free estimates, practical recommendations, and comprehensive site surveys for new installs or upgrades.',
    'footer_description' => 'Professional security systems for homes, offices, and businesses.',
    'hero_bg_image' => '',
    'about_bg_image' => '',
];

$blockValues = $defaults;
$contactValues = [
    'phone' => '+1 (234) 567-890',
    'email' => 'inquiry@chesthertech.com',
    'coverage' => 'Residential, commercial, and industrial properties',
    'whatsapp_url' => 'https://wa.me/1234567890',
    'messenger_url' => 'https://m.me/yourpage',
];

try {
    $statement = db()->query('SELECT block_key, title, body FROM content_blocks');
    foreach ($statement->fetchAll() as $row) {
        $key = (string) $row['block_key'];
        if (array_key_exists($key, $blockValues)) {
            $blockValues[$key] = (string) ($row['body'] ?? $row['title'] ?? $blockValues[$key]);
        }
        if ($key === 'hero_badge' && !empty($row['title'])) {
            $blockValues[$key] = (string) $row['title'];
        }
    }
} catch (Throwable $throwable) {
    $blockValues = $defaults;
}

try {
    $statement = db()->query('SELECT phone, email, coverage, whatsapp_url, messenger_url FROM contact_settings ORDER BY id DESC LIMIT 1');
    $row = $statement->fetch();
    if ($row) {
        foreach ($contactValues as $key => $value) {
            if (!empty($row[$key])) {
                $contactValues[$key] = (string) $row[$key];
            }
        }
    }
} catch (Throwable $throwable) {
    $contactValues = $contactValues;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $connection = null;

    try {
        $payload = [
            'hero_badge' => trim((string) ($_POST['hero_badge'] ?? '')),
            'hero_title' => trim((string) ($_POST['hero_title'] ?? '')),
            'hero_description' => trim((string) ($_POST['hero_description'] ?? '')),
            'hero_bg_image' => trim((string) ($_POST['hero_bg_image'] ?? '')),
            'about_label' => trim((string) ($_POST['about_label'] ?? '')),
            'about_title' => trim((string) ($_POST['about_title'] ?? '')),
            'about_description' => trim((string) ($_POST['about_description'] ?? '')),
            'about_bg_image' => trim((string) ($_POST['about_bg_image'] ?? '')),
            'services_label' => trim((string) ($_POST['services_label'] ?? '')),
            'services_title' => trim((string) ($_POST['services_title'] ?? '')),
            'services_description' => trim((string) ($_POST['services_description'] ?? '')),
            'portfolio_label' => trim((string) ($_POST['portfolio_label'] ?? '')),
            'portfolio_title' => trim((string) ($_POST['portfolio_title'] ?? '')),
            'portfolio_description' => trim((string) ($_POST['portfolio_description'] ?? '')),
            'contact_label' => trim((string) ($_POST['contact_label'] ?? '')),
            'contact_title' => trim((string) ($_POST['contact_title'] ?? '')),
            'contact_description' => trim((string) ($_POST['contact_description'] ?? '')),
            'footer_description' => trim((string) ($_POST['footer_description'] ?? '')),
        ];

        $connection = db();
        $connection->beginTransaction();

        $upsert = $connection->prepare('INSERT INTO content_blocks (block_key, title, body) VALUES (:block_key, :title, :body) ON DUPLICATE KEY UPDATE title = VALUES(title), body = VALUES(body)');

        foreach ($payload as $key => $value) {
            $upsert->execute([
                'block_key' => $key,
                'title' => in_array($key, ['hero_badge', 'about_label', 'services_label', 'portfolio_label', 'contact_label'], true) ? $value : null,
                'body' => in_array($key, ['hero_badge', 'about_label', 'services_label', 'portfolio_label', 'contact_label'], true) ? null : $value,
            ]);
        }

        $connection->exec('DELETE FROM contact_settings');
        $contactStatement = $connection->prepare('INSERT INTO contact_settings (phone, email, coverage, whatsapp_url, messenger_url) VALUES (:phone, :email, :coverage, :whatsapp_url, :messenger_url)');
        $contactStatement->execute([
            'phone' => trim((string) ($_POST['phone'] ?? '')),
            'email' => trim((string) ($_POST['email'] ?? '')),
            'coverage' => trim((string) ($_POST['coverage'] ?? '')),
            'whatsapp_url' => trim((string) ($_POST['whatsapp_url'] ?? '')),
            'messenger_url' => trim((string) ($_POST['messenger_url'] ?? '')),
        ]);

        $connection->commit();
        flash_set('success', 'Homepage content saved.');
        redirect_to('content.php');
    } catch (Throwable $throwable) {
        if ($connection instanceof PDO && $connection->inTransaction()) {
            $connection->rollBack();
        }
        $error = $throwable->getMessage();
    }
}

$success = flash_get('success');
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Homepage Content</title>
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
                    <a class="admin-nav-link admin-nav-link-active" href="content.php">Content</a>
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
                    <p class="admin-kicker">Global content</p>
                    <h1 class="admin-title">Homepage content</h1>
                    <p class="admin-subtitle">Edit the text and contact details shown on the public landing page.</p>
                </div>
                <div class="admin-page-actions">
                    <a class="admin-btn admin-btn-secondary" href="index.php">Dashboard</a>
                    <a class="admin-btn admin-btn-secondary" href="upload-image.php">Gallery</a>
                    <a class="admin-btn admin-btn-secondary" href="services.php">Services</a>
                </div>
            </div>

        <?php if (!empty($error)): ?>
            <div class="admin-notice admin-notice-error"><?php echo h($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="admin-notice admin-notice-success"><?php echo h($success); ?></div>
        <?php endif; ?>

        <form class="admin-panel admin-form" method="POST">
            <div class="admin-form-grid">
                <section>
                    <h2 style="margin:0;">Hero</h2>
                    <label for="hero_badge">Badge</label>
                    <input id="hero_badge" name="hero_badge" value="<?php echo h($blockValues['hero_badge']); ?>">
                    <label for="hero_title">Title</label>
                    <textarea id="hero_title" name="hero_title" rows="2"><?php echo h($blockValues['hero_title']); ?></textarea>
                    <label for="hero_description">Description</label>
                    <textarea id="hero_description" name="hero_description" rows="4"><?php echo h($blockValues['hero_description']); ?></textarea>
                    <label for="hero_bg_image">Hero background image URL</label>
                    <input id="hero_bg_image" name="hero_bg_image" value="<?php echo h($blockValues['hero_bg_image']); ?>" placeholder="uploads/gallery/your-image.jpg">
                </section>

                <section>
                    <h2 class="section-title">About</h2>
                    <div class="two">
                        <div>
                            <label for="about_label">Label</label>
                            <input id="about_label" name="about_label" value="<?php echo h($blockValues['about_label']); ?>">
                        </div>
                        <div>
                            <label for="contact_label">Contact label</label>
                            <input id="contact_label" name="contact_label" value="<?php echo h($blockValues['contact_label']); ?>">
                        </div>
                    </div>
                    <label for="about_title">Title</label>
                    <textarea id="about_title" name="about_title" rows="2"><?php echo h($blockValues['about_title']); ?></textarea>
                    <label for="about_description">Description</label>
                    <textarea id="about_description" name="about_description" rows="4"><?php echo h($blockValues['about_description']); ?></textarea>
                    <label for="about_bg_image">About background image URL</label>
                    <input id="about_bg_image" name="about_bg_image" value="<?php echo h($blockValues['about_bg_image']); ?>" placeholder="uploads/gallery/your-image.jpg">
                </section>

                <section>
                    <h2 class="section-title">Services and gallery</h2>
                    <div class="two">
                        <div>
                            <label for="services_label">Services label</label>
                            <input id="services_label" name="services_label" value="<?php echo h($blockValues['services_label']); ?>">
                        </div>
                        <div>
                            <label for="portfolio_label">Gallery label</label>
                            <input id="portfolio_label" name="portfolio_label" value="<?php echo h($blockValues['portfolio_label']); ?>">
                        </div>
                    </div>
                    <label for="services_title">Services title</label>
                    <textarea id="services_title" name="services_title" rows="2"><?php echo h($blockValues['services_title']); ?></textarea>
                    <label for="services_description">Services description</label>
                    <textarea id="services_description" name="services_description" rows="4"><?php echo h($blockValues['services_description']); ?></textarea>
                    <label for="portfolio_title">Gallery title</label>
                    <textarea id="portfolio_title" name="portfolio_title" rows="2"><?php echo h($blockValues['portfolio_title']); ?></textarea>
                    <label for="portfolio_description">Gallery description</label>
                    <textarea id="portfolio_description" name="portfolio_description" rows="3"><?php echo h($blockValues['portfolio_description']); ?></textarea>
                </section>

                <section>
                    <h2 class="section-title">Contact</h2>
                    <label for="contact_title">Contact title</label>
                    <textarea id="contact_title" name="contact_title" rows="2"><?php echo h($blockValues['contact_title']); ?></textarea>
                    <label for="contact_description">Contact description</label>
                    <textarea id="contact_description" name="contact_description" rows="3"><?php echo h($blockValues['contact_description']); ?></textarea>
                    <div class="two">
                        <div>
                            <label for="phone">Phone</label>
                            <input id="phone" name="phone" value="<?php echo h($contactValues['phone']); ?>">
                        </div>
                        <div>
                            <label for="email">Email</label>
                            <input id="email" name="email" type="email" value="<?php echo h($contactValues['email']); ?>">
                        </div>
                    </div>
                    <label for="coverage">Coverage</label>
                    <input id="coverage" name="coverage" value="<?php echo h($contactValues['coverage']); ?>">
                    <div class="two">
                        <div>
                            <label for="whatsapp_url">WhatsApp URL</label>
                            <input id="whatsapp_url" name="whatsapp_url" value="<?php echo h($contactValues['whatsapp_url']); ?>">
                        </div>
                        <div>
                            <label for="messenger_url">Messenger URL</label>
                            <input id="messenger_url" name="messenger_url" value="<?php echo h($contactValues['messenger_url']); ?>">
                        </div>
                    </div>
                </section>

                <section>
                    <h2 class="section-title">Footer</h2>
                    <label for="footer_description">Footer description</label>
                    <textarea id="footer_description" name="footer_description" rows="3"><?php echo h($blockValues['footer_description']); ?></textarea>
                </section>

                <div class="actions">
                    <button class="admin-btn admin-btn-primary" type="submit">Save homepage content</button>
                </div>
            </div>
        </form>
        </main>
    </div>
</body>
</html>