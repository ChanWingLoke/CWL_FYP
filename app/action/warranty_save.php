<?php

require_once '../init.php';

// Robust ID and mode handling
$mode = isset($_POST['mode']) ? strtolower(trim($_POST['mode'])) : '';
$id = 0;
if (isset($_POST['id'])) { $id = (int)$_POST['id']; }
elseif (isset($_POST['warranty_id'])) { $id = (int)$_POST['warranty_id']; }
elseif (isset($_POST['e_id'])) { $id = (int)$_POST['e_id']; }


// Admin only
if (!isset($_SESSION['user_role']) || strtolower($_SESSION['user_role']) !== 'admin') {
  header('Location: ../../index.php?page=warranty_list&type=danger&msg=' . urlencode('Permission denied')); exit;
}

if ($mode === 'edit' && $id <= 0) {
  header('Location: ../../index.php?page=warranty_list&type=danger&msg=' . urlencode('Edit failed: missing warranty ID.'));
  exit;
}

// Get PDO
$db = null;
if (isset($pdo) && $pdo) { $db = $pdo; }
elseif (isset($obj) && isset($obj->pdo)) { $db = $obj->pdo; }
if (!$db) {
  header('Location: ../../index.php?page=warranty_list&type=danger&msg=' . urlencode('No DB connection')); exit;
}

try {
  // Inputs
  // (id captured above)
  $asset_id    = $_POST['asset_id'] ?? '';
  $vendor_name = trim($_POST['vendor_name'] ?? '');
  $start_date  = trim($_POST['start_date'] ?? '');
  $end_date    = trim($_POST['end_date'] ?? '');

  // Validate IDs / dates
  if (!ctype_digit((string)$asset_id)) {
    header('Location: ../../index.php?page=warranty_list&type=danger&msg=' . urlencode('Please select an existing asset from the list.')); exit;
  }
  $asset_id = (int)$asset_id;

  $re = '/^\d{4}-\d{2}-\d{2}$/';
  if (!preg_match($re, $start_date) || !preg_match($re, $end_date)) {
    header('Location: ../../index.php?page=warranty_list&type=danger&msg=' . urlencode('Invalid date format. Use YYYY-MM-DD.')); exit;
  }
  if ($start_date > $end_date) {
    header('Location: ../../index.php?page=warranty_list&type=danger&msg=' . urlencode('Start date must be before end date.')); exit;
  }

  // Verify asset exists in `products`
  $chk = $db->prepare("SELECT COUNT(*) FROM `products` WHERE `id` = :id");
  $chk->execute([':id' => $asset_id]);
  if ((int)$chk->fetchColumn() === 0) {
    header('Location: ../../index.php?page=warranty_list&type=danger&msg=' . urlencode('Could not verify asset.')); exit;
  }

  // Determine status
  $status = ($end_date < date('Y-m-d')) ? 'expired' : 'active';

  if ($id > 0) {
    // Update row
    $stmt = $db->prepare("UPDATE `warranties`
                          SET `asset_id` = :asset_id,
                              `vendor_name` = :vendor_name,
                              `start_date` = :start_date,
                              `end_date` = :end_date,
                              `warranty_status` = :status
                          WHERE `id` = :id");
    $stmt->execute([
      ':asset_id' => $asset_id,
      ':vendor_name' => $vendor_name,
      ':start_date' => $start_date,
      ':end_date' => $end_date,
      ':status' => $status,
      ':id' => $id,
    ]);
    $msg = 'Warranty updated';
  } else {
    // Insert row
    $stmt = $db->prepare("INSERT INTO `warranties`
                          (`asset_id`, `vendor_name`, `start_date`, `end_date`, `warranty_status`)
                          VALUES (:asset_id, :vendor_name, :start_date, :end_date, :status)");
    $stmt->execute([
      ':asset_id' => $asset_id,
      ':vendor_name' => $vendor_name,
      ':start_date' => $start_date,
      ':end_date' => $end_date,
      ':status' => $status,
    ]);
    $msg = 'Warranty added';
  }

  header('Location: ../../index.php?page=warranty_list&type=success&msg=' . urlencode($msg));
  exit;

} catch (Throwable $e) {
  header('Location: ../../index.php?page=warranty_list&type=danger&msg=' . urlencode('DB error: ' . $e->getMessage()));
  exit;
}
