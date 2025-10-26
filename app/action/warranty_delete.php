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

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) {
  header('Location: ../../index.php?page=warranty_list&type=danger&msg=Invalid warranty'); exit;
}

try {
  $stmt = $db->prepare("DELETE FROM warranties WHERE id = :id");
  $stmt->execute([':id' => $id]);
  header('Location: ../../index.php?page=warranty_list&type=success&msg=' . urlencode('Warranty deleted'));
} catch (Throwable $e) {
  header('Location: ../../index.php?page=warranty_list&type=danger&msg=' . urlencode('DB error: ' . $e->getMessage()));
}
