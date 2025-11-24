<?php
// pages/reports.php
// Assumes inc/header.php -> app/init.php already set session & $pdo

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
$isAdmin = isset($_SESSION['user_role']) && strtolower($_SESSION['user_role']) === 'admin';

// Gate: Admins only (remove this block if you want to open Reports to all users)
if (!$isAdmin) {
  ?>
  <div class="content-wrapper">
    <div class="content-header">
      <div class="container-fluid"><h1 class="m-0 text-dark">Reports</h1></div>
    </div>
    <section class="content">
      <div class="container-fluid">
        <div class="alert alert-danger mt-3">You do not have permission to access Reports.</div>
      </div>
    </section>
  </div>
  <?php
  return;
}
?>
<div class="content-wrapper">
  <!-- Header -->
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6"><h1 class="m-0 text-dark">Reports & Exports</h1></div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item active">Reports</li>
          </ol>
        </div>
      </div>
    </div>
  </div>

  <!-- Main -->
  <section class="content">
    <div class="container-fluid">

      <!-- Generate Report -->
      <div class="card card-outline card-primary">
        <div class="card-header"><h3 class="card-title">Generate Report</h3></div>
        <div class="card-body">
          <form class="form" action="app/action/report_export.php" method="get" target="_blank" id="reportForm">
            <div class="form-row">
              <div class="form-group col-md-3">
                <label>Module <span class="text-danger">*</span></label>
                <select name="module" id="module" class="form-control" required>
                  <option value="assets">Assets</option>
                  <option value="bookings">Bookings</option>
                  <option value="warranties">Warranties</option>
                  <option value="maintenance">Maintenance</option>
                </select>
              </div>

              <div class="form-group col-md-3">
                <label>From (Date)</label>
                <input type="date" name="from" class="form-control" placeholder="YYYY-MM-DD">
              </div>

              <div class="form-group col-md-3">
                <label>To (Date)</label>
                <input type="date" name="to" class="form-control" placeholder="YYYY-MM-DD">
              </div>

              <div class="form-group col-md-3">
                <label>Format</label>
                <select name="format" class="form-control">
                  <option value="csv">CSV</option>
                  <option value="pdf">PDF</option>
                </select>
              </div>
            </div>

            <!-- Quick filters -->
            <div class="form-row">
              <div class="form-group col-md-12">
                <label class="d-block">Quick filters (optional)</label>
                <div class="btn-group btn-group-sm" role="group" aria-label="Quick date filters">
                  <button type="button" class="btn btn-outline-secondary" id="btnLast7">Last 7 days</button>
                  <button type="button" class="btn btn-outline-secondary" id="btnMTD">This month</button>                  
                </div>
                <button type="button" class="btn btn-light" id="btnClear">Clear dates</button>
                <small class="form-text text-muted">
                  Presets simply fill the date inputs — you can still edit them manually before exporting.
                </small>
              </div>
            </div>

            <!-- Status -->
            <!-- <div class="form-row">
              <div class="form-group col-md-6">
                <label>Status (optional)</label>
                <select name="status" id="status" class="form-control">
                </select>
                <small class="form-text text-muted">Leave blank to include all statuses.</small>
              </div>
            </div> -->

            <button type="submit" class="btn btn-primary">
              <i class="fas fa-file-export"></i> Export
            </button>
          </form>
        </div>
        <div class="card-footer small text-muted">
          CSV is recommended for large datasets. PDF requires Dompdf in <code>plugins/dompdf/</code>.
        </div>
      </div>

      <!-- Help -->
      <div class="card">
        <div class="card-body">
          <h5>Tips</h5>
          <ul class="mb-0">
            <li>Use date range to narrow results (e.g., Bookings by end date, Maintenance by created date).</li>
            <li>Status is optional — leave it blank to export all.</li>
            <li>You can switch modules and reuse the same date window.</li>
          </ul>
        </div>
      </div>

    </div>
  </section>
</div>

<script>
(function(){
  // Per-module status sets
//   var STATUS = {
//     assets:      ["", "active", "inactive", "retired"],
//     bookings:    ["", "pending", "approved", "returned", "rejected", "in_progress", "active"],
//     warranties:  ["", "active", "expired"],
//     maintenance: ["", "open", "in_progress", "waiting_parts", "resolved", "closed"]
//   };

//   function populateStatus(mod){
//     var s = document.getElementById('status');
//     s.innerHTML = "";
//     (STATUS[mod] || [""]).forEach(function(v){
//       var opt = document.createElement('option');
//       opt.value = v;
//       opt.textContent = (v==="" ? "(all)" : v);
//       s.appendChild(opt);
//     });
//   }

  // Quick filter helpers
  function pad(n){ return String(n).padStart(2,'0'); }
  function fmt(d){ return d.getFullYear() + '-' + pad(d.getMonth()+1) + '-' + pad(d.getDate()); }
  function setRange(from, to){
    var $from = document.querySelector('input[name="from"]');
    var $to   = document.querySelector('input[name="to"]');
    if ($from) $from.value = fmt(from);
    if ($to)   $to.value   = fmt(to);
  }
  function clearRange(){
    var $from = document.querySelector('input[name="from"]');
    var $to   = document.querySelector('input[name="to"]');
    if ($from) $from.value = '';
    if ($to)   $to.value   = '';
  }

  // Wire up module change
//   var modSel = document.getElementById('module');
//   populateStatus(modSel.value);
//   modSel.addEventListener('change', function(){ populateStatus(this.value); });

  // Quick filters
  document.getElementById('btnLast7').addEventListener('click', function(){
    var to = new Date(); var from = new Date(); from.setDate(to.getDate()-6);
    setRange(from, to);
  });
  document.getElementById('btnMTD').addEventListener('click', function(){
    var to = new Date(); var from = new Date(to.getFullYear(), to.getMonth(), 1);
    setRange(from, to);
  });
  document.getElementById('btnClear').addEventListener('click', function(){
    clearRange();
  });
})();
</script>
