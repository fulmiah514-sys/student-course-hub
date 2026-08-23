<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

require_permission('manage_programmes');

$id = isset($_GET['id']) && ctype_digit($_GET['id']) ? (int)$_GET['id'] : null;
$programme = ['ProgrammeName' => '', 'LevelID' => 1, 'ProgrammeLeaderID' => null,
              'Description' => '', 'Image' => '', 'ImageAltText' => ''];
$errors = [];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM Programmes WHERE ProgrammeID = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) { http_response_code(404); die('Programme not found.'); }
    $programme = $found;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        die('Invalid request.');
    }

    $name        = clean_input($_POST['programme_name'] ?? '');
    $levelId     = (int)($_POST['level_id'] ?? 0);
    $leaderId    = $_POST['leader_id'] !== '' ? (int)$_POST['leader_id'] : null;
    $description = clean_input($_POST['description'] ?? '');
    $image       = clean_input($_POST['image'] ?? '');
    $altText     = clean_input($_POST['alt_text'] ?? '');

    if ($name === '' || mb_strlen($name) > 255) $errors[] = 'Programme name is required (max 255 chars).';
    if (!$levelId) $errors[] = 'Please select a level.';

    // Note: HTML output of these fields is always escaped with h() at display
    // time (index.php / programme.php / this page), which is what actually
    // prevents stored XSS here — trimming alone is not a sanitizer.

    if (empty($errors)) {
        if ($id) {
            $stmt = $pdo->prepare(
                'UPDATE Programmes SET ProgrammeName=?, LevelID=?, ProgrammeLeaderID=?,
                 Description=?, Image=?, ImageAltText=? WHERE ProgrammeID=?'
            );
            $stmt->execute([$name, $levelId, $leaderId, $description, $image, $altText, $id]);
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO Programmes (ProgrammeName, LevelID, ProgrammeLeaderID, Description, Image, ImageAltText, IsPublished)
                 VALUES (?, ?, ?, ?, ?, ?, FALSE)'
            );
            $stmt->execute([$name, $levelId, $leaderId, $description, $image, $altText]);
        }
        header('Location: dashboard.php');
        exit;
    }

    // Re-populate the form with the submitted (but invalid) values
    $programme = array_merge($programme, [
        'ProgrammeName' => $name, 'LevelID' => $levelId, 'ProgrammeLeaderID' => $leaderId,
        'Description' => $description, 'Image' => $image, 'ImageAltText' => $altText,
    ]);
}

$levels = $pdo->query('SELECT LevelID, LevelName FROM Levels ORDER BY LevelID')->fetchAll();
$staff  = $pdo->query('SELECT StaffID, Name FROM Staff ORDER BY Name')->fetchAll();
$csrfToken = csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $id ? 'Edit' : 'Add' ?> Programme — Admin</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<header class="site-header">
    <div class="container">
        <a href="dashboard.php" class="logo">Admin — Student Course Hub</a>
        <nav><a href="dashboard.php">&larr; Back to Programmes</a></nav>
    </div>
</header>

<main class="container narrow" id="main-content">
    <h1><?= $id ? 'Edit' : 'Add' ?> Programme</h1>

    <?php foreach ($errors as $err): ?>
        <div class="flash error" role="alert"><?= h($err) ?></div>
    <?php endforeach; ?>

    <form method="post" action="edit_programme.php<?= $id ? '?id=' . (int)$id : '' ?>">
        <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">

        <label for="programme_name">Programme name</label>
        <input type="text" id="programme_name" name="programme_name" required maxlength="255"
               value="<?= h($programme['ProgrammeName']) ?>">

        <label for="level_id">Level</label>
        <select id="level_id" name="level_id" required>
            <?php foreach ($levels as $lvl): ?>
                <option value="<?= (int)$lvl['LevelID'] ?>" <?= (int)$programme['LevelID'] === (int)$lvl['LevelID'] ? 'selected' : '' ?>>
                    <?= h($lvl['LevelName']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="leader_id">Programme leader</label>
        <select id="leader_id" name="leader_id">
            <option value="">— None —</option>
            <?php foreach ($staff as $s): ?>
                <option value="<?= (int)$s['StaffID'] ?>" <?= (int)($programme['ProgrammeLeaderID'] ?? 0) === (int)$s['StaffID'] ? 'selected' : '' ?>>
                    <?= h($s['Name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="description">Description</label>
        <textarea id="description" name="description" rows="5"><?= h($programme['Description']) ?></textarea>

        <label for="image">Image URL</label>
        <input type="text" id="image" name="image" value="<?= h($programme['Image']) ?>">

        <label for="alt_text">Image alt text (for screen readers)</label>
        <input type="text" id="alt_text" name="alt_text" maxlength="255"
               value="<?= h($programme['ImageAltText']) ?>">

        <button type="submit"><?= $id ? 'Save Changes' : 'Create Programme' ?></button>
    </form>
</main>
</body>
</html>
