<?php
// pages/warranty_list.php

// Require login
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

// Admin-only guard (don’t exit; return so footer runs & preloader hides)
$isAdmin = isset($_SESSION['user_role']) && strtolower($_SESSION['user_role']) === 'admin';
if (!$isAdmin) {
  ?>
  <div class="content-wrapper">
    <div class="content-header">
      <div class="container-fluid">
        <h1 class="m-0 text-dark">Warranty</h1>
      </div>
    </div>
    <section class="content">
      <div class="container-fluid">
        <div class="alert alert-danger mt-3">You do not have permission to access Warranty.</div>
      </div>
    </section>
  </div>
  <?php
  return;
}

// Get PDO
$db = null;
if (isset($pdo) && $pdo) { $db = $pdo; }
elseif (isset($obj) && isset($obj->pdo)) { $db = $obj->pdo; }
if (!$db) {
  die('<div class="content-wrapper"><section class="content"><div class="container-fluid">
       <div class="alert alert-danger mt-3">Database connection not found.</div>
       </div></section></div>');
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

// Optional: normalize expired status in DB (cheap)
try {
  $db->exec("UPDATE warranties SET warranty_status='expired' WHERE end_date < CURDATE() AND warranty_status <> 'expired'");
} catch (Throwable $e) { /* ignore */ }

// Server-side filter (keep existing behavior)
$filter = strtolower($_GET['filter'] ?? 'active'); // active | expired | all
$where = '1=1';
if ($filter === 'active') {
  $where = "w.end_date >= CURDATE()";
} elseif ($filter === 'expired') {
  $where = "w.end_date < CURDATE()";
}

// Fetch warranties
$sql = "
  SELECT
    w.id, w.asset_id, w.vendor_name, w.start_date, w.end_date, w.warranty_status,
    p.product_name AS asset_name
  FROM warranties w
  JOIN `{$productTable}` p ON p.id = w.asset_id
  WHERE {$where}
  ORDER BY w.end_date ASC, w.start_date ASC
";
$stmt = $db->prepare($sql);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch assets for modal + filter select
$assets = $db->query("SELECT id, product_name FROM `{$productTable}` ORDER BY product_name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Flash
$msg  = $_GET['msg']  ?? null;
$type = $_GET['type'] ?? 'info';

function days_left($end) {
  $d = (new DateTime($end))->diff(new DateTime('today'))->format('%r%a');
  return (int)$d;
}
function badgeClass($status) {
  return ($status === 'expired') ? 'badge-danger' : 'badge-success';
}
?>
<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h1 class="m-0 text-dark">Warranty</h1>
        <ol class="breadcrumb float-sm-right mb-0">
          <li class="breadcrumb-item"><a href="index.php">Home</a></li>
          <li class="breadcrumb-item active">Warranty</li>
        </ol>
      </div>
      <?php if ($msg): ?>
        <div class="alert alert-<?= htmlspecialchars($type) ?>"><?= htmlspecialchars($msg) ?></div>
      <?php endif; ?>
    </div>
  </div>

  <section class="content">
    <div class="container-fluid">

      <!-- Top: Server-side filter buttons + Add -->
      <div class="mb-3 d-flex justify-content-between flex-wrap">
        <div class="mb-2">
          <a href="index.php?page=warranty_list&filter=active" class="btn btn-<?= $filter==='active'?'success':'outline-success' ?> btn-sm mr-1">Active</a>
          <a href="index.php?page=warranty_list&filter=expired" class="btn btn-<?= $filter==='expired'?'danger':'outline-danger' ?> btn-sm mr-1">Expired</a>
          <a href="index.php?page=warranty_list&filter=all" class="btn btn-<?= $filter==='all'?'secondary':'outline-secondary' ?> btn-sm">All</a>
        </div>

        <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#warrantyModal">
          <i class="fas fa-plus"></i> Add Warranty
        </button>
      </div>

      <!-- NEW: Client-side quick filter bar -->
    <div class="card mb-2">
        <div class="card-body py-2">
            <div class="form-row align-items-end">
                <div class="form-group col-md-3 mb-2">
                    <label class="mb-1">Asset</label>
                    <select id="fltAsset" class="form-control select2">
                        <option value="">All assets</option>
                        <?php foreach ($assets as $a): ?>
                            <option value="<?= htmlspecialchars($a['product_name']) ?>"><?= htmlspecialchars($a['product_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group col-md-3 mb-2">
                    <label class="mb-1">Vendor</label>
                    <input type="text" id="fltVendor" class="form-control" placeholder="Search vendor">
                </div>
                <div class="form-group col-md-2 mb-2">
                    <label class="mb-1">End date (from)</label>
                    <input type="text" id="fltEndFrom" class="form-control datepicker" placeholder="YYYY-MM-DD" inputmode="numeric">
                </div>
                <div class="form-group col-md-2 mb-2">
                    <label class="mb-1">End date (to)</label>
                    <input type="text" id="fltEndTo" class="form-control datepicker" placeholder="YYYY-MM-DD" inputmode="numeric">
                </div>
                <div class="form-group col-md-2 mb-2 d-flex">
                    <button id="fltApply" type="button" class="btn btn-primary mr-2 flex-fill">Search</button>
                    <button id="fltClear" type="button" class="btn btn-outline-secondary flex-fill">Clear</button>
                </div>
            </div>
        </div>
    </div>


      <div class="card">
        <div class="card-header d-flex align-items-center">
          <h3 class="card-title mb-0"><b><?= ucfirst($filter) ?> Warranties</b></h3>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-striped mb-0" id="warrantyTable">
              <thead class="thead-light">
                <tr>
                  <th>#</th>
                  <th>Asset</th>
                  <th>Vendor</th>
                  <th>Start</th>
                  <th>End</th>
                  <th>Days Left</th>
                  <th>Status</th>
                  <th class="text-right">Actions</th>
                </tr>
              </thead>
              <tbody>
              <?php if ($rows): foreach ($rows as $i => $r):
                $days = days_left($r['end_date']);
                $status = ($r['end_date'] < date('Y-m-d')) ? 'expired' : 'active';
                ?>
                <tr>
                  <td><?= $i+1 ?></td>
                  <td><?= htmlspecialchars($r['asset_name']) ?></td>
                  <td><?= htmlspecialchars($r['vendor_name'] ?? '-') ?></td>
                  <td><?= htmlspecialchars(date('Y-m-d', strtotime($r['start_date']))) ?></td>
                  <td><?= htmlspecialchars(date('Y-m-d', strtotime($r['end_date']))) ?></td>
                  <td class="<?= $days < 0 ? 'text-danger' : ($days <= 30 ? 'text-warning' : '') ?>">
                    <?= $days ?> day<?= ($days == 1 || $days == -1) ? '' : 's' ?>
                  </td>
                  <td><span class="badge <?= badgeClass($status) ?>"><?= ucfirst($status) ?></span></td>
                  <td class="text-right">
                    <button
                      class="btn btn-sm btn-info"
                      data-toggle="modal"
                      data-target="#warrantyModal"
                      data-id="<?= (int)$r['id'] ?>"
                      data-asset="<?= (int)$r['asset_id'] ?>"
                      data-vendor="<?= htmlspecialchars($r['vendor_name'] ?? '', ENT_QUOTES) ?>"
                      data-start="<?= htmlspecialchars($r['start_date']) ?>"
                      data-end="<?= htmlspecialchars($r['end_date']) ?>"
                    >Edit</button>

                    <form action="app/action/warranty_delete.php" method="post" class="d-inline" onsubmit="return confirm('Delete this warranty?');">
                      <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                      <button class="btn btn-sm btn-danger">Delete</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; else: ?>
                <tr><td colspan="8" class="text-center text-muted">No warranties found.</td></tr>
              <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </div>
  </section>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="warrantyModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-md modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header py-2">
        <h5 class="modal-title">Add Warranty</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"
                style="outline:none;border:0;background:transparent;font-size:28px;line-height:1;">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="app/action/warranty_save.php" method="post" autocomplete="off">
        <div class="modal-body">
          <input type="hidden" name="id" id="w_id" value="">
          <div class="form-group">
            <label>Asset</label>
            <select name="asset_id" id="w_asset" class="form-control select2" required>
              <option value="">Select asset</option>
              <?php foreach ($assets as $a): ?>
                <option value="<?= (int)$a['id'] ?>"><?= htmlspecialchars($a['product_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Vendor</label>
            <input type="text" name="vendor_name" id="w_vendor" class="form-control" placeholder="e.g. Dell, Lenovo, etc.">
          </div>
          <div class="form-group">
            <label>Start / End</label>
            <div class="d-flex">
              <input type="text"
                     name="start_date" id="w_start"
                     class="form-control mr-2 datepicker"
                     placeholder="YYYY-MM-DD"
                     inputmode="numeric"
                     pattern="^\d{4}-\d{2}-\d{2}$"
                     title="Use format YYYY-MM-DD"
                     required>
              <input type="text"
                     name="end_date" id="w_end"
                     class="form-control datepicker"
                     placeholder="YYYY-MM-DD"
                     inputmode="numeric"
                     pattern="^\d{4}-\d{2}-\d{2}$"
                     title="Use format YYYY-MM-DD"
                     required>
            </div>
            <small class="text-muted">Format: <code>YYYY-MM-DD</code></small>
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
window.addEventListener('load', function () {
  // At this point footer scripts (including jQuery) are loaded
  (function($){
    // --- Plugins ---
    if ($.fn.select2) { $('.select2').select2(); }
    if ($.fn.datepicker) { $('.datepicker').datepicker({ format: 'yyyy-mm-dd', autoclose: true, todayHighlight: true }); }

    // --- Get or create the DataTable once ---
    var table = $.fn.dataTable.isDataTable('#warrantyTable')
      ? $('#warrantyTable').DataTable()
      : $('#warrantyTable').DataTable({
          pageLength: 25,
          order: [[4, 'asc']] // End Date column
        });

    // --- Globals for date range filter (End Date col index 4) ---
    window.__wEndMin = '';
    window.__wEndMax = '';

    function warrantyEndRange(settings, data){
      if (settings.nTable.id !== 'warrantyTable') return true;
      var min = window.__wEndMin || '';
      var max = window.__wEndMax || '';
      if (!min && !max) return true;

      var end = (data[4] || '').trim(); // YYYY-MM-DD
      if (!end) return false;

      if (min && end < min) return false;
      if (max && end > max) return false;
      return true;
    }

    // Remove any previous copy, then add once
    $.fn.dataTable.ext.search = $.fn.dataTable.ext.search.filter(function(fn){
      return fn.name !== 'warrantyEndRange';
    });
    $.fn.dataTable.ext.search.push(warrantyEndRange);

    // Delegated click handlers (robust)
    $(document).on('click', '#fltApply', function(e){
      e.preventDefault();
      console.log('[Warranty] Search clicked');

      var asset  = ($('#fltAsset').val() || '').trim();
      var vendor = ($('#fltVendor').val() || '').trim();
      window.__wEndMin = ($('#fltEndFrom').val() || '').trim();
      window.__wEndMax = ($('#fltEndTo').val()   || '').trim();

      console.log('[Warranty] Filters -> asset:', asset, 'vendor:', vendor, 'min:', __wEndMin, 'max:', __wEndMax);

      // Asset exact match (column 1)
      if (asset) {
        var rx = '^' + $.fn.dataTable.util.escapeRegex(asset) + '$';
        table.column(1).search(rx, true, false);
      } else {
        table.column(1).search('');
      }

      // Vendor contains (column 2)
      table.column(2).search(vendor);

      table.draw();
    });

    $(document).on('click', '#fltClear', function(e){
      e.preventDefault();
      console.log('[Warranty] Clear clicked');

      $('#fltAsset').val('').trigger('change');
      $('#fltVendor').val('');
      $('#fltEndFrom').val('');
      $('#fltEndTo').val('');
      window.__wEndMin = window.__wEndMax = '';

      table.search('').columns().search('');
      table.draw();
    });
  })(jQuery);
});
</script>





