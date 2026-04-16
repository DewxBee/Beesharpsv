<?php
// ============================================================
// BEE SHARP SV — Customers API (Admin CRM)
// Actions: list, get, create, update, delete, stats
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
    case 'list':   listCustomers();                       break;
    case 'get':    getCustomer((int)($_GET['id'] ?? 0));  break;
    case 'create': createCustomer($input);                break;
    case 'update': updateCustomer($input);                break;
    case 'delete': deleteCustomer($input);                break;
    case 'stats':  getStats();                            break;
    default:
        jsonResponse(false, null, 'Unknown action.', 400);
}

// ── LIST CUSTOMERS ────────────────────────────────────────
function listCustomers(): void {
    $db     = getDB();
    $search = $_GET['search'] ?? '';
    $filter = $_GET['filter'] ?? '';
    $limit  = min((int)($_GET['limit']  ?? 100), 500);
    $offset = max((int)($_GET['offset'] ?? 0),   0);

    $where  = ['c.is_active = 1'];
    $params = [];

    if ($search) {
        $where[] = '(c.first_name LIKE ? OR c.last_name LIKE ? OR c.whatsapp LIKE ? OR c.email LIKE ?)';
        $s = "%{$search}%";
        $params = array_merge($params, [$s, $s, $s, $s]);
    }
    if ($filter === 'bitcoin') {
        $where[] = 'c.payment_pref IN ("bitcoin_lightning","bitcoin_onchain")';
    }
    if ($filter === 'new') {
        $where[] = 'c.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)';
    }

    $whereStr = implode(' AND ', $where);
    $havingStr = $filter === 'frequent' ? 'HAVING order_count >= 5' : '';

    $stmt = $db->prepare("
        SELECT c.*,
               COUNT(o.id) AS order_count,
               COALESCE(SUM(o.total), 0) AS total_spent,
               MAX(o.created_at) AS last_order_at
        FROM customers c
        LEFT JOIN orders o ON o.customer_id = c.id AND o.status != 'cancelled'
        WHERE {$whereStr}
        GROUP BY c.id
        {$havingStr}
        ORDER BY c.created_at DESC
        LIMIT ? OFFSET ?");
    $params[] = $limit;
    $params[] = $offset;
    $stmt->execute($params);
    $customers = $stmt->fetchAll();

    foreach ($customers as &$c) unset($c['password_hash']);

    $cntStmt = $db->prepare("SELECT COUNT(*) FROM customers c WHERE {$whereStr}");
    $cntStmt->execute(array_slice($params, 0, -2));

    jsonResponse(true, ['customers' => $customers, 'total' => (int)$cntStmt->fetchColumn()]);
}

// ── GET SINGLE CUSTOMER ───────────────────────────────────
function getCustomer(int $id): void {
    if (!$id) jsonResponse(false, null, 'Invalid ID.', 400);
    $db   = getDB();
    $stmt = $db->prepare('SELECT * FROM customers WHERE id = ?');
    $stmt->execute([$id]);
    $c = $stmt->fetch();
    if (!$c) jsonResponse(false, null, 'Customer not found.', 404);
    unset($c['password_hash']);

    $stmt = $db->prepare('SELECT id, order_number, service_type, status, total, created_at FROM orders WHERE customer_id = ? ORDER BY created_at DESC');
    $stmt->execute([$id]);
    $c['orders'] = $stmt->fetchAll();

    jsonResponse(true, $c);
}

// ── CREATE CUSTOMER ───────────────────────────────────────
function createCustomer(array $d): void {
    verifyCsrf($d);
    $firstName = trim($d['first_name'] ?? '');
    $whatsapp  = preg_replace('/[^0-9+]/', '', $d['whatsapp'] ?? '');
    if (!$firstName || !$whatsapp) {
        jsonResponse(false, null, 'First name and WhatsApp are required.', 400);
    }
    $db = getDB();
    $stmt = $db->prepare('SELECT id FROM customers WHERE whatsapp = ?');
    $stmt->execute([$whatsapp]);
    if ($stmt->fetch()) jsonResponse(false, null, 'Customer with this WhatsApp already exists.', 409);

    $hash = !empty($d['password']) ? password_hash($d['password'], PASSWORD_BCRYPT, ['cost' => 12]) : null;
    $db->prepare('INSERT INTO customers (first_name, last_name, email, whatsapp, address, area, payment_pref, password_hash, notes)
        VALUES (?,?,?,?,?,?,?,?,?)')->execute([
        $firstName,
        trim($d['last_name']   ?? ''),
        trim($d['email']       ?? '') ?: null,
        $whatsapp,
        trim($d['address']     ?? '') ?: null,
        trim($d['area']        ?? '') ?: null,
        $d['payment_pref']     ?? 'any',
        $hash,
        trim($d['notes']       ?? '') ?: null,
    ]);
    jsonResponse(true, ['id' => (int)$db->lastInsertId()], 'Customer created.');
}

// ── UPDATE CUSTOMER ───────────────────────────────────────
function updateCustomer(array $d): void {
    verifyCsrf($d);
    $id = (int)($d['id'] ?? 0);
    if (!$id) jsonResponse(false, null, 'Invalid ID.', 400);

    $db   = getDB();
    $stmt = $db->prepare('SELECT id FROM customers WHERE id = ?');
    $stmt->execute([$id]);
    if (!$stmt->fetch()) jsonResponse(false, null, 'Customer not found.', 404);

    $allowed = ['first_name','last_name','email','whatsapp','address','area','payment_pref','notes','media_consent','is_active'];
    $sets    = [];
    $params  = [];
    foreach ($allowed as $f) {
        if (array_key_exists($f, $d)) {
            $sets[]   = "{$f} = ?";
            $params[] = $d[$f];
        }
    }
    if (!empty($d['password'])) {
        $sets[]   = 'password_hash = ?';
        $params[] = password_hash($d['password'], PASSWORD_BCRYPT, ['cost' => 12]);
    }
    if (empty($sets)) jsonResponse(false, null, 'Nothing to update.', 400);

    $params[] = $id;
    $db->prepare('UPDATE customers SET ' . implode(', ', $sets) . ' WHERE id = ?')->execute($params);
    jsonResponse(true, null, 'Customer updated.');
}

// ── DEACTIVATE CUSTOMER (soft delete) ────────────────────
function deleteCustomer(array $d): void {
    verifyCsrf($d);
    $id = (int)($d['id'] ?? 0);
    if (!$id) jsonResponse(false, null, 'Invalid ID.', 400);
    $db = getDB();
    $db->prepare('UPDATE customers SET is_active = 0 WHERE id = ?')->execute([$id]);
    jsonResponse(true, null, 'Customer deactivated.');
}

// ── DASHBOARD STATS ───────────────────────────────────────
function getStats(): void {
    $db  = getDB();
    $out = [];

    $out['total_customers']    = (int)$db->query('SELECT COUNT(*) FROM customers WHERE is_active=1')->fetchColumn();
    $out['new_this_month']     = (int)$db->query('SELECT COUNT(*) FROM customers WHERE is_active=1 AND created_at >= DATE_FORMAT(NOW(),"%%Y-%%m-01")')->fetchColumn();
    $out['active_orders']      = (int)$db->query('SELECT COUNT(*) FROM orders WHERE status NOT IN ("complete","cancelled","delivered")')->fetchColumn();
    $out['total_orders_month'] = (int)$db->query('SELECT COUNT(*) FROM orders WHERE created_at >= DATE_FORMAT(NOW(),"%%Y-%%m-01")')->fetchColumn();
    $out['revenue_month']      = (float)$db->query('SELECT COALESCE(SUM(total),0) FROM orders WHERE status!="cancelled" AND created_at >= DATE_FORMAT(NOW(),"%%Y-%%m-01")')->fetchColumn();
    $out['revenue_ytd']        = (float)$db->query('SELECT COALESCE(SUM(total),0) FROM orders WHERE status!="cancelled" AND YEAR(created_at)=YEAR(NOW())')->fetchColumn();
    $out['bitcoin_count_month']= (int)$db->query('SELECT COUNT(*) FROM orders WHERE payment_method IN ("bitcoin_lightning","bitcoin_onchain") AND created_at >= DATE_FORMAT(NOW(),"%%Y-%%m-01")')->fetchColumn();
    $out['items_this_month']   = (int)$db->query('SELECT COALESCE(SUM(oi.quantity),0) FROM order_items oi JOIN orders o ON o.id=oi.order_id WHERE o.status!="cancelled" AND o.created_at >= DATE_FORMAT(NOW(),"%%Y-%%m-01")')->fetchColumn();

    jsonResponse(true, $out);
}
