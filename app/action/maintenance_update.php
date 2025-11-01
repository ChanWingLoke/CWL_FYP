<?php
// app/action/maintenance_update.php
require_once __DIR__ . '/../../app/init.php';

if (!isset($_SESSION['user_id'])) { header('Location: ../../login.php'); exit; }

$db = isset($pdo) && $pdo ? $pdo : ($obj->pdo ?? null);
if (!$db) { die('DB'); }

$id     = (int)($_POST['id'] ?? 0);
$action = strtolower($_POST['action'] ?? '');

if (!$id || !in_array($action, ['start','close'], true)) {
  header('Location: ../../index.php?page=maintenance_list&tab=all&type=danger&msg=Invalid+request'); exit;
}

// fetch current status
$cur = $db->prepare("SELECT status FROM maintenance_orders WHERE id = :id");
$cur->execute([':id'=>$id]);
$status = $cur->fetchColumn();

try {
  if ($action === 'start' && $status === 'open') {
    $stmt = $db->prepare("UPDATE maintenance_orders SET status='in_progress' WHERE id=:id");
    $stmt->execute([':id'=>$id]);
    $msg = 'Request started.';
  } elseif ($action === 'close' && $status !== 'closed') {
    $stmt = $db->prepare("UPDATE maintenance_orders SET status='closed' WHERE id=:id");
    $stmt->execute([':id'=>$id]);
    $msg = 'Request closed.';
  } else {
    $msg = 'No changes performed.';
  }
} catch (Throwable $e) {
  $msg = 'Update failed: '.rawurlencode($e->getMessage());
  header("Location: ../../index.php?page=maintenance_list&tab=all&type=danger&msg={$msg}"); exit;
}

header("Location: ../../index.php?page=maintenance_list&tab=all&type=success&msg=".rawurlencode($msg));
