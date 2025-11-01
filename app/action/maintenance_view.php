<?php
// app/action/maintenance_view.php
header('Content-Type: text/html; charset=UTF-8');

require_once __DIR__ . '/../../app/init.php';

// PDO from your app
$db = null;
if (isset($pdo) && $pdo) {
  $db = $pdo;
} elseif (isset($obj) && isset($obj->pdo)) {
  $db = $obj->pdo;
} else {
  http_response_code(500);
  echo '<div class="text-danger">Database connection not found.</div>';
  exit;
}

// detect product/assets table
$productTable = 'product';
try { $db->query("SELECT 1 FROM `product` LIMIT 1"); }
catch (Throwable $e) {
  foreach (['products','tbl_product','items','assets'] as $t) {
    try { $db->query("SELECT 1 FROM `{$t}` LIMIT 1"); $productTable = $t; break; }
    catch (Throwable $ignored) {}
  }
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  http_response_code(400);
  echo '<div class="text-danger">Invalid id.</div>';
  exit;
}

$sql = "
  SELECT
    mo.id,
    mo.asset_id,
    mo.title,
    mo.description,
    mo.priority,
    mo.status,
    mo.requested_by,
    mo.assigned_to,
    mo.requested_date,
    mo.due_date,
    p.product_name AS asset_name,
    u1.username    AS requested_name,
    u2.username    AS assigned_name
  FROM maintenance_orders mo
  JOIN `{$productTable}` p ON p.id = mo.asset_id
  LEFT JOIN user u1 ON u1.id = mo.requested_by
  LEFT JOIN user u2 ON u2.id = mo.assigned_to
  WHERE mo.id = :id
  LIMIT 1
";
$stmt = $db->prepare($sql);
$stmt->execute([':id'=>$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
  echo '<div class="text-danger">Maintenance request not found.</div>';
  exit;
}

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function badgePriority($p){
  $cls = [
    'low'    => 'badge-success',
    'medium' => 'badge-primary',
    'high'   => 'badge-warning',
    'urgent' => 'badge-danger',
  ][strtolower($p ?? '')] ?? 'badge-secondary';
  return '<span class="badge '.$cls.'">'.h(ucfirst($p ?: '-')).'</span>';
}
function badgeStatus($s){
  $map = [
    'open'          => ['Open','badge-secondary'],
    'in_progress'   => ['In Progress','badge-info'],
    'waiting_parts' => ['Waiting Parts','badge-warning'],
    'resolved'      => ['Resolved','badge-success'],
    'closed'        => ['Closed','badge-dark'],
  ];
  $s = strtolower($s ?? 'open');
  [$label,$cls] = $map[$s] ?? ['Open','badge-secondary'];
  return '<span class="badge '.$cls.'">'.h($label).'</span>';
}

$reported = $row['requested_date'] ? date('Y-m-d H:i', strtotime($row['requested_date'])) : '-';
$due      = $row['due_date']       ? date('Y-m-d', strtotime($row['due_date']))       : '-';

?>
<div class="container-fluid">
  <div class="row">
    <div class="col-md-6">
      <h5 class="mb-2"><?= h($row['title'] ?: 'Maintenance Request #'.$row['id']) ?></h5>
      <dl class="row mb-0">
        <dt class="col-sm-4">Asset</dt>
        <dd class="col-sm-8"><?= h($row['asset_name']) ?></dd>

        <dt class="col-sm-4">Priority</dt>
        <dd class="col-sm-8"><?= badgePriority($row['priority']) ?></dd>

        <dt class="col-sm-4">Status</dt>
        <dd class="col-sm-8"><?= badgeStatus($row['status']) ?></dd>

        <dt class="col-sm-4">Reported By</dt>
        <dd class="col-sm-8"><?= h($row['requested_name'] ?: '-') ?></dd>

        <dt class="col-sm-4">Assigned To</dt>
        <dd class="col-sm-8"><?= h($row['assigned_name'] ?: '-') ?></dd>

        <dt class="col-sm-4">Reported</dt>
        <dd class="col-sm-8"><?= h($reported) ?></dd>

        <dt class="col-sm-4">Due</dt>
        <dd class="col-sm-8"><?= h($due) ?></dd>
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
