<?php
// pages/maintenance_list.php

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

// Get PDO
$db = null;
if (isset($pdo) && $pdo)        { $db = $pdo; }
elseif (isset($obj) && $obj->pdo){ $db = $obj->pdo; }
if (!$db) { die('<div class="content-wrapper"><section class="content"><div class="container-fluid"><div class="alert alert-danger mt-3">Database connection not found.</div></div></section></div>'); }

// Detect product/assets table
$productTable = 'product';
try { $db->query("SELECT 1 FROM `product` LIMIT 1"); }
catch (Throwable $e) {
  foreach (['products','tbl_product','items','assets'] as $t) {
    try { $db->query("SELECT 1 FROM `{$t}` LIMIT 1"); $productTable = $t; break; }
    catch (Throwable $ignored) {}
  }
}

// Tabs / filter
$tab = strtolower($_GET['tab'] ?? 'open');
if ($tab === 'closed') { $tab = 'resolved'; }
switch ($tab) {
  case 'open':          $where = "mo.status='open'"; break;
  case 'in_progress':   $where = "mo.status='in_progress'"; break;
  case 'waiting_parts': $where = "mo.status='waiting_parts'"; break;
  case 'resolved':      $where = "mo.status='resolved'"; break;
case 'all':
  default:              $where = '1=1';
}

// Query (aliases keep template wording consistent)
$sql = "
  SELECT
    mo.id,
    mo.asset_id,
    mo.title,
    mo.description,
    mo.priority,
    mo.status,
    mo.requested_by   AS reported_by,
    mo.assigned_to,
    mo.requested_date AS reported_at,
    mo.due_date,
    p.product_name    AS asset_name,
    u1.username       AS reported_name,
    u2.username       AS assigned_name
  FROM maintenance_orders mo
  JOIN `{$productTable}` p ON p.id = mo.asset_id
  LEFT JOIN user u1 ON u1.id = mo.requested_by
  LEFT JOIN user u2 ON u2.id = mo.assigned_to
  WHERE {$where}
  ORDER BY mo.requested_date DESC, mo.id DESC
";
$rows = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

function priorityBadge($p){
  $p = strtolower((string)$p);
  $label = ucfirst($p ?: '-');
  $cls = ['low'=>'badge-success','medium'=>'badge-primary','high'=>'badge-warning','urgent'=>'badge-danger'][$p] ?? 'badge-secondary';
  return '<span class="badge '.$cls.'">'.$label.'</span>';
}
function statusBadge($s){
  $s = strtolower((string)$s);
  $label = ['open'=>'Open','in_progress'=>'In Progress','waiting_parts'=>'Waiting Parts','resolved'=>'Resolved','closed'=>'Closed'][$s] ?? ucfirst($s ?: 'Open');
  $cls   = ['open'=>'badge-secondary','in_progress'=>'badge-info','waiting_parts'=>'badge-warning','resolved'=>'badge-success','closed'=>'badge-dark'][$s] ?? 'badge-secondary';
  return '<span class="badge '.$cls.'">'.$label.'</span>';
}
?>
<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h1 class="m-0 text-dark">Maintenance</h1>
        <ol class="breadcrumb float-sm-right mb-0">
          <li class="breadcrumb-item"><a href="index.php">Home</a></li>
          <li class="breadcrumb-item active">Maintenance</li>
        </ol>
      </div>

      <div class="mb-3">
        <a class="btn btn-<?= $tab==='open'?'primary':'outline-primary' ?> btn-sm mr-2" href="index.php?page=maintenance_list&tab=open">Open</a>
        <a class="btn btn-<?= $tab==='in_progress'?'primary':'outline-primary' ?> btn-sm mr-2" href="index.php?page=maintenance_list&tab=in_progress">In Progress</a>
        <a class="btn btn-<?= $tab==='waiting_parts'?'primary':'outline-primary' ?> btn-sm mr-2" href="index.php?page=maintenance_list&tab=waiting_parts">Waiting Parts</a>
        <a class="btn btn-<?= $tab==='resolved'?'primary':'outline-primary' ?> btn-sm mr-2" href="index.php?page=maintenance_list&tab=resolved">Resolved</a>
        <a class="btn btn-<?= $tab==='all'?'secondary':'outline-secondary' ?> btn-sm" href="index.php?page=maintenance_list&tab=all">All</a>

        <a class="btn btn-success btn-sm float-right" href="index.php?page=maintenance_request">
          <i class="fas fa-plus"></i> New Request
        </a>
      </div>
    </div>
  </div>

  <section class="content">
    <div class="container-fluid">

      <div class="card">
        <div class="card-header"><b><?= ucfirst(str_replace('_',' ', $tab)) ?> Requests</b></div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-striped mb-0" id="maintTable">
              <thead class="thead-light">
                <tr>
                  <th>#</th>
                  <th>Asset</th>
                  <th>Title</th>
                  <th>Priority</th>
                  <th>Reported By</th>
                  <th>Assigned</th>
                  <th>Reported</th>
                  <th>Due</th>
                  <th>Status</th>
                  <th class="text-right">Actions</th>
                </tr>
              </thead>
              <tbody>
              <?php if ($rows): foreach ($rows as $i => $r): 
                $reportedBy = $r['reported_name'] ?? '-';
                $reportedAt = $r['reported_at'] ? date('Y-m-d', strtotime($r['reported_at'])) : '-';
                $dueOn      = $r['due_date']    ? date('Y-m-d', strtotime($r['due_date']))    : '-';
                $assigned   = $r['assigned_name'] ?? '-';
              ?>
                <tr>
                  <td><?= $i+1 ?></td>
                  <td><?= htmlspecialchars($r['asset_name']) ?></td>
                  <td><?= htmlspecialchars($r['title'] ?? '-') ?></td>
                  <td><?= priorityBadge($r['priority']) ?></td>
                  <td><?= htmlspecialchars($reportedBy) ?></td>
                  <td><?= htmlspecialchars($assigned) ?></td>
                  <td><?= htmlspecialchars($reportedAt) ?></td>
                  <td><?= htmlspecialchars($dueOn) ?></td>
                  <td><?= statusBadge($r['status']) ?></td>
                  <td class="text-right">
                    <div class="btn-group btn-group-sm" role="group">
                      <button type="button"
                              class="btn btn-outline-primary btn-sm btn-view"
                              data-id="<?= (int)$row['id'] ?>">
                        <i class="fas fa-eye"></i> View
                      </button>

                      <form action="app/action/maintenance_update.php" method="post" class="d-inline">
                        <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                        <input type="hidden" name="action" value="start">
                        <button class="btn btn-outline-success btn-sm">
                          <i class="fas fa-play"></i> Start
                        </button>
                      </form>

                      <form action="app/action/maintenance_update.php" method="post" class="d-inline" onsubmit="return confirm('Close this request?');">
                        <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                        <input type="hidden" name="action" value="resolved">
                        <button class="btn btn-outline-danger btn-sm">
                          <i class="fas fa-times"></i> Close
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; else: ?>
                <tr><td colspan="10" class="text-center text-muted">No requests found.</td></tr>
              <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </div>
  </section>
</div>

<<!-- View Modal -->
<div class="modal fade" id="maintenanceViewModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header py-2">
        <h5 class="modal-title">Maintenance Details</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"
                style="outline:none;border:0;background:transparent;font-size:28px;line-height:1;">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div id="mv-body" class="modal-body">
        <!-- Filled by AJAX -->
      </div>
      <div class="modal-footer py-2">
        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Resolve</button>
      </div>
    </div>
  </div>
</div>

<script>
// DataTable (optional)
if (window.jQuery && $.fn.DataTable) {
  $(function(){ $('#maintTable').DataTable({ pageLength: 25, order: [[6,'desc']] }); });
}

<script>
(function() {
  // Quick sanity: ensure jQuery & Bootstrap modal are present
  if (!window.jQuery) {
    console.error('[maint-view] jQuery not loaded');
    return;
  }
  if (!$.fn.modal) {
    console.error('[maint-view] Bootstrap JS (modal) not loaded');
    return;
  }

  // Delegated click handler (works with DataTables)
  $(document).on('click', '.btn-view', function(e) {
    e.preventDefault();
    var id = $(this).data('id');
    if (!id) {
      console.warn('[maint-view] Missing data-id on button');
      return;
    }

    var $modal = $('#maintenanceViewModal');
    var $body  = $('#mv-body');
    $body.html('<div class="p-3 text-muted">Loading…</div>');
    $modal.modal('show');

    // IMPORTANT: path must exist and echo HTML
    $.get('app/action/maintenance_view.php', { id: id })
      .done(function(html) {
        if (!html) {
          $body.html('<div class="alert alert-warning m-3">Empty response from server.</div>');
          return;
        }
        $body.html(html);
      })
      .fail(function(xhr) {
        var msg = 'Failed to load details (HTTP ' + xhr.status + ').';
        var extra = (xhr && xhr.responseText) ? '<pre class="mt-2 small text-monospace bg-light p-2">' + $('<div/>').text(xhr.responseText).html() + '</pre>' : '';
        $body.html('<div class="alert alert-danger m-3">' + msg + extra + '</div>');
        console.error('[maint-view] AJAX fail:', xhr);
      });
  });
})();
</script>

