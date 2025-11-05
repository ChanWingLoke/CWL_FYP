<?php
// Dashboard (reworked for IT Asset Management focus)
// Assumes app/init.php (via inc/header.php) has already started session and created $pdo
// We keep existing AdminLTE styling but simplify to the four core modules.

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
              <a href="index.php?page=warranty_list" class="small-box-footer">Manage Warranties <i class="fas fa-arrow-circle-right"></i></a>
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
                    Open / In Progress / Waiting Parts
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
                <a class="btn btn-sm btn-outline-success" href="index.php?page=warranty_list">Manage</a>
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

      </div><!-- /.container-fluid -->
    </section>
    <!-- /.content -->
  </div>
