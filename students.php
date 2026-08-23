<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

require_permission('view_students');

// Remove a single (invalid/duplicate) registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_id'])) {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) die('Invalid request.');
    $pdo->prepare('DELETE FROM InterestedStudents WHERE InterestID = ?')
        ->execute([(int)$_POST['remove_id']]);
    header('Location: students.php' . (isset($_GET['programme']) ? '?programme=' . (int)$_GET['programme'] : ''));
    exit;
}

$programmeId = isset($_GET['programme']) && ctype_digit($_GET['programme']) ? (int)$_GET['programme'] : null;

// CSV export (only active registrations, optionally filtered by programme)
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $sql = "SELECT s.StudentName, s.Email, p.ProgrammeName, s.RegisteredAt
            FROM InterestedStudents s JOIN Programmes p ON s.ProgrammeID = p.ProgrammeID
            WHERE s.IsActive = TRUE";
    $params = [];
    if ($programmeId) { $sql .= " AND s.ProgrammeID = ?"; $params[] = $programmeId; }
    $sql .= " ORDER BY p.ProgrammeName, s.StudentName";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="mailing_list.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Student Name', 'Email', 'Programme', 'Registered At']);
    foreach ($stmt->fetchAll() as $row) {
        fputcsv($out, [$row['StudentName'], $row['Email'], $row['ProgrammeName'], $row['RegisteredAt']]);
    }
    fclose($out);
    exit;
}

$sql = "SELECT s.InterestID, s.StudentName, s.Email, s.IsActive, s.RegisteredAt, p.ProgrammeName
        FROM InterestedStudents s JOIN Programmes p ON s.ProgrammeID = p.ProgrammeID";
$params = [];
if ($programmeId) { $sql .= " WHERE s.ProgrammeID = ?"; $params[] = $programmeId; }
$sql .= " ORDER BY s.RegisteredAt DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$registrations = $stmt->fetchAll();

$programmes = $pdo->query('SELECT ProgrammeID, ProgrammeName FROM Programmes ORDER BY ProgrammeName')->fetchAll();
$csrfToken = csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Student Interest — Admin</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<header class="site-header">
    <div class="container">
        <a href="dashboard.php" class="logo">Admin — Student Course Hub</a>
        <nav><a href="dashboard.php">&larr; Back to Programmes</a></nav>
    </div>
</header>

<main class="container" id="main-content">
    <h1>Student Interest / Mailing List</h1>

    <form method="get" action="students.php" class="filter-bar">
        <label for="programme">Filter by programme</label>
        <select id="programme" name="programme" onchange="this.form.submit()">
            <option value="">All programmes</option>
            <?php foreach ($programmes as $p): ?>
                <option value="<?= (int)$p['ProgrammeID'] ?>" <?= $programmeId === (int)$p['ProgrammeID'] ? 'selected' : '' ?>>
                    <?= h($p['ProgrammeName']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>

    <p>
        <a class="btn" href="students.php?export=csv<?= $programmeId ? '&programme=' . (int)$programmeId : '' ?>">
            Export mailing list (CSV)
        </a>
    </p>

    <table class="admin-table">
        <thead>
            <tr><th>Student</th><th>Email</th><th>Programme</th><th>Status</th><th>Registered</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($registrations as $r): ?>
            <tr>
                <td><?= h($r['StudentName']) ?></td>
                <td><?= h($r['Email']) ?></td>
                <td><?= h($r['ProgrammeName']) ?></td>
                <td><?= $r['IsActive'] ? 'Active' : 'Unsubscribed' ?></td>
                <td><?= h($r['RegisteredAt']) ?></td>
                <td>
                    <form method="post" action="students.php<?= $programmeId ? '?programme=' . (int)$programmeId : '' ?>"
                          onsubmit="return confirm('Remove this registration?');" class="inline-form">
                        <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                        <input type="hidden" name="remove_id" value="<?= (int)$r['InterestID'] ?>">
                        <button type="submit" class="danger">Remove</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($registrations)): ?>
            <tr><td colspan="6">No registrations found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</main>
</body>
</html>
