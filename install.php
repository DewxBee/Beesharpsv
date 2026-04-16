<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Bee Sharp SV — Install</title>
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:monospace;background:#0a0a0a;color:#e0e0e0;padding:30px;max-width:720px;margin:0 auto;}
h1,h2{color:#F7931A;margin-bottom:16px;}
.step{background:#111;border:1px solid #333;border-radius:8px;padding:20px;margin-bottom:20px;}
.step h3{color:#F7931A;margin-bottom:12px;font-size:1rem;}
.ok{color:#00c864;} .err{color:#ff5050;} .warn{color:#F7931A;}
label{display:block;color:#F7931A;font-size:0.82rem;margin:12px 0 4px;}
input[type=text],input[type=password],input[type=email]{
  width:100%;background:#1a1a1a;border:1px solid #333;color:#e0e0e0;
  padding:9px 12px;border-radius:4px;font-family:monospace;font-size:0.9rem;
}
input:focus{outline:none;border-color:#F7931A;}
.btn{background:#F7931A;color:#000;border:none;padding:12px 28px;border-radius:6px;
  cursor:pointer;font-family:monospace;font-size:1rem;font-weight:bold;margin-top:16px;}
.btn:hover{background:#ffa833;}
.msg{margin-top:8px;font-size:0.85rem;}
fieldset{border:1px solid #2a2a2a;border-radius:6px;padding:12px 16px;margin-bottom:8px;}
legend{color:#F7931A;font-size:0.85rem;padding:0 6px;}
</style>
</head>
<body>
<?php
// ============================================================
// BEE SHARP SV — INSTALL SCRIPT
// Run ONCE after uploading files to your server.
// DELETE THIS FILE from the server after installation!
// ============================================================

$lockFile    = __DIR__ . '/.installed';
$localConfig = __DIR__ . '/config.local.php';

// Already installed?
if (file_exists($lockFile)) {
    echo '<h1>🔪 Bee Sharp SV</h1>';
    echo '<div class="step"><h3 class="ok">✅ Already Installed</h3>';
    echo '<p>The site has already been installed.</p>';
    echo '<p style="margin-top:10px;">⚠️ <strong>Delete this file (install.php) from your server immediately!</strong></p>';
    echo '<p style="margin-top:8px;"><a href="/" style="color:#F7931A;">→ Go to site</a></p>';
    echo '</div>';
    exit;
}

// ── Process form submission ───────────────────────────────
$errors  = [];
$success = false;
$log     = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    $formToken = $_POST['_token'] ?? '';
    session_start();
    if (!hash_equals($_SESSION['install_token'] ?? '', $formToken)) {
        $errors[] = 'Invalid form token. Please reload the page and try again.';
    }

    if (empty($errors)) {
        $dbHost    = trim($_POST['db_host']      ?? 'localhost');
        $dbName    = trim($_POST['db_name']       ?? '');
        $dbUser    = trim($_POST['db_user']       ?? '');
        $dbPass    = $_POST['db_pass']            ?? '';
        $adminUser = trim($_POST['admin_user']    ?? '');
        $adminEmail= trim($_POST['admin_email']   ?? '');
        $adminPass = $_POST['admin_pass']         ?? '';
        $adminPass2= $_POST['admin_pass2']        ?? '';

        // Validate
        if (!$dbName || !$dbUser) $errors[] = 'Database name and username are required.';
        if (!$adminUser)          $errors[] = 'Admin username is required.';
        if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid admin email required.';
        if (strlen($adminPass) < 8) $errors[] = 'Admin password must be at least 8 characters.';
        if ($adminPass !== $adminPass2) $errors[] = 'Admin passwords do not match.';
    }

    if (empty($errors)) {
        // Test DB connection
        $dsn = "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4";
        try {
            $db = new PDO($dsn, $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            $log[] = ['ok', 'Database connection successful.'];
        } catch (PDOException $e) {
            $errors[] = 'Database connection failed. Check your credentials and try again.';
            error_log('Install DB error: ' . $e->getMessage());
        }
    }

    if (empty($errors)) {
        // Run schema
        $schemaFile = __DIR__ . '/db/schema.sql';
        if (!file_exists($schemaFile)) {
            $errors[] = 'Schema file not found: db/schema.sql';
        } else {
            $schema = file_get_contents($schemaFile);
            $statements = array_filter(
                array_map('trim', explode(';', $schema)),
                fn($s) => strlen($s) > 5 && !preg_match('/^\s*--/', $s)
            );
            $sqlErrors = 0;
            foreach ($statements as $sql) {
                try {
                    $db->exec($sql);
                } catch (PDOException $e) {
                    if (stripos($e->getMessage(), 'already exists') === false) {
                        error_log('Install SQL warning: ' . $e->getMessage());
                        $sqlErrors++;
                    }
                }
            }
            if ($sqlErrors === 0) {
                $log[] = ['ok', 'Database tables created successfully.'];
            } else {
                $log[] = ['warn', "Schema ran with {$sqlErrors} warning(s) — check server error log."];
            }
        }
    }

    if (empty($errors)) {
        // Create admin user
        try {
            $hash = password_hash($adminPass, PASSWORD_BCRYPT, ['cost' => 12]);
            $stmt = $db->prepare('INSERT INTO admin_users (username, email, password_hash, role)
                VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE password_hash=VALUES(password_hash), email=VALUES(email)');
            $stmt->execute([$adminUser, $adminEmail, $hash, 'superadmin']);
            $log[] = ['ok', "Admin account created: {$adminUser}"];
        } catch (PDOException $e) {
            $errors[] = 'Failed to create admin account.';
            error_log('Install admin error: ' . $e->getMessage());
        }
    }

    if (empty($errors)) {
        // Generate secret key and write config.local.php
        $secretKey = bin2hex(random_bytes(32));
        $configContent = "<?php\n"
            . "// Generated by install.php — DO NOT COMMIT THIS FILE\n"
            . "define('DB_HOST', " . var_export($dbHost, true) . ");\n"
            . "define('DB_NAME', " . var_export($dbName, true) . ");\n"
            . "define('DB_USER', " . var_export($dbUser, true) . ");\n"
            . "define('DB_PASS', " . var_export($dbPass, true) . ");\n"
            . "define('SECRET_KEY', " . var_export($secretKey, true) . ");\n";

        if (file_put_contents($localConfig, $configContent) === false) {
            $errors[] = 'Could not write config.local.php — check folder permissions.';
        } else {
            $log[] = ['ok', 'config.local.php created with your credentials.'];
        }
    }

    if (empty($errors)) {
        // Write lock file
        file_put_contents($lockFile, date('Y-m-d H:i:s'));
        $log[]   = ['ok', 'Installation lock file created.'];
        $success = true;
    }
}

// ── Generate CSRF token for form ─────────────────────────
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['install_token'])) {
    $_SESSION['install_token'] = bin2hex(random_bytes(32));
}
$formToken = $_SESSION['install_token'];
?>

<h1>🔪 Bee Sharp SV — Installer</h1>

<?php if ($success): ?>
<div class="step" style="border-color:#00c864;">
  <h3 class="ok">✅ Installation Complete!</h3>
  <?php foreach ($log as [$type, $msg]): ?>
    <p class="<?= $type === 'ok' ? 'ok' : 'warn' ?>" style="margin-top:6px;">
      <?= $type === 'ok' ? '✓' : '⚠' ?> <?= htmlspecialchars($msg) ?>
    </p>
  <?php endforeach; ?>
  <hr style="border-color:#333;margin:16px 0;">
  <p class="err" style="font-weight:bold;font-size:1rem;">
    ⚠️ DELETE install.php FROM YOUR SERVER NOW!
  </p>
  <p style="margin-top:8px;">Use SFTP or IONOS File Manager to delete this file before going live.</p>
  <p style="margin-top:12px;">
    <a href="/">→ Go to site</a> &nbsp;|&nbsp;
    <a href="/admin.php">→ Admin panel</a>
  </p>
</div>

<?php else: ?>

<?php if (!empty($errors)): ?>
<div class="step" style="border-color:#ff5050;">
  <h3 class="err">Installation errors</h3>
  <?php foreach ($errors as $e): ?>
    <p class="err" style="margin-top:4px;">✗ <?= htmlspecialchars($e) ?></p>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (!empty($log)): ?>
<div class="step">
  <h3>Progress so far</h3>
  <?php foreach ($log as [$type, $msg]): ?>
    <p class="<?= $type === 'ok' ? 'ok' : 'warn' ?>" style="margin-top:4px;">
      <?= $type === 'ok' ? '✓' : '⚠' ?> <?= htmlspecialchars($msg) ?>
    </p>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<form method="POST">
  <input type="hidden" name="_token" value="<?= htmlspecialchars($formToken) ?>">

  <div class="step">
    <h3>Step 1 — Database Connection</h3>
    <p style="color:#999;font-size:0.85rem;">Find these in: IONOS Control Panel → Hosting → Databases</p>
    <fieldset>
      <legend>Database</legend>
      <label>Host</label>
      <input type="text" name="db_host" value="<?= htmlspecialchars($_POST['db_host'] ?? 'localhost') ?>" required>
      <label>Database Name</label>
      <input type="text" name="db_name" value="<?= htmlspecialchars($_POST['db_name'] ?? '') ?>" placeholder="e.g. dbs12345678" required>
      <label>Username</label>
      <input type="text" name="db_user" value="<?= htmlspecialchars($_POST['db_user'] ?? '') ?>" placeholder="e.g. dbu12345678" required>
      <label>Password</label>
      <input type="password" name="db_pass" value="">
    </fieldset>
  </div>

  <div class="step">
    <h3>Step 2 — Admin Account</h3>
    <p style="color:#999;font-size:0.85rem;">This creates your admin login for the panel at /admin.php</p>
    <fieldset>
      <legend>Admin</legend>
      <label>Username</label>
      <input type="text" name="admin_user" value="<?= htmlspecialchars($_POST['admin_user'] ?? 'admin') ?>" required>
      <label>Email</label>
      <input type="email" name="admin_email" value="<?= htmlspecialchars($_POST['admin_email'] ?? 'bee-sharpSV@proton.me') ?>" required>
      <label>Password (min 8 characters)</label>
      <input type="password" name="admin_pass" minlength="8" required>
      <label>Confirm Password</label>
      <input type="password" name="admin_pass2" required>
    </fieldset>
  </div>

  <button type="submit" class="btn">🚀 Run Installation</button>
</form>

<?php endif; ?>
</body>
</html>
