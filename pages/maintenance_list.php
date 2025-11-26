<?php
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

$isAdmin = isset($_SESSION['user_role']) && strtolower($_SESSION['user_role']) === 'admin';

// --- 1. Database Connection and Setup ---
// Resolve PDO
$db = null;
if (isset($pdo) && $pdo)        { $db = $pdo; }
elseif (isset($obj) && isset($obj->pdo)) { $db = $obj->pdo; }
if (!$db) {
  echo '<div class="content-wrapper"><section class="content"><div class="container-fluid"><div class="alert alert-danger mt-3">Database connection not found.</div></div></section></div>';
  return;
}

// Detect asset table (supports 'products' or 'product')
$productTable = 'products';
try { $db->query("SELECT 1 FROM `products` LIMIT 1"); }
catch (Throwable $e) { try { $db->query("SELECT 1 FROM `product` LIMIT 1"); $productTable='product'; } catch (Throwable $e2) {} }

// --- 2. Filtering and Status Counting ---
// Allowed statuses for URL filter
$allowed = ['all','open','in_progress','resolved','closed'];
$status  = strtolower($_GET['status'] ?? 'all');
if (!in_array($status, $allowed, true)) { $status = 'all'; }

// Counts for filter tabs
$counts = array_fill_keys(['all','open','in_progress','resolved','closed'], 0);
try {
  // Query to get count of requests for each status
  $crows = $db->query("SELECT status, COUNT(*) AS c FROM maintenance_orders GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
  $total = 0;
  foreach ($crows as $st => $c) { $total += (int)$c; if (isset($counts[$st])) $counts[$st] = (int)$c; }
  $counts['all'] = $total; // Set the total count
} catch (Throwable $e) { /* ignore if maintenance_orders table doesn't exist yet */ }

// --- 3. Fetch Maintenance Requests ---
$where = "";
$params = [];
// Apply status filter if not 'all'
if ($status !== 'all') {
  $where = "WHERE mo.status = :status";
  $params[':status'] = $status;
}

$sql = "
  SELECT
    mo.id,
    mo.asset_id,
    mo.title,
    mo.priority,
    mo.status,
    mo.requested_by,
    mo.assigned_to,
    mo.requested_date,
    mo.due_date,
    p.product_name AS asset_name,
    u1.username   AS reported_name,
    u2.username   AS assigned_name
  FROM maintenance_orders mo
  JOIN `{$productTable}` p ON p.id = mo.asset_id
  LEFT JOIN user u1 ON u1.id = mo.requested_by
  LEFT JOIN user u2 ON u2.id = mo.assigned_to
  {$where}
  ORDER BY mo.created_at DESC
  LIMIT 500
";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- 4. Helper Functions for Badges ---
function badgePriority($p) {
  $label = ucfirst($p);
  $map = [
    'low'      => ['cls' => 'secondary', 'icon' => 'fa-angle-down', 'title' => 'Low priority'],
    'medium'   => ['cls' => 'info',      'icon' => 'fa-minus',      'title' => 'Medium priority'],
    'high'     => ['cls' => 'warning',   'icon' => 'fa-angle-up',   'title' => 'High priority'],
    'critical' => ['cls' => 'danger',    'icon' => 'fa-exclamation-triangle', 'title' => 'Critical priority'],
  ];
  $m = $map[$p] ?? ['cls'=>'secondary','icon'=>'fa-circle','title'=>'Priority'];
  $icon = '<i class="fas ' . $m['icon'] . ' mr-1" aria-hidden="true"></i>';
  return '<span class="badge badge-pill badge-' . $m['cls'] . '" title="' . htmlspecialchars($m['title']) . '">' . $icon . htmlspecialchars($label) . '</span>';
}
function badgeStatus($s) {
  $label = ucwords(str_replace('_',' ',$s));
  $map = [
    'open'           => ['cls' => 'secondary', 'icon' => 'fa-folder-open', 'title' => 'Newly opened'],
    'in_progress'    => ['cls' => 'warning',   'icon' => 'fa-spinner fa-spin', 'title' => 'Work in progress'],
    'resolved'       => ['cls' => 'success',   'icon' => 'fa-check', 'title' => 'Resolved (awaiting close)'],
    'closed'         => ['cls' => 'dark',      'icon' => 'fa-lock', 'title' => 'Closed (archived)'],
  ];
  $m = $map[$s] ?? ['cls'=>'secondary','icon'=>'fa-circle','title'=>'Status'];
  $icon = '<i class="fas ' . $m['icon'] . ' mr-1" aria-hidden="true"></i>';
  return '<span class="badge badge-pill badge-' . $m['cls'] . '" title="' . htmlspecialchars($m['title']) . '">' . $icon . htmlspecialchars($label) . '</span>';
}
?>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6"><h1 class="m-0 text-dark">Maintenance</h1></div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item active">Maintenance</li>
          </ol>
        </div>
      </div>
    </div>
  </div>

  <section class="content">
    <div class="container-fluid">

      <?php if (!empty($_GET['msg'])): ?>
        <div class="mb-2">
          <div class="alert alert-<?= isset($_GET['type']) && $_GET['type']==='danger' ? 'danger' : 'success' ?> mb-0 py-1 px-2 d-inline-block">
            <?= htmlspecialchars($_GET['msg']) ?>
          </div>
        </div>
      <?php endif; ?>

      <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap">
        <ul class="nav nav-pills mb-2 mb-sm-0">
          <?php
            $tabs = [
              'all' => 'All',
              'open' => 'Open',
              'in_progress' => 'In Progress',
              'resolved' => 'Resolved',
              'closed' => 'Closed'
            ];
            foreach ($tabs as $key => $label):
              $active = $status === $key ? 'active' : '';
              $count  = (int)($counts[$key] ?? 0);
          ?>
            <li class="nav-item mr-2 mb-2">
              <a class="nav-link <?= $active ?>" href="index.php?page=maintenance_list&status=<?= urlencode($key) ?>">
                <?= htmlspecialchars($label) ?> <span class="badge badge-light ml-1"><?= $count ?></span>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>

        <a class="btn btn-primary px-3 py-2 mb-2 mb-sm-0" href="index.php?page=maintenance_request" id="btn-new-request">
          <i class="fas fa-plus"></i> New Request
        </a>
      </div>

      <div class="d-flex flex-wrap align-items-center small text-muted mb-2" id="maint-legend">
        <div class="mr-3 mb-1">Priority:
          <?= badgePriority('low') ?> <?= badgePriority('medium') ?> <?= badgePriority('high') ?> <?= badgePriority('critical') ?>
        </div>
        <div class="mb-1">Status:
          <?= badgeStatus('open') ?> <?= badgeStatus('in_progress') ?> <?= badgeStatus('resolved') ?> <?= badgeStatus('closed') ?>
        </div>
      </div>

      <div class="card card-outline card-warning">
        <div class="card-header">
          <h3 class="card-title">
            <?= htmlspecialchars(ucwords(str_replace('_',' ', $status))) ?> Requests
          </h3>
        </div>
        <div class="card-body p-0 table-responsive">
          <table class="table table-hover table-sm mb-0" id="maint-table">
            <thead class="thead-light">
              <tr>
                <th style="width:48px;">#</th>
                <th>Asset</th>
                <th>Title</th>
                <th>Priority</th>
                <th>Status</th>
                <th>Requested By</th>
                <th>Assigned To</th>
                <th>Reported</th>
                <th>Due</th>
                <th class="text-right" style="width:260px;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($rows): foreach ($rows as $i => $r):
                // Check possible actions for the current row
                $reported = $r['requested_date'] ? date('Y-m-d', strtotime($r['requested_date'])) : '—';
                $due      = $r['due_date'] ? date('Y-m-d', strtotime($r['due_date'])) : '—';
                $canStart = in_array($r['status'], ['open']); // Can start if status is 'open'
                $canResolve = in_array($r['status'], ['open','in_progress']); // Can resolve if 'open' or 'in_progress'
              ?>
              <tr class="priority-<?= ($r['priority']==='critical' ? 'critical' : 'normal') ?> status-<?= htmlspecialchars($r['status']) ?>">
                <td><?= $i+1 ?></td>
                <td><?= htmlspecialchars($r['asset_name'] ?? ('#'.$r['asset_id'])) ?></td>
                <td><?= htmlspecialchars($r['title'] ?? '-') ?></td>
                <td><?= badgePriority($r['priority']) ?></td>
                <td><?= badgeStatus($r['status']) ?></td>
                <td><?= htmlspecialchars($r['reported_name'] ?? '-') ?></td>
                <td><?= htmlspecialchars($r['assigned_name'] ?? '-') ?></td>
                <td><?= htmlspecialchars($reported) ?></td>
                <td><?= htmlspecialchars($due) ?></td>
                <td class="text-right">
                  <div class="btn-group btn-group-sm" role="group">
                    
                    <?php if ($isAdmin && $r['status'] === 'resolved'):
                    // Admin-only: Option to 'Close' the request after it has been 'resolved'
                    ?>
                    <form action="app/action/maintenance_update.php" method="post" class="d-inline" onsubmit="return confirm('Permanently close this request?');">
                      <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                      <input type="hidden" name="action" value="close">
                      <button type="submit" class="btn btn-outline-dark btn-sm">
                        <i class="fas fa-lock"></i> Close
                      </button>
                    </form>
                    <?php endif; ?>
                    
                    <button type="button" class="btn btn-outline-primary btn-sm btn-view" data-id="<?= (int)$r['id'] ?>">
                      <i class="fas fa-eye"></i> View
                    </button>
                    
                    <form action="app/action/maintenance_update.php" method="post" class="d-inline">
                      <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                      <input type="hidden" name="action" value="start">
                      <button type="submit" class="btn btn-outline-success btn-sm" <?= $canStart ? '' : 'disabled' ?>>
                        <i class="fas fa-play"></i> Start
                      </button>
                    </form>
                    
                    <form action="app/action/maintenance_update.php" method="post" class="d-inline" onsubmit="return confirm('Mark this request as resolved?');">
                      <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                      <input type="hidden" name="action" value="resolve">
                      <button type="submit" class="btn btn-outline-danger btn-sm" <?= $canResolve ? '' : 'disabled' ?>>
                        <i class="fas fa-check"></i> Resolve
                      </button>
                    </form>
                    
                    <?php if ($isAdmin):
                    // Admin-only: Option to Delete the request
                    ?>
                    <form action="app/action/maintenance_delete.php" method="post" class="d-inline"
                          onsubmit="return confirm('Are you sure you want to delete this maintenance request?');">
                      <input type="hidden" name="id" value="<?= $r['id'] ?>">
                      <button type="submit" class="btn btn-outline-danger btn-sm">
                        <i class="fas fa-trash"></i>Delete</button>
                    </form>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
              <?php endforeach; else: ?>
                <tr><td colspan="10" class="text-center text-muted">No requests found for this filter.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <?php if (in_array($status, ['resolved','closed'])): ?>
        <style>.btn-reopen{margin-left:6px}</style>
      <?php endif; ?>

      <div class="modal fade" id="maintViewModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
          <div class="modal-content">
            <div class="modal-header py-2">
              <h5 class="modal-title">Maintenance Details</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div id="mv-body" class="modal-body">
              <div class="p-3 text-muted">Loading…</div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </section>
</div>

<style>
  /* Make the button visually level with nav-pills .nav-link */
  #btn-new-request { line-height: 1.25; }
  @media (min-width: 576px){
    #btn-new-request { padding: .5rem 1rem; }
  }
</style>  

<style>
  /* Make all maintenance action buttons equal height and spacing */
  .btn-group .btn {
    min-width: 80px;
    height: 32px;
    line-height: 1.25;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: .25rem .6rem;
    font-size: 0.875rem;
  }

  .btn-group .btn i {
    margin-right: 4px;
    font-size: 0.85rem;
  }

  /* Fix misalignment when some buttons are disabled */
  .btn-group .btn[disabled] {
    opacity: 0.6;
  }

  /* Ensure all rows’ action groups stay uniform */
  td.text-right .btn-group {
    white-space: nowrap;
  }
</style>

<style>
  #maint-legend .badge { margin-right: .25rem; }
  .badge i { margin-right: .35rem; }
  .badge.badge-pill { padding: .35rem .5rem; font-weight: 600; letter-spacing: .2px; }

  /* Optional: subtle row cues for easier scanning */
  tr.priority-critical td { background: rgba(220, 53, 69, 0.06); }    /* red-ish */
  tr.status-resolved td   { background: rgba(40, 167, 69, 0.04); }    /* green-ish */
  tr.status-closed td     { background: rgba(52, 58, 64, 0.05); }     /* dark-ish */
</style>

<script>
(function() {
  // Function to bind the click handler for the 'View' button
  function bindMaintHandlers() {
    if (!window.jQuery || !$.fn || !$.fn.modal) return false;
    // Unbind previous handler and bind new one
    $(document).off('click.maintView', '.btn-view').on('click.maintView', '.btn-view', function(e){
      e.preventDefault();
      var id = parseInt($(this).data('id'), 10);
      if (!id) return;
      var $modal = $('#maintViewModal');
      var $body  = $('#mv-body');
      // Show loading state and open modal
      $body.html('<div class="p-3 text-muted">Loading…</div>');
      $modal.modal('show');
      // Fetch details content via AJAX
      $.get('app/action/maintenance_view.php', { id: id })
        .done(function(html){ $body.html(html || '<div class="alert alert-warning m-3">Empty response.</div>'); })
        .fail(function(xhr){
          var msg = 'Failed to load details (HTTP ' + xhr.status + ').';
          var extra = xhr && xhr.responseText ? '<pre class="mt-2 small">' + $('<div/>').text(xhr.responseText).html() + '</pre>' : '';
          $body.html('<div class="alert alert-danger m-3">' + msg + extra + '</div>');
        });
    });
    return true;
  }
  // Attempt to bind handlers immediately, with a fallback interval if jQuery isn't ready
  if (!bindMaintHandlers()) {
    var tries=0, max=20, iv=setInterval(function(){ tries++; if (bindMaintHandlers() || tries>=max) clearInterval(iv); }, 250);
  }
})();
</script>