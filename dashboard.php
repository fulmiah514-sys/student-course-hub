<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

require_permission('manage_programmes');

// Handle publish/unpublish toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_id'])) {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        die('Invalid request.');
    }
    $id = (int)$_POST['toggle_id'];
    $stmt = $pdo->prepare('UPDATE Programmes SET IsPublished = NOT IsPublished WHERE ProgrammeID = ?');
    $stmt->execute([$id]);
    header('Location: dashboard.php');
    exit;
}

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        die('Invalid request.');
    }
    $id = (int)$_POST['delete_id'];
    $pdo->prepare('DELETE FROM ProgrammeModules WHERE ProgrammeID = ?')->execute([$id]);
    $pdo->prepare('DELETE FROM Programmes WHERE ProgrammeID = ?')->execute([$id]);
    header('Location: dashboard.php');
    exit;
}

$programmes = $pdo->query(
    "SELECT p.ProgrammeID, p.ProgrammeName, p.IsPublished, l.LevelName
     FROM Programmes p JOIN Levels l ON p.LevelID = l.LevelID
     ORDER BY p.ProgrammeName"
)->fetchAll();

$csrfToken = csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin Dashboard — Student Course Hub</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<header class="site-header">
    <div class="container">
        <a href="dashboard.php" class="logo">Admin — Student Course Hub</a>
        <nav>
            <span>Signed in as <?= h($_SESSION['admin_name']) ?></span>
            <a href="students.php">Student Interest</a>
            <a href="logout.php">Log Out</a>
        </nav>
    </div>
</header>

<main class="container" id="main-content">
    <h1>Programmes</h1>
    <p><a class="btn" href="edit_programme.php">+ Add New Programme</a></p>

    <table class="admin-table">
        <thead>
            <tr><th>Programme</th><th>Level</th><th>Status</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php foreach ($programmes as $p): ?>
            <tr>
                <td><?= h($p['ProgrammeName']) ?></td>
                <td><?= h($p['LevelName']) ?></td>
                <td>
                    <span class="status-tag <?= $p['IsPublished'] ? 'published' : 'draft' ?>">
                        <?= $p['IsPublished'] ? 'Published' : 'Draft' ?>
                    </span>
                </td>
                <td class="actions">
                    <a href="edit_programme.php?id=<?= (int)$p['ProgrammeID'] ?>">Edit</a>

                    <form method="post" action="dashboard.php" class="inline-form">
                        <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                        <input type="hidden" name="toggle_id" value="<?= (int)$p['ProgrammeID'] ?>">
                        <button type="submit"><?= $p['IsPublished'] ? 'Unpublish' : 'Publish' ?></button>
                    </form>

                    <form method="post" action="dashboard.php" class="inline-form"
                          onsubmit="return confirm('Delete this programme? This cannot be undone.');">
                        <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                        <input type="hidden" name="delete_id" value="<?= (int)$p['ProgrammeID'] ?>">
                        <button type="submit" class="danger">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</main>
</body>
</html>
