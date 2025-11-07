<?php
// app/action/maintenance_update.php (expanded transitions)
require_once __DIR__ . '/../../app/init.php';
if (!isset($_SESSION['user_id'])) { header('Location: ../../login.php'); exit; }

$db = isset($pdo) && $pdo ? $pdo : ($obj->pdo ?? null);
if (!$db) { die('DB'); }

$id     = (int)($_POST['id'] ?? 0);
$action = strtolower($_POST['action'] ?? '');
$allowed = ['start','resolve','close','reopen'];

if (!$id || !in_array($action, $allowed, true)) {
  header('Location: ../../index.php?page=maintenance_list&type=danger&msg=Invalid+request'); exit;
}

try {
  $stmt = $db->prepare("SELECT status FROM maintenance_orders WHERE id=:id");
  $stmt->execute([':id'=>$id]);
  $status = $stmt->fetchColumn();
  if ($status === false) { throw new Exception('Request not found'); }

  $msg = 'No changes performed.';
  switch ($action) {
    case 'start':
      if (!in_array($status, ['in_progress','resolved','closed'], true)) {
        $db->prepare("UPDATE maintenance_orders SET status='in_progress', updated_at=NOW() WHERE id=:id")->execute([':id'=>$id]);
        $msg = 'Request started.';
      }
      break;

    case 'resolve':
      if ($status !== 'resolved' && $status !== 'closed') {
        $db->prepare("UPDATE maintenance_orders SET status='resolved', resolved_date=CURDATE(), updated_at=NOW() WHERE id=:id")->execute([':id'=>$id]);
        $msg = 'Request resolved.';
      }
      break;

    case 'close':
      // Final closure/archival – typically after resolved
      if ($status !== 'closed') {
        $db->prepare("UPDATE maintenance_orders SET status='closed', updated_at=NOW() WHERE id=:id")->execute([':id'=>$id]);
        $msg = 'Request closed.';
      }
      break;

    case 'reopen':
      if ($status !== 'open') {
        $db->prepare("UPDATE maintenance_orders SET status='open', updated_at=NOW() WHERE id=:id")->execute([':id'=>$id]);
        $msg = 'Request reopened.';
      }
      break;
  }

  header('Location: ../../index.php?page=maintenance_list&status=' . urlencode($_GET['status'] ?? 'all') . '&type=success&msg='.rawurlencode($msg));
} catch (Throwable $e) {
  header('Location: ../../index.php?page=maintenance_list&type=danger&msg='.rawurlencode('Update failed: '.$e->getMessage()));
}
