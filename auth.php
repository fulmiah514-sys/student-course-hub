<?php
/**
 * Admin authentication / role-based access control helpers.
 * Include this at the top of any admin-only page.
 */

require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function require_login() {
    if (empty($_SESSION['admin_id'])) {
        header('Location: login.php');
        exit;
    }
}

// Call after require_login(). $permission is 'manage_programmes' or 'view_students'.
function require_permission($permission) {
    require_login();
    global $pdo;

    $stmt = $pdo->prepare(
        "SELECT r.CanManageProgrammes, r.CanViewStudents
         FROM Admins a JOIN AdminRoles r ON a.RoleID = r.RoleID
         WHERE a.AdminID = ?"
    );
    $stmt->execute([$_SESSION['admin_id']]);
    $role = $stmt->fetch();

    if (!$role) {
        session_destroy();
        header('Location: login.php');
        exit;
    }

    $allowed = ($permission === 'manage_programmes' && $role['CanManageProgrammes'])
        || ($permission === 'view_students' && $role['CanViewStudents']);

    if (!$allowed) {
        http_response_code(403);
        die('You do not have permission to access this page.');
    }
}
