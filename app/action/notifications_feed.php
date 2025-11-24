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

/**
 * Admin-only: warranties expiring 30/7/1 days before end_date + expired.
 * Idempotent via (user_id, type, ref_id) uniqueness or helper pre-check.
 */
function ensure_warranty_due_notifications(PDO $db, int $userId): void {
  // Run only for admins
  $isAdmin = isset($_SESSION['user_role']) && strtolower($_SESSION['user_role']) === 'admin';
  if (!$isAdmin) return;

  // throttle once/hour for this session/user
  if (!isset($_SESSION['notif_tick_warranty']) || (time() - (int)$_SESSION['notif_tick_warranty']) > 5) {
    $_SESSION['notif_tick_warranty'] = time();
  } else return;

  // 30, 7, 1 days before end_date
  foreach ([30,7,1] as $d) {
    $sql = "SELECT w.id, DATE(w.end_date) AS end_date
            FROM warranties w
            WHERE DATE(w.end_date) = (CURDATE() + INTERVAL {$d} DAY)
              AND NOT EXISTS (
                SELECT 1 FROM notifications n
                 WHERE n.user_id = :u AND n.type = :t AND n.ref_id = w.id
              )
            LIMIT 100";
    $st  = $db->prepare($sql);
    $type = "warranty_due_{$d}d";
    $st->execute([':u'=>$userId, ':t'=>$type]);
    while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
      $wid = (int)$r['id'];
      $title = "Warranty due in {$d} day".($d===1?'':'s')." (ID #{$wid})";
      $body  = "Warranty ends on {$r['end_date']}. Consider renewal or asset action.";
      $link  = "index.php?page=warranty_list";
      notif_insert($db, $userId, $title, $body, $link, $type, $wid);
    }
  }

  // Expired today or earlier (one notification per day max)
  $sqlX = "SELECT w.id, DATE(w.end_date) AS end_date
           FROM warranties w
           WHERE DATE(w.end_date) < CURDATE()
             AND NOT EXISTS (
               SELECT 1 FROM notifications n
                WHERE n.user_id = :u AND n.type = 'warranty_expired' AND n.ref_id = w.id
                  AND n.created_at >= CURDATE()
             )
           LIMIT 100";
  $stx = $db->prepare($sqlX);
  $stx->execute([':u'=>$userId]);
  while ($r = $stx->fetch(PDO::FETCH_ASSOC)) {
    $wid = (int)$r['id'];
    $title = "Warranty expired (ID #{$wid})";
    $body  = "Expired on {$r['end_date']}.";
    $link  = "index.php?page=warranty_list";
    notif_insert($db, $userId, $title, $body, $link, "warranty_expired", $wid);
  }
}

/**
 * Everyone: notify if any asset is currently under maintenance.
 * Users get the message the first time they visit (lazy-per-user).
 * No duplicates per maintenance ticket via (user_id, type, ref_id).
 *
 * Assumes: maintenance_orders(id, product_id?, status), products(id,name?)
 * Statuses treated as “under maintenance”: open, in_progress, waiting_parts
 */
function ensure_maintenance_broadcast_notifications(PDO $db, int $userId): void {
  // throttle once/hour per session (set to 5s while testing)
  if (!isset($_SESSION['notif_tick_maint']) || (time() - (int)$_SESSION['notif_tick_maint']) > 5) {
    $_SESSION['notif_tick_maint'] = time();
  } else return;

  // “Under maintenance” statuses in your DB
  $maintStatuses = ['open','in_progress','waiting_parts'];
  $ph = implode(',', array_fill(0, count($maintStatuses), '?'));

  // For THIS user, enqueue one notice per open maintenance ticket not yet announced to them
  $sql = "SELECT m.id AS maint_id, m.status, m.title, m.asset_id
          FROM maintenance_orders m
          WHERE m.status IN ($ph)
            AND NOT EXISTS (
              SELECT 1 FROM notifications n
               WHERE n.user_id = ?
                 AND n.type = 'maint_asset_open'
                 AND n.ref_id = m.id
            )
          ORDER BY m.id DESC
          LIMIT 10";

  $params = $maintStatuses;
  $params[] = $userId;

  $st = $db->prepare($sql);
  $st->execute($params);

  while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
    $mid   = (int)$r['maint_id'];
    $mstat = (string)$r['status'];
    $titleTxt = trim((string)$r['title']);
    $assetId  = (int)$r['asset_id'];

    $title = "Asset under maintenance (#{$mid})";
    $body  = ($titleTxt !== '' ? "{$titleTxt} — " : "")
          . "Asset #{$assetId} is currently '{$mstat}'.";
    $link  = "index.php?page=maintenance_list";

    // Idempotent via unique (user_id, type, ref_id)
    notif_insert($db, $userId, $title, $body, $link, 'maint_asset_open', $mid);
  }
}

/**
 * Everyone: notify once when a maintenance ticket is CLOSED.
 * Idempotent per user via (user_id, type, ref_id) uniqueness.
 * Uses maintenance_orders(id, title, asset_id, status, updated_at).
 */
function ensure_maintenance_closed_notifications(PDO $db, int $userId): void {
  // throttle once/hour per session (set to 5s while testing)
  if (!isset($_SESSION['notif_tick_maint_closed']) || (time() - (int)$_SESSION['notif_tick_maint_closed']) > 5) {
    $_SESSION['notif_tick_maint_closed'] = time();
  } else return;

  $sql = "SELECT m.id AS maint_id,
                 m.title,
                 m.asset_id,
                 DATE(m.updated_at) AS closed_on
          FROM maintenance_orders m
          WHERE m.status = 'closed'
            AND NOT EXISTS (
              SELECT 1 FROM notifications n
               WHERE n.user_id = ?
                 AND n.type   = 'maint_asset_closed'
                 AND n.ref_id = m.id
            )
          ORDER BY m.updated_at DESC
          LIMIT 10";

  $st = $db->prepare($sql);
  $st->execute([$userId]);

  while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
    $mid   = (int)$r['maint_id'];
    $titleTxt = trim((string)$r['title']);
    $assetId  = (int)$r['asset_id'];
    $closedOn = $r['closed_on'] ?? '';

    $title = "Maintenance closed (#{$mid})";
    $body  = ($titleTxt !== '' ? "{$titleTxt} — " : "")
           . "Asset #{$assetId} was closed"
           . ($closedOn ? " on {$closedOn}" : "")
           . ".";
    $link  = "index.php?page=maintenance_list";

    // one per user per ticket
    notif_insert($db, $userId, $title, $body, $link, 'maint_asset_closed', $mid);
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
try {
  ensure_due_soon_notifications($db, $userId);     // bookings 1–3 days (already in your file)
  ensure_warranty_due_notifications($db, $userId); // NEW: admins only, 30/7/1 + expired
  ensure_maintenance_broadcast_notifications($db, $userId); // NEW: everyone sees maint items
  ensure_maintenance_closed_notifications($db, $userId);
} catch (Throwable $e) { /* ignore */ }


// --- Warranty reminders (admins only): 30, 7, 1 days and "today" (0) ---
try {
    // Get all admin user IDs (your user table uses 'user_role')
    $admins = $db->query("SELECT id FROM user WHERE LOWER(user_role) = 'admin'")
                 ->fetchAll(PDO::FETCH_COLUMN);

    if ($admins) {
        // Warranties ending between today and 30 days ahead
        // (includes today = 0 days left)
        $ws = $db->query("
            SELECT
                id,
                asset_id,
                end_date,
                DATEDIFF(end_date, CURDATE()) AS days_left
            FROM warranties
            WHERE warranty_status = 'active'
              AND end_date BETWEEN CURDATE() AND (CURDATE() + INTERVAL 30 DAY)
        ")->fetchAll(PDO::FETCH_ASSOC);

        // Prepare de-dupe + insert
        $chk = $db->prepare("
            SELECT 1
            FROM notifications
            WHERE user_id = ?
              AND DATE(created_at) = CURDATE()
              AND title = ?
            LIMIT 1
        ");

        $ins = $db->prepare("
            INSERT INTO notifications (user_id, title, body, link, is_read, created_at)
            VALUES (?, ?, ?, ?, 0, NOW())
        ");

        foreach ($ws as $w) {
            $wid      = (int)$w['id'];
            $daysLeft = (int)$w['days_left'];

            // Only send on 30, 7, 1 days left, and on the day it expires (0)
            if (!in_array($daysLeft, [30, 7, 1, 0], true)) {
                continue;
            }

            $label = ($daysLeft > 1)
                        ? "{$daysLeft} days left"
                        : ($daysLeft === 1 ? "1 day left" : "expires today");

            $title = "Warranty {$label} (ID #{$wid})";
            $body  = "Warranty #{$wid} ends on {$w['end_date']}.";
            $link  = "index.php?page=warranty_list";

            foreach ($admins as $adminId) {
                // One notification per admin per day
                $chk->execute([$adminId, $title]);
                if ($chk->fetchColumn()) {
                    continue;
                }
                $ins->execute([$adminId, $title, $body, $link]);
            }
        }
    }
} catch (Throwable $e) {
    // optional: error log
    // error_log('Warranty notify error: '.$e->getMessage());
}


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
