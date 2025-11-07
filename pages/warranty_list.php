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

// Normalize expired status (optional)
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

      <!-- Top: Server-side filter buttons + Add -->
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

      <!-- Client-side quick filter bar -->
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

      <!-- Table -->
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

    if ($.fn.select2) { $('.select2').select2({ width: '100%', minimumResultsForSearch: 0 }); }

    var $fltDp = $('#fltEndFrom, #fltEndTo');
    $fltDp.each(function(){
      var $i = $(this);
      if ($i.data('datepicker')) return;
      $i.datepicker({
        format: 'yyyy-mm-dd',
        autoclose: true,
        orientation: 'bottom auto',
        container: 'body'
      });
    // === FILTER BAR: Search / Clear handlers (strict by ID) ===
    (function(){
      var $form = $('#warrantyFilterForm');
      $('#fltSearch').off('click.wflt').on('click.wflt', function(e){
        // type=submit already submits; keep this as a safety to avoid preventDefault elsewhere
        if (!$form.length) return;
        // let native submit happen
      });
    // === Client-side filter for warranty table ===
    function parseYMD(s){
      var m = String(s||'').match(/^(\d{4})-(\d{2})-(\d{2})$/);
      if(!m) return null;
      return new Date(parseInt(m[1],10), parseInt(m[2],10)-1, parseInt(m[3],10));
    }
    $('#fltApply').off('click.wflt').on('click.wflt', function(e){
      e.preventDefault();
      var assetTextSel = $('#fltAsset option:selected').text().trim();
      var vend = ($('#fltVendor').val()||'').toLowerCase().trim();
      var dFrom = parseYMD($('#fltEndFrom').val());
      var dTo   = parseYMD($('#fltEndTo').val());
      $('#warrantyTable tbody tr').each(function(){
        var $tr = $(this), $td = $tr.find('td');
        if($td.length < 6) return;
        var assetText  = $td.eq(1).text().trim();
        var vendorText = ($td.eq(2).text()||'').toLowerCase().trim();
        var endText    = $td.eq(4).text().trim();
        var endDate    = parseYMD(endText);
        var pass = true;
        if($('#fltAsset').val()){
          if(assetTextSel && assetText.indexOf(assetTextSel) === -1) pass = false;
        }
        if(vend && vendorText.indexOf(vend) === -1) pass = false;
        if(dFrom && (!endDate || endDate < dFrom)) pass = false;
        if(dTo   && (!endDate || endDate > dTo))   pass = false;
        $tr.toggle(pass);
      });
    });
    $('#fltClear').off('click.wflt').on('click.wflt', function(e){
      e.preventDefault();
      $('#fltAsset').val('').trigger('change');
      $('#fltVendor').val('');
      $('#fltEndFrom').val('');
      $('#fltEndTo').val('');
      $('#warrantyTable tbody tr').show();
    });

      $('#fltClear').off('click.wflt').on('click.wflt', function(e){
        e.preventDefault();
        if ($.fn.select2) { $('#fltAsset').val('').trigger('change'); }
        else { $('#fltAsset').val(''); }
        $('#fltVendor').val('');
        $('#fltEndFrom').val('');
        $('#fltEndTo').val('');
        var curTab = ($form.find('input[name="tab"]').val() || 'active').toString();
        window.location.href = 'index.php?page=warranty_list&tab=' + encodeURIComponent(curTab);
      });
    })();

    });
    // === FILTER BAR: Search / Clear handlers (server-side submission) ===
    (function(){
      var params = new URLSearchParams(window.location.search);
      var curTab = params.get('tab') || 'active';
      var baseUrl = 'index.php?page=warranty_list&tab='+encodeURIComponent(curTab);

      // Find the warranty filter form (the one that contains hidden page=warranty_list)
      var $form = $('form').filter(function(){
        var $f = $(this);
        var $page = $f.find('input[name="page"]');
        return $page.length && $page.val() === 'warranty_list';
      }).first();

      // Submit on Search
      $('#fltApply, .btn-filter').filter(function(){
        return $(this).text().trim().toLowerCase() === 'search';
      }).off('click.wflt').on('click.wflt', function(e){
        e.preventDefault();
        if ($form.length) { $form.trigger('submit'); }
      });

      // Clear inputs and reset to base tab URL
      $('#fltClear, .btn-filter').filter(function(){
        var t = $(this).text().trim().toLowerCase();
        return t === 'clear' || t === 'reset';
      }).off('click.wflt').on('click.wflt', function(e){
        e.preventDefault();
        $('#fltAsset').val('').trigger('change');
        $('#fltVendor').val('');
        $('#fltEndFrom').val('');
        $('#fltEndTo').val('');
        window.location.href = baseUrl;
      });
    })();


    // === FILTER BAR: Client-side Search / Clear (no page reload) ===
    (function(){
      function parseYMD(s){
        var m = String(s||'').match(/^(\d{4})-(\d{2})-(\d{2})$/);
        if(!m) return null;
        return new Date(parseInt(m[1],10), parseInt(m[2],10)-1, parseInt(m[3],10));
      }

      $('#fltApply').off('click.wflt').on('click.wflt', function(e){
        e.preventDefault();
        var assetTextSel = $('#fltAsset option:selected').text().trim();
        var hasAssetSel  = $('#fltAsset').val() && $('#fltAsset').val() !== '';
        var vend = ($('#fltVendor').val()||'').toLowerCase().trim();
        var dFrom = parseYMD($('#fltEndFrom').val());
        var dTo   = parseYMD($('#fltEndTo').val());

        $('#warrantyTable tbody tr').each(function(){
          var $tr = $(this), $td = $tr.find('td');
          if($td.length < 6) return;
          var assetText  = $td.eq(1).text().trim();
          var vendorText = ($td.eq(2).text()||'').toLowerCase().trim();
          var endText    = $td.eq(4).text().trim();
          var endDate    = parseYMD(endText);
          var pass = true;
          if(hasAssetSel && assetTextSel && assetText.indexOf(assetTextSel) === -1) pass = false;
          if(vend && vendorText.indexOf(vend) === -1) pass = false;
          if(dFrom && (!endDate || endDate < dFrom)) pass = false;
          if(dTo   && (!endDate || endDate > dTo))   pass = false;
          $tr.toggle(pass);
        });
      });

      $('#fltClear').off('click.wflt').on('click.wflt', function(e){
        e.preventDefault();
        if ($.fn.select2) { $('#fltAsset').val('').trigger('change'); } else { $('#fltAsset').val(''); }
        $('#fltVendor').val('');
        $('#fltEndFrom').val('');
        $('#fltEndTo').val('');
        $('#warrantyTable tbody tr').show();
      });
    })();

  })(jQuery);
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function(){
  var $ = window.jQuery;
  if (!$) return;

  // ADD modal behavior
  var $add = $('#modalWarrantyAdd');
  if ($add.length) {
    $add.on('hidden.bs.modal', function(){
      $('#a_asset').val('').trigger('change');
      $('#a_vendor').val('');
      $('#a_start').val('');
      $('#a_end').val('');
    });
  }

  // EDIT button -> open Edit modal with data
    var id    = $btn.data('id') || $btn.closest('tr').data('id') || $btn.closest('[data-id]').data('id') || '';
  var asset = $btn.data('asset') || $btn.closest('tr').data('asset') || '';
  var vend  = $btn.data('vendor') || $btn.closest('tr').data('vendor') || '';
  var start = $btn.data('start') || $btn.closest('tr').data('start') || '';
  var end   = $btn.data('end')   || $btn.closest('tr').data('end')   || '';

  // Stash id on the modal as extra safety
  var     // Populate fields
  $('#e_id').val(id);
  $('#e_asset').val(asset).trigger('change');
  $('#e_vendor').val(vend || '');
  $('#e_start').val(start || '');
  $('#e_end').val(end || '');

  });

// Guard: prevent submitting Edit without an id
$(document).on('submit', '#modalWarrantyEdit form', function(e){
  var id = $('#e_id').val() || $('#modalWarrantyEdit').data('row-id') || '';
  if (!id) {
    e.preventDefault();
    alert('Edit failed: missing warranty ID. Please refresh the page and try again.');
    return false;
  }
  return true;
});
    var asset = $(this).data('asset');
    var vend  = $(this).data('vendor');
    var start = $(this).data('start');
    var end   = $(this).data('end');

    $('#e_id').val(id);
    $('#e_asset').val(asset).trigger('change');
    $('#e_vendor').val(vend || '');
    $('#e_start').val(start || '');
    $('#e_end').val(end || '');

    $('#modalWarrantyEdit').modal('show');
  });
});
</script>

<?php include 'warranty_modal_add.php'; ?>
<?php include 'warranty_modal_edit.php'; ?>



<script>
document.addEventListener('DOMContentLoaded', function(){
  var $ = window.jQuery;
  if (!$) return;

  // Initialize bootstrap-datepicker on open for both modals
  $('#modalWarrantyAdd, #modalWarrantyEdit').on('shown.bs.modal', function(){
    var $scope = $(this);
    // Only init once per input
    $scope.find('.datepicker').each(function(){
      var $input = $(this);
      if (!$input.data('datepicker')) {
        try {
          $input.datepicker({
            format: 'yyyy-mm-dd',
            autoclose: true,
            todayHighlight: true
          });
        } catch(e) { /* plugin missing? fail quietly */ }
      }
    });
  });
});
</script>



<script>
document.addEventListener('DOMContentLoaded', function(){
  var $ = window.jQuery;
  if (!$) return;
  $('#modalWarrantyAdd, #modalWarrantyEdit').on('shown.bs.modal', function(){
    var $scope = $(this);
    $scope.find('.datepicker').each(function(){
      var $input = $(this);
      if (!$input.data('datepicker')) {
        try {
          $input.datepicker({ format:'yyyy-mm-dd', autoclose:true, todayHighlight:true });
        } catch(e) {}
      }
    });
  });
});
</script>


<script>
document.addEventListener('DOMContentLoaded', function(){
  var $ = window.jQuery;
  if (!$) return;

  // When the Edit modal is about to be shown, pull data from the triggering element
  $('#modalWarrantyEdit').on('show.bs.modal', function(e){
    var $trigger = $(e.relatedTarget || null);
    if (!$trigger || !$trigger.length) return;

    var id    = $trigger.data('id')    || $trigger.closest('tr').data('id')    || '';
    var asset = $trigger.data('asset') || $trigger.closest('tr').data('asset') || '';
    var vend  = $trigger.data('vendor')|| $trigger.closest('tr').data('vendor')|| '';
    var start = $trigger.data('start') || $trigger.closest('tr').data('start') || '';
    var end   = $trigger.data('end')   || $trigger.closest('tr').data('end')   || '';

    // Fill fields
    $('#e_id').val(id);
    $('#e_asset').val(asset).trigger('change');
    $('#e_vendor').val(vend || '');
    $('#e_start').val(start || '');
    $('#e_end').val(end || '');

    // Stash id on modal as backup for submit guard
    $(this).data('row-id', id || '');
  });

  // Fallback: clicking any element that targets the edit modal should not navigate
  $(document).on('click', '[data-target="#modalWarrantyEdit"]', function(ev){
    ev.preventDefault();
    // Let Bootstrap open the modal; 'show.bs.modal' will populate fields
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

  // Initialize datepickers when either modal opens
  $('#modalWarrantyAdd, #modalWarrantyEdit').on('shown.bs.modal', function(){
    var $scope = $(this);
    $scope.find('.datepicker').each(function(){
      var $input = $(this);
      if (!$input.data('datepicker')) {
        try {
          $input.datepicker({ format: 'yyyy-mm-dd', autoclose: true, todayHighlight: true });
        } catch(e) {}
      }
    });
  });
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function(){
  var $ = window.jQuery;
  if (!$) return;

  // Direct, simple handlers for Search / Clear
  $('#fltApply').off('click.fix').on('click.fix', function(e){
    e.preventDefault();
    // If you have a server-side filter form, submit it; else trigger client-side filter
    // Here we just trigger the existing client-side routine if present
    var asset = ($('#fltAsset').val() || '').toString().toLowerCase();
    var vend  = ($('#fltVendor').val() || '').toString().toLowerCase();
    var d1    = $('#fltEndFrom').val();
    var d2    = $('#fltEndTo').val();
    function parseYMD(s){
      var m = String(s||'').match(/^(\d{4})-(\d{2})-(\d{2})$/);
      if(!m) return null;
      return new Date(parseInt(m[1],10), parseInt(m[2],10)-1, parseInt(m[3],10));
    }
    var dt1 = parseYMD(d1), dt2 = parseYMD(d2);

    $('#warrantyTable tbody tr').each(function(){
      var $tr = $(this);
      var a = ($tr.data('asset') || '').toString().toLowerCase();
      var v = ($tr.data('vendor')|| '').toString().toLowerCase();
      var e = ($tr.data('end')   || '').toString();

      var ok = true;
      if (asset && a !== asset) ok = false;
      if (vend && v.indexOf(vend) === -1) ok = false;
      if (ok && (dt1 || dt2)) {
        var de = parseYMD(e);
        if (!de) ok = false;
        if (ok && dt1 && de < dt1) ok = false;
        if (ok && dt2 && de > dt2) ok = false;
      }
      $tr.toggle(ok);
    });
  });

  $('#fltClear').off('click.fix').on('click.fix', function(e){
    e.preventDefault();
    if ($.fn.select2) { $('#fltAsset').val('').trigger('change'); } else { $('#fltAsset').val(''); }
    $('#fltVendor').val('');
    $('#fltEndFrom').val('');
    $('#fltEndTo').val('');
    $('#warrantyTable tbody tr').show();
  });
});
</script>
