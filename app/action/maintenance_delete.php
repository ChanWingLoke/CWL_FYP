<?php
/**
 * Fresh Maintenance Module (drop-in)
 * Stack: AdminLTE + jQuery + PDO (via app/init.php loaded by inc/header.php)
 * Routes: index.php?page=maintenance_list, maintenance_request
 * Actions: app/action/maintenance_save.php, maintenance_update.php, maintenance_view.php, maintenance_delete.php
 */
?>

<?php
require_once __DIR__ . '/../../app/init.php';
if (!isset($_SESSION['user_id'])) { header('Location: ../../login.php'); exit; }

$db = isset($pdo) && $pdo ? $pdo : ($obj->pdo ?? null);
if (!$db) { die('DB'); }

$id = (int)($_POST['id'] ?? 0);
if (!$id) { header('Location: ../../index.php?page=maintenance_list&type=danger&msg=Invalid+request'); exit; }

try {
  $stmt = $db->prepare("DELETE FROM maintenance_orders WHERE id=:id");
  $stmt->execute([':id'=>$id]);
  header('Location: ../../index.php?page=maintenance_list&type=success&msg=Request+deleted');
} catch (Throwable $e) {
  header('Location: ../../index.php?page=maintenance_list&type=danger&msg='.rawurlencode('Delete failed: '.$e->getMessage()));
}
