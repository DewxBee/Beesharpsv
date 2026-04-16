<?php
// ============================================================
// BEE SHARP SV — Schedule API
// Actions: get_slots, book_slot, release_slot, block_day, admin_view
// ============================================================
require_once __DIR__ . '/../config.php';
securityHeaders();

header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

$action = $_GET['action'] ?? '';
$input  = json_decode(file_get_contents('php://input'), true) ?? [];

switch ($action) {
    case 'get_slots':    getSlots();          break;
    case 'book_slot':    bookSlot($input);    break;
    case 'release_slot': releaseSlot($input); break;
    case 'block_day':    blockDay($input);    break;
    case 'admin_view':   adminView();         break;
    default:
        jsonResponse(false, null, 'Unknown action.', 400);
}

function defaultSlots(): array {
    return ['09:00','10:00','11:00','12:00','13:00','14:00','15:00','16:00','17:00'];
}

function validTimeSlot(string $time): bool {
    return in_array($time, defaultSlots(), true);
}

// ── GET SLOTS FOR A DATE ─────────────────────────────────
function getSlots(): void {
    $date = $_GET['date'] ?? date('Y-m-d');
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        jsonResponse(false, null, 'Invalid date format. Use YYYY-MM-DD.', 400);
    }
    if ($date < date('Y-m-d')) {
        jsonResponse(false, null, 'Cannot query past dates.', 400);
    }

    // Block Sundays
    if (date('N', strtotime($date)) === '7') {
        jsonResponse(true, ['date' => $date, 'slots' => [], 'closed' => true, 'message' => 'Closed on Sundays.']);
    }

    $db   = getDB();
    $stmt = $db->prepare('SELECT slot_time, is_available FROM schedule_slots WHERE slot_date = ?');
    $stmt->execute([$date]);
    $dbSlots = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $result = [];
    foreach (defaultSlots() as $time) {
        $available = isset($dbSlots[$time]) ? (bool)$dbSlots[$time] : true;
        $result[]  = ['time' => $time, 'available' => $available];
    }

    jsonResponse(true, ['date' => $date, 'slots' => $result]);
}

// ── BOOK A SLOT ──────────────────────────────────────────
function bookSlot(array $d): void {
    verifyCsrf($d);
    $date    = $d['date']     ?? '';
    $time    = $d['time']     ?? '';
    $orderId = (int)($d['order_id'] ?? 0);

    if (!$date || !$time) jsonResponse(false, null, 'Date and time are required.', 400);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) jsonResponse(false, null, 'Invalid date format.', 400);
    if ($date < date('Y-m-d')) jsonResponse(false, null, 'Cannot book past dates.', 400);
    if (!validTimeSlot($time)) jsonResponse(false, null, 'Invalid time slot.', 400);

    $db   = getDB();
    $stmt = $db->prepare('SELECT id, is_available FROM schedule_slots WHERE slot_date = ? AND slot_time = ?');
    $stmt->execute([$date, $time]);
    $slot = $stmt->fetch();

    if ($slot && !$slot['is_available']) {
        jsonResponse(false, null, 'This slot is no longer available. Please choose another.', 409);
    }

    try {
        if ($slot) {
            $db->prepare('UPDATE schedule_slots SET is_available = 0, order_id = ? WHERE id = ?')
               ->execute([$orderId ?: null, $slot['id']]);
        } else {
            $db->prepare('INSERT INTO schedule_slots (slot_date, slot_time, is_available, order_id) VALUES (?,?,0,?)')
               ->execute([$date, $time, $orderId ?: null]);
        }
    } catch (PDOException $e) {
        // Unique constraint violation = double-booking attempt
        jsonResponse(false, null, 'Slot was just booked by someone else. Please choose another.', 409);
    }

    if ($orderId) {
        $db->prepare('UPDATE orders SET scheduled_date = ?, scheduled_time = ?, status = "scheduled" WHERE id = ?')
           ->execute([$date, $time, $orderId]);
    }

    jsonResponse(true, ['date' => $date, 'time' => $time], 'Slot booked successfully.');
}

// ── RELEASE A SLOT (Admin) ───────────────────────────────
function releaseSlot(array $d): void {
    if (empty($_SESSION['admin_id'])) jsonResponse(false, null, 'Admin required.', 403);
    verifyCsrf($d);
    $date = $d['date'] ?? '';
    $time = $d['time'] ?? '';
    if (!$date || !$time) jsonResponse(false, null, 'Date and time required.', 400);
    $db = getDB();
    $db->prepare('UPDATE schedule_slots SET is_available = 1, order_id = NULL WHERE slot_date = ? AND slot_time = ?')
       ->execute([$date, $time]);
    jsonResponse(true, null, 'Slot released.');
}

// ── BLOCK / UNBLOCK ENTIRE DAY (Admin) ──────────────────
function blockDay(array $d): void {
    if (empty($_SESSION['admin_id'])) jsonResponse(false, null, 'Admin required.', 403);
    verifyCsrf($d);
    $date    = $d['date']    ?? '';
    $blocked = !isset($d['blocked']) || (bool)$d['blocked'];
    if (!$date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) jsonResponse(false, null, 'Valid date required.', 400);

    $db  = getDB();
    $avl = $blocked ? 0 : 1;
    foreach (defaultSlots() as $time) {
        $stmt = $db->prepare('SELECT id FROM schedule_slots WHERE slot_date = ? AND slot_time = ?');
        $stmt->execute([$date, $time]);
        if ($stmt->fetch()) {
            $db->prepare('UPDATE schedule_slots SET is_available = ? WHERE slot_date = ? AND slot_time = ?')
               ->execute([$avl, $date, $time]);
        } elseif ($blocked) {
            $db->prepare('INSERT INTO schedule_slots (slot_date, slot_time, is_available) VALUES (?,?,0)')
               ->execute([$date, $time]);
        }
    }
    jsonResponse(true, null, $blocked ? "Day {$date} blocked." : "Day {$date} unblocked.");
}

// ── ADMIN WEEK VIEW ──────────────────────────────────────
function adminView(): void {
    if (empty($_SESSION['admin_id'])) jsonResponse(false, null, 'Admin required.', 403);
    $startDate = $_GET['start_date'] ?? date('Y-m-d');
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) $startDate = date('Y-m-d');
    $db   = getDB();
    $stmt = $db->prepare('
        SELECT ss.slot_date, ss.slot_time, ss.is_available, ss.order_id,
               o.order_number, o.customer_name, o.service_type, o.status
        FROM schedule_slots ss
        LEFT JOIN orders o ON o.id = ss.order_id
        WHERE ss.slot_date BETWEEN ? AND DATE_ADD(?, INTERVAL 7 DAY)
        ORDER BY ss.slot_date, ss.slot_time');
    $stmt->execute([$startDate, $startDate]);
    jsonResponse(true, ['schedule' => $stmt->fetchAll()]);
}
