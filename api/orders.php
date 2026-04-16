<?php
// ============================================================
// BEE SHARP SV — Orders API
// Actions: create, list, get, my_orders, update_status, cancel
// ============================================================
require_once __DIR__ . '/../config.php';
securityHeaders();

header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

$action = $_GET['action'] ?? '';
$input  = json_decode(file_get_contents('php://input'), true) ?? [];

switch ($action) {
    case 'create':        createOrder($input);                 break;
    case 'list':          listOrders();                        break;
    case 'get':           getOrder((int)($_GET['id'] ?? 0));   break;
    case 'my_orders':     myOrders();                          break;
    case 'update_status': updateStatus($input);                break;
    case 'cancel':        cancelOrder($input);                 break;
    default:
        jsonResponse(false, null, 'Unknown action.', 400);
}

// ── HELPERS ──────────────────────────────────────────────
function isAdmin(): bool  { return !empty($_SESSION['admin_id']); }
function requireAdmin(): void {
    if (!isAdmin()) jsonResponse(false, null, 'Admin access required.', 403);
}

// ── CREATE ORDER ─────────────────────────────────────────
function createOrder(array $d): void {
    verifyCsrf($d);
    $db = getDB();

    $customerName  = trim($d['customer_name']  ?? '');
    $customerPhone = trim($d['customer_phone'] ?? '');
    $serviceType   = $d['service_type']         ?? 'pickup_delivery';
    $items         = $d['items']                ?? [];
    $paymentMethod = $d['payment_method']       ?? 'cash';

    if (!$customerName || !$customerPhone) {
        jsonResponse(false, null, 'Name and phone are required.', 400);
    }
    if (empty($items) || !is_array($items)) {
        jsonResponse(false, null, 'At least one item is required.', 400);
    }

    $validServices = ['pickup_delivery', 'onsite', 'market'];
    if (!in_array($serviceType, $validServices)) $serviceType = 'pickup_delivery';

    $validPayments = ['cash', 'bitcoin_lightning', 'bitcoin_onchain'];
    if (!in_array($paymentMethod, $validPayments)) $paymentMethod = 'cash';

    $itemTypes = [
        'knife'        => PRICE_KNIFE,
        'axe_machete'  => PRICE_AXE,
        'garden_tool'  => PRICE_GARDEN,
        'pizza_cutter' => PRICE_PIZZA,
        'repair'       => 0.00,
        'other'        => 0.00,
    ];

    $subtotal   = 0.00;
    $totalItems = 0;
    $cleanItems = [];

    foreach ($items as $item) {
        $type  = $item['item_type'] ?? 'knife';
        if (!array_key_exists($type, $itemTypes)) $type = 'other';
        $qty   = max(1, min(99, (int)($item['quantity'] ?? 1)));
        $price = $itemTypes[$type];
        $line  = round($price * $qty, 2);
        $subtotal   += $line;
        $totalItems += $qty;
        $cleanItems[] = [
            'item_type'   => $type,
            'description' => substr(trim($item['description'] ?? ''), 0, 200),
            'quantity'    => $qty,
            'unit_price'  => $price,
            'line_total'  => $line,
        ];
    }

    // Delivery fee (server-calculated)
    $deliveryFee = 0.00;
    if ($serviceType === 'pickup_delivery' && $totalItems < FREE_DELIVERY_MIN) {
        $deliveryFee = DELIVERY_FEE;
    }

    // Discounts (server-calculated — never trust client totals)
    $discountPct = 0.0;
    $isBitcoin   = in_array($paymentMethod, ['bitcoin_lightning', 'bitcoin_onchain']);
    if ($isBitcoin)                   $discountPct += BTC_DISCOUNT_PCT;
    if ($totalItems >= BULK_DISCOUNT_MIN) $discountPct += BULK_DISCOUNT_PCT;
    $discountAmt = round($subtotal * ($discountPct / 100), 2);
    $total       = round($subtotal + $deliveryFee - $discountAmt, 2);

    // Resolve customer
    $customerId = null;
    if (!empty($_SESSION['customer_id'])) {
        $customerId = (int)$_SESSION['customer_id'];
    } else {
        $phone = preg_replace('/[^0-9+]/', '', $customerPhone);
        $stmt  = $db->prepare('SELECT id FROM customers WHERE whatsapp = ?');
        $stmt->execute([$phone]);
        $existing = $stmt->fetchColumn();
        if ($existing) {
            $customerId = (int)$existing;
        } else {
            $parts = explode(' ', $customerName, 2);
            $db->prepare('INSERT INTO customers (first_name, last_name, whatsapp) VALUES (?,?,?)')
               ->execute([$parts[0], $parts[1] ?? '', $phone]);
            $customerId = (int)$db->lastInsertId();
        }
    }

    // Insert order
    $orderNum = generateOrderNumber();
    $db->prepare('INSERT INTO orders
        (order_number, customer_id, customer_name, customer_phone, service_type,
         pickup_address, scheduled_date, scheduled_time, payment_method,
         subtotal, delivery_fee, discount_pct, discount_amt, total, media_consent, notes)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)')
       ->execute([
        $orderNum,
        $customerId,
        $customerName,
        $customerPhone,
        $serviceType,
        substr($d['pickup_address'] ?? '', 0, 255) ?: null,
        $d['scheduled_date'] ?? null,
        $d['scheduled_time'] ?? null,
        $paymentMethod,
        $subtotal,
        $deliveryFee,
        $discountPct,
        $discountAmt,
        $total,
        !empty($d['media_consent']) ? 1 : 0,
        substr($d['notes'] ?? '', 0, 1000) ?: null,
    ]);
    $orderId = (int)$db->lastInsertId();

    // Insert items
    $stmtItem = $db->prepare('INSERT INTO order_items
        (order_id, item_type, description, quantity, unit_price, line_total)
        VALUES (?,?,?,?,?,?)');
    foreach ($cleanItems as $ci) {
        $stmtItem->execute([
            $orderId, $ci['item_type'], $ci['description'] ?: null,
            $ci['quantity'], $ci['unit_price'], $ci['line_total'],
        ]);
    }

    // Status history
    $db->prepare('INSERT INTO order_status_history (order_id, new_status, changed_by) VALUES (?,?,?)')
       ->execute([$orderId, 'pending', $customerName]);

    // Bitcoin payment record
    if ($isBitcoin) {
        $db->prepare('INSERT INTO bitcoin_payments (order_id, method, amount_usd) VALUES (?,?,?)')
           ->execute([$orderId, str_replace('bitcoin_', '', $paymentMethod), $total]);
    }

    jsonResponse(true, [
        'order_id'     => $orderId,
        'order_number' => $orderNum,
        'total'        => number_format($total, 2),
        'discount_pct' => $discountPct,
        'wa_link'      => buildWaLink($orderNum, $customerName, $cleanItems, $total, $paymentMethod, $d),
    ], "Order {$orderNum} created!");
}

// ── BUILD WHATSAPP LINK ───────────────────────────────────
function buildWaLink(string $num, string $name, array $items, float $total, string $payment, array $d): string {
    $lines   = ["🔪 *BEE SHARP SV ORDER*", "Order: {$num}", "Name: {$name}"];
    $lines[] = "Service: " . ($d['service_type'] ?? 'pickup_delivery');
    if (!empty($d['pickup_address'])) $lines[] = "Address: " . $d['pickup_address'];
    if (!empty($d['scheduled_date'])) $lines[] = "Date: " . $d['scheduled_date'];
    $lines[] = "Payment: " . $payment;
    $lines[] = "";
    $lines[] = "*Items:*";
    foreach ($items as $i) {
        $label  = ucwords(str_replace('_', ' ', $i['item_type']));
        $lines[] = "- {$i['quantity']}x {$label}" . ($i['line_total'] > 0 ? " (\${$i['line_total']})" : " (FREE)");
    }
    $lines[] = "";
    $lines[] = "*Total: \$" . number_format($total, 2) . "*";
    if (!empty($d['notes'])) $lines[] = "Notes: " . $d['notes'];
    return 'https://wa.me/' . WA_NUMBER . '?text=' . rawurlencode(implode("\n", $lines));
}

// ── LIST ORDERS (Admin) ───────────────────────────────────
function listOrders(): void {
    requireAdmin();
    $db = getDB();

    $status = $_GET['status'] ?? '';
    $search = $_GET['search'] ?? '';
    $limit  = min((int)($_GET['limit']  ?? 50), 200);
    $offset = max((int)($_GET['offset'] ?? 0),  0);

    $where  = ['1=1'];
    $params = [];
    if ($status) { $where[] = 'o.status = ?'; $params[] = $status; }
    if ($search) {
        $where[] = '(o.customer_name LIKE ? OR o.order_number LIKE ? OR o.customer_phone LIKE ?)';
        $s = "%{$search}%";
        $params = array_merge($params, [$s, $s, $s]);
    }
    $whereStr = implode(' AND ', $where);

    $stmt = $db->prepare("SELECT o.*, COUNT(oi.id) AS item_count
        FROM orders o
        LEFT JOIN order_items oi ON oi.order_id = o.id
        WHERE {$whereStr}
        GROUP BY o.id
        ORDER BY o.created_at DESC
        LIMIT ? OFFSET ?");
    $params[] = $limit;
    $params[] = $offset;
    $stmt->execute($params);
    $orders = $stmt->fetchAll();

    $countStmt = $db->prepare("SELECT COUNT(*) FROM orders o WHERE {$whereStr}");
    $countStmt->execute(array_slice($params, 0, -2));

    jsonResponse(true, ['orders' => $orders, 'total' => (int)$countStmt->fetchColumn()]);
}

// ── GET SINGLE ORDER ─────────────────────────────────────
function getOrder(int $id): void {
    if (!$id) jsonResponse(false, null, 'Invalid ID.', 400);
    $db   = getDB();
    $stmt = $db->prepare('SELECT * FROM orders WHERE id = ?');
    $stmt->execute([$id]);
    $order = $stmt->fetch();
    if (!$order) jsonResponse(false, null, 'Order not found.', 404);

    if (!isAdmin()) {
        if (empty($_SESSION['customer_id']) || (int)$order['customer_id'] !== (int)$_SESSION['customer_id']) {
            jsonResponse(false, null, 'Forbidden.', 403);
        }
    }

    $stmt = $db->prepare('SELECT * FROM order_items WHERE order_id = ?');
    $stmt->execute([$id]);
    $order['items'] = $stmt->fetchAll();

    $stmt = $db->prepare('SELECT * FROM order_status_history WHERE order_id = ? ORDER BY created_at ASC');
    $stmt->execute([$id]);
    $order['history'] = $stmt->fetchAll();

    jsonResponse(true, $order);
}

// ── MY ORDERS (customer) ─────────────────────────────────
function myOrders(): void {
    if (empty($_SESSION['customer_id'])) jsonResponse(false, null, 'Not authenticated.', 401);
    $db   = getDB();
    $stmt = $db->prepare('SELECT o.*, COUNT(oi.id) AS item_count
        FROM orders o
        LEFT JOIN order_items oi ON oi.order_id = o.id
        WHERE o.customer_id = ?
        GROUP BY o.id
        ORDER BY o.created_at DESC');
    $stmt->execute([$_SESSION['customer_id']]);
    jsonResponse(true, ['orders' => $stmt->fetchAll()]);
}

// ── UPDATE STATUS (Admin) ─────────────────────────────────
function updateStatus(array $d): void {
    requireAdmin();
    verifyCsrf($d);

    $orderId   = (int)($d['order_id'] ?? 0);
    $newStatus = trim($d['status']    ?? '');
    $note      = trim($d['note']      ?? '');

    $validStatuses = ['pending','scheduled','picked_up','in_progress','ready','out_for_delivery','delivered','complete','cancelled'];
    if (!$orderId || !in_array($newStatus, $validStatuses)) {
        jsonResponse(false, null, 'Invalid order ID or status.', 400);
    }

    $db   = getDB();
    $stmt = $db->prepare('SELECT status FROM orders WHERE id = ?');
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();
    if (!$order) jsonResponse(false, null, 'Order not found.', 404);

    $db->prepare('UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?')
       ->execute([$newStatus, $orderId]);

    if ($note) {
        $db->prepare('UPDATE orders SET admin_notes = CONCAT(COALESCE(admin_notes,""), ?) WHERE id = ?')
           ->execute(["\n[" . date('Y-m-d H:i') . "] $note", $orderId]);
    }

    $db->prepare('INSERT INTO order_status_history (order_id, old_status, new_status, changed_by, note) VALUES (?,?,?,?,?)')
       ->execute([$orderId, $order['status'], $newStatus, $_SESSION['admin_username'] ?? 'admin', $note ?: null]);

    jsonResponse(true, ['status' => $newStatus], "Status updated to {$newStatus}.");
}

// ── CANCEL ORDER ─────────────────────────────────────────
function cancelOrder(array $d): void {
    verifyCsrf($d);
    $orderId = (int)($d['order_id'] ?? 0);
    if (!$orderId) jsonResponse(false, null, 'Invalid order ID.', 400);

    $db   = getDB();
    $stmt = $db->prepare('SELECT * FROM orders WHERE id = ?');
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();
    if (!$order) jsonResponse(false, null, 'Order not found.', 404);

    if (!isAdmin()) {
        if (empty($_SESSION['customer_id']) || (int)$order['customer_id'] !== (int)$_SESSION['customer_id']) {
            jsonResponse(false, null, 'Forbidden.', 403);
        }
        if (!in_array($order['status'], ['pending', 'scheduled'])) {
            jsonResponse(false, null, 'Only pending or scheduled orders can be cancelled.', 400);
        }
    }

    $db->prepare('UPDATE orders SET status = "cancelled", updated_at = NOW() WHERE id = ?')->execute([$orderId]);
    $db->prepare('INSERT INTO order_status_history (order_id, old_status, new_status, changed_by) VALUES (?,?,?,?)')
       ->execute([$orderId, $order['status'], 'cancelled', $_SESSION['admin_username'] ?? $_SESSION['customer_name'] ?? 'customer']);

    jsonResponse(true, null, 'Order cancelled.');
}
