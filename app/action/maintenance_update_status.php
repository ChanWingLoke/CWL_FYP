<?php
// app/action/maintenance_update_status.php
session_start();
require_once __DIR__ . '/../../app/init.php';

// ---- get a PDO handle ----
$db = null;
if (isset($pdo) && $pdo) { $db = $pdo; }
elseif (isset($obj) && isset($obj->pdo)) { $db = $obj->pdo; }

if (!$db) {
  header('Location: ../../index.php?page=maintenance_list&type=danger&msg=' . urlencode('DB not available'));
  exit;
}

// ---- inputs ----
$id          = (int)($_POST['id'] ?? 0);
$nextStatus  = isset($_POST['status']) ? strtolower(trim($_POST['status'])) : null; // open|in_progress|waiting_parts|resolved|closed
$assigned_to = isset($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : 0;
$back        = trim($_POST['back'] ?? 'maintenance_list');

if ($id <= 0) {
  header('Location: ../../index.php?page=' . $back . '&type=danger&msg=' . urlencode('Invalid request ID'));
  exit;
}

$allowed = ['open','in_progress','waiting_parts','resolved','resolved'];
$set = [];
$params = [':id' => $id];

// optional assignment (works alone or together with a status change)
if ($assigned_to > 0) {
  $set[] = 'assigned_to = :assigned_to';
  $params[':assigned_to'] = $assigned_to;
}

// status transition
if ($nextStatus && in_array($nextStatus, $allowed, true)) {
  $set[] = 'status = :status';
  $params[':status'] = $nextStatus;

  // timestamps for milestones
  switch ($nextStatus) {
    case 'open':
      $set[] = 'started_at = NULL';
      $set[] = 'resolved_at = NULL';
      $set[] = 'closed_at = NULL';
      break;

    case 'in_progress':
      // mark when work first started
      $set[] = 'started_at = IFNULL(started_at, NOW())';
      break;

    case 'waiting_parts':
      // nothing special besides status
      break;

    case 'resolved':
      $set[] = 'resolved_at = NOW()';
      break;

    case 'resolved':
      $set[] = 'closed_at = NOW()';
      break;
  }
}

if (!$set) {
  header('Location: ../../index.php?page=' . $back . '&type=warning&msg=' . urlencode('No changes submitted'));
  exit;
}

$sql = 'UPDATE maintenance_orders SET ' . implode(', ', $set) . ' WHERE id = :id';
try {
  $stmt = $db->prepare($sql);
  $stmt->execute($params);
  header('Location: ../../index.php?page=' . $back . '&type=success&msg=' . urlencode('Maintenance order updated'));
} catch (Throwable $e) {
  header('Location: ../../index.php?page=' . $back . '&type=danger&msg=' . urlencode('Update failed: ' . $e->getMessage()));
}
