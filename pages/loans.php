<?php
// pages/loans.php

// Require login
if (!isset($_SESSION['user_id'])) {
  header('Location: login.php'); exit;
}

// Admin-only guard (based on your User.php is_admin logic)
$isAdmin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
if (!$isAdmin) {
  ?>
  <div class="content-wrapper">
    <div class="content-header">
      <div class="container-fluid">
        <h1 class="m-0 text-dark">Loans</h1>
      </div>
    </div>
    <section class="content">
      <div class="container-fluid">
        <div class="alert alert-danger mt-3">
          You do not have permission to access Loans.
        </div>
      </div>
    </section>
  </div>
  <?php
  return; // not exit — lets footer JS run
}

// Get PDO
$db = null;
if (isset($pdo) && $pdo)       { $db = $pdo; }
elseif (isset($obj) && $obj->pdo) { $db = $obj->pdo; }
if (!$db) {
  die('<div class="content-wrapper"><section class="content"><div class="container-fluid">
       <div class="alert alert-danger mt-3">Database connection not found.</div>
       </div></section></div>');
}

// Detect product table (product / products / items / assets)
$productTable = 'product';
try { $db->query("SELECT 1 FROM `product` LIMIT 1"); }
catch (Throwable $e) {
  foreach (['products','tbl_product','items','assets'] as $t) {
    try { $db->query("SELECT 1 FROM `{$t}` LIMIT 1"); $productTable = $t; break; }
    catch (Throwable $ignored) {}
  }
}

// Filter tabs: all | due | overdue
$filter = strtolower($_GET['filter'] ?? 'all');
$today  = date('Y-m-d');

// Build WHERE clause
$where = "b.status = 'approved'";
$params = [];
if ($filter === 'due') {
  $where .= " AND DATE(b.end_time) = :today";
  $params[':today'] = $today;
} elseif ($filter === 'overdue') {
  $where .= " AND DATE(b.end_time) < :today";
  $params[':today'] = $today;
}

// Fetch loans
$sql = "
  SELECT b.id, b.asset_id, b.user_id, b.start_time, b.end_time, b.status, b.notes,
         u.username AS user_name,
         p.product_name AS asset_name
  FROM bookings b
  JOIN user u ON u.id = b.user_id
  JOIN `{$productTable}` p ON p.id = b.asset_id
  WHERE {$where}
  ORDER BY b.end_time ASC, b.start_time ASC
";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Simple helper
function badgeClass($status) {
  return [
    'pending'  => 'badge-warning',
    'approved' => 'badge-success',
    'rejected' => 'badge-danger',
    'returned' => 'badge-secondary',
  ][$status] ?? 'badge-light';
}

$flashMsg  = $_GET['msg']  ?? null;
$flashType = $_GET['type'] ?? 'info';
?>
<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h1 class="m-0 text-dark">Loans</h1>
        <ol class="breadcrumb float-sm-right mb-0">
          <li class="breadcrumb-item"><a href="index.php">Home</a></li>
          <li class="breadcrumb-item active">Loans</li>
        </ol>
      </div>
      <?php if ($flashMsg): ?>
        <div class="alert alert-<?= htmlspecialchars($flashType) ?>"><?= htmlspecialchars($flashMsg) ?></div>
      <?php endif; ?>
    </div>
  </div>

  <section class="content">
    <div class="container-fluid">

      <!-- Filter Tabs -->
      <div class="mb-3">
        <a href="index.php?page=loans&filter=all" 
            class="btn btn-filter <?= $filter==='all'?'active all':'all' ?>">All Loans</a>
        <a href="index.php?page=loans&filter=due" 
            class="btn btn-filter <?= $filter==='due'?'active due':'due' ?>">Due Today</a>
        <a href="index.php?page=loans&filter=overdue" 
            class="btn btn-filter <?= $filter==='overdue'?'active overdue':'overdue' ?>">Overdue</a>
      </div>

      <div class="card">
        <div class="card-header d-flex align-items-center">
          <h3 class="card-title mb-0"><b><?= ucfirst($filter) ?> Loans</b></h3>
        </div>

        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-striped mb-0" id="loansTable">
              <thead class="thead-light">
                <tr>
                  <th>#</th>
                  <th>Asset Name</th>
                  <th>Checked Out To</th>
                  <th>Start Date</th>
                  <th>End Date</th>
                  <th>Status</th>
                  <th class="text-right">Action</th>
                </tr>
              </thead>
              <tbody>
              <?php if ($rows): foreach ($rows as $i => $r): 
                $isOverdue = (date('Y-m-d', strtotime($r['end_time'])) < $today);
                $isDueToday = (date('Y-m-d', strtotime($r['end_time'])) === $today);
              ?>
                <tr>
                  <td><?= $i+1 ?></td>
                  <td class="<?= $isOverdue ? 'text-danger' : '' ?>"><?= htmlspecialchars($r['asset_name']) ?></td>
                  <td><?= htmlspecialchars($r['user_name']) ?></td>
                  <td><?= htmlspecialchars(date('d M Y', strtotime($r['start_time']))) ?></td>
                  <td class="<?= $isOverdue ? 'text-danger' : ($isDueToday ? 'text-warning' : '') ?>">
                    <?= htmlspecialchars(date('d M Y', strtotime($r['end_time']))) ?>
                  </td>
                  <td><span class="badge <?= badgeClass($r['status']) ?>"><?= ucfirst($r['status']) ?></span></td>
                  <td class="text-right">
                    <form action="app/action/booking_update_status.php" method="post" class="d-inline">
                      <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                      <input type="hidden" name="action" value="return">
                      <input type="hidden" name="back" value="loans<?= $filter && $filter!=='all' ? '&filter='.urlencode($filter) : '' ?>">
                      <button class="btn btn-sm btn-secondary" onclick="return confirm('Mark this loan as returned?')">
                        Return
                      </button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; else: ?>
                <tr><td colspan="7" class="text-center text-muted">No loans found.</td></tr>
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
.btn-filter {
  border: 2px solid transparent;
  border-radius: 4px;
  padding: 6px 14px;
  font-weight: 600;
  font-size: 14px;
  margin-right: 6px;
  transition: all 0.2s ease-in-out;
}

.btn-filter.all { border-color: #28a745; color: #28a745; }
.btn-filter.all.active, .btn-filter.all:hover { background: #28a745; color: #fff; }

.btn-filter.due { border-color: #ffc107; color: #ffc107; }
.btn-filter.due.active, .btn-filter.due:hover { background: #ffc107; color: #fff; }

.btn-filter.overdue { border-color: #dc3545; color: #dc3545; }
.btn-filter.overdue.active, .btn-filter.overdue:hover { background: #dc3545; color: #fff; }

.btn-filter:focus { box-shadow: none !important; outline: none !important; }
</style>

<script>
$(function(){
  if ($.fn.DataTable) {
    $('#loansTable').DataTable({ pageLength: 25, order: [[4, 'asc']] });
  }
});
</script>
