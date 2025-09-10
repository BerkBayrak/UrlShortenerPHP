<?php
// author Lycradiata
// Redirect short URL to original target

require_once __DIR__ . '/helpers.php';

$code = trim($_GET['c'] ?? '');

if ($code === '') {
    http_response_code(404);
    echo 'No code provided.';
    exit;
}

$row = find_url_by_code($code);

if (!$row) {
    http_response_code(404);
    echo 'URL not found or expired.';
    exit;
}

// Increment clicks
increment_clicks($row['id']);

// Redirect to target URL
header('Location: ' . $row['url']);
exit;
