<?php
// app/action/booking_update_status.php
require_once __DIR__ . '/../../app/init.php';

// Basic auth guard: user must be logged in; optionally enforce admin
if (!isset($_SESSION['user_id'])) {
  header('Location: ../../login.php'); exit;
}
// If your app supports role checks, uncomment:
// if (empty($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') { header('Location: ../../index.php'); exit; }

// Get PDO
$db = null;
if (isset($pdo) && $pdo) {
  $db = $pdo;
} elseif (isset($obj) && isset($obj->pdo)) {
  $db = $obj->pdo;
}
if (!$db) { die('No DB handle'); }

$id     = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$action = $_POST['action'] ?? '';

$allowed = ['approve' => 'approved', 'reject' => 'rejected', 'return' => 'returned'];
if (!$id || !isset($allowed[$action])) {
  header('Location: ../../index.php?page=bookings_requests&msg=Invalid+request&type=danger'); exit;
}

$newStatus = $allowed[$action];

try {
  $stmt = $db->prepare("UPDATE bookings SET status = :status, updated_at = NOW() WHERE id = :id");
  $stmt->execute([':status' => $newStatus, ':id' => $id]);
  $msg = ucfirst($newStatus) . ' successfully.';
  $back = $_POST['back'] ?? 'bookings_requests';
  header('Location: ../../index.php?page=' . urlencode($back) . '&msg=' . urlencode($msg) . '&type=success');
} catch (Throwable $e) {
  $back = $_POST['back'] ?? 'bookings_requests';
  header('Location: ../../index.php?page=' . urlencode($back) . '&msg=' . urlencode('DB error: '.$e->getMessage()) . '&type=danger');
}
