<?php
// pages/maintenance_list.php

// Require login
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

// Get PDO
$db = null;
if (isset($pdo) && $pdo) { $db = $pdo; }
elseif (isset($obj) && isset($obj->pdo)) { $db = $obj->pdo; }
if (!$db) {
  die('<div class="content-wrapper"><section class="content"><div class="container-fluid">
       <div class="alert alert-danger mt-3">Database connection not found.</div>
       </div></section></div>');
}

// Detect product table name (product | products | items | assets)
$productTable = 'product';
try { $db->query("SELECT 1 FROM `product` LIMIT 1"); }
catch (Throwable $e) {
  foreach (['products','tbl_product','items','assets'] as $t) {
    try { $db->query("SELECT 1 FROM `{$t}` LIMIT 1"); $productTable = $t; break; }
    catch (Throwable $ignored) {}
  }
}

// Tabs / filter
$validStatuses = ['open','in_progress','waiting_parts','resolved','closed','all'];
$filter = strtolower($_GET['filter'] ?? 'open');
if (!in_array($filter, $validStatuses, true)) $filter = 'open';

$where = '1=1';
$params = [];
if ($filter !== 'all') {
  $where = 'mo.status = :st';
  $params[':st'] = $filter;
}

// Query list (note: requested_by + assigned_to + product name)
$sql = "
  SELECT
    mo.id, mo.asset_id, mo.title, mo.description, mo.priority, mo.status,
    mo.requested_by, mo.assigned_to, mo.requested_date, mo.due_date, mo.resolved_date,
    p.product_name AS asset_name,
    ru.username    AS requested_by_name,
    au.username    AS assigned_to_name
  FROM maintenance_orders mo
  JOIN `{$productTable}` p ON p.id = mo.asset_id
  LEFT JOIN user ru ON ru.id = mo.requested_by
  LEFT JOIN user au ON au.id = mo.assigned_to
  WHERE {$where}
  ORDER BY mo.created_at DESC, mo.id DESC
";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

function badge($s) {
  $map = [
    'open'          => 'badge-primary',
    'in_progress'   => 'badge-info',
    'waiting_parts' => 'badge-warning',
    'resolved'      => 'badge-success',
    'closed'        => 'badge-secondary',
  ];
  return $map[$s] ?? 'badge-light';
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

      <div class="mb-3">
        <a class="btn btn-<?= $filter==='open'?'primary':'outline-primary' ?> btn-sm mr-2" href="index.php?page=maintenance_list&filter=open">Open</a>
        <a class="btn btn-<?= $filter==='in_progress'?'primary':'outline-primary' ?> btn-sm mr-2" href="index.php?page=maintenance_list&filter=in_progress">In Progress</a>
        <a class="btn btn-<?= $filter==='waiting_parts'?'primary':'outline-primary' ?> btn-sm mr-2" href="index.php?page=maintenance_list&filter=waiting_parts">Waiting Parts</a>
        <a class="btn btn-<?= $filter==='resolved'?'primary':'outline-primary' ?> btn-sm mr-2" href="index.php?page=maintenance_list&filter=resolved">Resolved</a>
        <a class="btn btn-<?= $filter==='closed'?'primary':'outline-primary' ?> btn-sm mr-2" href="index.php?page=maintenance_list&filter=closed">Closed</a>
        <a class="btn btn-<?= $filter==='all'?'secondary':'outline-secondary' ?> btn-sm" href="index.php?page=maintenance_list&filter=all">All</a>

        <button class="btn btn-success btn-sm float-right" data-toggle="modal" data-target="#maintModal">
          <i class="fas fa-plus"></i> New Request
        </button>
      </div>
    </div>
  </div>

  <section class="content">
    <div class="container-fluid">
      <div class="card">
        <div class="card-header"><b><?= ucfirst(str_replace('_',' ', $filter)) ?> Tickets</b></div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-striped mb-0" id="maintTable">
              <thead class="thead-light">
                <tr>
                  <th>#</th>
                  <th>Asset</th>
                  <th>Title</th>
                  <th>Priority</th>
                  <th>Status</th>
                  <th>Requested by</th>
                  <th>Assigned to</th>
                  <th>Requested</th>
                  <th>Due</th>
                </tr>
              </thead>
              <tbody>
              <?php if ($rows): foreach ($rows as $i => $r): ?>
                <tr>
                  <td><?= $i+1 ?></td>
                  <td><?= htmlspecialchars($r['asset_name']) ?></td>
                  <td><?= htmlspecialchars($r['title']) ?></td>
                  <td><?= ucfirst($r['priority']) ?></td>
                  <td><span class="badge <?= badge($r['status']) ?>"><?= ucfirst(str_replace('_',' ',$r['status'])) ?></span></td>
                  <td><?= htmlspecialchars($r['requested_by_name'] ?? '-') ?></td>
                  <td><?= htmlspecialchars($r['assigned_to_name'] ?? '-') ?></td>
                  <td><?= htmlspecialchars($r['requested_date']) ?></td>
                  <td><?= htmlspecialchars($r['due_date'] ?? '-') ?></td>
                </tr>
              <?php endforeach; else: ?>
                <tr><td colspan="9" class="text-center text-muted">No tickets found.</td></tr>
              <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

<!-- Create Modal -->
<div class="modal fade" id="maintModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-md modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header py-2">
        <h5 class="modal-title">New Maintenance Request</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"
                style="outline:none;border:0;background:transparent;font-size:28px;line-height:1;">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="app/action/maintenance_save.php" method="post" autocomplete="off">
        <div class="modal-body">
          <div class="form-group">
            <label>Asset</label>
            <select name="asset_id" class="form-control select2" required>
              <option value="">Select asset</option>
              <?php
              $assets = $db->query("SELECT id, product_name FROM `{$productTable}` ORDER BY product_name")->fetchAll(PDO::FETCH_ASSOC);
              foreach ($assets as $a) {
                echo '<option value="'.(int)$a['id'].'">'.htmlspecialchars($a['product_name']).'</option>';
              }
              ?>
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
              <option value="critical">Critical</option>
            </select>
          </div>
          <div class="form-row">
            <div class="form-group col-md-6">
              <label>Requested date</label>
              <input type="text" name="requested_date" class="form-control datepicker" placeholder="YYYY-MM-DD" required>
            </div>
            <div class="form-group col-md-6">
              <label>Due date</label>
              <input type="text" name="due_date" class="form-control datepicker" placeholder="YYYY-MM-DD">
            </div>
          </div>
          <div class="form-group">
            <label>Assign to (optional)</label>
            <select name="assigned_to" class="form-control select2">
              <option value="">— Unassigned —</option>
              <?php
              $users = $db->query("SELECT id, username FROM user ORDER BY username")->fetchAll(PDO::FETCH_ASSOC);
              foreach ($users as $u) {
                echo '<option value="'.(int)$u['id'].'">'.htmlspecialchars($u['username']).'</option>';
              }
              ?>
            </select>
          </div>
        </div>
        <div class="modal-footer py-2">
          <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>


<script>
$(function(){
  if ($.fn.DataTable) $('#maintTable').DataTable({ pageLength: 25 });
  if ($.fn.select2) $('.select2').select2();
  if ($.fn.datepicker) $('.datepicker').datepicker({ format: 'yyyy-mm-dd', autoclose: true, todayHighlight: true });
});
</script>
