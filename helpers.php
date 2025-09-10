<?php
// author Lycradiata
// Common helper functions used across files

function get_config() {
    static $cfg = null;
    if ($cfg === null) $cfg = require __DIR__ . '/config.php';
    return $cfg;
}

function get_pdo() {
    static $pdo = null;
    if ($pdo) return $pdo;

    $cfg = get_config();
    $dbPath = $cfg['db_path'];

    // Ensure data directory exists
    $dir = dirname($dbPath);
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    // Create PDO connection to SQLite
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Initialize table if not exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS urls (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        code TEXT UNIQUE,
        url TEXT NOT NULL,
        created_at INTEGER NOT NULL,
        clicks INTEGER NOT NULL DEFAULT 0,
        expires_at INTEGER DEFAULT NULL,
        custom INTEGER NOT NULL DEFAULT 0
    )");

    // Index on code for fast lookup
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_code ON urls(code)');

    return $pdo;
}

function getDbConnection($dbPath) {
    if (!file_exists(dirname($dbPath))) {
        mkdir(dirname($dbPath), 0755, true);
    }

    $pdo = new PDO("sqlite:" . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create table if it does not exist
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS urls (
            code TEXT PRIMARY KEY,
            url TEXT NOT NULL
        )
    ");

    return $pdo;
}

function normalize_url($url) {
    $url = trim($url);
    if ($url === '') return '';

    if (!preg_match('#^https?://#i', $url)) {
        $url = 'http://' . $url;
    }

    if (filter_var($url, FILTER_VALIDATE_URL) === false) return '';
    return $url;
}

function generate_code($length = null) {
    $cfg = get_config();
    $alphabet = $cfg['alphabet'];
    if ($length === null) $length = $cfg['code_length'];

    $max = strlen($alphabet) - 1;
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $idx = random_int(0, $max);
        $code .= $alphabet[$idx];
    }
    return $code;
}

function unique_code($length = null) {
    $pdo = get_pdo();
    for ($i = 0; $i < 6; $i++) {
        $code = generate_code($length);
        $stmt = $pdo->prepare('SELECT 1 FROM urls WHERE code = :code');
        $stmt->execute([':code' => $code]);
        if ($stmt->fetchColumn() === false) return $code;
    }
    return unique_code($length + 1);
}

function create_short_url($targetUrl, $custom = null, $expiresAt = null) {
    $pdo = get_pdo();
    $cfg = get_config();

    if ($custom) {
        $custom = trim($custom);
        if (strlen($custom) > $cfg['max_alias_length']) throw new Exception('Custom alias too long');
        if (!preg_match('/^[0-9A-Za-z_-]+$/', $custom)) throw new Exception('Alias contains invalid characters');

        $stmt = $pdo->prepare('SELECT 1 FROM urls WHERE code = :code');
        $stmt->execute([':code' => $custom]);
        if ($stmt->fetchColumn()) throw new Exception('Alias already taken');

        $code = $custom;
        $customFlag = 1;
    } else {
        $code = unique_code();
        $customFlag = 0;
    }

    $stmt = $pdo->prepare('INSERT INTO urls (code, url, created_at, expires_at, custom) VALUES (:code, :url, :created_at, :expires_at, :custom)');
    $stmt->execute([
        ':code' => $code,
        ':url' => $targetUrl,
        ':created_at' => time(),
        ':expires_at' => $expiresAt ? $expiresAt : null,
        ':custom' => $customFlag,
    ]);

    return $code;
}

function find_url_by_code($code) {
    $pdo = get_pdo();
    $stmt = $pdo->prepare('SELECT * FROM urls WHERE code = :code LIMIT 1');
    $stmt->execute([':code' => $code]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) return null;
    if ($row['expires_at'] && time() > (int)$row['expires_at']) return null;
    return $row;
}

function increment_clicks($id) {
    $pdo = get_pdo();
    $stmt = $pdo->prepare('UPDATE urls SET clicks = clicks + 1 WHERE id = :id');
    $stmt->execute([':id' => $id]);
}

function base_url() {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = dirname($_SERVER['SCRIPT_NAME']);
    $script = rtrim($script, '/\\');
    return $scheme . '://' . $host . $script . '/';
}
