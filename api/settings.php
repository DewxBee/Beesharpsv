<?php
// ============================================================
// BEE SHARP SV — Settings API (Admin only)
// Actions: get, update, change_password, dashboard_stats
// ============================================================
require_once __DIR__ . '/../config.php';
securityHeaders();

header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

if (empty($_SESSION['admin_id'])) {
    jsonResponse(false, null, 'Admin access required.', 403);
}

$action = $_GET['action'] ?? '';
$input  = json_decode(file_get_contents('php://input'), true) ?? [];

switch ($action) {
    case 'get':             getSettings();          break;
    case 'update':          updateSettings($input); break;
    case 'change_password': changePassword($input); break;
    case 'dashboard_stats': dashboardStats();       break;
    default:
        jsonResponse(false, null, 'Unknown action.', 400);
}

function getSettings(): void {
    $db   = getDB();
    $rows = $db->query('SELECT setting_key, setting_value FROM settings')->fetchAll();
    $out  = [];
    foreach ($rows as $r) $out[$r['setting_key']] = $r['setting_value'];
    jsonResponse(true, $out);
}

function updateSettings(array $d): void {
    verifyCsrf($d);
    $db = getDB();

    // Whitelist of allowed keys — prevents arbitrary key injection
    $allowed = [
        'business_name', 'whatsapp_number', 'email', 'service_area',
        'delivery_fee', 'free_delivery_min',
        'bitcoin_discount_pct', 'bulk_discount_pct', 'bulk_discount_min',
        'price_knife', 'price_axe', 'price_garden',
        'lightning_address', 'onchain_address',
        'instagram_handle', 'facebook_url', 'telegram_handle', 'nostr_handle',
    ];

    $stmt = $db->prepare('INSERT INTO settings (setting_key, setting_value)
        VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');

    $updated = 0;
    foreach ($allowed as $key) {
        if (array_key_exists($key, $d)) {
            $stmt->execute([$key, (string)$d[$key]]);
            $updated++;
        }
    }

    jsonResponse(true, ['updated' => $updated], 'Settings saved.');
}

function changePassword(array $d): void {
    verifyCsrf($d);
    $current = $d['current_password'] ?? '';
    $newPass = $d['new_password']     ?? '';

    if (strlen($newPass) < 8) {
        jsonResponse(false, null, 'New password must be at least 8 characters.', 400);
    }

    $db   = getDB();
    $stmt = $db->prepare('SELECT password_hash FROM admin_users WHERE id = ?');
    $stmt->execute([$_SESSION['admin_id']]);
    $admin = $stmt->fetch();

    if (!$admin || !password_verify($current, $admin['password_hash'])) {
        jsonResponse(false, null, 'Current password is incorrect.', 401);
    }

    $db->prepare('UPDATE admin_users SET password_hash = ? WHERE id = ?')
       ->execute([password_hash($newPass, PASSWORD_BCRYPT, ['cost' => 12]), $_SESSION['admin_id']]);

    jsonResponse(true, null, 'Password updated.');
}

function dashboardStats(): void {
    $db  = getDB();
    $out = [];

    $out['active_orders']   = (int)$db->query('SELECT COUNT(*) FROM orders WHERE status NOT IN ("complete","cancelled","delivered")')->fetchColumn();
    $out['total_customers'] = (int)$db->query('SELECT COUNT(*) FROM customers WHERE is_active=1')->fetchColumn();
    $out['revenue_month']   = (float)$db->query('SELECT COALESCE(SUM(total),0) FROM orders WHERE status!="cancelled" AND created_at>=DATE_FORMAT(NOW(),"%%Y-%%m-01")')->fetchColumn();
    $out['btc_rate_month']  = (int)$db->query('SELECT COUNT(*) FROM orders WHERE payment_method IN ("bitcoin_lightning","bitcoin_onchain") AND created_at>=DATE_FORMAT(NOW(),"%%Y-%%m-01")')->fetchColumn();
    $out['items_month']     = (int)$db->query('SELECT COALESCE(SUM(oi.quantity),0) FROM order_items oi JOIN orders o ON o.id=oi.order_id WHERE o.status!="cancelled" AND o.created_at>=DATE_FORMAT(NOW(),"%%Y-%%m-01")')->fetchColumn();

    $stmt = $db->query('SELECT DATE_FORMAT(created_at,"%%Y-%%m") AS month, COALESCE(SUM(total),0) AS revenue
        FROM orders WHERE status!="cancelled" AND created_at>=DATE_SUB(NOW(),INTERVAL 6 MONTH)
        GROUP BY month ORDER BY month ASC');
    $out['monthly_revenue'] = $stmt->fetchAll();

    jsonResponse(true, $out);
}
