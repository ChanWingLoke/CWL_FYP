<?php
require_once __DIR__ . '/../../app/init.php';
if (!isset($_SESSION['user_id'])) { header('Location: ../../login.php'); exit; }

// PDO
$db = isset($pdo) && $pdo ? $pdo : ($obj->pdo ?? null);
if (!$db) { die('DB'); }

$asset_id       = (int)($_POST['asset_id'] ?? 0);
$title          = trim($_POST['title'] ?? '');
$description    = trim($_POST['description'] ?? '');
$priority       = strtolower($_POST['priority'] ?? 'low');
$requested_date = $_POST['requested_date'] ?? date('Y-m-d');
$due_date       = $_POST['due_date'] ?? null;
$requested_by   = (int)$_SESSION['user_id'];

if (!$asset_id || !$title || !$description) {
  header('Location: ../../index.php?page=maintenance_request&type=danger&msg=Missing+required+fields'); exit;
}

try {
  $stmt = $db->prepare("
    INSERT INTO maintenance_orders
      (asset_id, title, description, priority, status, requested_by, requested_date, due_date, created_at)
    VALUES
      (:asset_id, :title, :description, :priority, 'open', :requested_by, :requested_date, :due_date, NOW())
  ");
  $stmt->execute([
    ':asset_id'       => $asset_id,
    ':title'          => $title,
    ':description'    => $description,
    ':priority'       => in_array($priority, ['low','medium','high','critical']) ? $priority : 'low',
    ':requested_by'   => $requested_by,
    ':requested_date' => $requested_date,
    ':due_date'       => $due_date ?: null,
  ]);

  header('Location: ../../index.php?page=maintenance_list&type=success&msg=Request+created');
} catch (Throwable $e) {
  $msg = rawurlencode('Save failed: '.$e->getMessage());
  header("Location: ../../index.php?page=maintenance_request&type=danger&msg={$msg}");
}
