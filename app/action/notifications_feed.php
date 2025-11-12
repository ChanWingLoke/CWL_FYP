<?php
// app/action/notifications_feed.php
// Lazy job (no cron) version with 1–3 days range.

header('Content-Type: application/json');
require_once __DIR__ . '/../init.php';
require_once __DIR__ . '/notifications_helpers.php';

if (!isset($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['ok'=>false,'error'=>'not-auth']); exit; }
$db = $pdo ?? ($obj->pdo ?? null);
if (!$db) { http_response_code(500); echo json_encode(['ok'=>false,'error'=>'no-db']); exit; }

$userId = (int)$_SESSION['user_id'];

function ensure_due_soon_notifications(PDO $db, int $userId): void {
  // throttle (1 hour; use 5 for testing)
  if (!isset($_SESSION['notif_tick_last']) || (time() - (int)$_SESSION['notif_tick_last']) > 5) {
    $_SESSION['notif_tick_last'] = time();
  } else return;

  $activeStatuses = ["pending","approved","in_progress","active"];
  $ph = implode(',', array_fill(0, count($activeStatuses), '?'));

  $sql = "SELECT
            b.id AS booking_id,
            DATE(b.end_time) AS end_date,
            TIME(b.end_time) AS end_time,
            DATEDIFF(DATE(b.end_time), CURDATE()) AS days_left
          FROM bookings b
          WHERE b.user_id = ?
            AND b.status IN ($ph)
            AND DATE(b.end_time) > CURDATE()
            AND DATE(b.end_time) <= (CURDATE() + INTERVAL 3 DAY)
            AND DATEDIFF(DATE(b.end_time), CURDATE()) BETWEEN 1 AND 3
            AND NOT EXISTS (
              SELECT 1 FROM notifications n
              WHERE n.user_id = b.user_id
                AND n.ref_id  = b.id
                AND n.type    IN ('booking_due_1d','booking_due_2d','booking_due_3d')
                AND n.created_at >= CURDATE()
            )";

  $params = array_merge([$userId], $activeStatuses);
  $st = $db->prepare($sql);
  $st->execute($params);

  while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
    $bid      = (int)$row['booking_id'];
    $date     = $row['end_date'];
    $time     = $row['end_time'];
    $daysLeft = (int)$row['days_left'];

    $title = "Booking due in {$daysLeft} day" . ($daysLeft === 1 ? "" : "s") . " (#{$bid})";
    $body  = "Your booking is due on {$date} {$time}. Please return or extend if needed.";
    $link  = "index.php?page=bookings_all";
    $type  = "booking_due_{$daysLeft}d";

    notif_insert($db, $userId, $title, $body, $link, $type, $bid);
  }
}

// Mark read / mark all
if (isset($_GET['mark']) && $_GET['mark'] === 'all') {
  $st = $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = :u AND is_read = 0");
  $st->execute([':u' => $userId]);
  echo json_encode(['ok'=>true]); exit;
}
if (isset($_GET['read'])) {
  $id = (int)$_GET['read'];
  $st = $db->prepare("UPDATE notifications SET is_read = 1 WHERE id = :id AND user_id = :u");
  $st->execute([':id'=>$id, ':u'=>$userId]);
  echo json_encode(['ok'=>true]); exit;
}

// Run lazy job
try { ensure_due_soon_notifications($db, $userId); } catch (Throwable $e) { /* ignore */ }

// Return feed
$limit = isset($_GET['limit']) ? max(1, min(20, (int)$_GET['limit'])) : 8;
$unread = (int)$db->query("SELECT COUNT(*) FROM notifications WHERE user_id = {$userId} AND is_read = 0")->fetchColumn();

$st = $db->prepare("SELECT id,title,body,link,is_read,created_at
                    FROM notifications
                    WHERE user_id = :u
                    ORDER BY is_read ASC, created_at DESC
                    LIMIT {$limit}");
$st->execute([':u' => $userId]);
$items = $st->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['ok'=>true, 'unread'=>$unread, 'items'=>$items]);
