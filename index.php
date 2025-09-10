<?php
// author Lycradiata
// Main page: form for creating short URLs and a simple admin view
require_once __DIR__ . '/helpers.php';

session_start();
$cfg = get_config();
$errors = [];
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic CSRF protection using session token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        $errors[] = 'Invalid CSRF token';
    } else {
        $rawUrl = $_POST['url'] ?? '';
        $custom = trim($_POST['custom_alias'] ?? null);
        $expiry = $_POST['expires_at'] ?? null;

        $url = normalize_url($rawUrl);
        if ($url === '') $errors[] = 'Please provide a valid URL.';
        if ($custom === '') $custom = null;

        if (!empty($custom) && !preg_match('/^[0-9A-Za-z_-]+$/', $custom)) {
            $errors[] = 'Custom alias can only contain letters, numbers, _ and -';
        }

        if (empty($errors)) {
            try {
                $expiresAt = null;
                if ($expiry) {
                    $ts = strtotime($expiry);
                    if ($ts === false) throw new Exception('Bad expiry date format');
                    $expiresAt = $ts;
                }

                $code = create_short_url($url, $custom, $expiresAt);
                $success = base_url() . $code;
            } catch (Exception $e) {
                $errors[] = $e->getMessage();
            }
        }
    }
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
$csrf = $_SESSION['csrf_token'];
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Simple PHP URL Shortener</title>
<style>
    body{font-family:Arial,Helvetica,sans-serif;margin:24px}
    form{max-width:700px}
    input,button{padding:8px;margin:6px 0;width:100%}
    .small{width:48%;display:inline-block}
    .note{color:#555;font-size:0.9em}
    .error{color:#a00}
    .success{color:#070}
    table{border-collapse:collapse;width:100%;margin-top:18px}
    th,td{border:1px solid #ddd;padding:8px;text-align:left}
</style>
</head>
<body>
<h1>Simple PHP URL Shortener</h1>

<?php if ($errors): ?>
    <div class="error">
        <ul>
            <?php foreach ($errors as $err) echo '<li>' . htmlspecialchars($err) . '</li>'; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="success">Short URL created: <a href="<?=htmlspecialchars($success)?>" target="_blank"><?=htmlspecialchars($success)?></a></div>
<?php endif; ?>

<form method="post">
    <label>Target URL</label>
    <input type="text" name="url" placeholder="https://example.com/very/long/link" maxlength="<?= $cfg['max_url_length'] ?>" required>

    <label>Custom alias (optional) — letters, numbers, _ and - only</label>
    <input type="text" name="custom_alias" placeholder="my-link" maxlength="<?= $cfg['max_alias_length'] ?>">

    <label>Expires at (optional) — YYYY-MM-DD or human readable (e.g. +7 days)</label>
    <input type="text" name="expires_at" placeholder="2025-12-31 or +7 days">

    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
    <button type="submit">Create Short URL</button>
</form>

<hr>
<h2>Admin: list short URLs</h2>
<p class="note">Provide admin password to see full list.</p>
<form method="get">
    <input type="password" name="admin_password" placeholder="Admin password" style="width:auto;display:inline-block">
    <button type="submit">View</button>
</form>

<?php
if (!empty($_GET['admin_password'])) {
    if ($_GET['admin_password'] === $cfg['admin_password']) {
        $pdo = get_pdo();
        $stmt = $pdo->query('SELECT * FROM urls ORDER BY created_at DESC LIMIT 200');
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo '<table><tr><th>Short</th><th>Target</th><th>Clicks</th><th>Created</th><th>Expires</th><th>Custom</th></tr>';
        foreach ($rows as $r) {
            $short = base_url() . htmlspecialchars($r['code']);
            echo '<tr>';
            echo '<td><a href="' . $short . '" target="_blank">' . htmlspecialchars($r['code']) . '</a></td>';
            echo '<td><a href="' . htmlspecialchars($r['url']) . '" target="_blank">' . htmlspecialchars($r['url']) . '</a></td>';
            echo '<td>' . (int)$r['clicks'] . '</td>';
            echo '<td>' . date('Y-m-d H:i', (int)$r['created_at']) . '</td>';
            echo '<td>' . ($r['expires_at'] ? date('Y-m-d H:i', (int)$r['expires_at']) : '-') . '</td>';
            echo '<td>' . ($r['custom'] ? 'yes' : 'no') . '</td>';
            echo '</tr>';
        }
        echo '</table>';
    } else {
        echo '<p class="error">Wrong admin password.</p>';
    }
}
?>

</body>
</html>
