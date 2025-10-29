<?php
// app/action/maintenance_delete.php
require_once __DIR__ . '/../../app/init.php';

session_start();
if (!isset($_SESSION['user_id'])) { header('Location: ../../login.php'); exit; }
if (!isset($_SESSION['user_role']) || strtolower($_SESSION['user_role']) !== 'admin') {
  header('Location: ../../index.php?page=maintenance_list&msg=Forbidden&type=danger'); exit;
}

$db = null;
if (isset($pdo) && $pdo) { $db = $pdo; }
elseif (isset($obj) && isset($obj->pdo)) { $db = $obj->pdo; }
if (!$db) { header('Location: ../../index.php?page=maintenance_list&msg=DB%20error&type=danger'); exit; }

try {
  $id = (int)($_POST['id'] ?? 0);
  if ($id <= 0) { throw new Exception('Invalid id.'); }

  $stmt = $db->prepare("DELETE FROM maintenance_orders WHERE id=:id");
  $stmt->execute([':id' => $id]);

  header('Location: ../../index.php?page=maintenance_list&msg=Deleted&type=success'); exit;
} catch (Throwable $e) {
  $m = urlencode($e->getMessage());
  header("Location: ../../index.php?page=maintenance_list&msg={$m}&type=danger"); exit;
}
