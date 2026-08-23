<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$id = isset($_GET['id']) && ctype_digit($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    http_response_code(404);
    die('Programme not found.');
}

$stmt = $pdo->prepare(
    "SELECT p.*, l.LevelName, s.Name AS LeaderName, s.JobTitle, s.Bio, s.PhotoURL
     FROM Programmes p
     JOIN Levels l ON p.LevelID = l.LevelID
     LEFT JOIN Staff s ON p.ProgrammeLeaderID = s.StaffID
     WHERE p.ProgrammeID = ? AND p.IsPublished = TRUE"
);
$stmt->execute([$id]);
$programme = $stmt->fetch();

if (!$programme) {
    http_response_code(404);
    die('Programme not found or not currently published.');
}

// Modules grouped by year, with their module leader
$stmt = $pdo->prepare(
    "SELECT pm.Year, m.ModuleID, m.ModuleName, m.Description, m.Image, m.ImageAltText,
            st.Name AS ModuleLeaderName,
            (SELECT COUNT(*) FROM ProgrammeModules pm2 WHERE pm2.ModuleID = m.ModuleID) AS COUNT(DISTINCT pm2.ProgrammeID)
     FROM ProgrammeModules pm
     JOIN Modules m ON pm.ModuleID = m.ModuleID
     LEFT JOIN Staff st ON m.ModuleLeaderID = st.StaffID
     WHERE pm.ProgrammeID = ?
     ORDER BY pm.Year, m.ModuleName"
);
$stmt->execute([$id]);
$modulesByYear = [];
foreach ($stmt->fetchAll() as $row) {
    $modulesByYear[$row['Year']][] = $row;
}

$csrfToken = csrf_token();
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($programme['ProgrammeName']) ?> — Student Course Hub</title>
<link rel="stylesheet" href="css/style.css">
</head>
<body>

<header class="site-header">
    <div class="container">
        <a href="index.php" class="logo">Student Course Hub</a>
        <nav aria-label="Main navigation"><a href="index.php">Programmes</a></nav>
    </div>
</header>

<main class="container" id="main-content">
    <p><a href="index.php">&larr; Back to all programmes</a></p>

    <?php if ($flash): ?>
        <div class="flash <?= h($flash['type']) ?>" role="status"><?= h($flash['message']) ?></div>
    <?php endif; ?>

    <article class="programme-detail">
        <img src="<?= h($programme['Image'] ?: 'images/placeholder.svg') ?>"
             alt="<?= h($programme['ImageAltText'] ?: 'Illustration for ' . $programme['ProgrammeName']) ?>">

        <h1><?= h($programme['ProgrammeName']) ?></h1>
        <p class="level-tag"><?= h($programme['LevelName']) ?></p>
        <p class="description"><?= nl2br(h($programme['Description'])) ?></p>

        <?php if ($programme['LeaderName']): ?>
            <section class="leader-info">
                <h2>Programme Leader</h2>
                <p><strong><?= h($programme['LeaderName']) ?></strong>
                    <?= $programme['JobTitle'] ? ' — ' . h($programme['JobTitle']) : '' ?></p>
                <?php if ($programme['Bio']): ?><p><?= h($programme['Bio']) ?></p><?php endif; ?>
            </section>
        <?php endif; ?>

        <section class="modules">
            <h2>Programme Structure</h2>
            <?php foreach ($modulesByYear as $year => $modules): ?>
                <h3>Year <?= (int)$year ?></h3>
                <ul class="module-list">
                    <?php foreach ($modules as $m): ?>
                        <li>
                            <strong><?= h($m['ModuleName']) ?></strong>
                            <?php if ($m['ModuleLeaderName']): ?>
                                <span class="module-leader">— led by <?= h($m['ModuleLeaderName']) ?></span>
                            <?php endif; ?>
                            <?php if ($m['COUNT(DISTINCT pm2.ProgrammeID)'] > 1): ?>
                                <span class="shared-tag">Shared across programmes</span>
                            <?php endif; ?>
                            <?php if ($m['Description']): ?>
                                <p class="module-desc"><?= h($m['Description']) ?></p>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endforeach; ?>
        </section>

        <section class="register-interest">
            <h2>Register Your Interest</h2>
            <p>Get updates about open days, application deadlines, and programme news.</p>
            <form id="interest-form">
                <input type="hidden" name="programme_id" value="<?= (int)$programme['ProgrammeID'] ?>">
                <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">

                <label for="student_name">Full name</label>
                <input type="text" id="student_name" name="student_name" required maxlength="100">

                <label for="email">Email address</label>
                <input type="email" id="email" name="email" required maxlength="255">

                <button type="submit">Register Interest</button>
                <p id="interest-result" role="status" aria-live="polite"></p>
            </form>
        </section>
    </article>
</main>

<footer class="site-footer">
    <div class="container">
        <p>&copy; <?= date('Y') ?> Student Course Hub</p>
    </div>
</footer>

<script src="js/interest.js"></script>
</body>
</html>
