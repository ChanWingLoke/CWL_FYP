<?php
/**
 * Fresh Maintenance Module (drop-in)
 * Stack: AdminLTE + jQuery + PDO (via app/init.php loaded by inc/header.php)
 * Routes: index.php?page=maintenance_list, maintenance_request
 * Actions: app/action/maintenance_save.php, maintenance_update.php, maintenance_view.php, maintenance_delete.php
 */
?>

<?php
header('Content-Type: text/html; charset=UTF-8');
require_once __DIR__ . '/../../app/init.php';

$db = isset($pdo) && $pdo ? $pdo : ($obj->pdo ?? null);
if (!$db) { http_response_code(500); echo '<div class="text-danger">DB not available</div>'; exit; }

$id = (int)($_GET['id'] ?? 0);
if (!$id) { http_response_code(400); echo '<div class="text-danger">Invalid id.</div>'; exit; }

// Detect product table
$productTable = 'products';
try { $db->query("SELECT 1 FROM `products` LIMIT 1"); }
catch (Throwable $e) { try { $db->query("SELECT 1 FROM `product` LIMIT 1"); $productTable='product'; } catch (Throwable $e2) {} }

$sql = "
  SELECT
    mo.*,
    p.product_name AS asset_name,
    u1.username   AS requested_name,
    u2.username   AS assigned_name
  FROM maintenance_orders mo
  JOIN `{$productTable}` p ON p.id = mo.asset_id
  LEFT JOIN user u1 ON u1.id = mo.requested_by
  LEFT JOIN user u2 ON u2.id = mo.assigned_to
  WHERE mo.id=:id
  LIMIT 1
";
$stmt = $db->prepare($sql);
$stmt->execute([':id'=>$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) { echo '<div class="alert alert-danger m-3">Request not found.</div>'; exit; }

function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
$reported = $row['requested_date'] ? date('Y-m-d', strtotime($row['requested_date'])) : '—';
$due      = $row['due_date'] ? date('Y-m-d', strtotime($row['due_date'])) : '—';
?>
<div>
  <div class="row">
    <div class="col-md-6">
      <dl class="row mb-0">
        <dt class="col-sm-4">ID</dt>
        <dd class="col-sm-8">#<?= (int)$row['id'] ?></dd>

        <dt class="col-sm-4">Asset</dt>
        <dd class="col-sm-8"><?= h($row['asset_name'] ?: ('#'.$row['asset_id'])) ?></dd>

        <dt class="col-sm-4">Title</dt>
        <dd class="col-sm-8"><?= h($row['title']) ?></dd>

        <dt class="col-sm-4">Priority</dt>
        <dd class="col-sm-8"><?= h($row['priority']) ?></dd>

        <dt class="col-sm-4">Status</dt>
        <dd class="col-sm-8"><?= h(str_replace('_',' ',$row['status'])) ?></dd>

        <dt class="col-sm-4">Reported</dt>
        <dd class="col-sm-8"><?= h($reported) ?></dd>

        <dt class="col-sm-4">Due</dt>
        <dd class="col-sm-8"><?= h($due) ?></dd>

        <dt class="col-sm-4">Requested by</dt>
        <dd class="col-sm-8"><?= h($row['requested_name'] ?: ('#'.$row['requested_by'])) ?></dd>

        <dt class="col-sm-4">Assigned to</dt>
        <dd class="col-sm-8"><?= h($row['assigned_name'] ?: ($row['assigned_to'] ? '#'.$row['assigned_to'] : '—')) ?></dd>
      </dl>
    </div>
    <div class="col-md-6">
      <label class="mb-1">Description</label>
      <div class="border rounded p-2" style="min-height:100px; white-space:pre-wrap;">
        <?= $row['description'] ? nl2br(h($row['description'])) : '<span class="text-muted">—</span>' ?>
      </div>
    </div>
  </div>
</div>
