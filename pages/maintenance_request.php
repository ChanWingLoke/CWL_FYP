<?php
// pages/maintenance_request.php

// Require login
if (!isset($_SESSION['user_id'])) {
  header('Location: login.php');
  exit;
}

// Connect to DB
$db = $pdo ?? ($obj->pdo ?? null);
if (!$db) {
  die('<div class="alert alert-danger m-3">Database connection missing.</div>');
}

// Detect assets table
$productTable = 'product';
try { $db->query("SELECT 1 FROM `product` LIMIT 1"); }
catch (Throwable $e) {
  foreach (['products','tbl_product','items','assets'] as $t) {
    try { $db->query("SELECT 1 FROM `{$t}` LIMIT 1"); $productTable = $t; break; }
    catch (Throwable $ignored) {}
  }
}

// Load data for selects
$assets = $db->query("SELECT id, product_name FROM `$productTable` ORDER BY product_name ASC")->fetchAll(PDO::FETCH_ASSOC);
$users  = $db->query("SELECT id, username FROM user ORDER BY username ASC")->fetchAll(PDO::FETCH_ASSOC);

// Handle submission
$flash = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    $asset_id  = (int)$_POST['asset_id'];
    $title     = trim($_POST['title']);
    $desc      = trim($_POST['description']);
    $priority  = $_POST['priority'] ?? 'medium';
    $due_date  = $_POST['due_date'] ?? null;
    $requested_by = $_SESSION['user_id'];
    $assigned_to  = (int)($_POST['assigned_to'] ?? 0);

    if (!$asset_id || !$title) throw new Exception("Please fill all required fields.");

    $stmt = $db->prepare("
      INSERT INTO maintenance_orders (asset_id, title, description, priority, status, requested_by, assigned_to, requested_date, due_date)
      VALUES (:asset, :title, :desc, :prio, 'open', :req_by, :assign, NOW(), :due)
    ");
    $stmt->execute([
      ':asset' => $asset_id,
      ':title' => $title,
      ':desc'  => $desc,
      ':prio'  => $priority,
      ':req_by'=> $requested_by,
      ':assign'=> $assigned_to ?: null,
      ':due'   => $due_date ?: null
    ]);

    $flash = ['type' => 'success', 'msg' => 'Maintenance request submitted successfully.'];
  } catch (Throwable $e) {
    $flash = ['type' => 'danger', 'msg' => 'Error: ' . $e->getMessage()];
  }
}
?>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <h1 class="m-0 text-dark">New Maintenance Request</h1>
      <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
        <li class="breadcrumb-item"><a href="index.php?page=maintenance_list">Maintenance</a></li>
        <li class="breadcrumb-item active">New Request</li>
      </ol>
    </div>
  </div>

  <section class="content">
    <div class="container-fluid">
      <?php if ($flash): ?>
        <div class="alert alert-<?= htmlspecialchars($flash['type']) ?>"><?= htmlspecialchars($flash['msg']) ?></div>
      <?php endif; ?>

      <div class="card">
        <div class="card-header"><b>Create Request</b></div>
        <div class="card-body">
          <form method="post" autocomplete="off">
            <div class="form-group">
              <label>Asset</label>
              <select name="asset_id" class="form-control" required>
                <option value="">-- Select Asset --</option>
                <?php foreach ($assets as $a): ?>
                  <option value="<?= (int)$a['id'] ?>"><?= htmlspecialchars($a['product_name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-group">
              <label>Title</label>
              <input type="text" name="title" class="form-control" required>
            </div>

            <div class="form-group">
              <label>Description</label>
              <textarea name="description" class="form-control" rows="3"></textarea>
            </div>

            <div class="form-group">
              <label>Priority</label>
              <select name="priority" class="form-control">
                <option value="low">Low</option>
                <option value="medium" selected>Medium</option>
                <option value="high">High</option>
                <option value="urgent">Urgent</option>
              </select>
            </div>

            <div class="form-group">
              <label>Assign To</label>
              <select name="assigned_to" class="form-control">
                <option value="">-- Optional --</option>
                <?php foreach ($users as $u): ?>
                  <option value="<?= (int)$u['id'] ?>"><?= htmlspecialchars($u['username']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-group">
              <label>Reported Date</label>
              <input type="text" class="form-control" value="<?= date('Y-m-d H:i:s') ?>" readonly>
              <small class="text-muted">Automatically recorded as the current date and time.</small>
            </div>

            <div class="form-group">
              <label>Due Date</label>
              <input type="date" name="due_date" class="form-control">
            </div>

            <button type="submit" class="btn btn-primary btn-block">Submit Request</button>
          </form>
        </div>
      </div>
    </div>
  </section>
</div>
