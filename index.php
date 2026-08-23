<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

// --- Read + validate filters from the query string ---
$search  = clean_input($_GET['search'] ?? '');
$levelId = isset($_GET['level']) && ctype_digit($_GET['level']) ? (int)$_GET['level'] : null;

// --- Build query safely with prepared statements (prevents SQL injection) ---
$sql = "SELECT p.ProgrammeID, p.ProgrammeName, p.Description, p.Image, p.ImageAltText,
               l.LevelName, s.Name AS LeaderName
        FROM Programmes p
        JOIN Levels l ON p.LevelID = l.LevelID
        LEFT JOIN Staff s ON p.ProgrammeLeaderID = s.StaffID
        WHERE p.IsPublished = TRUE";
$params = [];

if ($search !== '') {
    $sql .= " AND (p.ProgrammeName LIKE :search OR p.Description LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}
if ($levelId) {
    $sql .= " AND p.LevelID = :level";
    $params[':level'] = $levelId;
}
$sql .= " ORDER BY p.ProgrammeName";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$programmes = $stmt->fetchAll();

$levels = $pdo->query("SELECT LevelID, LevelName FROM Levels ORDER BY LevelID")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Student Course Hub — Find Your Programme</title>
<link rel="stylesheet" href="css/style.css">
</head>
<body>

<header class="site-header">
    <div class="container">
        <a href="index.php" class="logo">Student Course Hub</a>
        <nav aria-label="Main navigation">
            <a href="index.php">Programmes</a>
        </nav>
    </div>
</header>

<main class="container" id="main-content">
    <h1>Explore our Programmes</h1>

    <form class="filter-bar" method="get" action="index.php" role="search">
        <label for="search">Search programmes</label>
        <input type="text" id="search" name="search" placeholder="e.g. Cyber Security"
               value="<?= h($search) ?>">

        <label for="level">Level</label>
        <select id="level" name="level">
            <option value="">All levels</option>
            <?php foreach ($levels as $lvl): ?>
                <option value="<?= (int)$lvl['LevelID'] ?>" <?= $levelId === (int)$lvl['LevelID'] ? 'selected' : '' ?>>
                    <?= h($lvl['LevelName']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit">Search</button>
    </form>

    <div class="programme-grid">
        <?php if (empty($programmes)): ?>
            <p>No programmes matched your search. Try a different keyword or level.</p>
        <?php endif; ?>

        <?php foreach ($programmes as $p): ?>
            <article class="programme-card">
                <img src="<?= h($p['Image'] ?: 'images/placeholder.svg') ?>"
                     alt="<?= h($p['ImageAltText'] ?: 'Illustration for ' . $p['ProgrammeName']) ?>">
                <h2><a href="programme.php?id=<?= (int)$p['ProgrammeID'] ?>"><?= h($p['ProgrammeName']) ?></a></h2>
                <p class="level-tag"><?= h($p['LevelName']) ?></p>
                <p><?= h(mb_strimwidth($p['Description'] ?? '', 0, 140, '…')) ?></p>
                <?php if ($p['LeaderName']): ?>
                    <p class="leader">Programme leader: <?= h($p['LeaderName']) ?></p>
                <?php endif; ?>
                <a class="btn" href="programme.php?id=<?= (int)$p['ProgrammeID'] ?>">View details</a>
            </article>
        <?php endforeach; ?>
    </div>
</main>

<footer class="site-footer">
    <div class="container">
        <p>&copy; <?= date('Y') ?> Student Course Hub</p>
    </div>
</footer>

</body>
</html>
