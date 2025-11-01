<?php
// pages/maintenance_list.php

// ------------------------------------------------------------------
// Require login (adjust to your app’s guard as needed)
// ------------------------------------------------------------------
if (!isset($_SESSION['user_id'])) {
  header('Location: login.php'); exit;
}

// ------------------------------------------------------------------
// Get PDO from app bootstrap
// ------------------------------------------------------------------
$db = null;
if (isset($pdo) && $pdo) {
  $db = $pdo;
} elseif (isset($obj) && isset($obj->pdo)) {
  $db = $obj->pdo;
}
if (!$db) {
  die('<div class="content-wrapper"><section class="content"><div class="container-fluid">
        <div class="alert alert-danger mt-3">Database connection not found.</div>
      </div></section></div>');
}

// ------------------------------------------------------------------
// Detect your product/assets table name dynamically
// ------------------------------------------------------------------
$productTable = 'product';
try { $db->query("SELECT 1 FROM `product` LIMIT 1"); }
catch (Throwable $e) {
  foreach (['products','tbl_product','items','assets'] as $t) {
    try { $db->query("SELECT 1 FROM `{$t}` LIMIT 1"); $productTable = $t; break; }
    catch (Throwable $ignored) {}
  }
}

// ------------------------------------------------------------------
// Tab / status filter
// Tabs: open | in_progress | waiting_parts | resolved | closed | all
// ------------------------------------------------------------------
$tab = strtolower($_GET['tab'] ?? 'open');

$where = '1=1';
$params = [];

switch ($tab) {
  case 'open':
    $where = "mo.status = 'open'";
    break;
  case 'in_progress':
    $where = "mo.status = 'in_progress'";
    break;
  case 'waiting_parts':
    $where = "mo.status = 'waiting_parts'";
    break;
  case 'resolved':
    $where = "mo.status = 'resolved'";
    break;
  case 'closed':
    $where = "mo.status = 'closed'";
    break;
  case 'all':
  default:
    $where = '1=1';
    break;
}

// ------------------------------------------------------------------
// QUERY
// IMPORTANT: we alias requested_by→reported_by and requested_date→reported_at
// so the rest of the template can stay consistent.
// ------------------------------------------------------------------
$sql = "
  SELECT
    mo.id,
    mo.asset_id,
    mo.title,
    mo.description,
    mo.priority,
    mo.status,
    mo.requested_by   AS reported_by,   -- alias
    mo.assigned_to,
    mo.requested_date AS reported_at,   -- alias
    mo.due_date,
    p.product_name    AS asset_name,
    u1.username       AS reported_name, -- who requested
    u2.username       AS assigned_name  -- who it’s assigned to
  FROM maintenance_orders mo
  JOIN `{$productTable}` p ON p.id = mo.asset_id
  LEFT JOIN user u1 ON u1.id = mo.requested_by
  LEFT JOIN user u2 ON u2.id = mo.assigned_to
  WHERE {$where}
  ORDER BY mo.requested_date DESC, mo.id DESC
";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Users list for assignment dropdown (optional)
$users = [];
try {
  $users = $db->query("SELECT id, username FROM user ORDER BY username ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) { /* ignore */ }

function priorityBadge($p) {
  $p = strtolower((string)$p);
  $label = ucfirst($p ?: '-');
  $cls = [
    'low'    => 'badge-success',
    'medium' => 'badge-primary',
    'high'   => 'badge-warning',
    'urgent' => 'badge-danger',
  ][$p] ?? 'badge-secondary';
  return '<span class="badge '.$cls.'">'.htmlspecialchars($label).'</span>';
}

function statusBadge($s) {
  $s = strtolower((string)$s);
  $label = [
    'open'          => 'Open',
    'in_progress'   => 'In Progress',
    'waiting_parts' => 'Waiting Parts',
    'resolved'      => 'Resolved',
    'closed'        => 'Closed',
  ][$s] ?? ucfirst($s ?: 'Open');

  $cls = [
    'open'          => 'badge-secondary',
    'in_progress'   => 'badge-info',
    'waiting_parts' => 'badge-warning',
    'resolved'      => 'badge-success',
    'closed'        => 'badge-dark',
  ][$s] ?? 'badge-secondary';

  return '<span class="badge '.$cls.'">'.htmlspecialchars($label).'</span>';
}

?>
<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h1 class="m-0 text-dark">Maintenance</h1>
        <ol class="breadcrumb float-sm-right mb-0">
          <li class="breadcrumb-item"><a href="index.php">Home</a></li>
          <li class="breadcrumb-item active">Maintenance</li>
        </ol>
      </div>

      <!-- Tabs -->
      <div class="mb-3">
        <a class="btn btn-<?= $tab==='open'?'primary':'outline-primary' ?> btn-sm mr-2" href="index.php?page=maintenance_list&tab=open">Open</a>
        <a class="btn btn-<?= $tab==='in_progress'?'primary':'outline-primary' ?> btn-sm mr-2" href="index.php?page=maintenance_list&tab=in_progress">In Progress</a>
        <a class="btn btn-<?= $tab==='waiting_parts'?'primary':'outline-primary' ?> btn-sm mr-2" href="index.php?page=maintenance_list&tab=waiting_parts">Waiting Parts</a>
        <a class="btn btn-<?= $tab==='resolved'?'primary':'outline-primary' ?> btn-sm mr-2" href="index.php?page=maintenance_list&tab=resolved">Resolved</a>
        <a class="btn btn-<?= $tab==='closed'?'primary':'outline-primary' ?> btn-sm mr-2" href="index.php?page=maintenance_list&tab=closed">Closed</a>
        <a class="btn btn-<?= $tab==='all'?'secondary':'outline-secondary' ?> btn-sm" href="index.php?page=maintenance_list&tab=all">All</a>

        <a class="btn btn-success btn-sm float-right" href="index.php?page=maintenance_request">
          <i class="fas fa-plus"></i> New Request
        </a>
      </div>
    </div>
  </div>

  <section class="content">
    <div class="container-fluid">

      <div class="card">
        <div class="card-header"><b><?= ucfirst(str_replace('_',' ', $tab)) ?> Requests</b></div>

        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-striped mb-0" id="maintTable">
              <thead class="thead-light">
                <tr>
                  <th>#</th>
                  <th>Asset</th>
                  <th>Title</th>
                  <th>Priority</th>
                  <th>Reported By</th>
                  <th>Assigned</th>
                  <th>Reported</th>
                  <th>Due</th>
                  <th>Status</th>
                  <th class="text-right">Actions</th>
                </tr>
              </thead>
              <tbody>
              <?php if ($rows): foreach ($rows as $i => $row): ?>
                <?php
                  $reportedBy = $row['reported_name'] ?? '-';
                  $reportedAt = !empty($row['reported_at'])
                      ? date('Y-m-d', strtotime($row['reported_at']))
                      : '-';
                  $dueOn = !empty($row['due_date'])
                      ? date('Y-m-d', strtotime($row['due_date']))
                      : '-';
                  $assigned = $row['assigned_name'] ?? '-';
                ?>
                <tr>
                  <td><?= $i+1 ?></td>
                  <td><?= htmlspecialchars($row['asset_name']) ?></td>
                  <td><?= htmlspecialchars($row['title'] ?? '-') ?></td>
                  <td><?= priorityBadge($row['priority']) ?></td>
                  <td><?= htmlspecialchars($reportedBy) ?></td>
                  <td><?= htmlspecialchars($assigned) ?></td>
                  <td><?= htmlspecialchars($reportedAt) ?></td>
                  <td><?= htmlspecialchars($dueOn) ?></td>
                  <td><?= statusBadge($row['status']) ?></td>
                  <td class="text-right">
                    <!-- Example actions (wire these to your handlers if needed) -->
                    <div class="btn-group btn-group-sm" role="group">
                      <form action="app/action/maintenance_update.php" method="post" class="d-inline">
                        <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                        <input type="hidden" name="action" value="start">
                        <button class="btn btn-info">Start</button>
                      </form>
                      <form action="app/action/maintenance_update.php" method="post" class="d-inline" onsubmit="return confirm('Close this request?');">
                        <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                        <input type="hidden" name="action" value="close">
                        <button class="btn btn-secondary">Close</button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; else: ?>
                <tr><td colspan="10" class="text-center text-muted">No requests found.</td></tr>
              <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </div>
  </section>
</div>

<script>
// Keep it minimal to avoid jQuery/version clashes; DataTables is optional
if (window.jQuery && $.fn.DataTable) {
  $(function(){
    $('#maintTable').DataTable({
      pageLength: 25,
      order: [[6, 'desc']] // sort by Reported date
    });
  });
}
</script>
