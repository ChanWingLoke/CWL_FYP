<?php
// pages/user_delete.php (Admin-only; confirm + delete; FULL URL redirects based on current request path)

if (!isset($_SESSION)) {
  session_start();
}

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// Helper: absolute URL for a path in the same directory as /index.php in the current request (e.g., /ample)
function app_url($path = '') {
  $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
  $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
  // Use REQUEST_URI to capture the directory actually used in the browser (works even when this file is included)
  $dir    = rtrim(dirname(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)), '/\\'); // e.g., '/ample'
  return $scheme . '://' . $host . $dir . '/' . ltrim($path, '/');
}

// Require login
if (!isset($_SESSION['user_id'])) {
  header('Location: ' . app_url('login.php'));
  exit;
}

// Admin-only
$isAdmin = isset($_SESSION['user_role']) && strtolower($_SESSION['user_role']) === 'admin';
if (!$isAdmin) {
  echo "
  <div class='content-wrapper'>
    <section class='content'>
      <div class='container-fluid'>
        <div class='alert alert-danger mt-3'>You do not have permission to delete users.</div>
      </div>
    </section>
  </div>";
  return;
}

// Bring DB into scope
$included = [];
$paths = [
  __DIR__ . '/../config.php',
  __DIR__ . '/../connection.php',
  __DIR__ . '/../../config.php',
  __DIR__ . '/../../connection.php',
];
foreach ($paths as $inc) {
  if (is_file($inc)) { include_once $inc; $included[] = $inc; }
}
if (!isset($db) && isset($pdo) && ($pdo instanceof PDO)) { $db = $pdo; }

$db_error = null;
if (!($db instanceof PDO)) {
  $db_error = "Database connection not found. Tried: " . h(implode(', ', $included));
}

// Target user id
$uid = isset($_GET['id']) ? trim($_GET['id']) : '';
if ($uid === '') {
  echo "
  <div class='content-wrapper'>
    <section class='content'>
      <div class='container-fluid'>
        <div class='alert alert-danger mt-3'>Missing user id.</div>
      </div>
    </section>
  </div>";
  return;
}

// Build absolute URLs (host + correct app folder)
$listUrl = app_url('index.php?page=users_list');
$viewUrl = app_url('index.php?page=user_view&id=' . urlencode($uid));

// Load user
$u = null;
if (!$db_error) {
  try {
    $st = $db->prepare("SELECT `id`,`username`,`user_role`,`photo` FROM `user` WHERE `id`=:id LIMIT 1");
    $st->execute([':id' => $uid]);
    $u = $st->fetch(PDO::FETCH_ASSOC);
  } catch (Throwable $e) { $db_error = $e->getMessage(); }
}

// Handle POST (confirm delete)
$flash = ['type' => null, 'msg' => null];
if (!$db_error && $_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    if (!$u) { throw new Exception("User not found."); }
    if ((string)$uid === (string)$_SESSION['user_id']) {
      throw new Exception("You cannot delete your own account while logged in.");
    }

    $del = $db->prepare("DELETE FROM `user` WHERE `id`=:id LIMIT 1");
    $del->execute([':id' => $uid]);

    if ($del->rowCount() > 0) {
      while (function_exists('ob_get_level') && ob_get_level() > 0) { @ob_end_clean(); }
      header('Location: ' . $listUrl . '&msg=' . urlencode('User #' . $uid . ' deleted.') . '&type=success');
      exit;
    } else {
      throw new Exception("Delete failed or user already removed.");
    }
  } catch (Throwable $e) {
    $flash = ['type' => 'danger', 'msg' => $e->getMessage() .
      " If the user has dependent records (e.g., bookings/warranties), delete/transfer them first."];
  }
}
?>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <h1 class="m-0 text-dark">Delete User #<?= h($uid) ?></h1>
      <div>
        <a class="btn btn-sm btn-outline-secondary" href="<?= h($listUrl) ?>">Back to Users</a>
        <?php if ($u): ?>
          <a class="btn btn-sm btn-outline-primary" href="<?= h($viewUrl) ?>">View</a>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <section class="content">
    <div class="container-fluid">
      <?php if ($db_error): ?>
        <div class="alert alert-danger mt-2"><?= h($db_error) ?></div>
      <?php endif; ?>

      <?php if ($flash['type'] && $flash['msg']): ?>
        <div class="alert alert-<?= h($flash['type']) ?>"><?= $flash['msg'] ?></div>
      <?php endif; ?>

      <div class="card">
        <div class="card-body">
          <?php if (!$u && !$db_error): ?>
            <div class="alert alert-warning">User not found.</div>
          <?php else: ?>
            <div class="mb-3">
              <p>You're about to delete the following user:</p>
              <ul class="mb-3">
                <li><strong>ID:</strong> <?= h($u['id']) ?></li>
                <li><strong>Username:</strong> <?= h($u['username']) ?></li>
                <li><strong>Role:</strong> <?= h($u['user_role']) ?></li>
              </ul>
              <div class="alert alert-warning">
                This action cannot be undone. If this user has related records (e.g., bookings/warranties),
                database constraints may block deletion.
              </div>
            </div>

            <form method="post" action="">
              <button type="submit" class="btn btn-danger">Yes, delete this user</button>
              <a href="<?= h($listUrl) ?>" class="btn btn-outline-secondary"
                 onclick="if (window.history.length > 1) { history.back(); return false; }">
                Cancel
              </a>
            </form>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </section>
</div>
