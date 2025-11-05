<?php
// pages/bookings_all.php
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

$db = null;
if (isset($pdo) && $pdo) { $db = $pdo; }
elseif (isset($obj) && isset($obj->pdo)) { $db = $obj->pdo; }
if (!$db) { die('<div class="alert alert-danger m-3">No DB connection.</div>'); }

// Detect product table
$productTable = 'product';
try { $db->query("SELECT 1 FROM `product` LIMIT 1"); }
catch (Throwable $e) { foreach (['products','tbl_product','items','assets'] as $t) { try { $db->query("SELECT 1 FROM `{$t}` LIMIT 1"); $productTable=$t; break; } catch(Throwable $ignore){} } }

// Fetch all
$sql = "
  SELECT b.id, b.start_time, b.end_time, b.status, b.notes,
         u.username, p.product_name
  FROM bookings b
  JOIN user u ON u.id = b.user_id
  JOIN `{$productTable}` p ON p.id = b.asset_id
  ORDER BY b.start_time DESC
";
$rows = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

$flashMsg = $_GET['msg'] ?? null;
$flashType = $_GET['type'] ?? 'info';
?>
<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h1 class="m-0 text-dark">All Bookings</h1>
        <ol class="breadcrumb float-sm-right mb-0">
          <li class="breadcrumb-item"><a href="index.php">Home</a></li>
          <li class="breadcrumb-item active">All Bookings</li>
        </ol>
      </div>
      <?php if ($flashMsg): ?>
        <div class="alert alert-<?= htmlspecialchars($flashType) ?>"><?= htmlspecialchars($flashMsg) ?></div>
      <?php endif; ?>
    </div>
  </div>

  <section class="content">
    <div class="container-fluid">
      <div class="card">
        <div class="card-header"><b>Bookings</b></div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-striped mb-0" id="bookingsTable">
              <thead class="thead-light">
                <tr>
                  <th>#</th>
                  <th>Asset</th>
                  <th>Employee</th>
                  <th>Start</th>
                  <th>End</th>
                  <th>Status</th>
                  <th>Notes</th>
                  <th class="text-right">Actions</th>
                </tr>
              </thead>
              <tbody>
              <?php if ($rows): foreach ($rows as $i => $r): 
                $cls = [
                  'pending'  => 'badge-warning',
                  'approved' => 'badge-success',
                  'rejected' => 'badge-danger',
                  'returned' => 'badge-secondary'
                ][$r['status']] ?? 'badge-light';
              ?>
                <tr>
                  <td><?= $i+1 ?></td>
                  <td><?= htmlspecialchars($r['product_name']) ?></td>
                  <td><?= htmlspecialchars($r['username']) ?></td>
                  <td><?= htmlspecialchars(date('Y-m-d', strtotime($r['start_time']))) ?></td>
                  <td><?= htmlspecialchars(date('Y-m-d', strtotime($r['end_time']))) ?></td>
                  <td><span class="badge <?= $cls ?>"><?= ucfirst($r['status']) ?></span></td>
                  <td><?= htmlspecialchars($r['notes'] ?? '') ?></td>
                  <td class="text-right">
                    <?php $isAdmin = isset($_SESSION['user_role']) && strtolower($_SESSION['user_role']) === 'admin'; ?>
                    <?php if ($isAdmin && $r['status'] === 'pending'): ?>
                      <form action="app/action/booking_update_status.php" method="post" class="d-inline">
                        <input type="hidden" name="id" value="<?= (int)$rows[$i]['id'] ?>">
                        <input type="hidden" name="action" value="approve">
                        <input type="hidden" name="back" value="bookings_all">
                        <button class="btn btn-sm btn-success" title="Approve" onclick="return confirm('Approve this booking?')">
                          <i class="fas fa-check"></i>
                        </button>
                      </form>
                      <form action="app/action/booking_update_status.php" method="post" class="d-inline">
                        <input type="hidden" name="id" value="<?= (int)$rows[$i]['id'] ?>">
                        <input type="hidden" name="action" value="reject">
                        <input type="hidden" name="back" value="bookings_all">
                        <button class="btn btn-sm btn-danger" title="Reject" onclick="return confirm('Reject this booking?')">
                          <i class="fas fa-times"></i>
                        </button>
                      </form>
                    <?php elseif ($r['status'] === 'approved'): ?>
                      <form action="app/action/booking_update_status.php" method="post" class="d-inline">
                        <input type="hidden" name="id" value="<?= (int)$rows[$i]['id'] ?>">
                        <input type="hidden" name="action" value="return">
                        <input type="hidden" name="back" value="bookings_all">
                        <button class="btn btn-sm btn-secondary" title="Mark Returned" onclick="return confirm('Mark as returned?')">
                          <i class="fas fa-undo"></i>
                        </button>
                      </form>
                    <?php else: ?>
                      <em class="text-muted">—</em>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; else: ?>
                <tr><td colspan="8" class="text-center text-muted">No bookings found.</td></tr>
              <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

<script>
$(function(){
  if ($.fn.DataTable) {
    $('#bookingsTable').DataTable({ pageLength: 25, order: [[3, 'desc']] });
  }
});
</script>
