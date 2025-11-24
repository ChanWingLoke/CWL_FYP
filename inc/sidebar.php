<?php
$pg = isset($_GET['page']) ? trim($_GET['page']) : '';

$assetNewPages = ['new_assets_list', 'new_asset_add', 'new_asset_edit', 'new_asset_view'];
$isAssetNewOpen = in_array($pg, $assetNewPages) ? ' menu-open' : '';
$isAssetNewActive = in_array($pg, $assetNewPages) ? ' active' : '';

$isAssetNewListActive = ($pg === 'new_assets_list') ? ' active' : '';
$isAssetNewAddActive  = ($pg === 'new_asset_add')   ? ' active' : '';
?>


<!-- Main Sidebar Container -->
  <aside class="main-sidebar sidebar ">

    <!-- Brand Logo -->
    <a href="index.php" class="brand-link">
      <img src="dist/img/log.jpg" alt="logo" class="brand-image">    
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
      <!-- Sidebar user panel (optional) -->
      <!-- <div class="user-panel mt-3 pb-3 mb-3 d-flex">
        <div class="image">
          <img src="dist/img/user2-160x160.jpg" class="img-circle elevation-2" alt="User Image">
        </div>
        <div class="info">
          <a href="#" class="d-block">
            <?php 
               $login_user = $_SESSION['user_id'];
               $login_user = $obj->find('user','id',$login_user);
               echo $login_user->username;
             ?>
          </a>
        </div>
      </div> -->

      <!-- Sidebar Menu -->
      <nav class="">
        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
          <!-- Add icons to the links using the .nav-icon class
               with font-awesome or any other icon font library -->

          <!-- Dashboard -->
          <li class="nav-item">
            <a href="index.php" class="nav-link <?= ($current === 'dashboard') ? 'active' : '' ?>">
              <i class="material-symbols-outlined">dashboard</i>
              <p>
                Dashboard
              </p>
            </a>
          </li>

          <?php
          $isAssets = in_array($current, ['assets_list','add_product']);
          ?>
          <!-- Assets -->
          <li class="nav-item has-treeview <?= $isAssets ? 'menu-open' : '' ?>">
            <a href="#" class="nav-link <?= $isAssets ? 'active' : '' ?>">
              <i class="material-symbols-outlined">inventory</i>
              <p>
                Assets
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="index.php?page=assets_list"
                  class="nav-link <?= in_array($current, ['assets','assets_list','asset_list']) ? 'active' : '' ?>">                 
                  <p>Assets List</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="index.php?page=add_product"
                  class="nav-link <?= in_array($current, ['add_product']) ? 'active' : '' ?>">
                  <p>Add Asset</p>
                </a>
              </li>
            </ul>
          </li>

          <!-- Category -->
          <li class="nav-item">
            <a href="index.php?page=category" class="nav-link <?php echo $actual_link=='category'?'active':'';?>">
              <i class="material-symbols-outlined">difference</i><p>
                 Category
              </p>
            </a>
          </li>

          <!-- Warranty -->
          <li class="nav-item">
            <a href="index.php?page=warranty_list" class="nav-link <?= ($current === 'warranty_list') ? 'active' : '' ?>">
              <i class="material-symbols-outlined">verified</i>
              <p>Warranty</p>
            </a>
          </li>

          <!-- Maintenance -->
          <li class="nav-item">
            <a href="index.php?page=maintenance_list" class="nav-link <?= ($current === 'maintenance_list') ? 'active' : '' ?>">
              <i class="material-symbols-outlined">build</i>
              <p>Maintenance</p>
            </a>
          </li>
          
          <!-- Bookings -->
          <li class="nav-item has-treeview <?= in_array($current, ['bookings','bookings_requests','bookings_all']) ? 'menu-open' : '' ?>">
            <a href="#" class="nav-link <?= in_array($current, ['bookings','bookings_requests','bookings_all']) ? 'active' : '' ?>">
              <i class="material-symbols-outlined">event</i>
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
              <i class="material-symbols-outlined">assignment_return</i>
              <p>Loans</p>
            </a>
          </li>

          <!-- Supplier -->
           <!-- <li class="nav-item">
            <a href="index.php?page=suppliar" class="nav-link <?php echo $actual_link=='suppliar'?'active':'';?>">
              <i class="material-symbols-outlined">group</i>
              <p>
                Supplier
              </p>
            </a>
          </li> -->

          <!-- Users -->
           <li class="nav-item">
             <a href="index.php?page=users_list" class="nav-link <?php echo $actual_link=='users_list'?'active':'';?>">
               <i class="material-symbols-outlined">diversity_3</i>
               <p>Users</p>
             </a>
           </li>

          <!-- Staff 
           <li class="nav-item has-treeview">
            <a href="#" class="nav-link <?php 
              if ($actual_link == 'add_stuff' || $actual_link =='staff_list') {echo "active";
          }else{
            echo "";
          }
            ?>">
               <i  class="material-symbols-outlined">diversity_3</i>
              <p>
                Staff
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="index.php?page=add_stuff" class="nav-link <?php echo $actual_link=='add_stuff'?'active':'';?>">
                 
                  <p>Add Staff</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="index.php?page=staff_list" class="nav-link <?php echo $actual_link=='staff_list'?'active':'';?>">
                 
                  <p>Staff list</p>
                </a>
              </li>
            </ul>
          </li> -->
        
          <!-- Reports -->
          <li class="nav-item">
             <a href="index.php?page=reports" class="nav-link <?php echo $actual_link=='reports'?'active':'';?>">
               <i class="material-symbols-outlined">assignment_returned</i>
               <p>Reports</p>
             </a>
           </li>

          <!-- Settings -->
          <li class="nav-item has-treeview">
              <a href="#" class="nav-link <?php 
                if ($actual_link == 'backup_database') {echo "active";
            }else{
              echo "";
            }
              ?>">
                <i class="material-symbols-outlined">settings</i>
                <p>
                  Setting
                  <i class="right fas fa-angle-left"></i>
                </p>
              </a>
              <ul class="nav nav-treeview">
                <li class="nav-item">
                  <a href="index.php?page=backup_database" class="nav-link <?php echo $actual_link=='backup_database'?'active':'';?>">
                    <!-- <i class="far fa-circle nav-icon"></i> -->
                    <p>Backup database</p>
                  </a>
                </li>
              
              </ul>
          </li>
      </nav>
      <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
  </aside>

    </div>
    <?php require_once 'inc/catagory_modal.php'; ?>
    <?php require_once 'inc/suppliar_modal.php'; ?>
    <?php require_once 'inc/expense_catagory_modal.php'; ?>