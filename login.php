<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

if (session_status() === PHP_SESSION_NONE) session_start();

if (!empty($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        $error = 'Your session expired. Please try again.';
    } else {
        $username = clean_input($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        $stmt = $pdo->prepare(
            'SELECT AdminID, Username, PasswordHash, RoleID FROM Admins WHERE Username = ?'
        );
        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        // password_verify() runs in constant time and works even if $admin is
        // false (verifying against a dummy hash), which avoids leaking via
        // response-time whether a username exists.
        $hashToCheck = $admin['PasswordHash'] ?? '$2y$10$abcdefghijklmnopqrstuuXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX';

        if ($admin && password_verify($password, $hashToCheck)) {
            session_regenerate_id(true);
            $_SESSION['admin_id']   = $admin['AdminID'];
            $_SESSION['admin_name'] = $admin['Username'];
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    }
}

$csrfToken = csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin Login — Student Course Hub</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<main class="container narrow" id="main-content">
    <h1>Admin Login</h1>
    <?php if ($error): ?>
        <div class="flash error" role="alert"><?= h($error) ?></div>
    <?php endif; ?>
    <form method="post" action="login.php">
        <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" required autofocus>

        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>

        <button type="submit">Log In</button>
    </form>
</main>
</body>
</html>
