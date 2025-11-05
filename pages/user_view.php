<?php
// pages/user_view.php
if (!isset($_SESSION)) { session_start(); }
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

$isAdmin = isset($_SESSION['user_role']) && strtolower($_SESSION['user_role']) === 'admin';
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// Bring DB into scope (same wiring as users_list.php)
$included = [];
$paths = [
  __DIR__ . '/../config.php',
  __DIR__ . '/../connection.php',
  __DIR__ . '/../../config.php',
  __DIR__ . '/../../connection.php',
];
foreach ($paths as $inc) { if (is_file($inc)) { include_once $inc; $included[] = $inc; } }
if (!isset($db) && isset($pdo) && ($pdo instanceof PDO)) { $db = $pdo; }

$id = isset($_GET['id']) ? trim($_GET['id']) : '';
if ($id === '') {
  http_response_code(400);
  echo "<div class='content-wrapper'><section class='content'><div class='container-fluid'><div class='alert alert-danger mt-3'>Missing user id.</div></div></section></div>";
  exit;
}

$db_error = null; $u = null;
if (isset($db) && $db instanceof PDO) {
  try {
    $stmt = $db->prepare("SELECT `id`,`username`,`user_role`,`added_date`,`last_update_at`,`photo` FROM `user` WHERE `id` = :id");
    $stmt->execute([':id' => $id]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC);
  } catch (Throwable $e) { $db_error = $e->getMessage(); }
} else { $db_error = "Database connection not found. Tried: ".h(implode(', ', $included)); }
?>
<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <h1 class="m-0 text-dark">User <?= $u ? '#'.h($u['id']) : '' ?></h1>
      <div>
        <a class="btn btn-sm btn-outline-secondary" href="index.php?page=users_list.php">Back to Users</a>
        <?php if ($isAdmin && $u): ?>
          <a class="btn btn-sm btn-primary" href="user_edit.php?id=<?=urlencode($u['id'])?>">Edit</a>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <section class="content">
    <div class="container-fluid">
      <?php if ($db_error): ?>
        <div class="alert alert-danger mt-2"><?= h($db_error) ?></div>
      <?php endif; ?>
      <?php if (!$u && !$db_error): ?>
        <div class="alert alert-warning mt-2">User not found.</div>
      <?php endif; ?>

      <?php if ($u): ?>
      <div class="card">
        <div class="card-body">
          <div class="row">
            <div class="col-md-3 text-center">
              <?php if (!empty($u['photo'])): ?>
                <img src="<?=h($u['photo'])?>" alt="photo" style="height:120px;width:120px;object-fit:cover;border-radius:50%;border:1px solid #ddd;">
              <?php else: ?>
                <div class="text-muted small">No photo</div>
              <?php endif; ?>
            </div>
            <div class="col-md-9">
              <dl class="row mb-0">
                <dt class="col-sm-3">ID</dt><dd class="col-sm-9"><?=h($u['id'])?></dd>
                <dt class="col-sm-3">Username</dt><dd class="col-sm-9"><?=h($u['username'])?></dd>
                <dt class="col-sm-3">Role</dt>
                <dd class="col-sm-9">
                  <?php if (strtolower($u['user_role']) === 'admin'): ?>
                    <span class="badge badge-danger">admin</span>
                  <?php else: ?>
                    <span class="badge badge-secondary"><?=h($u['user_role'] ?: 'user')?></span>
                  <?php endif; ?>
                </dd>
                <dt class="col-sm-3">Added</dt><dd class="col-sm-9"><?=h($u['added_date'])?></dd>
                <dt class="col-sm-3">Updated</dt><dd class="col-sm-9"><?=h($u['last_update_at'] ?? '')?></dd>
              </dl>
            </div>
          </div>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </section>
</div>
