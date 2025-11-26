<?php
// pages/bookings.php

$db = null;
if (isset($pdo) && $pdo) {
  $db = $pdo;
} elseif (isset($obj) && isset($obj->pdo)) {
  $db = $obj->pdo;
}
if (!$db) {
  die('<div style="padding:16px" class="alert alert-danger">Database connection not found. Ensure $pdo or $obj->pdo is available in app/init.php.</div>');
}

// ----------------------------------------------------
// Determine the correct assets table name dynamically
// ----------------------------------------------------
$productTable = 'product';
try {
  $db->query("SELECT 1 FROM `product` LIMIT 1");
} catch (Throwable $e) {
  foreach (['products','tbl_product','items','assets'] as $t) {
    try { $db->query("SELECT 1 FROM `{$t}` LIMIT 1"); $productTable = $t; break; }
    catch (Throwable $ignored) {}
  }
}

$flash = ['type' => null, 'msg' => null];

// --------------------------------------------
// Handle submission (strict YYYY-MM-DD format)
// --------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['do_booking'])) {
  try {
    // 1. Input Retrieval
    $asset_id  = (int)($_POST['asset_id'] ?? 0);
    $user_id   = (int)($_POST['user_id'] ?? 0);
    $expected_user_id = (int)($_POST['expected_user_id'] ?? 0); // From JavaScript/Scan logic
    $start_raw = trim($_POST['start_time'] ?? '');
    $end_raw   = trim($_POST['end_time'] ?? '');
    $notes     = trim($_POST['notes'] ?? '');

    // 2. Core Validation
    if (!$asset_id || !$user_id) {
      throw new Exception('Please select both an asset and a user.');
    }
    // Check if the scanned ID (if provided) matches the selected user ID
    if ($expected_user_id && $expected_user_id !== $user_id) {
      throw new Exception('Scanned ID does not match the selected user.');
    }

    // 3. Date Validation (Format and Validity)
    $re = '/^\d{4}-\d{2}-\d{2}$/';
    if (!preg_match($re, $start_raw) || !preg_match($re, $end_raw)) {
      throw new Exception('Dates must use format YYYY-MM-DD (e.g., 2025-06-07).');
    }

    // Check if dates are real calendar dates (e.g., prevents 2025-02-30)
    $valid_date = function (string $s): bool {
      [$y,$m,$d] = array_map('intval', explode('-', $s));
      return checkdate($m, $d, $y);
    };
    if (!$valid_date($start_raw) || !$valid_date($end_raw)) {
      throw new Exception('One or both dates are not valid calendar dates.');
    }

    // Set precise boundaries for DB conflict checking (start of start day, end of end day)
    $start = $start_raw . ' 00:00:00';
    $end   = $end_raw   . ' 23:59:59';

    if ($start >= $end) {
      throw new Exception('End date must be after start date.');
    }

    // 4. Maintenance Conflict Check
    $blockStatuses = ['open','in_progress','resolved']; // Maintenance statuses that block booking
    $ph = implode(',', array_fill(0, count($blockStatuses), '?')); // Placeholder string (?, ?, ?)

    $msql = "SELECT 1
            FROM maintenance_orders
            WHERE asset_id = ?
              AND status IN ($ph)
            LIMIT 1";
    $mstmt = $db->prepare($msql);
    $mstmt->execute(array_merge([$asset_id], $blockStatuses));

    if ($mstmt->fetchColumn()) {
      throw new Exception('This asset is currently under maintenance and cannot be booked until the maintenance is closed.');
    }

    // 5. Booking Conflict Check
    $sql = "SELECT COUNT(*) FROM bookings
            WHERE asset_id = :asset_id
              AND status IN ('pending','approved')
              AND start_time <= :end_time AND end_time >= :start_time"; // Date range overlap logic
    $stmt = $db->prepare($sql);
    $stmt->execute([
      ':asset_id'   => $asset_id,
      ':start_time' => $start,
      ':end_time'   => $end
    ]);

    if ((int)$stmt->fetchColumn() > 0) {
      throw new Exception('This asset is already booked for the selected date range.');
    }

    // 6. Insertion
    $ins = $db->prepare("INSERT INTO bookings (asset_id, user_id, start_time, end_time, notes, status)
                         VALUES (:asset_id, :user_id, :start_time, :end_time, :notes, 'pending')");
    $ins->execute([
      ':asset_id'   => $asset_id,
      ':user_id'    => $user_id,
      ':start_time' => $start,
      ':end_time'   => $end,
      ':notes'      => $notes
    ]);

    $flash = ['type' => 'success', 'msg' => 'Booking submitted! Status: pending.'];
  } catch (Throwable $e) {
    $flash = ['type' => 'danger', 'msg' => $e->getMessage()];
  }
}

// -------------------------
// Preload selects and list
// -------------------------
try {
  // Fetch list of assets for the dropdown
  $assets = $db->query("SELECT id, product_name FROM `{$productTable}` ORDER BY product_name ASC")
               ->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) { $assets = []; $flash = ['type'=>'danger','msg'=>'DB error: '.$e->getMessage()]; }

try {
  // Fetch list of users for the dropdown
  $users  = $db->query("SELECT id, username FROM user ORDER BY username ASC")
               ->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) { $users = []; $flash = ['type'=>'danger','msg'=>'DB error: '.$e->getMessage()]; }

try {
  // Fetch upcoming bookings list (used in the table on the left)
  $upcoming = $db->query("
    SELECT b.id, b.asset_id, b.user_id, b.start_time, b.end_time, b.status, b.notes,
           p.product_name AS asset_name,
           u.username     AS user_name
    FROM bookings b
    JOIN `{$productTable}` p ON p.id = b.asset_id
    JOIN user u             ON u.id = b.user_id
    WHERE b.end_time >= NOW()
    ORDER BY b.start_time ASC
  ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) { $upcoming = []; $flash = ['type'=>'danger','msg'=>'DB error: '.$e->getMessage()]; }
?>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="m-0 text-dark">Bookings</h1>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item active">Bookings</li>
          </ol>
        </div>
      </div>

      <?php if ($flash['type']): ?>
        <div class="alert alert-<?= htmlspecialchars($flash['type']) ?> mb-0">
          <?= htmlspecialchars($flash['msg']) ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <section class="content">
    <div class="container-fluid">
      <div class="row">

        <div class="col-12">
          <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
              <h3 class="card-title mb-0"><b>Bookings Calendar</b></h3>
              <div class="status-legend">
                </div>
            </div>
            <div class="card-body">
              <div id="calendar"></div>
            </div>
          </div>
        </div>

        <div class="modal fade" id="bookingModal" tabindex="-1" role="dialog" aria-hidden="true">
          <div class="modal-dialog modal-md modal-dialog-centered" role="document">
            <div class="modal-content">
              <div class="modal-header py-2">
                <h5 class="modal-title">Booking Details</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"
                        style="outline:none;border:0;background:transparent;font-size:28px;line-height:1;">
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>
              <div class="modal-body" id="bookingModalBody"></div>
              <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Close</button>
              </div>
            </div>
          </div>
        </div>

        <div class="col-lg-8">
          <div class="card">
            <div class="card-header d-flex align-items-center">
              <h3 class="card-title mb-0"><b>Upcoming Reservations</b></h3>
            </div>
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table table-striped mb-0">
                  <thead class="thead-light">
                    <tr>
                      <th>#</th>
                      <th>Asset</th>
                      <th>User</th>
                      <th>Start</th>
                      <th>End</th>
                      <th>Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (!empty($upcoming)): foreach ($upcoming as $i => $r): ?>
                      <tr>
                        <td><?= $i+1 ?></td>
                        <td><?= htmlspecialchars($r['asset_name']) ?></td>
                        <td><?= htmlspecialchars($r['user_name']) ?></td>
                        <td><?= htmlspecialchars(date('Y-m-d', strtotime($r['start_time']))) ?></td>
                        <td><?= htmlspecialchars(date('Y-m-d', strtotime($r['end_time']))) ?></td>
                        <td>
                          <?php
                            // Logic to determine the correct badge color based on status
                            $cls = [
                              'pending'  => 'badge-warning',
                              'approved' => 'badge-success',
                              'rejected' => 'badge-danger',
                              'returned' => 'badge-secondary'
                            ][$r['status']] ?? 'badge-light';
                          ?>
                          <span class="badge <?= $cls ?>"><?= ucfirst($r['status']) ?></span>
                        </td>
                      </tr>
                    <?php endforeach; else: ?>
                      <tr><td colspan="6" class="text-center text-muted">No upcoming reservations.</td></tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <div class="col-lg-4">
          <div class="card">
            <div class="card-header">
              <h3 class="card-title mb-0"><b>Booking Form</b></h3>
            </div>
            <div class="card-body">
              <form method="post" autocomplete="off" id="booking-form">
                <input type="hidden" name="do_booking" value="1">
                <input type="hidden" name="expected_user_id" value=""> <div class="form-group">
                  <label>Asset</label>
                  <select name="asset_id" class="form-control select2" required>
                    <option value="">Select asset</option>
                    <?php foreach ($assets as $a): ?>
                      <option value="<?= (int)$a['id'] ?>"><?= htmlspecialchars($a['product_name']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <div class="form-group">
                  <label>User</label>
                  <select name="user_id" class="form-control select2" required>
                    <option value="">Select user</option>
                    <?php foreach ($users as $u): ?>
                      <option value="<?= (int)$u['id'] ?>"><?= htmlspecialchars($u['username']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <div class="form-group">
                  <label>Date</label>
                  <div class="input-daterange input-group" id="bookingDatepicker">
                    <input
                      type="text"
                      name="start_time"
                      id="start_time"
                      class="form-control datepicker"
                      placeholder="YYYY-MM-DD"
                      inputmode="numeric"
                      pattern="^\d{4}-\d{2}-\d{2}$"
                      title="Use format YYYY-MM-DD"
                      autocomplete="off"
                      required
                    >
                    <div class="input-group-append input-group-prepend">
                      <span class="input-group-text">to</span>
                    </div>
                    <input
                      type="text"
                      name="end_time"
                      id="end_time"
                      class="form-control datepicker"
                      placeholder="YYYY-MM-DD"
                      inputmode="numeric"
                      pattern="^\d{4}-\d{2}-\d{2}$"
                      title="Use format YYYY-MM-DD"
                      autocomplete="off"
                      required
                    >
                  </div>
                  <small class="text-muted">Format: <code>YYYY-MM-DD</code></small>
                </div>

                <div class="form-group">
                  <label>Notes</label>
                  <textarea name="notes" class="form-control" rows="2" placeholder="Enter additional notes..."></textarea>
                </div>

                <button type="submit" name="do_booking" class="btn btn-primary btn-block">Submit</button>
              </form>
            </div>
          </div>
        </div>

      </div></div>
  </section>
</div>

<style>
/* CSS for status badges */
.badge-status {
  font-size: 0.9rem;
  font-weight: 600;
  padding: 6px 10px;
  border-radius: 6px;
  margin-right: 6px;
  display: inline-block;
  vertical-align: middle;
  color: #fff !important;
}
.badge-status.pending  { background-color: #f39c12; } /* orange */
.badge-status.approved { background-color: #28a745; } /* green */
.badge-status.rejected { background-color: #dc3545; } /* red */
.badge-status.returned { background-color: #6c757d; } /* grey */
</style>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/main.min.css">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
  var el = document.getElementById('calendar');
  if (!el) return;
  if (!window.FullCalendar) {
    el.innerHTML = '<div class="text-danger">FullCalendar script not loaded.</div>';
    return;
  }
  var calendar = new FullCalendar.Calendar(el, {
    initialView: 'dayGridMonth',
    height: 'auto',
    headerToolbar: {
      left: 'prev,next today',
      center: 'title',
      right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
    },
    events: {
      // Endpoint that serves booking data
      url: 'app/action/booking_events.php',
      // By default, only show approved bookings on the calendar
      extraParams: { status: 'approved' }, 
      failure: function() {
        el.insertAdjacentHTML('beforeend','<div class="text-danger mt-2">Could not load bookings.</div>');
      }
    }
  });
  calendar.render();
});
</script>

<script>
window.addEventListener('load', function () {
  if (!window.jQuery) return;

  // Select2, if present (for Asset and User dropdowns)
  if ($.fn.select2) {
    $('.select2').select2();
  }

  // Datepickers (YYYY-MM-DD) with simple range constraints
  if ($.fn.datepicker) {
    // Re-initialize datepicker to ensure consistent settings
    $('.datepicker').datepicker('destroy').datepicker({
      format: 'yyyy-mm-dd',
      autoclose: true,
      todayHighlight: true
    });

    // Enforce Start Date <= End Date logic
    $('#start_time').on('changeDate', function (e) {
      $('#end_time').datepicker('setStartDate', e.date);
      var s = $('#start_time').val();
      var eVal = $('#end_time').val();
      // Clear end date if it becomes chronologically invalid
      if (eVal && eVal < s) { $('#end_time').val(''); }
    });
    $('#end_time').on('changeDate', function (e) {
      $('#start_time').datepicker('setEndDate', e.date);
    });
  }
});
</script>


<div class="modal fade" id="scanUserModal" tabindex="-1" role="dialog" aria-labelledby="scanUserModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-sm" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="scanUserModalLabel">Please scan User ID to proceed</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="alert alert-info" id="scanPrompt" style="margin-bottom:8px">Waiting for barcode... Press Enter to finish.</div>
        <div class="small text-muted">Tip: Your scanner should send an <strong>Enter</strong> after the digits.</div>
        <div class="alert alert-danger d-none" id="scanError" style="margin-top:8px"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
      </div>
    </div>
  </div>
</div>


<script>
(function() {
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  function init() {
    var form = document.getElementById('booking-form');
    // Fallback to find the form if ID is missing (less reliable)
    if (!form) {
      var forms = document.getElementsByTagName('form');
      for (var i=0;i<forms.length;i++) {
        if ((forms[i].getAttribute('method')||'').toLowerCase()==='post') { form = forms[i]; break; }
      }
    }
    if (!form) return;

    // Ensure necessary hidden fields exist for the scanning process
    var userSelect = form.querySelector('select[name="user_id"]');
    var expectedHidden = form.querySelector('input[name="expected_user_id"]');
    if (!expectedHidden) {
      expectedHidden = document.createElement('input');
      expectedHidden.type = 'hidden';
      expectedHidden.name = 'expected_user_id';
      form.appendChild(expectedHidden);
    }

    form.addEventListener('submit', function(e) {
      // Check if the confirmation flag is set (meaning scan succeeded)
      if (!form.__scanConfirmed) {
        e.preventDefault(); // Stop default submission

        // Store the currently selected user ID before scanning
        var selectedVal = userSelect ? (userSelect.value || '').trim() : '';
        expectedHidden.value = selectedVal;

        // Open the modal and pass a callback function to run on successful scan
        openScanModal(function(scannedId, detach) {
          // 1. Re-check: Does the scanned ID match the previously selected user? (If selected, it must match)
          var selectedNow = userSelect ? (userSelect.value || '').trim() : expectedHidden.value;
          if (selectedNow && String(scannedId) !== String(selectedNow)) {
            showScanError('Scanned ID does not match the selected user.');
            return; // Stay in modal for re-scan
          }

          // 2. Validate/Set User ID
          var ok = false;
          if (userSelect) {
            // Check if the scanned ID exists in the dropdown options
            for (var i=0; i<userSelect.options.length; i++) {
              if (String(userSelect.options[i].value) === String(scannedId)) {
                userSelect.value = scannedId; 
                ok = true; 
                if ($.fn.select2) $(userSelect).trigger('change');
                break;
              }
            }
          }
          
          // 3. Confirm and Submit
          form.__scanConfirmed = true; // Set flag to allow submission next time
          form.noValidate = true;     // Disable HTML5 validation on resubmit
          detach();                   // Remove the keydown listener
          hideScanModal();
          form.submit();              // Programmatically submit the form
        });
      }
    });

    // Prevent direct button clicks from bypassing the submit handler
    var submitBtns = form.querySelectorAll('button[type="submit"],input[type="submit"]');
    for (var i=0;i<submitBtns.length;i++) {
      submitBtns[i].addEventListener('click', function(ev) {
        if (!form.__scanConfirmed) ev.stopImmediatePropagation();
      }, true);
    }
  }

  // --- Modal Control Functions ---
  function openScanModal(onDone) {
    var modal = document.getElementById('scanUserModal');
    var err = document.getElementById('scanError');
    if (err) { err.textContent = ''; err.classList.add('d-none'); }

    function show() {
      if (typeof $ !== 'undefined' && typeof $(modal).modal === 'function') $(modal).modal('show');
      else { modal.style.display = 'block'; modal.classList.add('show'); }
    }
    function hide() {
      if (typeof $ !== 'undefined' && typeof $(modal).modal === 'function') $(modal).modal('hide');
      else { modal.style.display = 'none'; modal.classList.remove('show'); }
    }

    // Barcode Listener Logic
    var buffer = '';
    var lastTs = 0;
    var timeoutMs = 100; // Time window to capture sequential keystrokes

    function keyHandler(ev) {
      if (ev.key === 'Shift' || ev.key === 'Alt' || ev.key === 'Control') return;
      var now = Date.now();
      // Reset buffer if keystroke delay exceeds timeout (scanner sends rapid input)
      if (now - lastTs > timeoutMs) buffer = ''; 
      lastTs = now;

      if (ev.key === 'Enter') {
        ev.preventDefault();
        var value = buffer.trim();
        buffer = '';
        if (!value) { showScanError('No data received. Please scan again.'); return; }
        onDone(value, detach); // Execute callback with scanned value
        return;
      }
      if (ev.key && ev.key.length === 1) buffer += ev.key; // Accumulate keystrokes
    }

    function detach() { window.removeEventListener('keydown', keyHandler, true); }
    function showScanError(msg) {
      var e = document.getElementById('scanError');
      if (e) { e.textContent = msg; e.classList.remove('d-none'); }
    }

    // Start listening for barcode input (high priority capture: true)
    window.addEventListener('keydown', keyHandler, true);
    show();

    // Export helpers for use outside the modal logic
    window.hideScanModal = hide;
    window.showScanError = showScanError;
  }

  function hideScanModal() {
    var modal = document.getElementById('scanUserModal');
    if (!modal) return;
    if (typeof $ !== 'undefined' && typeof $(modal).modal === 'function') $(modal).modal('hide');
    else { modal.style.display = 'none'; modal.classList.remove('show'); }
  }
})();
</script>