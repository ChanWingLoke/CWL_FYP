<?php
// pages/maintenance_requests.php (adapted to project init/style)

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

// Get PDO ($pdo or $obj->pdo provided by app/init.php via inc/header.php)
$db = null;
if (isset($pdo) && $pdo)        { $db = $pdo; }
elseif (isset($obj) && $obj->pdo){ $db = $obj->pdo; }
if (!$db) {
  die('<div class="content-wrapper"><section class="content"><div class="container-fluid"><div class="alert alert-danger">Database connection not found.</div></div></section></div>');
}

// Detect product/assets table and name column, mirroring maintenance_list.php
$productTable = 'product';
$productNameCol = 'product_name';
try { $db->query("SELECT 1 FROM `product` LIMIT 1"); }
catch (Throwable $e) {
  foreach (['products','tbl_product','items','assets'] as $t) {
    try { $db->query("SELECT 1 FROM `$t` LIMIT 1"); $productTable = $t; break; } catch (Throwable $e2) {}
  }
}
// detect name column
try {
  $cols = $db->query("SHOW COLUMNS FROM `$productTable`")->fetchAll(PDO::FETCH_COLUMN);
  if ($cols) {
    foreach (['product_name','name','asset_name','title'] as $cand) {
      if (in_array($cand, $cols, true)) { $productNameCol = $cand; break; }
    }
  }
} catch (Throwable $e) {}

// Filters
$flt_asset = isset($_GET['asset']) ? trim($_GET['asset']) : '';
$flt_pri   = isset($_GET['priority']) ? trim($_GET['priority']) : '';
$flt_from  = isset($_GET['from']) ? trim($_GET['from']) : '';
$flt_to    = isset($_GET['to']) ? trim($_GET['to']) : '';

$where = ["1=1"];
$args = [];
if ($flt_asset !== '') { $where[] = "mo.asset_id = :asset"; $args[':asset'] = (int)$flt_asset; }
if ($flt_pri !== '')   { $where[] = "mo.priority = :pri";   $args[':pri']   = $flt_pri; }
if ($flt_from !== '')  { $where[] = "mo.requested_date >= :from"; $args[':from'] = $flt_from; }
if ($flt_to !== '')    { $where[] = "mo.requested_date <= :to";   $args[':to']   = $flt_to; }

// Data
$assets = $db->query("SELECT id, `$productNameCol` AS name FROM `$productTable` ORDER BY `$productNameCol` ASC")->fetchAll(PDO::FETCH_ASSOC);

$sql = "SELECT mo.id, mo.title, mo.priority, mo.status, mo.requested_date, mo.due_date,
               p.`$productNameCol` AS asset_name, u.username AS requester
        FROM maintenance_orders mo
        LEFT JOIN `$productTable` p ON p.id = mo.asset_id
        LEFT JOIN user u ON u.id = mo.requested_by
        WHERE ".implode(' AND ', $where)."
        ORDER BY mo.id DESC";
$stmt = $db->prepare($sql);
$stmt->execute($args);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

function badge_priority($pri){
  $pri = strtolower($pri ?? '');
  switch ($pri) {
    case 'urgent': return '<span class="badge badge-danger">Urgent</span>';
    case 'high':   return '<span class="badge badge-warning">High</span>';
    case 'normal': return '<span class="badge badge-info">Normal</span>';
    case 'low':    return '<span class="badge badge-secondary">Low</span>';
  }
  return '<span class="badge badge-light">'.htmlspecialchars($pri).'</span>';
}
function badge_status($st){
  $st = strtolower($st ?? '');
  switch ($st) {
    case 'open':          return '<span class="badge badge-secondary">Open</span>';
    case 'in_progress':   return '<span class="badge badge-warning">In&nbsp;Progress</span>';
    case 'waiting_parts': return '<span class="badge badge-info">Waiting&nbsp;Parts</span>';
    case 'resolved':      return '<span class="badge badge-success">Resolved</span>';
  }
  return '<span class="badge badge-light">'.htmlspecialchars($st).'</span>';
}
?>
<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <h1 class="m-0 text-dark">Maintenance — Requests</h1>
      <ol class="breadcrumb float-sm-right mb-0">
        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
        <li class="breadcrumb-item active">Requests</li>
      </ol>
    </div>
  </div>
  <section class="content">
    <div class="container-fluid">

      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <a href="index.php?page=maintenance_list&tab=open" class="btn btn-outline-primary btn-sm mr-2">Back to List</a>
        </div>
        <div>
          <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#reqModal">
            <i class="fa fa-plus-circle mr-1"></i> New Request
          </button>
        </div>
      </div>

      <div class="card mb-3">
        <div class="card-body">
          <form method="get" class="form-inline">
            <input type="hidden" name="page" value="maintenance_requests">
            <div class="form-group mr-2 mb-2">
              <label class="mr-2">Asset</label>
              <select name="asset" id="fltAsset" class="form-control select2" style="min-width:220px">
                <option value="">-- Any --</option>
                <?php foreach ($assets as $a): ?>
                  <option value="<?= (int)$a['id'] ?>" <?= ($flt_asset==(string)$a['id']?'selected':'') ?>>
                    <?= htmlspecialchars($a['name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group mr-2 mb-2">
              <label class="mr-2">Priority</label>
              <select name="priority" id="fltPri" class="form-control">
                <option value="">-- Any --</option>
                <?php foreach (['urgent','high','normal','low'] as $pri): ?>
                  <option value="<?= $pri ?>" <?= ($flt_pri===$pri?'selected':'') ?>>
                    <?= ucfirst($pri) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group mr-2 mb-2">
              <label class="mr-2">From</label>
              <input type="text" class="form-control" name="from" id="fltFrom" value="<?= htmlspecialchars($flt_from) ?>" placeholder="YYYY-MM-DD" autocomplete="off">
            </div>
            <div class="form-group mr-2 mb-2">
              <label class="mr-2">To</label>
              <input type="text" class="form-control" name="to" id="fltTo" value="<?= htmlspecialchars($flt_to) ?>" placeholder="YYYY-MM-DD" autocomplete="off">
            </div>
            <button class="btn btn-primary mb-2 mr-2" type="submit"><i class="fa fa-filter mr-1"></i> Search</button>
            <a href="index.php?page=maintenance_requests" class="btn btn-secondary mb-2"><i class="fa fa-undo mr-1"></i> Reset</a>
          </form>
        </div>
      </div>

      <div class="card">
        <div class="card-body table-responsive p-0">
          <table class="table table-hover table-sm mb-0" id="reqTable">
            <thead class="thead-light">
              <tr>
                <th style="width:70px">#</th>
                <th>Asset</th>
                <th>Title</</th>
                <th>Priority</th>
                <th>Status</th>
                <th>Requested</th>
                <th>Due</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!$rows): ?>
                <tr><td colspan="7" class="text-muted text-center py-4">No requests found.</td></tr>
              <?php endif; ?>
              <?php foreach ($rows as $r): ?>
                <tr>
                  <td><?= (int)$r['id'] ?></td>
                  <td><?= htmlspecialchars($r['asset_name'] ?? '') ?></td>
                  <td><?= htmlspecialchars($r['title'] ?? '') ?></td>
                  <td><?= badge_priority($r['priority']) ?></td>
                  <td><?= badge_status($r['status']) ?></td>
                  <td><?= htmlspecialchars($r['requested_date'] ?? '') ?></td>
                  <td><?= htmlspecialchars($r['due_date'] ?? '') ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </section>
</div>

<!-- New Request Modal -->
<div class="modal fade" id="reqModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">New Maintenance Request</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form method="post" action="app/action/maintenance_request_save.php" id="reqForm" autocomplete="off">
        <div class="modal-body">
          <div class="form-row">
            <div class="form-group col-md-6">
              <label>Asset</label>
              <select name="asset_id" id="rqAsset" class="form-control select2" style="width:100%" required>
                <option value="">-- Select asset --</option>
                <?php foreach ($assets as $a): ?>
                  <option value="<?= (int)$a['id'] ?>"><?= htmlspecialchars($a['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group col-md-6">
              <label>Priority</label>
              <select name="priority" id="rqPri" class="form-control" required>
                <?php foreach (['urgent','high','normal','low'] as $pri): ?>
                  <option value="<?= $pri ?>"><?= ucfirst($pri) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label>Title</label>
            <input type="text" name="title" id="rqTitle" class="form-control" required maxlength="150">
          </div>
          <div class="form-group">
            <label>Due Date</label>
            <input type="text" name="due_date" id="rqDue" class="form-control" placeholder="YYYY-MM-DD">
          </div>
          <div class="form-group mb-0">
            <label>Description</label>
            <textarea name="description" id="rqDesc" class="form-control" rows="4" maxlength="1000"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Create Request</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function(){
  (function($){

    // Select2 in modal; allow typing, no tag creation
    if ($.fn.select2) {
      $('#fltAsset, #rqAsset').each(function(){
        var $sel = $(this);
        if ($sel.data('select2')) { try { $sel.select2('destroy'); } catch(e){} }
        $sel.select2({
          width: '100%',
          tags: false,
          minimumResultsForSearch: 0,
          dropdownParent: $sel.closest('.modal').length ? $sel.closest('.modal') : $(document.body),
          allowClear: true,
          placeholder: $sel.is('#rqAsset') ? 'Select asset' : '-- Any --'
        });
      });
    }

    // Datepickers (no auto-open)
    var $dps = $('#fltFrom, #fltTo, #rqDue');
    $dps.each(function(){
      var $i = $(this);
      if ($i.data('datepicker')) try { $i.datepicker('destroy'); } catch(e){}
      $i.datepicker({
        format: 'yyyy-mm-dd',
        autoclose: true,
        orientation: 'bottom auto',
        container: $i.closest('.modal').length ? $i.closest('.modal') : 'body'
      });
    });

  })(jQuery);
});
</script>
