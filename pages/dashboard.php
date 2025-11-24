<?php
// Dashboard (reworked for IT Asset Management focus)

// Helper: safe count fetch
function quick_count(PDO $pdo, $sql) {
    try {
        $stmt = $pdo->query($sql);
        return (int)$stmt->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}

// Compute core metrics
$total_assets         = quick_count($pdo, "SELECT COUNT(*) FROM `products`");
$total_maint_open     = quick_count($pdo, "SELECT COUNT(*) FROM `maintenance_orders` WHERE `status` IN ('open','in_progress','waiting_parts')");
$total_maint_resolved = quick_count($pdo, "SELECT COUNT(*) FROM `maintenance_orders` WHERE `status` IN ('resolved','closed')");
$total_warranties     = quick_count($pdo, "SELECT COUNT(*) FROM `warranties`");
$active_warranties    = quick_count($pdo, "SELECT COUNT(*) FROM `warranties` WHERE `warranty_status`='active' AND `end_date` >= CURDATE()");
$expired_warranties   = quick_count($pdo, "SELECT COUNT(*) FROM `warranties` WHERE `warranty_status`='expired' OR `end_date` < CURDATE()");
$total_bookings       = quick_count($pdo, "SELECT COUNT(*) FROM `bookings`");
$active_bookings      = quick_count($pdo, "SELECT COUNT(*) FROM `bookings` WHERE `status` IN ('pending','approved')");
$completed_bookings   = quick_count($pdo, "SELECT COUNT(*) FROM `bookings` WHERE `status` IN ('returned','rejected')");
$isAdmin = isset($_SESSION['user_role']) && strtolower($_SESSION['user_role']) === 'admin';

?>
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0 text-dark">Dashboard</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="index.php">Home</a></li>
              <li class="breadcrumb-item active">Dashboard</li>
            </ol>
          </div>
        </div>
      </div>
    </div>

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        
        <!-- Top summary cards -->
        <div class="row">
          <!-- Assets -->
          <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
              <div class="inner">
                <h3><?php echo $total_assets; ?></h3>
                <p>Assets</p>
              </div>
              <div class="icon">
                <i class="fas fa-boxes"></i>
              </div>
              <a href="index.php?page=assets_list" class="small-box-footer">Manage Assets <i class="fas fa-arrow-circle-right"></i></a>
            </div>
          </div>

          <!-- Maintenance (Open) -->
          <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
              <div class="inner">
                <h3><?php echo $total_maint_open; ?></h3>
                <p>Open Maintenance</p>
              </div>
              <div class="icon">
                <i class="fas fa-tools"></i>
              </div>
              <a href="index.php?page=maintenance_list" class="small-box-footer">View Maintenance <i class="fas fa-arrow-circle-right"></i></a>
            </div>
          </div>

          <!-- Warranties (Active) -->
          <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
              <div class="inner">
                <h3><?php echo $active_warranties; ?></h3>
                <p>Active Warranties</p>
              </div>
              <div class="icon">
                <i class="fas fa-shield-alt"></i>
              </div>
              <?php if ($isAdmin): ?>
                <a href="index.php?page=warranty_list" class="small-box-footer">
                  Manage Warranties <i class="fas fa-arrow-circle-right"></i>
                </a>
              <?php endif; ?>
            </div>
          </div>

          <!-- Bookings (Active) -->
          <div class="col-lg-3 col-6">
            <div class="small-box bg-primary">
              <div class="inner">
                <h3><?php echo $active_bookings; ?></h3>
                <p>Active Bookings</p>
              </div>
              <div class="icon">
                <i class="fas fa-calendar-check"></i>
              </div>
              <a href="index.php?page=bookings" class="small-box-footer">Go to Bookings <i class="fas fa-arrow-circle-right"></i></a>
            </div>
          </div>
        </div>

        <!-- Secondary details -->
        <div class="row">
          <!-- Maintenance breakdown -->
          <div class="col-md-6">
            <div class="card card-outline card-warning">
              <div class="card-header">
                <h3 class="card-title">Maintenance Overview</h3>
              </div>
              <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                  <li class="list-group-item d-flex justify-content-between align-items-center">
                    Open / In Progress
                    <span class="badge badge-warning badge-pill"><?php echo $total_maint_open; ?></span>
                  </li>
                  <li class="list-group-item d-flex justify-content-between align-items-center">
                    Resolved / Closed
                    <span class="badge badge-success badge-pill"><?php echo $total_maint_resolved; ?></span>
                  </li>
                </ul>
              </div>
              <div class="card-footer text-right">
                <a class="btn btn-sm btn-outline-warning" href="index.php?page=maintenance_list">View All</a>
              </div>
            </div>
          </div>

          <!-- Warranty breakdown -->
          <div class="col-md-6">
            <div class="card card-outline card-success">
              <div class="card-header">
                <h3 class="card-title">Warranty Overview</h3>
              </div>
              <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                  <li class="list-group-item d-flex justify-content-between align-items-center">
                    Total Warranties
                    <span class="badge badge-secondary badge-pill"><?php echo $total_warranties; ?></span>
                  </li>
                  <li class="list-group-item d-flex justify-content-between align-items-center">
                    Active
                    <span class="badge badge-success badge-pill"><?php echo $active_warranties; ?></span>
                  </li>
                  <li class="list-group-item d-flex justify-content-between align-items-center">
                    Expired
                    <span class="badge badge-danger badge-pill"><?php echo $expired_warranties; ?></span>
                  </li>
                </ul>
              </div>
              <div class="card-footer text-right">
                <?php if ($isAdmin): ?>
                  <a class="btn btn-sm btn-outline-success" href="index.php?page=warranty_list">Manage</a>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>

        <!-- Bookings breakdown -->
        <div class="row">
          <div class="col-md-12">
            <div class="card card-outline card-primary">
              <div class="card-header">
                <h3 class="card-title">Bookings Overview</h3>
              </div>
              <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                  <li class="list-group-item d-flex justify-content-between align-items-center">
                    Total Bookings
                    <span class="badge badge-secondary badge-pill"><?php echo $total_bookings; ?></span>
                  </li>
                  <li class="list-group-item d-flex justify-content-between align-items-center">
                    Active (Pending / Approved)
                    <span class="badge badge-primary badge-pill"><?php echo $active_bookings; ?></span>
                  </li>
                  <li class="list-group-item d-flex justify-content-between align-items-center">
                    Completed (Returned / Rejected)
                    <span class="badge badge-light badge-pill"><?php echo $completed_bookings; ?></span>
                  </li>
                </ul>
              </div>
              <div class="card-footer text-right">
                <a class="btn btn-sm btn-outline-primary" href="index.php?page=bookings">Open Bookings</a>
              </div>
            </div>
          </div>
        </div>

        <?php
        // === Admin-only "Due Soon + Expired Bookings + Warranties Expiring Soon" ===
        $db = $pdo ?? ($obj->pdo ?? null);

        if ($isAdmin && $db):
          // Active booking statuses
          $activeStatuses = ['pending','approved','in_progress','active'];
          $ph = implode(',', array_fill(0, count($activeStatuses), '?'));

          // -------- Due Soon Bookings (1–3 days) --------
          $sqlCount = "SELECT COUNT(*) FROM bookings b
                      WHERE DATE(b.end_time) >  CURDATE()
                        AND DATE(b.end_time) <= (CURDATE() + INTERVAL 3 DAY)
                        AND b.status IN ($ph)";
          $stc = $db->prepare($sqlCount);
          $stc->execute($activeStatuses);
          $dueSoonCount = (int)$stc->fetchColumn();

          $sqlList = "SELECT
                        b.id AS booking_id,
                        u.username,
                        b.status,
                        DATE(b.end_time) AS end_date,
                        TIME(b.end_time) AS end_time,
                        DATEDIFF(DATE(b.end_time), CURDATE()) AS days_left
                      FROM bookings b
                      JOIN user u ON u.id = b.user_id
                      WHERE DATE(b.end_time) >  CURDATE()
                        AND DATE(b.end_time) <= (CURDATE() + INTERVAL 3 DAY)
                        AND b.status IN ($ph)
                      ORDER BY b.end_time ASC
                      LIMIT 10";
          $st = $db->prepare($sqlList);
          $st->execute($activeStatuses);
          $dueSoonRows = $st->fetchAll(PDO::FETCH_ASSOC);

          // -------- Expired Bookings --------
          $sqlOverCount = "SELECT COUNT(*) FROM bookings b
                          WHERE DATE(b.end_time) < CURDATE()
                            AND b.status IN ($ph)";
          $sto = $db->prepare($sqlOverCount);
          $sto->execute($activeStatuses);
          $overdueCount = (int)$sto->fetchColumn();

          $sqlOverList = "SELECT
                            b.id AS booking_id,
                            u.username,
                            b.status,
                            DATE(b.end_time) AS end_date,
                            TIME(b.end_time) AS end_time,
                            DATEDIFF(CURDATE(), DATE(b.end_time)) AS days_overdue
                          FROM bookings b
                          JOIN user u ON u.id = b.user_id
                          WHERE DATE(b.end_time) < CURDATE()
                            AND b.status IN ($ph)
                          ORDER BY b.end_time ASC
                          LIMIT 10";
          $stol = $db->prepare($sqlOverList);
          $stol->execute($activeStatuses);
          $overdueRows = $stol->fetchAll(PDO::FETCH_ASSOC);

          // -------- Warranties Expiring Soon (≤30 days) --------
          // Only active warranties ending within the next 30 days
          $sqlWCount = "SELECT COUNT(*) FROM warranties w
                        WHERE DATE(w.end_date) BETWEEN CURDATE() AND (CURDATE() + INTERVAL 30 DAY)
                          AND w.warranty_status = 'active'";
          $wcount = (int)$db->query($sqlWCount)->fetchColumn();

          $sqlWList = "SELECT
                        w.id AS warranty_id,
                        DATE(w.end_date) AS end_date,
                        DATEDIFF(DATE(w.end_date), CURDATE()) AS days_left
                      FROM warranties w
                      WHERE DATE(w.end_date) BETWEEN CURDATE() AND (CURDATE() + INTERVAL 30 DAY)
                        AND w.warranty_status = 'active'
                      ORDER BY w.end_date ASC
                      LIMIT 10";
          $wrows = $db->query($sqlWList)->fetchAll(PDO::FETCH_ASSOC);
        ?>
        <div class="row">
          <!-- Due Soon Bookings -->
          <div class="col-md-12 col-xl-4">
            <div class="card">
              <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">Due Soon Bookings (1–3 days)</h3>
                <span class="badge badge-primary" style="font-size:0.9rem;"><?= (int)$dueSoonCount ?></span>
              </div>
              <div class="card-body p-0">
                <?php if (!empty($dueSoonRows)): ?>
                  <div class="table-responsive">
                    <table class="table table-sm mb-0">
                      <thead>
                        <tr>
                          <th style="width:80px;">#</th>
                          <th>User</th>
                          <th>Days Left</th>
                          <th>Due</th>
                          <th>Status</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($dueSoonRows as $r): ?>
                          <tr>
                            <td><a href="index.php?page=bookings_all">#<?= (int)$r['booking_id'] ?></a></td>
                            <td><?= htmlspecialchars($r['username'] ?? '') ?></td>
                            <td>
                              <?php $dl=(int)$r['days_left']; ?>
                              <span class="badge badge-<?= $dl===1?'danger':($dl===2?'warning':'info') ?>">
                                <?= $dl ?> day<?= $dl===1?'':'s' ?>
                              </span>
                            </td>
                            <td><?= htmlspecialchars($r['end_date'].' '.$r['end_time']) ?></td>
                            <td><span class="badge badge-light text-dark"><?= htmlspecialchars($r['status']) ?></span></td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                <?php else: ?>
                  <div class="p-3 text-muted">No bookings due in the next 3 days.</div>
                <?php endif; ?>
              </div>
              <div class="card-footer text-right">
                <a class="btn btn-sm btn-outline-primary" href="index.php?page=bookings_all">View all bookings</a>
              </div>
            </div>
          </div>

          <!-- Expired Bookings -->
          <div class="col-md-12 col-xl-4">
            <div class="card border-danger">
              <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">Expired Bookings</h3>
                <span class="badge badge-danger" style="font-size:0.9rem;"><?= (int)$overdueCount ?></span>
              </div>
              <div class="card-body p-0">
                <?php if (!empty($overdueRows)): ?>
                  <div class="table-responsive">
                    <table class="table table-sm mb-0">
                      <thead>
                        <tr>
                          <th style="width:80px;">#</th>
                          <th>User</th>
                          <th>Days Overdue</th>
                          <th>Was Due</th>
                          <th>Status</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($overdueRows as $r): ?>
                          <tr>
                            <td><a href="index.php?page=bookings_all">#<?= (int)$r['booking_id'] ?></a></td>
                            <td><?= htmlspecialchars($r['username'] ?? '') ?></td>
                            <td>
                              <?php $od=(int)$r['days_overdue']; ?>
                              <span class="badge badge-<?= $od>=7?'dark':($od>=3?'danger':'warning') ?>">
                                <?= $od ?> day<?= $od===1?'':'s' ?>
                              </span>
                            </td>
                            <td><?= htmlspecialchars($r['end_date'].' '.$r['end_time']) ?></td>
                            <td><span class="badge badge-light text-dark"><?= htmlspecialchars($r['status']) ?></span></td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                <?php else: ?>
                  <div class="p-3 text-muted">No expired bookings 🎉</div>
                <?php endif; ?>
              </div>
              <div class="card-footer text-right">
                <a class="btn btn-sm btn-outline-danger" href="index.php?page=bookings_all">Review bookings</a>
              </div>
            </div>
          </div>

          <!-- Warranties Expiring Soon (≤30 days) -->
          <div class="col-md-12 col-xl-4">
            <div class="card border-success">
              <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">Warranties Expiring Soon (≤30 days)</h3>
                <span class="badge badge-success" style="font-size:0.9rem;"><?= (int)$wcount ?></span>
              </div>
              <div class="card-body p-0">
                <?php if (!empty($wrows)): ?>
                  <div class="table-responsive">
                    <table class="table table-sm mb-0">
                      <thead>
                        <tr>
                          <th style="width:80px;">#</th>
                          <th>Days Left</th>
                          <th>Ends On</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($wrows as $wr): ?>
                          <tr>
                            <td><a href="index.php?page=warranty_list">#<?= (int)$wr['warranty_id'] ?></a></td>
                            <td>
                              <?php $wdl=(int)$wr['days_left']; ?>
                              <span class="badge badge-<?= $wdl<=3?'danger':($wdl<=7?'warning':'success') ?>">
                                <?= $wdl ?> day<?= $wdl===1?'':'s' ?>
                              </span>
                            </td>
                            <td><?= htmlspecialchars($wr['end_date']) ?></td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                <?php else: ?>
                  <div class="p-3 text-muted">No warranties expiring soon.</div>
                <?php endif; ?>
              </div>
              <div class="card-footer text-right">
                <a class="btn btn-sm btn-outline-success" href="index.php?page=warranty_list">Manage warranties</a>
              </div>
            </div>
          </div>
        </div>
        <?php endif; ?>

      </div><!-- /.container-fluid -->
    </section>
    <!-- /.content -->
  </div>
