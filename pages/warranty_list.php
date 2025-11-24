<?php
// pages/warranty_list.php

// Require login
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

// Admin-only (don’t exit; return so footer runs & preloader hides)
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

// Detect assets table (your project uses `products`)
$productTable = 'product';
try { $db->query("SELECT 1 FROM `product` LIMIT 1"); }
catch (Throwable $e) {
  foreach (['products','tbl_product','items','assets'] as $t) {
    try { $db->query("SELECT 1 FROM `{$t}` LIMIT 1"); $productTable = $t; break; }
    catch (Throwable $ignored) {}
  }
}

// Normalize expired status
try {
  $db->exec("UPDATE warranties 
             SET warranty_status='expired' 
             WHERE end_date < CURDATE() AND warranty_status <> 'expired'");
} catch (Throwable $e) { /* ignore */ }

// Top server-side filter tabs
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

      <div class="mb-3 d-flex justify-content-between flex-wrap">
        <div class="mb-2">
          <a href="index.php?page=warranty_list&filter=active"
            class="btn btn-filter btn-sm mr-1 <?= $filter==='active'?'btn-success active':'btn-outline-success' ?>">Active</a>
          <a href="index.php?page=warranty_list&filter=expired"
            class="btn btn-filter btn-sm mr-1 <?= $filter==='expired'?'btn-danger active':'btn-outline-danger' ?>">Expired</a>
          <a href="index.php?page=warranty_list&filter=all"
            class="btn btn-filter btn-sm <?= $filter==='all'?'btn-secondary active':'btn-outline-secondary' ?>">All</a>
        </div>

        <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modalWarrantyAdd">
          <i class="fas fa-plus"></i> Add Warranty
        </button>
      </div>

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
              <input type="text" id="fltEndFrom" class="form-control datepicker" placeholder="YYYY-MM-DD" inputmode="numeric" autocomplete="off">
            </div>
            <div class="form-group col-md-2 mb-2">
              <label class="mb-1">End date (to)</label>
              <input type="text" id="fltEndTo" class="form-control datepicker" placeholder="YYYY-MM-DD" inputmode="numeric" autocomplete="off">
            </div>
            <div class="form-group col-md-2 mb-2 d-flex">
              <button id="fltApply" class="btn btn-primary mr-2 flex-fill">Search</button>
              <button id="fltClear" class="btn btn-outline-secondary flex-fill">Clear</button>
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
                  <td>
                    <?= htmlspecialchars($r['asset_name']) ?>
                    <span class="badge badge-light text-muted ml-1" title="Warranty ID">#<?= (int)$r['id'] ?></span>
                  </td>
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
                      data-target="#modalWarrantyEdit"
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

<?php include 'warranty_modal_add.php'; ?>
<?php include 'warranty_modal_edit.php'; ?>

<style>
/* Consistent filter button styling like Loans */
.btn-filter {
  font-weight: 600;
  font-size: 14px !important;
  line-height: 1.3 !important;
  padding: 6px 14px !important;
  border-width: 2px !important;
  transition: all 0.15s ease;
  min-width: 90px; /* optional: keep them uniform width */
  text-align: center;
}

.btn-filter:hover {
  opacity: 0.9;
}

/* Outline states */
.btn-outline-success.btn-filter {
  color: #198754 !important;
  border-color: #198754 !important;
  background: transparent !important;
}
.btn-outline-danger.btn-filter {
  color: #dc3545 !important;
  border-color: #dc3545 !important;
  background: transparent !important;
}
.btn-outline-secondary.btn-filter {
  color: #6c757d !important;
  border-color: #6c757d !important;
  background: transparent !important;
}

/* Active (filled) states */
.btn-success.btn-filter.active,
.btn-success.btn-filter {
  background-color: #198754 !important;
  border-color: #198754 !important;
  color: #fff !important;
}

.btn-danger.btn-filter.active,
.btn-danger.btn-filter {
  background-color: #dc3545 !important;
  border-color: #dc3545 !important;
  color: #fff !important;
}

.btn-secondary.btn-filter.active,
.btn-secondary.btn-filter {
  background-color: #6c757d !important;
  border-color: #6c757d !important;
  color: #fff !important;
}
.select2-container--open{z-index:2050!important}
</style>

<script>
window.addEventListener('DOMContentLoaded', function () {
  (function ($) {

    // --- 1. Initialization and Input Enhancement ---
    // Initialize Select2 for Asset filter and modal selects
    if ($.fn.select2) { $('.select2').select2({ width: '100%', minimumResultsForSearch: 0 }); }

    // Utility function to convert YYYY-MM-DD string to a Date object
    function parseYMD(s){
      var m = String(s||'').match(/^(\d{4})-(\d{2})-(\d{2})$/);
      if(!m) return null;
      // Note: Month is 0-indexed in JS Date
      return new Date(parseInt(m[1],10), parseInt(m[2],10)-1, parseInt(m[3],10));
    }

    // Initialize Datepicker for filter fields
    $('#fltEndFrom, #fltEndTo').each(function(){
      var $i = $(this);
      if ($i.data('datepicker')) return;
      $i.datepicker({
        format: 'yyyy-mm-dd',
        autoclose: true,
        orientation: 'bottom auto',
        container: 'body'
      });
    });

    // --- 2. Client-Side Filtering Functions ---
    
    // Handler for the 'Apply' (Search) button
    $('#fltApply').off('click.wflt').on('click.wflt', function(e){
      e.preventDefault();
      
      // Capture filter criteria
      var assetTextSel = $('#fltAsset option:selected').text().trim();
      var hasAssetSel  = $('#fltAsset').val() && $('#fltAsset').val() !== '';
      var vend = ($('#fltVendor').val()||'').toLowerCase().trim();
      var dFrom = parseYMD($('#fltEndFrom').val());
      var dTo   = parseYMD($('#fltEndTo').val());

      // Iterate through every table row
      $('#warrantyTable tbody tr').each(function(){
        var $tr = $(this), $td = $tr.find('td');
        if($td.length < 6) return; // Skip invalid rows
        
        // Extract row data from table columns
        var assetText  = $td.eq(1).text().trim();
        var vendorText = ($td.eq(2).text()||'').toLowerCase().trim();
        var endText    = $td.eq(4).text().trim();
        var endDate    = parseYMD(endText);
        var pass = true;

        // Apply all filtering rules (show only if all rules pass)
        if(hasAssetSel && assetTextSel && assetText.indexOf(assetTextSel) === -1) pass = false;
        if(vend && vendorText.indexOf(vend) === -1) pass = false;
        if(dFrom && (!endDate || endDate < dFrom)) pass = false;
        if(dTo   && (!endDate || endDate > dTo))   pass = false;

        // Toggle visibility
        $tr.toggle(pass);
      });
    });

    // Handler for the 'Clear' button
    $('#fltClear').off('click.wflt').on('click.wflt', function(e){
      e.preventDefault();
      // Reset all filter inputs
      if ($.fn.select2) { $('#fltAsset').val('').trigger('change'); } else { $('#fltAsset').val(''); }
      $('#fltVendor').val('');
      $('#fltEndFrom').val('');
      $('#fltEndTo').val('');
      
      // Show all rows
      $('#warrantyTable tbody tr').show();
    });
    
    // --- 3. Modal Functionality ---

    // ADD modal behavior: clear fields when closed
    var $add = $('#modalWarrantyAdd');
    if ($add.length) {
      $add.on('hidden.bs.modal', function(){
        $('#a_asset').val('').trigger('change');
        $('#a_vendor').val('');
        $('#a_start').val('');
        $('#a_end').val('');
      });
    }

    // EDIT modal behavior: populate fields when shown
    $('#modalWarrantyEdit').on('show.bs.modal', function(e){
      var $trigger = $(e.relatedTarget || null);
      if (!$trigger || !$trigger.length) return;

      var id    = $trigger.data('id')    || $trigger.closest('tr').data('id')    || '';
      var asset = $trigger.data('asset') || $trigger.closest('tr').data('asset') || '';
      var vend  = $trigger.data('vendor')|| $trigger.closest('tr').data('vendor')|| '';
      var start = $trigger.data('start') || $trigger.closest('tr').data('start') || '';
      var end   = $trigger.data('end')   || $trigger.closest('tr').data('end')   || '';

      $('#modalWarrantyEdit .modal-title').text('Edit Warranty #' + id);

      // Fill fields
      $('#e_id').val(id);
      $('#e_asset').val(asset).trigger('change'); 
      $('#e_vendor').val(vend || '');
      $('#e_start').val(start || '');
      $('#e_end').val(end || '');

      // Stash id on modal as backup for submit guard
      $(this).data('row-id', id || '');
    });
    
    // Guard: prevent submitting Edit without an id
    $(document).on('submit', '#modalWarrantyEdit form', function(e){
      var id = $('#e_id').val() || $('#modalWarrantyEdit').data('row-id') || '';
      if (!id) {
        e.preventDefault();
        alert('Edit failed: missing warranty ID. Please refresh and try again.');
        return false;
      }
      return true;
    });
    
    // Initialize datepickers for modal inputs when either modal opens
    $('#modalWarrantyAdd, #modalWarrantyEdit').on('shown.bs.modal', function(){
      var $scope = $(this);
      $scope.find('.datepicker').each(function(){
        var $input = $(this);
        // Check if datepicker is already initialized
        if (!$input.data('datepicker')) {
          try {
            $input.datepicker({ format: 'yyyy-mm-dd', autoclose: true, todayHighlight: true });
          } catch(e) { /* fail quietly if plugin is missing */ }
        }
      });
    });

  })(jQuery);
});
</script>