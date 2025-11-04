<?php
// app/action/maintenance_request_save.php (adapted)

require_once '../init.php';
if ($Ouser->is_login() == false) {
  header("location:../../login.php");
  exit;
}

function redirect_back($ok=true, $msg=''){
  $qs = $ok ? 'ok=1' : ('err=' . urlencode($msg));
  header('Location: ../../index.php?page=maintenance_requests&' . $qs);
  exit;
}

try {
  if (!isset($_POST['asset_id'], $_POST['title'], $_POST['priority'])) {
    redirect_back(false, 'Missing required fields.');
  }
  $asset_id = (int)$_POST['asset_id'];
  $title = trim($_POST['title']);
  $priority = strtolower(trim($_POST['priority']));
  $due_date = isset($_POST['due_date']) ? trim($_POST['due_date']) : null;
  $desc = isset($_POST['description']) ? trim($_POST['description']) : '';

  if ($asset_id <= 0 || $title === '' || $priority === '') {
    redirect_back(false, 'Invalid values.');
  }

  $uid = (int)($_SESSION['user_id'] ?? 0);
  if ($uid <= 0) { redirect_back(false, 'Not authenticated.'); }

  // Prefer PDO from $pdo; fallback to $obj->pdo
  $db = $pdo ?? ($obj->pdo ?? null);
  if (!$db) { redirect_back(false, 'DB connection missing.'); }

  $stmt = $db->prepare("INSERT INTO maintenance_orders
    (asset_id, title, description, priority, status, requested_date, due_date, requested_by)
    VALUES (:asset_id, :title, :description, :priority, 'open', NOW(), :due_date, :requested_by)");
  $stmt->execute([
    ':asset_id'     => $asset_id,
    ':title'        => $title,
    ':description'  => $desc,
    ':priority'     => $priority,
    ':due_date'     => ($due_date !== '' ? $due_date : null),
    ':requested_by' => $uid,
  ]);

  redirect_back(true);
} catch (Throwable $e) {
  redirect_back(false, $e->getMessage());
}
