<?php
require_once 'app/init.php';

if ($Ouser->is_login() == false) {
  header("location:login.php");
}

// derive current page for sidebar highlight
$actual_link = explode('=', "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");
$actual_link = end($actual_link);

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$current = strtolower(basename($path, '.php'));

if ($current === 'index' || $current === '') {
    $current = 'dashboard';
}

if (isset($_GET['page']) && $_GET['page']) {
    $current = strtolower($_GET['page']);
}

// fetch logged-in user info
$login_user_id = $_SESSION['user_id'] ?? null;
$user = $login_user_id ? $obj->find('user','id',$login_user_id) : null;
$displayName = $user->username ?? 'User';

// Normalize and verify the photo path
$photo = isset($user->photo) ? trim((string)$user->photo) : '';

// Build filesystem path to check existence (header.php is in inc/, so go up one folder)
$projectRoot = realpath(__DIR__ . '/..');
$publicDefault = 'dist/img/log.jpg'; // safe fallback that ships with template

$avatar = $publicDefault;
if ($photo !== '') {
  $photoRel = ltrim($photo, '/\\');
  $photoFs  = $projectRoot . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $photoRel);
  if (file_exists($photoFs)) {
    $avatar = $photoRel;
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta http-equiv="x-ua-compatible" content="ie=edge">
  <title>Ample IT Asset Management System</title>

  <!-- Font Awesome Icons -->
  <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
  <!-- overlayScrollbars -->
  <link rel="stylesheet" href="plugins/overlayScrollbars/css/OverlayScrollbars.min.css">
  <!-- DataTables -->
  <link href='//cdn.datatables.net/1.10.19/css/jquery.dataTables.min.css' rel='stylesheet' type='text/css'>
  <!-- datepicker css -->
  <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css">
  <!-- select2 css -->
  <link rel="stylesheet" type="text/css" href="plugins/select2/css/select2.min.css"/>
  <!-- custom css -->
  <link rel="stylesheet" href="assets/css/style.css">
  <!-- date range picker -->
  <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
  <!-- Theme style -->
  <link rel="stylesheet" href="dist/css/adminlte.min.css">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
  <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700" rel="stylesheet">

  <style>
    .select2-container .select2-selection--single { height: 37px; }

    .navbar-nav.ml-auto { align-items: center; }
    .navbar-nav .nav-link { display: flex; align-items: center; }
    .navbar-nav .material-symbols-outlined { line-height: 1; font-size: 22px; }

    /* Google Translate stacked label + credit */
    #google_translate_element { display: flex; flex-direction: column; align-items: flex-start; line-height: 1.2; }
    #google_translate_element .goog-te-gadget { display: flex; flex-direction: column; align-items: flex-start; }

    /* Clean, watermark-free preloader */
    .preloader {
      position: fixed;
      inset: 0;
      background: rgba(255, 255, 255, 0.9);
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 2000;
      opacity: 1;
      visibility: visible;
      transition: opacity .2s ease, visibility .2s ease;
    }
    .preloader.hidden { opacity: 0; visibility: hidden; }
    .preloader-box {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 14px 18px;
      border-radius: 10px;
      background: #ffffff;
      box-shadow: 0 8px 30px rgba(0,0,0,.08);
      border: 1px solid rgba(0,0,0,.06);
    }
    .preloader .spinner {
      width: 22px;
      height: 22px;
      border: 3px solid #e5e7eb;      /* light gray */
      border-top-color: #3b82f6;       /* blue accent */
      border-radius: 50%;
      animation: spin 0.8s linear infinite;
    }
    .preloader-text {
      font: 600 14px/1.2 "Source Sans Pro", system-ui, -apple-system, Segoe UI, Roboto, "Helvetica Neue", Arial, sans-serif;
      color: #374151;
    }
    @keyframes spin { to { transform: rotate(360deg); } }

    /* Kill old watermark-based loaders if they still exist anywhere */
    #page, #loading { display: none !important; background: none !important; }

    /* --- Sidebar scroll fix (reach the last items like Settings) --- */
    .layout-fixed .main-sidebar .sidebar{
      /* available height = viewport - fixed top navbar (~57px on desktop) */
      height: calc(100vh - 57px);
      overflow-y: auto;
      overscroll-behavior: contain;   /* keep scroll from bubbling the page */
      padding-bottom: 80px;           /* breathing room so last item is clickable */
    }

    /* If you have very long submenus, a little extra space helps */
    .main-sidebar .nav-sidebar{
      padding-bottom: 72px;
    }

    /* Slightly different topbar height on small screens */
    @media (max-width: 991.98px){
      .layout-fixed .main-sidebar .sidebar{
        height: calc(100vh - 56px);
      }
    }

  </style>

</head>
<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed layout-footer-fixed">

<!-- Clean preloader (no watermark) -->
<div id="app-preloader" class="preloader" aria-hidden="true">
  <div class="preloader-box">
    <div class="spinner"></div>
    <div class="preloader-text">Loading…</div>
  </div>
</div>

<div class="wrapper">
  <!-- Navbar -->
  <nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <!-- Left navbar links -->
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" data-widget="pushmenu" href="#"><i class="fas fa-bars"></i></a>
      </li>
    </ul>

    <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto align-items-center">
      <!-- Keep Google Translate -->
      <div id="google_translate_element"></div>

      <!-- Notification icon -->
      <li class="nav-item dropdown" id="notifDropdown">
        <a class="nav-link" data-toggle="dropdown" href="#" title="Notifications">
          <i class="material-symbols-outlined">notifications</i>
          <span class="badge badge-danger navbar-badge" id="notifBadge" style="display:none">0</span>
        </a>
        <div class="dropdown-menu dropdown-menu-right p-0" style="min-width:320px; max-height:60vh; overflow:auto;">
          <div class="px-3 py-2 border-bottom d-flex justify-content-between align-items-center">
            <strong>Notifications</strong>
            <a href="#" id="notifMarkAll" class="small">Mark all as read</a>
          </div>
          <div id="notifList" class="list-group list-group-flush">
            <div class="p-3 text-muted small">Loading…</div>
          </div>
          <div class="p-2 border-top text-center">
            <a href="index.php?page=notifications" class="small">View all</a>
          </div>
        </div>
      </li>

      <script>
      (function(){
        function escapeHtml(s){return String(s).replace(/[&<>"']/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];});}
        function render(data){
          var badge=document.getElementById('notifBadge');
          if(badge){ if(data.unread>0){badge.textContent=data.unread;badge.style.display='inline-block';} else {badge.style.display='none';} }
          var list=document.getElementById('notifList');
          if(!list)return;
          list.innerHTML='';
          if(!data.items||!data.items.length){ list.innerHTML='<div class="p-3 text-muted small">No notifications.</div>'; return; }
          data.items.forEach(function(n){
            var a=document.createElement('a');
            a.href=n.link?n.link:'#';
            a.className='list-group-item list-group-item-action d-flex justify-content-between align-items-start '+(n.is_read==0?'bg-light':'');
            a.innerHTML='<div class="ms-2 me-auto"><div class="fw-bold">'+escapeHtml(n.title)+'</div>'+(n.body?'<div class="small text-muted">'+escapeHtml(n.body)+'</div>':'')+'<div class="small text-secondary">'+escapeHtml(n.created_at)+'</div></div>';
            a.addEventListener('click',function(){ fetch('app/action/notifications_feed.php?read='+encodeURIComponent(n.id)); });
            list.appendChild(a);
          });
        }
        function load(){ fetch('app/action/notifications_feed.php').then(r=>r.json()).then(function(d){ if(d&&d.ok){ render(d);} }); }
        document.addEventListener('click',function(e){ if(e.target&&e.target.id==='notifMarkAll'){ e.preventDefault(); fetch('app/action/notifications_feed.php?mark=all').then(load); } });
        load(); setInterval(load,30000);
      })();
      </script>

      <!-- Mail icon -->
      <!-- <li class="nav-item">
        <a class="nav-link" href="index.php?page=inbox" title="Mail">
          <i class="material-symbols-outlined">mail</i>
        </a>
      </li> -->

      <!-- Profile Dropdown (dynamic user) -->
      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle d-flex align-items-center" data-toggle="dropdown" href="#">
          <img src="<?= htmlspecialchars($avatar) ?>" alt="Profile"
               style="width:32px;height:32px;border-radius:50%;object-fit:cover;">
          <span class="ml-2"><?= htmlspecialchars($displayName) ?></span>
        </a>
        <div class="dropdown-menu dropdown-menu-right p-0">
          <a href="index.php?page=profile" class="dropdown-item p-1">
            <i class="material-symbols-outlined">person</i> Profile
          </a>
          <a href="app/action/logout.php" class="dropdown-item pic p-1">
            <i class="material-symbols-outlined">logout</i> Logout
          </a>
        </div>
      </li>
    </ul>
  </nav>
  <!-- /.navbar -->
