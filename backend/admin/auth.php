<?php
/**
 * GeminiMuse Admin — Session Auth
 * Include this at the top of every admin page.
 *
 * Set ADMIN_PASSWORD below (or move to a config file outside web root).
 */

define('ADMIN_PASSWORD', 'change-me-in-production');
define('SESSION_NAME', 'gm_admin');

session_name(SESSION_NAME);
session_start();

function is_logged_in(): bool {
    return !empty($_SESSION['admin_authed']) && $_SESSION['admin_authed'] === true;
}

function handle_login(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
        if ($_POST['password'] === ADMIN_PASSWORD) {
            $_SESSION['admin_authed'] = true;
            header('Location: index.php');
            exit;
        } else {
            $_SESSION['login_error'] = 'Invalid password.';
        }
    }
}

function handle_logout(): void {
    if (isset($_GET['logout'])) {
        session_destroy();
        header('Location: index.php');
        exit;
    }
}

handle_logout();
handle_login();

if (!is_logged_in()) {
    $error = $_SESSION['login_error'] ?? '';
    unset($_SESSION['login_error']);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <title>GeminiMuse Admin — Login</title>
      <link rel="stylesheet" href="style.css">
    </head>
    <body class="login-page">
      <div class="login-box">
        <h1>GeminiMuse Admin</h1>
        <?php if ($error): ?>
          <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <form method="post">
          <input type="password" name="password" placeholder="Admin password" autofocus required>
          <button type="submit">Login</button>
        </form>
      </div>
    </body>
    </html>
    <?php
    exit;
}
