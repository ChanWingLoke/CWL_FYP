<?php
// pages/bookings.php

// -------------------------------
// Get a PDO handle used by the app
// -------------------------------
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
    $asset_id  = (int)($_POST['asset_id'] ?? 0);
    $user_id   = (int)($_POST['user_id'] ?? 0);
    $start_raw = trim($_POST['start_time'] ?? '');
    $end_raw   = trim($_POST['end_time'] ?? '');
    $notes     = trim($_POST['notes'] ?? '');

    $re = '/^\d{4}-\d{2}-\d{2}$/';
    if (!preg_match($re, $start_raw) || !preg_match($re, $end_raw)) {
      throw new Exception('Dates must use format YYYY-MM-DD (e.g., 2025-06-07).');
    }

    $valid_date = function (string $s): bool {
      [$y,$m,$d] = array_map('intval', explode('-', $s));
      return checkdate($m, $d, $y);
    };
    if (!$valid_date($start_raw) || !$valid_date($end_raw)) {
      throw new Exception('One or both dates are not valid calendar dates.');
    }

    $start = $start_raw . ' 00:00:00';
    $end   = $end_raw   . ' 23:59:59';

    if (!$asset_id || !$user_id) {
      throw new Exception('Please select both an asset and a user.');
    }
    if ($start >= $end) {
      throw new Exception('End date must be after start date.');
    }

    // Conflict check stays as a backstop
    $sql = "SELECT COUNT(*) FROM bookings
            WHERE asset_id = :asset_id
              AND status IN ('pending','approved')
              AND NOT (end_time <= :start_time OR start_time >= :end_time)";
    $stmt = $db->prepare($sql);
    $stmt->execute([
      ':asset_id'   => $asset_id,
      ':start_time' => $start,
      ':end_time'   => $end
    ]);
    if ((int)$stmt->fetchColumn() > 0) {
      throw new Exception('This asset is already booked for the selected date range.');
    }

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
  $assets = $db->query("SELECT id, product_name FROM `{$productTable}` ORDER BY product_name ASC")
               ->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) { $assets = []; $flash = ['type'=>'danger','msg'=>'DB error: '.$e->getMessage()]; }

try {
  $users  = $db->query("SELECT id, username FROM user ORDER BY username ASC")
               ->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) { $users = []; $flash = ['type'=>'danger','msg'=>'DB error: '.$e->getMessage()]; }

try {
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

        <!-- Calendar Card -->
        <div class="col-12">
          <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
              <h3 class="card-title mb-0"><b>Bookings Calendar</b></h3>
              <div class="status-legend">
                <span class="badge-status pending">Pending</span>
                <span class="badge-status approved">Approved</span>
                <span class="badge-status rejected">Rejected</span>
                <span class="badge-status returned">Returned</span>
              </div>

            </div>
            <div class="card-body">
              <div id="calendar"></div>
            </div>
          </div>
        </div>

        <!-- Event detail modal -->
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
              <div class="modal-body" id="bookingModalBody"><!-- filled by JS --></div>
              <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Close</button>
              </div>
            </div>
          </div>
        </div>

        <!-- Left: Upcoming Reservations -->
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

        <!-- Right: Booking Form -->
        <div class="col-lg-4">
          <div class="card">
            <div class="card-header">
              <h3 class="card-title mb-0"><b>Booking Form</b></h3>
            </div>
            <div class="card-body">
              <form method="post" autocomplete="off">
                <div class="form-group">
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
                  <div class="d-flex">
                    <input type="text"
                           name="start_time"
                           id="start_time"
                           class="form-control mr-2"
                           placeholder="Start (yyyy-mm-dd)"
                           inputmode="numeric"
                           pattern="^\d{4}-\d{2}-\d{2}$"
                           title="Use format YYYY-MM-DD, e.g. 2025-06-07"
                           required>

                    <input type="text"
                           name="end_time"
                           id="end_time"
                           class="form-control"
                           placeholder="End (yyyy-mm-dd)"
                           inputmode="numeric"
                           pattern="^\d{4}-\d{2}-\d{2}$"
                           title="Use format YYYY-MM-DD, e.g. 2025-06-08"
                           required>
                  </div>
                  <small class="text-muted">Format: <code>YYYY-MM-DD</code> (e.g. 2025-06-07)</small>
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

      </div><!-- /.row -->
    </div>
  </section>
</div>

<style>
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


<!-- FullCalendar CSS/JS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/main.min.css">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>

<!-- Minimal calendar init (no events feed for now) -->
<script>
document.addEventListener('DOMContentLoaded', function() {
  var el = document.getElementById('calendar');
  if (!el) { return; }
  if (!window.FullCalendar) {
    el.innerHTML = '<div class="text-danger">FullCalendar script not loaded. Check your network or use local files.</div>';
    return;
  }
  try {
    var calendar = new FullCalendar.Calendar(el, {
      initialView: 'dayGridMonth',
      height: 'auto',
      headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
      },
      events: [] // keep simple, as before
    });
    calendar.render();
  } catch (e) {
    el.innerHTML = '<div class="text-danger">Calendar failed to render: ' + (e.message || e) + '</div>';
  }
});
</script>
