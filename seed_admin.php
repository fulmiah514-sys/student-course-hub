<?php
/**
// Admin login: checks username and password securely using password hashing
 * Run this once from the command line to generate a password hash for your
 * own admin password, then paste the output into schema_updates.sql (or run
 * an UPDATE against the Admins table) instead of using the placeholder hash.
 *
 * Usage:  php includes/seed_admin.php "YourChosenPassword123!"
 */
if (php_sapi_name() !== 'cli') {
    die('Run this from the command line.');
}
$password = $argv[1] ?? null;
if (!$password) {
    die("Usage: php seed_admin.php \"YourChosenPassword\"\n");
}
echo password_hash($password, PASSWORD_DEFAULT) . "\n";
