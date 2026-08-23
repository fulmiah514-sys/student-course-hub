<?php
/**
 * Central database connection.
 * Uses PDO with prepared statements throughout the app to prevent SQL injection.
 */

$DB_HOST = 'localhost';
$DB_NAME = 'student_course_hub';
$DB_USER = 'root';       // change for your environment
$DB_PASS = '';           // change for your environment

try {
    $pdo = new PDO(
        "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    die('Database connection failed. Please try again later.');
}
