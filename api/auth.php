<?php
// ============================================================
// BEE SHARP SV — Auth API
// Actions: register, login, admin_login, logout, me
// ============================================================
require_once __DIR__ . '/../config.php';
securityHeaders();

header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

$action = $_GET['action'] ?? '';
$input  = json_decode(file_get_contents('php://input'), true) ?? [];

switch ($action) {
    case 'register':    customerRegister($input); break;
    case 'login':       customerLogin($input);    break;
    case 'admin_login': adminLogin($input);       break;
    case 'logout':      logout();                 break;
    case 'me':          getMe();                  break;
    default:
        jsonResponse(false, null, 'Unknown action.', 400);
}

// ── CUSTOMER REGISTER ────────────────────────────────────
function customerRegister(array $d): void {
    verifyCsrf($d);

    $firstName = trim($d['first_name'] ?? '');
    $lastName  = trim($d['last_name']  ?? '');
    $whatsapp  = trim($d['whatsapp']   ?? '');
    $email     = trim($d['email']      ?? '');
    $password  = $d['password']        ?? '';
    $address   = trim($d['address']    ?? '');
    $consent   = !empty($d['media_consent']);

    if (!$firstName || !$whatsapp) {
        jsonResponse(false, null, 'Name and WhatsApp number are required.', 400);
    }
    if (strlen($password) < 8) {
        jsonResponse(false, null, 'Password must be at least 8 characters.', 400);
    }
    if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(false, null, 'Invalid email address.', 400);
    }

    $whatsapp = preg_replace('/[^0-9+]/', '', $whatsapp);
    if (strlen($whatsapp) < 7) {
        jsonResponse(false, null, 'Invalid WhatsApp number.', 400);
    }

    $db = getDB();

    $stmt = $db->prepare('SELECT id FROM customers WHERE whatsapp = ?');
    $stmt->execute([$whatsapp]);
    if ($stmt->fetch()) {
        jsonResponse(false, null, 'A customer with this WhatsApp number already exists.', 409);
    }

    if ($email) {
        $stmt = $db->prepare('SELECT id FROM customers WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            jsonResponse(false, null, 'A customer with this email already exists.', 409);
        }
    }

    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    $stmt = $db->prepare('INSERT INTO customers
        (first_name, last_name, email, whatsapp, address, password_hash, media_consent)
        VALUES (?,?,?,?,?,?,?)');
    $stmt->execute([$firstName, $lastName, $email ?: null, $whatsapp, $address ?: null, $hash, $consent ? 1 : 0]);
    $id = (int)$db->lastInsertId();

    session_regenerate_id(true);
    $_SESSION['customer_id']   = $id;
    $_SESSION['customer_name'] = trim("$firstName $lastName");
    $_SESSION['role']          = 'customer';

    jsonResponse(true, ['id' => $id, 'name' => trim("$firstName $lastName")], 'Registration successful!');
}

// ── CUSTOMER LOGIN ────────────────────────────────────────
function customerLogin(array $d): void {
    verifyCsrf($d);

    $login    = trim($d['login']    ?? '');
    $password = $d['password']      ?? '';

    if (!$login || !$password) {
        jsonResponse(false, null, 'Login and password are required.', 400);
    }

    $db   = getDB();
    $stmt = $db->prepare('SELECT * FROM customers WHERE (email = ? OR whatsapp = ?) AND is_active = 1 LIMIT 1');
    $stmt->execute([$login, $login]);
    $cust = $stmt->fetch();

    if (!$cust || !$cust['password_hash'] || !password_verify($password, $cust['password_hash'])) {
        jsonResponse(false, null, 'Invalid credentials.', 401);
    }

    session_regenerate_id(true);
    $_SESSION['customer_id']   = (int)$cust['id'];
    $_SESSION['customer_name'] = trim($cust['first_name'] . ' ' . $cust['last_name']);
    $_SESSION['role']          = 'customer';

    jsonResponse(true, [
        'id'   => (int)$cust['id'],
        'name' => trim($cust['first_name'] . ' ' . $cust['last_name']),
        'role' => 'customer',
    ], 'Login successful!');
}

// ── ADMIN LOGIN ───────────────────────────────────────────
function adminLogin(array $d): void {
    verifyCsrf($d);

    $username = trim($d['username'] ?? '');
    $password = $d['password']      ?? '';

    if (!$username || !$password) {
        jsonResponse(false, null, 'Username and password are required.', 400);
    }

    // Rate limiting via session
    $attempts  = (int)($_SESSION['admin_login_attempts'] ?? 0);
    $lockUntil = (int)($_SESSION['admin_lock_until']    ?? 0);
    if ($lockUntil > time()) {
        $wait = (int)ceil(($lockUntil - time()) / 60);
        jsonResponse(false, null, "Too many attempts. Try again in {$wait} minute(s).", 429);
    }

    $db   = getDB();
    $stmt = $db->prepare('SELECT * FROM admin_users WHERE (username = ? OR email = ?) AND is_active = 1 LIMIT 1');
    $stmt->execute([$username, $username]);
    $admin = $stmt->fetch();

    if (!$admin || !password_verify($password, $admin['password_hash'])) {
        $_SESSION['admin_login_attempts'] = $attempts + 1;
        if ($attempts + 1 >= ADMIN_RATE_LIMIT) {
            $_SESSION['admin_lock_until']     = time() + 900;
            $_SESSION['admin_login_attempts'] = 0;
            jsonResponse(false, null, 'Too many failed attempts. Locked for 15 minutes.', 429);
        }
        jsonResponse(false, null, 'Invalid credentials.', 401);
    }

    // Successful login — reset counters, regenerate session
    $_SESSION['admin_login_attempts'] = 0;
    $_SESSION['admin_lock_until']     = 0;

    session_regenerate_id(true);

    $_SESSION['admin_id']       = (int)$admin['id'];
    $_SESSION['admin_username'] = $admin['username'];
    $_SESSION['admin_role']     = $admin['role'];
    $_SESSION['role']           = 'admin';

    $db->prepare('UPDATE admin_users SET last_login = NOW() WHERE id = ?')
       ->execute([$admin['id']]);

    jsonResponse(true, ['username' => $admin['username'], 'role' => $admin['role']], 'Admin login successful!');
}

// ── LOGOUT ────────────────────────────────────────────────
function logout(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
    jsonResponse(true, null, 'Logged out.');
}

// ── WHO AM I ─────────────────────────────────────────────
function getMe(): void {
    if (!empty($_SESSION['admin_id'])) {
        jsonResponse(true, [
            'role'     => 'admin',
            'username' => $_SESSION['admin_username'] ?? '',
        ]);
    } elseif (!empty($_SESSION['customer_id'])) {
        jsonResponse(true, [
            'role' => 'customer',
            'id'   => $_SESSION['customer_id'],
            'name' => $_SESSION['customer_name'] ?? '',
        ]);
    } else {
        jsonResponse(false, null, 'Not authenticated.', 401);
    }
}
