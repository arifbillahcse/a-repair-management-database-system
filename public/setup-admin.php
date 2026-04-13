<?php
/**
 * ONE-TIME ADMIN SETUP SCRIPT
 * ----------------------------
 * 1. Upload this file to your server's public/ folder
 * 2. Visit https://yourdomain.com/setup-admin.php
 * 3. Fill in the form and submit
 * 4. DELETE THIS FILE immediately after use
 */

// ── Block access if admin already exists ────────────────────────────────────
define('BASE_PATH', dirname(__DIR__));

// Load .env
$envFile = BASE_PATH . '/config/.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
        [$key, $val] = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($val);
    }
}

$dbHost = $_ENV['DB_HOST'] ?? 'localhost';
$dbPort = $_ENV['DB_PORT'] ?? '3306';
$dbName = $_ENV['DB_NAME'] ?? 'repair_system';
$dbUser = $_ENV['DB_USER'] ?? 'root';
$dbPass = $_ENV['DB_PASS'] ?? '';

$error   = '';
$success = '';

try {
    $pdo = new PDO(
        "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4",
        $dbUser,
        $dbPass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die('<div style="font-family:sans-serif;color:red;padding:2rem">
        <h2>Database connection failed</h2>
        <p>' . htmlspecialchars($e->getMessage()) . '</p>
        <p>Check your <code>config/.env</code> file.</p>
    </div>');
}

// Check if any admin user exists
$existingAdmin = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin' AND is_active = 1")->fetchColumn();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName  = trim($_POST['last_name']  ?? '');
    $email     = trim($_POST['email']      ?? '');
    $username  = trim($_POST['username']   ?? '');
    $password  = $_POST['password']        ?? '';
    $confirm   = $_POST['confirm']         ?? '';

    if (!$firstName || !$lastName || !$email || !$username || !$password) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        // Check username not taken
        $taken = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $taken->execute([$username]);
        if ($taken->fetchColumn() > 0) {
            $error = 'Username already taken.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $now  = date('Y-m-d H:i:s');

            $pdo->beginTransaction();
            try {
                // Insert staff
                $stmt = $pdo->prepare("
                    INSERT INTO staff (first_name, last_name, email, role, is_active, created_at, updated_at)
                    VALUES (?, ?, ?, 'admin', 1, ?, ?)
                ");
                $stmt->execute([$firstName, $lastName, $email, $now, $now]);
                $staffId = (int) $pdo->lastInsertId();

                // Insert user
                $stmt = $pdo->prepare("
                    INSERT INTO users (username, email, password_hash, role, staff_id, is_active, created_at, updated_at)
                    VALUES (?, ?, ?, 'admin', ?, 1, ?, ?)
                ");
                $stmt->execute([$username, $email, $hash, $staffId, $now, $now]);

                $pdo->commit();
                $success = "Admin account created! Username: <strong>{$username}</strong> — You can now <a href='/login'>log in</a>.<br><br><strong style='color:red'>Delete this file (setup-admin.php) from your server immediately!</strong>";
            } catch (PDOException $e) {
                $pdo->rollBack();
                $error = 'Database error: ' . htmlspecialchars($e->getMessage());
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin Setup</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0 }
  body { background: #1a1a1a; color: #e5e5e5; font-family: sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 2rem }
  .card { background: #2a2a2a; border-radius: 12px; padding: 2rem; width: 100%; max-width: 480px }
  h1 { color: #10b981; margin-bottom: .25rem; font-size: 1.4rem }
  p.sub { color: #888; font-size: .85rem; margin-bottom: 1.5rem }
  label { display: block; font-size: .85rem; color: #aaa; margin-bottom: .25rem; margin-top: 1rem }
  input { width: 100%; padding: .6rem .8rem; background: #1a1a1a; border: 1px solid #444; border-radius: 6px; color: #e5e5e5; font-size: .95rem }
  input:focus { outline: none; border-color: #10b981 }
  .row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem }
  .alert { padding: .75rem 1rem; border-radius: 6px; margin-bottom: 1rem; font-size: .9rem }
  .alert-error { background: #3b1a1a; border: 1px solid #dc2626; color: #f87171 }
  .alert-success { background: #1a3b2a; border: 1px solid #10b981; color: #6ee7b7 }
  .warning { background: #3b2e1a; border: 1px solid #f59e0b; color: #fcd34d; padding: .6rem .9rem; border-radius: 6px; font-size: .82rem; margin-bottom: 1.5rem }
  button { margin-top: 1.5rem; width: 100%; padding: .75rem; background: #10b981; color: #fff; border: none; border-radius: 6px; font-size: 1rem; cursor: pointer; font-weight: 600 }
  button:hover { background: #059669 }
  .existing { text-align: center; padding: 1rem 0 }
  .existing a { color: #10b981 }
</style>
</head>
<body>
<div class="card">
  <h1>Admin Account Setup</h1>
  <p class="sub">Create the first administrator for your Repair Management System</p>

  <?php if ($success): ?>
    <div class="alert alert-success"><?= $success ?></div>
  <?php elseif ($existingAdmin > 0): ?>
    <div class="warning">An admin account already exists. For security this form is disabled.</div>
    <div class="existing"><a href="/login">Go to login</a></div>
  <?php else: ?>

    <?php if ($error): ?>
      <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif ?>

    <div class="warning">Delete this file after creating your account.</div>

    <form method="POST">
      <div class="row">
        <div>
          <label>First Name</label>
          <input type="text" name="first_name" value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>" required>
        </div>
        <div>
          <label>Last Name</label>
          <input type="text" name="last_name" value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>" required>
        </div>
      </div>
      <label>Email</label>
      <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
      <label>Username</label>
      <input type="text" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
      <label>Password (min 8 characters)</label>
      <input type="password" name="password" required>
      <label>Confirm Password</label>
      <input type="password" name="confirm" required>
      <button type="submit">Create Admin Account</button>
    </form>

  <?php endif ?>
</div>
</body>
</html>
