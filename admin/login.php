<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

if (is_admin_logged_in()) {
    redirect_to('index.php');
}

$error = null;
$setupMode = false;

try {
    $adminCount = (int) db()->query('SELECT COUNT(*) FROM admins')->fetchColumn();
    $setupMode = $adminCount === 0;
} catch (Throwable $throwable) {
    $error = 'Create the database first using database.sql, then refresh this page.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($setupMode) {
        $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

        if ($username === '' || $password === '' || $confirmPassword === '') {
            $error = 'All fields are required.';
        } elseif (strlen($password) < 8) {
            $error = 'Use at least 8 characters for the password.';
        } elseif ($password !== $confirmPassword) {
            $error = 'Passwords do not match.';
        } else {
            $statement = db()->prepare('INSERT INTO admins (username, password_hash) VALUES (:username, :password_hash)');
            $statement->execute([
                'username' => $username,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ]);

            $_SESSION['admin_id'] = (int) db()->lastInsertId();
            $_SESSION['admin_username'] = $username;

            flash_set('success', 'Admin account created.');
            redirect_to('index.php');
        }
    } else {
        $statement = db()->prepare('SELECT id, username, password_hash FROM admins WHERE username = :username LIMIT 1');
        $statement->execute(['username' => $username]);
        $admin = $statement->fetch();

        if (!$admin || !password_verify($password, (string) $admin['password_hash'])) {
            $error = 'Invalid username or password.';
        } else {
            $_SESSION['admin_id'] = (int) $admin['id'];
            $_SESSION['admin_username'] = (string) $admin['username'];
            flash_set('success', 'Welcome back.');
            redirect_to('index.php');
        }
    }
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
    <div class="admin-login-shell">
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
                    <a class="admin-nav-link" href="../index.html">View Homepage</a>
                </nav>
            </div>
        </header>

        <main class="admin-login-main">
            <section class="admin-card admin-auth-card">
                <div class="admin-auth-header">
                    <div>
                        <p class="admin-auth-badge"><?php echo $setupMode ? 'First-time setup' : 'Admin sign in'; ?></p>
                        <h1 class="admin-auth-title"><?php echo $setupMode ? 'Create Admin' : 'Welcome back'; ?></h1>
                        <p class="admin-auth-copy"><?php echo $setupMode ? 'Create the first account to start managing the site.' : 'Use your admin credentials to manage the landing page content.'; ?></p>
                    </div>
                </div>

                <?php if ($error): ?>
                    <div class="admin-notice admin-notice-error"><?php echo h($error); ?></div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="admin-notice admin-notice-success"><?php echo h($success); ?></div>
                <?php endif; ?>

                <form class="admin-form" method="POST">
                    <label for="username">Username</label>
                    <input id="username" name="username" type="text" autocomplete="username" required>

                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" autocomplete="current-password" required>

                    <?php if ($setupMode): ?>
                        <label for="confirm_password">Confirm Password</label>
                        <input id="confirm_password" name="confirm_password" type="password" autocomplete="new-password" required>
                    <?php endif; ?>

                    <button class="admin-btn admin-btn-primary" type="submit"><?php echo $setupMode ? 'Create Admin Account' : 'Sign In'; ?></button>
                </form>

                <p class="admin-auth-meta">If you have not imported the schema yet, run <a class="admin-auth-link" href="../database.sql">database.sql</a> in MySQL first.</p>
            </section>
        </main>
    </div>
</body>
</html>