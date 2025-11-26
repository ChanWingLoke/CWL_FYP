<?php
// PHP logic to determine the current page for setting 'active' class on menu items.
$pg = isset($_GET['page']) ? trim($_GET['page']) : '';
?>

<aside class="main-sidebar sidebar">

  <a href="index.php" class="brand-link">
    <img src="dist/img/log.jpg" alt="logo" class="brand-image">
  </a>

  <div class="sidebar">
    <nav class="mt-2">
      <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
        
        <!-- Dashboard -->
        <li class="nav-item">
          <a href="index.php" class="nav-link <?= ($current === 'dashboard' || $pg === '') ? 'active' : '' ?>">
            <i class="material-symbols-outlined nav-icon">dashboard</i>
            <p>
              Dashboard
            </p>
          </a>
        </li>

        <!-- Assets -->
        <?php
        $isAssets = in_array($current, ['assets_list', 'add_product', 'product_edit']);
        ?>
        <li class="nav-item has-treeview <?= $isAssets ? 'menu-open' : '' ?>">
          <a href="#" class="nav-link <?= $isAssets ? 'active' : '' ?>">
            <i class="material-symbols-outlined nav-icon">inventory</i>
            <p>
              Assets
              <i class="right fas fa-angle-left"></i>
            </p>
          </a>
          <ul class="nav nav-treeview">
            <li class="nav-item">
              <a href="index.php?page=assets_list"
                class="nav-link <?= in_array($current, ['assets_list', 'product_edit']) ? 'active' : '' ?>">
                <p>Assets List</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="index.php?page=add_product"
                class="nav-link <?= ($current === 'add_product') ? 'active' : '' ?>">
                <p>Add Asset</p>
              </a>
            </li>
          </ul>
        </li>

        <!-- Category -->
        <li class="nav-item">
          <a href="index.php?page=category" class="nav-link <?php echo $actual_link == 'category' || $actual_link == 'catagory_edit' ? 'active' : '';?>">
            <i class="material-symbols-outlined nav-icon">difference</i>
            <p>
               Category
            </p>
          </a>
        </li>

        <!-- Warranty -->
        <li class="nav-item">
          <a href="index.php?page=warranty_list" class="nav-link <?= ($current === 'warranty_list') ? 'active' : '' ?>">
            <i class="material-symbols-outlined nav-icon">verified</i>
            <p>Warranty</p>
          </a>
        </li>

        <!-- Maintenance -->
        <li class="nav-item">
          <a href="index.php?page=maintenance_list" class="nav-link <?= ($current === 'maintenance_list') ? 'active' : '' ?>">
            <i class="material-symbols-outlined nav-icon">build</i>
            <p>Maintenance</p>
          </a>
        </li>
        
        <!-- Bookings -->
        <?php
        $isBookingsOpen = in_array($current, ['bookings','bookings_requests','bookings_all']);
        ?>
        <li class="nav-item has-treeview <?= $isBookingsOpen ? 'menu-open' : '' ?>">
          <a href="#" class="nav-link <?= $isBookingsOpen ? 'active' : '' ?>">
            <i class="material-symbols-outlined nav-icon">event</i>
            <p>
              Bookings
              <i class="right fas fa-angle-left"></i>
            </p>
          </a>
          <ul class="nav nav-treeview">
            <li class="nav-item">
              <a href="index.php?page=bookings" class="nav-link <?= ($current === 'bookings') ? 'active' : '' ?>">
                <p>New Booking</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="index.php?page=bookings_requests" class="nav-link <?= ($current === 'bookings_requests') ? 'active' : '' ?>">
                <p>Booking Requests</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="index.php?page=bookings_all" class="nav-link <?= ($current === 'bookings_all') ? 'active' : '' ?>">
                <p>All Bookings</p>
              </a>
            </li>
          </ul>
        </li>

        <!-- Loans -->
        <li class="nav-item">
          <a href="index.php?page=loans" class="nav-link <?= ($current === 'loans') ? 'active' : '' ?>">
            <i class="material-symbols-outlined nav-icon">assignment_return</i>
            <p>Loans</p>
          </a>
        </li>

        <!-- Users -->
        <li class="nav-item">
           <a href="index.php?page=users_list" class="nav-link <?php echo $actual_link == 'users_list' ? 'active' : '';?>">
             <i class="material-symbols-outlined nav-icon">diversity_3</i>
             <p>Users</p>
           </a>
         </li>

        <!-- Reports -->
        <li class="nav-item">
           <a href="index.php?page=reports" class="nav-link <?php echo $actual_link == 'reports' ? 'active' : '';?>">
             <i class="material-symbols-outlined nav-icon">assignment_returned</i>
             <p>Reports</p>
           </a>
         </li>
         
      </ul>
    </nav>
    </div>
  </aside>

<?php require_once 'inc/catagory_modal.php'; ?>
<?php require_once 'inc/suppliar_modal.php'; ?>
<?php require_once 'inc/expense_catagory_modal.php'; ?>