<?php
// ============================================================
// BEE SHARP SV — Configuration
// IONOS Hosting · PHP 8.x · MySQL/MariaDB
// ============================================================
// DO NOT edit DB credentials here directly.
// Run install.php once — it creates config.local.php with your
// real credentials. config.local.php is gitignored and never
// committed to the repo.
// ============================================================

// ── DEFAULT / FALLBACK VALUES ─────────────────────────────
define('DB_HOST',    'localhost');
define('DB_NAME',    '');
define('DB_USER',    '');
define('DB_PASS',    '');
define('DB_CHARSET', 'utf8mb4');

define('SITE_URL',  'https://beesharpsv.com');
define('SITE_NAME', 'Bee Sharp SV');
define('TIMEZONE',  'America/El_Salvador');

define('SESSION_LIFETIME', 86400);   // 24 hours
define('ADMIN_RATE_LIMIT',  5);      // attempts before 15-min lockout

define('WA_NUMBER', '50379522492');

define('BTC_DISCOUNT_PCT',   10);
define('BULK_DISCOUNT_PCT',  10);
define('BULK_DISCOUNT_MIN',  10);

define('PRICE_KNIFE',        5.00);
define('PRICE_AXE',          7.00);
define('PRICE_GARDEN',       9.00);
define('PRICE_PIZZA',        0.00);
define('DELIVERY_FEE',      10.00);
define('FREE_DELIVERY_MIN',  10);

define('DEBUG_MODE', false);

// ── LOAD LOCAL CONFIG (written by install.php) ────────────
$localConfig = __DIR__ . '/config.local.php';
if (file_exists($localConfig)) {
    require_once $localConfig;
}

// ── SECRET KEY (auto-generate if not set by local config) ─
if (!defined('SECRET_KEY')) {
    // In production this will be defined in config.local.php.
    // During install, generate a temporary one.
    define('SECRET_KEY', bin2hex(random_bytes(32)));
}

// ── ENVIRONMENT ───────────────────────────────────────────
date_default_timezone_set(TIMEZONE);

if (DEBUG_MODE) {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}
ini_set('log_errors', 1);

// ── SESSION ───────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure',   1);
    ini_set('session.use_strict_mode', 1);
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path'     => '/',
        'domain'   => '',
        'secure'   => true,
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}

// ── CSRF ──────────────────────────────────────────────────
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(array $input): void {
    $token = $input['csrf_token']
        ?? $_SERVER['HTTP_X_CSRF_TOKEN']
        ?? $_POST['csrf_token']
        ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token.']);
        exit;
    }
}

// ── DATABASE ──────────────────────────────────────────────
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        if (!DB_NAME || !DB_USER) {
            http_response_code(503);
            die(json_encode(['success' => false, 'message' => 'Database not configured. Please run install.php.']));
        }
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log('DB connection failed: ' . $e->getMessage());
            http_response_code(500);
            die(json_encode(['success' => false, 'message' => 'Database connection failed.']));
        }
    }
    return $pdo;
}

// ── RESPONSE HELPERS ──────────────────────────────────────
function jsonResponse(bool $success, $data = null, string $message = '', int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data'    => $data,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function generateOrderNumber(): string {
    return 'BSV-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));
}

function securityHeaders(): void {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}
