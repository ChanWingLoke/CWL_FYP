<?php
// app/action/maintenance_save.php

require_once __DIR__ . '/../../app/init.php';

if (!isset($_SESSION['user_id'])) { header('Location: ../../login.php'); exit; }

$db = null;
if (isset($pdo) && $pdo) { $db = $pdo; }
elseif (isset($obj) && isset($obj->pdo)) { $db = $obj->pdo; }
if (!$db) { die('DB not available'); }

try {
  // Collect + validate
  $asset_id       = (int)($_POST['asset_id'] ?? 0);
  $title          = trim($_POST['title'] ?? '');
  $description    = trim($_POST['description'] ?? '');
  $priority       = $_POST['priority'] ?? 'medium';
  $requested_date = trim($_POST['requested_date'] ?? '');
  $due_date       = trim($_POST['due_date'] ?? '');
  $assigned_to    = isset($_POST['assigned_to']) && $_POST['assigned_to'] !== '' ? (int)$_POST['assigned_to'] : null;

  if (!$asset_id || $title === '' || $requested_date === '') {
    throw new Exception('Missing required fields.');
  }

  // Enforce YYYY-MM-DD
  $re = '/^\d{4}-\d{2}-\d{2}$/';
  if (!preg_match($re, $requested_date)) throw new Exception('Requested date must be YYYY-MM-DD.');
  if ($due_date !== '' && !preg_match($re, $due_date)) throw new Exception('Due date must be YYYY-MM-DD.');

  // Insert — notice requested_by and default status 'open'
  $sql = "INSERT INTO maintenance_orders
            (asset_id, title, description, priority, status, requested_by, assigned_to, requested_date, due_date)
          VALUES
            (:asset_id, :title, :description, :priority, 'open', :requested_by, :assigned_to, :requested_date, :due_date)";
  $stmt = $db->prepare($sql);
  $stmt->execute([
    ':asset_id'       => $asset_id,
    ':title'          => $title,
    ':description'    => $description ?: null,
    ':priority'       => $priority,
    ':requested_by'   => $_SESSION['user_id'], // current user
    ':assigned_to'    => $assigned_to,
    ':requested_date' => $requested_date,
    ':due_date'       => $due_date ?: null,
  ]);

  header('Location: ../../index.php?page=maintenance_list&filter=open&msg=Created&type=success');
  exit;
} catch (Throwable $e) {
  header('Location: ../../index.php?page=maintenance_list&filter=all&msg='.urlencode('Save failed: '.$e->getMessage()).'&type=danger');
  exit;
}
