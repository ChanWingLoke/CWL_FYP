<?php
/**
 * Fresh Maintenance Module (drop-in)
 * Stack: AdminLTE + jQuery + PDO (via app/init.php loaded by inc/header.php)
 * Routes: index.php?page=maintenance_list, maintenance_request
 * Actions: app/action/maintenance_save.php, maintenance_update.php, maintenance_view.php, maintenance_delete.php
 */
?>

<?php
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }


// Resolve PDO ($pdo or $obj->pdo)
$db = null;
if (isset($pdo) && $pdo)        { $db = $pdo; }
elseif (isset($obj) && isset($obj->pdo)) { $db = $obj->pdo; }

if (!$db) {
  die('<div class="content-wrapper"><section class="content"><div class="container-fluid"><div class="alert alert-danger mt-3">Database connection not found.</div></div></section></div>');
}

// Detect product table: 'products' or 'product' (fall back to 'products')
$productTable = 'products';
try {
  $db->query("SELECT 1 FROM `products` LIMIT 1");
} catch (Throwable $e) {
  try { $db->query("SELECT 1 FROM `product` LIMIT 1"); $productTable = 'product'; }
  catch (Throwable $e2) {}
}


// Fetch assets for dropdown
$assets = $db->query("SELECT id, product_name FROM `{$productTable}` ORDER BY product_name ASC LIMIT 1000")->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6"><h1 class="m-0 text-dark">New Maintenance Request</h1></div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item"><a href="index.php?page=maintenance_list">Maintenance</a></li>
            <li class="breadcrumb-item active">New Request</li>
          </ol>
        </div>
      </div>
    </div>
  </div>

  <section class="content">
    <div class="container-fluid">
      <form action="app/action/maintenance_save.php" method="post" class="card card-outline card-primary">
        <div class="card-body">
          <div class="form-row">
            <div class="form-group col-md-6">
              <label>Asset</label>
              <select name="asset_id" class="form-control" required>
                <option value="">-- Select asset --</option>
                <?php foreach ($assets as $a): ?>
                  <option value="<?= (int)$a['id'] ?>"><?= htmlspecialchars($a['product_name'] ?: ('#'.$a['id'])) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group col-md-6">
              <label>Priority</label>
              <select name="priority" class="form-control" required>
                <option value="low">Low</option>
                <option value="medium">Medium</option>
                <option value="high">High</option>
                <option value="critical">Critical</option>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label>Title</label>
            <input type="text" name="title" class="form-control" maxlength="150" required>
          </div>

          <div class="form-group">
            <label>Description</label>
            <textarea name="description" class="form-control" rows="4" placeholder="Describe the issue..." required></textarea>
          </div>

          <div class="form-row">
            <div class="form-group col-md-6">
              <label>Reported Date</label>
              <input type="date" name="requested_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="form-group col-md-6">
              <label>Due Date (optional)</label>
              <input type="date" name="due_date" class="form-control">
            </div>
          </div>
        </div>
        <div class="card-footer text-right">
          <a href="index.php?page=maintenance_list" class="btn btn-secondary">Cancel</a>
          <button type="submit" class="btn btn-primary">Save Request</button>
        </div>
      </form>
    </div>
  </section>
</div>
