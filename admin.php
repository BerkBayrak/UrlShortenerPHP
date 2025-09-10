<?php
//author Lycradiata
// Include configuration and helpers
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

// Load config
$config = include __DIR__ . '/config.php';

// Simple authentication
session_start();
if (!isset($_SESSION['logged_in'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $password = $_POST['password'] ?? '';
        if ($password === $config['admin_password']) {
            $_SESSION['logged_in'] = true;
            header("Location: admin.php");
            exit;
        } else {
            $error = "Invalid password!";
        }
    }

    // Login form
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Admin Login</title>
    </head>
    <body>
        <h2>Admin Login</h2>
        <?php if (!empty($error)) echo "<p style='color:red;'>$error</p>"; ?>
        <form method="post">
            <input type="password" name="password" placeholder="Enter admin password" required>
            <button type="submit">Login</button>
        </form>
    </body>
    </html>
    <?php
    exit;
}

// If logged in, show all links
try {
    $pdo = getDbConnection($config['db_path']);
    $stmt = $pdo->query("SELECT code, url FROM urls ORDER BY rowid DESC");
    $links = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>URL Shortener Admin</title>
    <style>
        table { border-collapse: collapse; width: 80%; }
        th, td { border: 1px solid #999; padding: 8px; }
        th { background: #eee; }
    </style>
</head>
<body>
    <h2>Stored Links</h2>
    <table>
        <tr>
            <th>Short Code</th>
            <th>Full Short URL</th>
            <th>Original URL</th>
        </tr>
        <?php foreach ($links as $link): ?>
        <tr>
            <td><?php echo htmlspecialchars($link['code']); ?></td>
            <td>
                <a href="<?php echo '/' . htmlspecialchars($link['code']); ?>" target="_blank">
                    <?php echo $_SERVER['HTTP_HOST'] . '/' . htmlspecialchars($link['code']); ?>
                </a>
            </td>
            <td><?php echo htmlspecialchars($link['url']); ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <p><a href="logout.php">Logout</a></p>
</body>
</html>
