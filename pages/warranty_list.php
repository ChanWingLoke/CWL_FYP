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
  $db->exec("UPDATE warranties SET warranty_status='expired' WHERE end_date < CURDATE() AND warranty_status <> 'expired'");
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

        <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#warrantyModal">
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
                    autocomplete="off"
                    required>

              <input type="text"
                    name="end_date" id="w_end"
                    class="form-control datepicker"
                    placeholder="YYYY-MM-DD"
                    inputmode="numeric"
                    pattern="^\d{4}-\d{2}-\d{2}$"
                    title="Use format YYYY-MM-DD"
                    autocomplete="off"
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


    var $modal = $('#warrantyModal');

    $modal
      .on('hidden.bs.modal', function(){
        $('#w_id').val('');
        $('#w_asset').val('').trigger('change');
        $('#w_vendor').val('');
        $('#w_start').val('');
        $('#w_end').val('');
        $modal.find('.modal-title').text('Add Warranty');
      })
      .on('shown.bs.modal', function(){
        var $start = $('#w_start');
        var $end   = $('#w_end');

        try {
          if ($start.data('datepicker')) $start.datepicker('destroy');
          if ($end.data('datepicker'))   $end.datepicker('destroy');
        } catch(e) {}
        $start.datepicker({
          format: 'yyyy-mm-dd',
          autoclose: true,
          todayHighlight: true,
          orientation: 'bottom auto',
          container: '#warrantyModal'
        });
        $end.datepicker({
          format: 'yyyy-mm-dd',
          autoclose: true,
          todayHighlight: true,
          orientation: 'bottom auto',
          container: '#warrantyModal'
        });

        $start.off('changeDate.warr').on('changeDate.warr', function (e) {
          $end.datepicker('setStartDate', e.date);
          var sD = $start.datepicker('getDate');
          var eD = $end.datepicker('getDate');
          if (eD && sD && eD < sD) { $end.datepicker('setDate', sD); }
        });
        $end.off('changeDate.warr').on('changeDate.warr', function (e) {
          $start.datepicker('setEndDate', e.date);
        });

        function normalizeYMD(s) {
          var m = String(s || '').match(/^(\d{4})-(\d{1,2})-(\d{1,2})$/);
          if (!m) return s;
          var y = m[1], mo = ('00'+m[2]).slice(-2), d = ('00'+m[3]).slice(-2);
          return [y, mo, d].join('-');
        }
        $start.add($end).off('blur.warr').on('blur.warr', function(){
          var $i = $(this), norm = normalizeYMD($i.val());
          if (norm !== $i.val()) $i.val(norm);
        });

        var $assetSel = $('#w_asset');
        if ($.fn.select2) {
          if ($assetSel.data('select2')) { try { $assetSel.select2('destroy'); } catch(e){} }
          $assetSel.select2({
            width: '100%',
            tags: false,
            placeholder: 'Select asset',
            minimumResultsForSearch: 0,
            dropdownParent: $modal,
            allowClear: true
          });

          (function(){
            var ModalProto = $.fn.modal && $.fn.modal.Constructor && $.fn.modal.Constructor.prototype;
            var prop = null, saved = null;
            if (ModalProto) {
              if (typeof ModalProto.enforceFocus === 'function') { prop = 'enforceFocus'; }
              else if (typeof ModalProto._enforceFocus === 'function') { prop = '_enforceFocus'; }
              saved = prop ? ModalProto[prop] : null;
            }
            $assetSel.off('select2:open.wasset').on('select2:open.wasset', function(){
              try { if (prop) { ModalProto[prop] = function(){}; } } catch(e){}
              setTimeout(function(){
                var el = document.querySelector('.select2-container--open .select2-search__field');
                if (el) el.focus();
              }, 0);
            });
            $assetSel.off('select2:close.wasset').on('select2:close.wasset', function(){
              try { if (prop && saved) { ModalProto[prop] = saved; } } catch(e){}
            });
          })();
        }
      });

    $(document).on('click', '.btn-edit', function(){
      var id    = $(this).data('id');
      var asset = $(this).data('asset');
      var vend  = $(this).data('vendor');
      var start = $(this).data('start');
      var end   = $(this).data('end');
      $('#w_id').val(id);
      $('#w_asset').val(asset).trigger('change');
      $('#w_vendor').val(vend);
      $('#w_start').val(start);
      $('#w_end').val(end);
      $modal.find('.modal-title').text('Edit Warranty');
      $modal.modal('show');
    });

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
