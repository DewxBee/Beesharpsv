<?php
// ============================================================
// BEE SHARP SV — Notifications API (Admin only)
// Actions: send, list, templates
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
    case 'send':      sendNotification($input); break;
    case 'list':      listNotifications();      break;
    case 'templates': getTemplates();           break;
    default:
        jsonResponse(false, null, 'Unknown action.', 400);
}

// ── SEND / LOG NOTIFICATION ───────────────────────────────
function sendNotification(array $d): void {
    verifyCsrf($d);

    $validTypes = ['order_status','ready','promotion','market_event','custom'];
    $type       = in_array($d['type'] ?? '', $validTypes) ? $d['type'] : 'custom';
    $recipient  = $d['recipient'] ?? 'single';
    $message    = trim($d['message'] ?? '');
    $custId     = (int)($d['customer_id'] ?? 0);

    if (!$message) jsonResponse(false, null, 'Message is required.', 400);

    $validRecipients = ['single','all','bitcoin_customers','active_orders'];
    if (!in_array($recipient, $validRecipients)) {
        jsonResponse(false, null, 'Invalid recipient type.', 400);
    }

    $db     = getDB();
    $phones = [];

    if ($recipient === 'single' && $custId) {
        $stmt = $db->prepare('SELECT whatsapp, first_name FROM customers WHERE id = ? AND is_active = 1');
        $stmt->execute([$custId]);
        $c = $stmt->fetch();
        if ($c) $phones[] = ['phone' => $c['whatsapp'], 'name' => $c['first_name']];
    } elseif ($recipient === 'bitcoin_customers') {
        $stmt = $db->query('SELECT whatsapp AS phone, first_name AS name FROM customers
            WHERE payment_pref IN ("bitcoin_lightning","bitcoin_onchain") AND is_active=1');
        $phones = $stmt->fetchAll();
    } elseif ($recipient === 'active_orders') {
        $stmt = $db->query('SELECT DISTINCT c.whatsapp AS phone, c.first_name AS name
            FROM customers c JOIN orders o ON o.customer_id = c.id
            WHERE o.status NOT IN ("complete","cancelled","delivered") AND c.is_active=1');
        $phones = $stmt->fetchAll();
    } elseif ($recipient === 'all') {
        $stmt = $db->query('SELECT whatsapp AS phone, first_name AS name FROM customers WHERE is_active=1');
        $phones = $stmt->fetchAll();
    }

    // Log the notification
    $db->prepare('INSERT INTO notifications (type, recipient, customer_id, message, channel, status, sent_by, sent_at)
        VALUES (?,?,?,?,?,?,?,NOW())')
       ->execute([$type, $recipient, $custId ?: null, $message, 'whatsapp', 'sent', $_SESSION['admin_username'] ?? 'admin']);

    // Build WhatsApp links
    $waLinks = [];
    foreach ($phones as $p) {
        $phone = preg_replace('/[^0-9]/', '', $p['phone'] ?? '');
        if ($phone) {
            $waLinks[] = [
                'name'    => htmlspecialchars($p['name'] ?? 'Customer'),
                'phone'   => $phone,
                'wa_link' => 'https://wa.me/' . $phone . '?text=' . rawurlencode($message),
            ];
        }
    }

    jsonResponse(true, [
        'recipients_count' => count($waLinks),
        'wa_links'         => $waLinks,
        'fallback_wa_link' => 'https://wa.me/' . WA_NUMBER . '?text=' . rawurlencode($message),
    ], 'Notification logged. Open the WhatsApp links to send.');
}

// ── LIST NOTIFICATIONS ────────────────────────────────────
function listNotifications(): void {
    $db    = getDB();
    $limit = min((int)($_GET['limit'] ?? 50), 200);
    $stmt  = $db->prepare('
        SELECT n.*, c.first_name, c.last_name, c.whatsapp
        FROM notifications n
        LEFT JOIN customers c ON c.id = n.customer_id
        ORDER BY n.created_at DESC
        LIMIT ?');
    $stmt->execute([$limit]);
    jsonResponse(true, ['notifications' => $stmt->fetchAll()]);
}

// ── MESSAGE TEMPLATES ─────────────────────────────────────
function getTemplates(): void {
    $waNum = WA_NUMBER;
    jsonResponse(true, [
        'order_status' => "🔪 *BEE SHARP SV UPDATE*\n\nHello [NAME]! Your order [ORDER_NUM] status has been updated.\n\nStatus: [STATUS]\n\nQuestions? Reply here anytime. ✂️",
        'ready'        => "✅ *BEE SHARP SV — READY!*\n\nHello [NAME]! Your items have been sharpened and are ready for pickup/delivery. We'll contact you to arrange.\n\nThank you for choosing Bee Sharp SV! ₿",
        'picked_up'    => "📦 *BEE SHARP SV*\n\nWe've picked up your items and sharpening is underway! We'll notify you when they're ready.\n\nOrder: [ORDER_NUM]",
        'delivered'    => "🚗 *BEE SHARP SV — DELIVERED!*\n\nYour freshly sharpened items have been delivered. Enjoy those razor-sharp edges!\n\nPaid with ₿ Bitcoin? You got a 10% discount. 🎉",
        'promotion'    => "⚡ *BEE SHARP SV SPECIAL OFFER!*\n\nPay with Bitcoin (Lightning or on-chain) and get 10% OFF!\n\nOrder 10+ items and get another 10% off — stack the discounts! 🔪\n\nBook now: wa.me/{$waNum}",
        'market_event' => "🌾 *BEE SHARP SV — FARMERS MARKET!*\n\nWe'll be at the Club Cocal Bitcoin Farmers Market!\n\nBring your knives, axes, machetes, and garden tools for same-day on-site sharpening.\n\n📅 [DATE]\n\n₿ Bitcoin accepted — 10% discount!",
        'review_ask'   => "📸 *BEE SHARP SV — FEEDBACK REQUEST*\n\nWe hope you're loving your freshly sharpened blades! 🔪\n\nSend us a before & after video and receive *\$2 back in Bitcoin* if we use it in our marketing!\n\nBy sending you consent to social media use. Thank you! ₿",
    ]);
}
