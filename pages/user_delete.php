<?php

// --- 1. Session & Helper Functions ---
if (!isset($_SESSION)) { session_start(); }

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

/**
 * Builds an absolute URL to ensure redirects work correctly
 * regardless of inclusion depth or server configuration.
 */
function app_url($path = '') {
  $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
  $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
  // Use REQUEST_URI to find the actual directory of the current page
  $dir    = rtrim(dirname(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)), '/\\'); 
  return $scheme . '://' . $host . $dir . '/' . ltrim($path, '/');
}

// --- 2. Auth Guard ---
if (!isset($_SESSION['user_id'])) {
  header('Location: ' . app_url('login.php'));
  exit;
}

$isAdmin = isset($_SESSION['user_role']) && strtolower($_SESSION['user_role']) === 'admin';
if (!$isAdmin) {
  echo "<div class='content-wrapper'><section class='content'><div class='container-fluid'><div class='alert alert-danger mt-3'>You do not have permission to delete users.</div></div></section></div>";
  return;
}

// --- 3. Database Wiring ---
$included = [];
$paths = [
  __DIR__ . '/../config.php',
  __DIR__ . '/../connection.php',
  __DIR__ . '/../../config.php',
  __DIR__ . '/../../connection.php',
];
foreach ($paths as $inc) { if (is_file($inc)) { include_once $inc; $included[] = $inc; } }
if (!isset($db) && isset($pdo) && ($pdo instanceof PDO)) { $db = $pdo; }

$db_error = null;
if (!($db instanceof PDO)) {
  $db_error = "Database connection not found. Tried: " . h(implode(', ', $included));
}

// --- 4. Load Target User ---
$uid = isset($_GET['id']) ? trim($_GET['id']) : '';
if ($uid === '') {
  echo "<div class='content-wrapper'><section class='content'><div class='container-fluid'><div class='alert alert-danger mt-3'>Missing user id.</div></div></section></div>";
  return;
}

$listUrl = app_url('index.php?page=users_list');
$u = null;

if (!$db_error) {
  try {
    $st = $db->prepare("SELECT `id`,`username`,`user_role`,`photo` FROM `user` WHERE `id`=:id LIMIT 1");
    $st->execute([':id' => $uid]);
    $u = $st->fetch(PDO::FETCH_ASSOC);
  } catch (Throwable $e) { 
    $db_error = $e->getMessage(); 
  }
}

// --- 5. Handle Delete Submission ---
$flash = ['type' => null, 'msg' => null];

if (!$db_error && $_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    // Validations
    if (!$u) { throw new Exception("User not found."); }
    
    // Prevent self-deletion
    if ((string)$uid === (string)$_SESSION['user_id']) {
      throw new Exception("You cannot delete your own account while logged in.");
    }

    // Attempt Deletion
    $del = $db->prepare("DELETE FROM `user` WHERE `id`=:id LIMIT 1");
    $del->execute([':id' => $uid]);

    if ($del->rowCount() > 0) {
      // Clear output buffer to ensure header redirect works
      while (function_exists('ob_get_level') && ob_get_level() > 0) { @ob_end_clean(); }
      header('Location: ' . $listUrl . '&msg=' . urlencode('User #' . $uid . ' deleted.') . '&type=success');
      exit;
    } else {
      throw new Exception("Delete failed or user already removed.");
    }

  } catch (Throwable $e) {
    // Database constraint errors (foreign keys) usually end up here
    $flash = ['type' => 'danger', 'msg' => $e->getMessage() . 
      " (Hint: This user might have bookings or warranties linked to them. Delete or reassign those records first.)"];
  }
}
?>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <h1 class="m-0 text-dark">Delete User #<?= h($uid) ?></h1>
      <div>
        <a class="btn btn-sm btn-outline-secondary" href="index.php?page=users_list">
          <i class="fas fa-arrow-left"></i> Back to Users
        </a>        
      </div>
    </div>
  </div>

  <section class="content">
    <div class="container-fluid">
      
      <?php if ($db_error): ?>
        <div class="alert alert-danger mt-2"><?= h($db_error) ?></div>
      <?php endif; ?>

      <?php if ($flash['type'] && $flash['msg']): ?>
        <div class="alert alert-<?= h($flash['type']) ?>"><?= h($flash['msg']) ?></div>
      <?php endif; ?>

      <div class="card">
        <div class="card-body">
          <?php if (!$u && !$db_error): ?>
            <div class="alert alert-warning">User not found.</div>
          <?php else: ?>
            <div class="mb-4">
              <h5 class="text-danger">Warning: Permanent Action</h5>
              <p>You are about to delete the following user:</p>
              
              <div class="p-3 bg-light border rounded mb-3">
                <dl class="row mb-0">
                  <dt class="col-sm-2">ID:</dt>
                  <dd class="col-sm-10"><?= h($u['id']) ?></dd>
                  
                  <dt class="col-sm-2">Username:</dt>
                  <dd class="col-sm-10"><strong><?= h($u['username']) ?></strong></dd>
                  
                  <dt class="col-sm-2">Role:</dt>
                  <dd class="col-sm-10"><?= h(ucfirst($u['user_role'])) ?></dd>
                </dl>
              </div>

              <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                This action cannot be undone. If this user has related records (e.g., active bookings), 
                the deletion may be blocked by database constraints.
              </div>
            </div>

            <form method="post" action="">
              <button type="submit" class="btn btn-danger">
                <i class="fas fa-trash"></i> Yes, delete this user
              </button>
              <a href="index.php?page=users_list" class="btn btn-outline-secondary">Cancel</a>
            </form>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </section>
</div>