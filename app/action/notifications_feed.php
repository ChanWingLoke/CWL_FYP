<?php
// app/action/notifications_feed.php
// Lazy job notification generator & JSON feed

header('Content-Type: application/json');
require_once __DIR__ . '/../init.php';
require_once __DIR__ . '/notifications_helpers.php';

// 1. Auth Guard
if (!isset($_SESSION['user_id'])) { 
    http_response_code(401); 
    echo json_encode(['ok'=>false,'error'=>'not-auth']); 
    exit; 
}

$db = $pdo ?? ($obj->pdo ?? null);
if (!$db) { 
    http_response_code(500); 
    echo json_encode(['ok'=>false,'error'=>'no-db']); 
    exit; 
}

$userId = (int)$_SESSION['user_id'];

// 2. Handle "Mark as Read" Actions (AJAX)
if (isset($_GET['mark']) && $_GET['mark'] === 'all') {
    $st = $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = :u AND is_read = 0");
    $st->execute([':u' => $userId]);
    echo json_encode(['ok'=>true]); 
    exit;
}
if (isset($_GET['read'])) {
    $id = (int)$_GET['read'];
    $st = $db->prepare("UPDATE notifications SET is_read = 1 WHERE id = :id AND user_id = :u");
    $st->execute([':id'=>$id, ':u'=>$userId]);
    echo json_encode(['ok'=>true]); 
    exit;
}

// 3. Lazy Job Generation Functions

function ensure_due_soon_notifications(PDO $db, int $userId): void {
    // Throttle: Run max once every 5 seconds per session
    if (!isset($_SESSION['notif_tick_last']) || (time() - (int)$_SESSION['notif_tick_last']) > 5) {
        $_SESSION['notif_tick_last'] = time();
    } else return;

    // Check bookings due in 1-3 days
    $activeStatuses = ["pending","approved","in_progress","active"];
    $ph = implode(',', array_fill(0, count($activeStatuses), '?'));

    $sql = "SELECT b.id, DATE(b.end_time) as end_date, TIME(b.end_time) as end_time,
                   DATEDIFF(DATE(b.end_time), CURDATE()) as days_left
            FROM bookings b
            WHERE b.user_id = ? 
              AND b.status IN ($ph)
              AND DATE(b.end_time) > CURDATE()
              AND DATE(b.end_time) <= (CURDATE() + INTERVAL 3 DAY)
              AND NOT EXISTS (
                  SELECT 1 FROM notifications n 
                  WHERE n.user_id = b.user_id AND n.ref_id = b.id 
                    AND n.type IN ('booking_due_1d','booking_due_2d','booking_due_3d')
                    AND n.created_at >= CURDATE()
              )";
    
    $st = $db->prepare($sql);
    $st->execute(array_merge([$userId], $activeStatuses));

    while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
        $days = (int)$row['days_left'];
        $type = "booking_due_{$days}d";
        $title = "Booking due in {$days} day" . ($days===1?'':'s');
        $body  = "Booking #{$row['id']} ends on {$row['end_date']} {$row['end_time']}.";
        
        notif_insert($db, $userId, $title, $body, "index.php?page=bookings_all", $type, $row['id']);
    }
}

function ensure_warranty_due_notifications(PDO $db, int $userId): void {
    $isAdmin = isset($_SESSION['user_role']) && strtolower($_SESSION['user_role']) === 'admin';
    if (!$isAdmin) return;

    if (!isset($_SESSION['notif_tick_warranty']) || (time() - (int)$_SESSION['notif_tick_warranty']) > 5) {
        $_SESSION['notif_tick_warranty'] = time();
    } else return;

    // 30, 7, 1, and 0 (today) days left
    foreach ([30,7,1,0] as $d) {
        $sql = "SELECT id, DATE(end_date) as end_date FROM warranties 
                WHERE warranty_status = 'active' 
                  AND DATE(end_date) = (CURDATE() + INTERVAL {$d} DAY)";
        
        $rows = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $r) {
            $type  = "warranty_due_{$d}d";
            $label = ($d===0) ? "expires today" : "due in {$d} days";
            $title = "Warranty {$label} (#{$r['id']})";
            $body  = "Warranty ends on {$r['end_date']}.";
            
            notif_insert($db, $userId, $title, $body, "index.php?page=warranty_list", $type, $r['id']);
        }
    }

    // Expired
    $sqlX = "SELECT id, DATE(end_date) as end_date FROM warranties 
             WHERE DATE(end_date) < CURDATE() AND warranty_status != 'expired' LIMIT 50";
    $rowsX = $db->query($sqlX)->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rowsX as $r) {
        // Only notify if we haven't already
        notif_insert($db, $userId, "Warranty Expired (#{$r['id']})", "Expired on {$r['end_date']}", "index.php?page=warranty_list", "warranty_expired", $r['id']);
    }
}

function ensure_maintenance_broadcasts(PDO $db, int $userId): void {
    if (!isset($_SESSION['notif_tick_maint']) || (time() - (int)$_SESSION['notif_tick_maint']) > 5) {
        $_SESSION['notif_tick_maint'] = time();
    } else return;

    // Open tickets
    $sql = "SELECT m.id, m.status, m.title, m.asset_id FROM maintenance_orders m
            WHERE m.status IN ('open','in_progress','waiting_parts')
            ORDER BY m.id DESC LIMIT 5";
    $rows = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($rows as $r) {
        $title = "Asset under maintenance (#{$r['id']})";
        $body  = "Asset #{$r['asset_id']} is currently '{$r['status']}'.";
        notif_insert($db, $userId, $title, $body, "index.php?page=maintenance_list", 'maint_asset_open', $r['id']);
    }

    // Closed tickets
    $sqlC = "SELECT m.id, m.asset_id FROM maintenance_orders m WHERE m.status = 'closed' ORDER BY m.updated_at DESC LIMIT 5";
    $rowsC = $db->query($sqlC)->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rowsC as $r) {
        notif_insert($db, $userId, "Maintenance Closed (#{$r['id']})", "Asset #{$r['asset_id']} maintenance is complete.", "index.php?page=maintenance_list", 'maint_asset_closed', $r['id']);
    }
}

// 4. Execute Generators
try {
    ensure_due_soon_notifications($db, $userId);
    ensure_warranty_due_notifications($db, $userId);
    ensure_maintenance_broadcasts($db, $userId);
} catch (Throwable $e) { 
    // Silently fail logic generation to allow feed reading
}

// 5. Return Feed Data
$limit = isset($_GET['limit']) ? max(1, min(20, (int)$_GET['limit'])) : 8;
$unread = (int)$db->query("SELECT COUNT(*) FROM notifications WHERE user_id = {$userId} AND is_read = 0")->fetchColumn();

$st = $db->prepare("SELECT id, title, body, link, is_read, created_at 
                    FROM notifications 
                    WHERE user_id = :u 
                    ORDER BY is_read ASC, created_at DESC 
                    LIMIT :lim");
$st->bindValue(':u', $userId, PDO::PARAM_INT);
$st->bindValue(':lim', $limit, PDO::PARAM_INT);
$st->execute();

echo json_encode([
    'ok' => true, 
    'unread' => $unread, 
    'items' => $st->fetchAll(PDO::FETCH_ASSOC)
]);