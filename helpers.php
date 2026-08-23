<?php
/**
 * Shared helper functions.
 */

// Escape output to prevent XSS when printing user- or DB-sourced text into HTML.
function h($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

// Generate (and store) a CSRF token for the current session.
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify a submitted CSRF token matches the session's token.
function csrf_verify($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token ?? '');
}

// Basic, defensive input trimming — validation still happens per-field where used.
function clean_input($value) {
    return trim($value ?? '');
}
