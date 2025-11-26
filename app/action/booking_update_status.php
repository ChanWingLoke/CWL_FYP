<?php session_start(); 
if (!isset($_SESSION['user_role']) || strtolower($_SESSION['user_role']) !== 'admin') {
  http_response_code(403);
  die('Permission denied');
}
?>
<?php
// app/action/booking_update_status.php
require_once __DIR__ . '/../../app/init.php';

// Basic auth guard: user must be logged in; optionally enforce admin
if (!isset($_SESSION['user_id'])) {
  header('Location: ../../login.php'); exit;
}

// Get PDO
$db = null;
if (isset($pdo) && $pdo) {
  $db = $pdo;
} elseif (isset($obj) && isset($obj->pdo)) {
  $db = $obj->pdo;
}
if (!$db) { die('No DB handle'); }

$id     = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$action = $_POST['action'] ?? '';

$allowed = ['approve' => 'approved', 'reject' => 'rejected', 'return' => 'returned'];
if (!$id || !isset($allowed[$action])) {
  header('Location: ../../index.php?page=bookings_requests&msg=Invalid+request&type=danger'); exit;
}

$newStatus = $allowed[$action];

// If approving, ensure the asset isn't under maintenance (open, in_progress, resolved)
if ($newStatus === 'approved') {
  // Look up the booking's asset_id
  $bst = $db->prepare("SELECT asset_id FROM bookings WHERE id = :id LIMIT 1");
  $bst->execute([':id' => $id]);
  $assetId = (int)$bst->fetchColumn();

  if ($assetId) {
    $blockStatuses = ['open','in_progress','resolved'];
    $ph = implode(',', array_fill(0, count($blockStatuses), '?'));

    $msql = "SELECT 1 FROM maintenance_orders
             WHERE asset_id = ?
               AND status IN ($ph)
             LIMIT 1";
    $mstmt = $db->prepare($msql);
    $mstmt->execute(array_merge([$assetId], $blockStatuses));

    if ($mstmt->fetchColumn()) {
      $back = $_POST['back'] ?? 'bookings_requests';
      header('Location: ../../index.php?page=' . urlencode($back) .
             '&msg=' . urlencode('Cannot approve: asset is under maintenance (open/in_progress/resolved).') .
             '&type=danger');
      exit;
    }
  }
}

try {
  $stmt = $db->prepare("UPDATE bookings SET status = :status, updated_at = NOW() WHERE id = :id");
  $stmt->execute([':status' => $newStatus, ':id' => $id]);
  $msg = ucfirst($newStatus) . ' successfully.';
  $back = $_POST['back'] ?? 'bookings_requests';
  header('Location: ../../index.php?page=' . urlencode($back) . '&msg=' . urlencode($msg) . '&type=success');
} catch (Throwable $e) {
  $back = $_POST['back'] ?? 'bookings_requests';
  header('Location: ../../index.php?page=' . urlencode($back) . '&msg=' . urlencode('DB error: '.$e->getMessage()) . '&type=danger');
}
