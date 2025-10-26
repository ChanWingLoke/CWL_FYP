<?php
require_once '../init.php';

// Admin only
if (!isset($_SESSION['user_role']) || strtolower($_SESSION['user_role']) !== 'admin') {
  header('Location: ../../index.php?page=warranty_list&type=danger&msg=Permission denied'); exit;
}

// Get PDO
$db = null;
if (isset($pdo) && $pdo) { $db = $pdo; }
elseif (isset($obj) && isset($obj->pdo)) { $db = $obj->pdo; }
if (!$db) {
  header('Location: ../../index.php?page=warranty_list&type=danger&msg=No DB connection'); exit;
}

$id          = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$asset_id    = isset($_POST['asset_id']) ? (int)$_POST['asset_id'] : 0;
$vendor_name = trim($_POST['vendor_name'] ?? '');
$start_date  = trim($_POST['start_date'] ?? '');
$end_date    = trim($_POST['end_date'] ?? '');

$re = '/^\d{4}-\d{2}-\d{2}$/';
if (!$asset_id || !preg_match($re, $start_date) || !preg_match($re, $end_date)) {
  header('Location: ../../index.php?page=warranty_list&type=danger&msg=Invalid input'); exit;
}
if ($start_date > $end_date) {
  header('Location: ../../index.php?page=warranty_list&type=danger&msg=Start must be before end'); exit;
}

$status = ($end_date < date('Y-m-d')) ? 'expired' : 'active';

try {
  if ($id > 0) {
    $stmt = $db->prepare("UPDATE warranties
                          SET asset_id=:asset_id, vendor_name=:vendor_name, start_date=:start_date, end_date=:end_date, warranty_status=:status
                          WHERE id=:id");
    $stmt->execute([
      ':asset_id' => $asset_id,
      ':vendor_name' => $vendor_name,
      ':start_date' => $start_date,
      ':end_date' => $end_date,
      ':status' => $status,
      ':id' => $id
    ]);
    $msg = 'Warranty updated';
  } else {
    $stmt = $db->prepare("INSERT INTO warranties (asset_id, vendor_name, start_date, end_date, warranty_status)
                          VALUES (:asset_id, :vendor_name, :start_date, :end_date, :status)");
    $stmt->execute([
      ':asset_id' => $asset_id,
      ':vendor_name' => $vendor_name,
      ':start_date' => $start_date,
      ':end_date' => $end_date,
      ':status' => $status
    ]);
    $msg = 'Warranty added';
  }
  header('Location: ../../index.php?page=warranty_list&type=success&msg=' . urlencode($msg));
} catch (Throwable $e) {
  header('Location: ../../index.php?page=warranty_list&type=danger&msg=' . urlencode('DB error: ' . $e->getMessage()));
}
