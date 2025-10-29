<?php
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$isAdmin = isset($_SESSION['user_role']) && strtolower($_SESSION['user_role'])==='admin';
?>
<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <h1 class="m-0 text-dark">Maintenance — Requests</h1>
      <ol class="breadcrumb float-sm-right mb-0">
        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
        <li class="breadcrumb-item active">Requests</li>
      </ol>
    </div>
  </div>
  <section class="content">
    <div class="container-fluid">
      <?php if (!$isAdmin): ?>
        <div class="alert alert-danger">You do not have permission to view this page.</div>
      <?php else: ?>
        <div class="alert alert-info">Admin queue stub. Next step: list pending requests with approve/reject.</div>
      <?php endif; ?>
    </div>
  </section>
</div>
