<?php
// pages/reports.php
// Admin-only report generation interface
// Assumes inc/header.php -> app/init.php already set session & $pdo

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$isAdmin = isset($_SESSION['user_role']) && strtolower($_SESSION['user_role']) === 'admin';

// Gate: Admins only
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

  <section class="content">
    <div class="container-fluid">

      <div class="card card-outline card-primary">
        <div class="card-header">
          <h3 class="card-title"><i class="fas fa-file-alt mr-1"></i> Generate Report</h3>
        </div>
        <div class="card-body">
          <form class="form" action="app/action/report_export.php" method="get" target="_blank" id="reportForm">
            <div class="form-row">
              <div class="form-group col-md-3">
                <label for="module">Module <span class="text-danger">*</span></label>
                <select name="module" id="module" class="form-control" required>
                  <option value="assets">Assets</option>
                  <option value="bookings">Bookings</option>
                  <option value="warranties">Warranties</option>
                  <option value="maintenance">Maintenance</option>
                </select>
              </div>

              <div class="form-group col-md-3">
                <label for="from">From (Date)</label>
                <input type="date" name="from" id="from" class="form-control" placeholder="YYYY-MM-DD">
              </div>

              <div class="form-group col-md-3">
                <label for="to">To (Date)</label>
                <input type="date" name="to" id="to" class="form-control" placeholder="YYYY-MM-DD">
              </div>

              <div class="form-group col-md-3">
                <label for="format">Format</label>
                <select name="format" id="format" class="form-control">
                  <option value="csv">CSV (Spreadsheet)</option>
                  <option value="pdf">PDF (Document)</option>
                </select>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group col-md-12">
                <label class="d-block">Quick filters (optional)</label>
                <div class="btn-group btn-group-sm" role="group" aria-label="Quick date filters">
                  <button type="button" class="btn btn-outline-secondary" id="btnLast7">Last 7 days</button>
                  <button type="button" class="btn btn-outline-secondary" id="btnMTD">This month</button>                  
                </div>
                <button type="button" class="btn btn-light btn-sm ml-2" id="btnClear">
                  <i class="fas fa-times"></i> Clear dates
                </button>
                <small class="form-text text-muted mt-2">
                  Presets simply fill the date inputs above. You can still edit them manually before exporting.
                </small>
              </div>
            </div>

            <hr>

            <button type="submit" class="btn btn-primary">
              <i class="fas fa-file-export mr-1"></i> Export Report
            </button>
          </form>
        </div>
        
        <div class="card-footer small text-muted">
          <i class="fas fa-info-circle"></i> <strong>Note:</strong> CSV format is recommended for large datasets. PDF export requires the Dompdf plugin.
        </div>
      </div>

      <div class="card">
        <div class="card-body">
          <h5><i class="fas fa-lightbulb text-warning mr-1"></i> Tips</h5>
          <ul class="mb-0 pl-3">
            <li>Use the <strong>Date Range</strong> to narrow results (e.g., Bookings by end date, Maintenance by created date).</li>
            <li>Leaving the dates blank will attempt to export <strong>all records</strong> for that module.</li>
            </ul>
        </div>
      </div>

    </div>
  </section>
</div>

<script>
(function(){
  // --- Date Formatting Helpers ---
  function pad(n){ return String(n).padStart(2,'0'); }
  
  // Format date as YYYY-MM-DD for input[type="date"]
  function fmt(d){ 
    return d.getFullYear() + '-' + pad(d.getMonth()+1) + '-' + pad(d.getDate()); 
  }

  // Set values for the 'from' and 'to' inputs
  function setRange(from, to){
    var $from = document.getElementById('from');
    var $to   = document.getElementById('to');
    if ($from) $from.value = fmt(from);
    if ($to)   $to.value   = fmt(to);
  }

  // Clear the date inputs
  function clearRange(){
    var $from = document.getElementById('from');
    var $to   = document.getElementById('to');
    if ($from) $from.value = '';
    if ($to)   $to.value   = '';
  }

  // --- Event Listeners ---
  
  // "Last 7 days" button
  var btnLast7 = document.getElementById('btnLast7');
  if (btnLast7) {
    btnLast7.addEventListener('click', function(){
      var to = new Date(); 
      var from = new Date(); 
      from.setDate(to.getDate() - 6); // Go back 6 days + today = 7 days range
      setRange(from, to);
    });
  }

  // "This month" button
  var btnMTD = document.getElementById('btnMTD');
  if (btnMTD) {
    btnMTD.addEventListener('click', function(){
      var to = new Date(); 
      var from = new Date(to.getFullYear(), to.getMonth(), 1); // 1st day of current month
      setRange(from, to);
    });
  }

  // "Clear dates" button
  var btnClear = document.getElementById('btnClear');
  if (btnClear) {
    btnClear.addEventListener('click', function(){
      clearRange();
    });
  }

})();
</script>