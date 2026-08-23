<?php
/**
// Prevents students from registering interest twice for the same programme
 * Handles "Register Interest" submissions via AJAX (JSON in, JSON out).
 * Security measures:
 *  - CSRF token check
 *  - Server-side validation (never trust client-side checks alone)
 *  - Prepared statements (prevents SQL injection)
 *  - Output is JSON only — no HTML reflected back, so nothing to XSS-escape here,
 *    but stored values are still escaped with h() wherever they're later displayed.
 *  - Relies on the DB's UNIQUE(ProgrammeID, Email) constraint as a second line
 *    of defence against duplicate/racing submissions.
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

function respond($success, $message) {
    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    respond(false, 'Invalid request method.');
}

$data = json_decode(file_get_contents('php://input'), true) ?? $_POST;

if (!csrf_verify($data['csrf_token'] ?? '')) {
    http_response_code(403);
    respond(false, 'Your session has expired. Please refresh the page and try again.');
}

$programmeId = isset($data['programme_id']) ? (int)$data['programme_id'] : 0;
$name        = clean_input($data['student_name'] ?? '');
$email       = clean_input($data['email'] ?? '');

if (!$programmeId || $name === '' || $email === '') {
    respond(false, 'Please fill in all fields.');
}
if (mb_strlen($name) > 100) {
    respond(false, 'Name is too long.');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 255) {
    respond(false, 'Please enter a valid email address.');
}

// Confirm the programme exists and is published
$stmt = $pdo->prepare('SELECT ProgrammeID FROM Programmes WHERE ProgrammeID = ? AND IsPublished = TRUE');
$stmt->execute([$programmeId]);
if (!$stmt->fetch()) {
    respond(false, 'This programme is not available.');
}

try {
    // If the student previously withdrew interest, reactivate rather than duplicate.
    $stmt = $pdo->prepare(
        'INSERT INTO InterestedStudents (ProgrammeID, StudentName, Email, IsActive)
         VALUES (:pid, :name, :email, TRUE)
         ON DUPLICATE KEY UPDATE StudentName = VALUES(StudentName), IsActive = TRUE'
    );
    $stmt->execute([':pid' => $programmeId, ':name' => $name, ':email' => $email]);
    respond(true, 'Thanks! Your interest has been registered — we\'ll be in touch.');
} catch (PDOException $e) {
    respond(false, 'Something went wrong. Please try again shortly.');
}
