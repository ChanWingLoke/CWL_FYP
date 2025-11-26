<?php

// --- 1. Session & Auth Guard ---
if (!isset($_SESSION)) { session_start(); }
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

$isAdmin = isset($_SESSION['user_role']) && strtolower($_SESSION['user_role']) === 'admin';
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

if (!$isAdmin) {
  echo "<div class='content-wrapper'><section class='content'><div class='container-fluid'><div class='alert alert-danger mt-3'>You do not have permission to edit users.</div></div></section></div>";
  return;
}

// --- 2. Database Connection Wiring ---
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

// --- 3. Password Policy Helper (Same as user_add.php) ---
function validate_password_strong($password, $username) {
  if (strlen($password) < 8) return "Password must be at least 8 characters.";
  if (!preg_match('/[A-Z]/', $password)) return "Password must include at least one uppercase letter.";
  if (!preg_match('/[a-z]/', $password)) return "Password must include at least one lowercase letter.";
  if (!preg_match('/[0-9]/', $password)) return "Password must include at least one number.";
  if (!preg_match('/[^A-Za-z0-9]/', $password)) return "Password must include at least one symbol.";
  if (preg_match('/\s/', $password)) return "Password cannot contain spaces.";
  if ($username !== '' && stripos($password, $username) !== false) return "Password cannot contain the username.";
  
  $common = ['password','password123','qwerty','123456','123456789','letmein','admin','welcome'];
  foreach ($common as $w) { if (strcasecmp($password, $w) === 0) return "Password is too common."; }
  
  return null;
}

// --- 4. Load Target User ---
$uid = isset($_GET['id']) ? trim($_GET['id']) : '';
if ($uid === '') {
  echo "<div class='content-wrapper'><section class='content'><div class='container-fluid'><div class='alert alert-danger mt-3'>Missing user id.</div></div></section></div>";
  return;
}

$flash = ['type'=>null,'msg'=>null];
$u = null;

try {
  if (!$db_error) {
    $st = $db->prepare("SELECT `id`,`username`,`user_role`,`photo` FROM `user` WHERE `id`=:id LIMIT 1");
    $st->execute([':id'=>$uid]);
    $u = $st->fetch(PDO::FETCH_ASSOC);
    
    if (!$u) {
      echo "<div class='content-wrapper'><section class='content'><div class='container-fluid'><div class='alert alert-warning mt-3'>User not found.</div></div></section></div>";
      return;
    }
  }
} catch (Throwable $e) {
  $db_error = $e->getMessage();
}

// --- 5. Handle Update Submission ---
if (!$db_error && $_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    // Input Retrieval
    $username = trim($_POST['username'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    $confirm  = (string)($_POST['confirm_password'] ?? '');
    $role     = strtolower(trim($_POST['user_role'] ?? 'user'));
    $photo    = trim($_POST['photo'] ?? '');

    // Basic Validation
    if ($username === '') throw new Exception("Username is required.");
    if (!in_array($role, ['admin','user'])) $role = 'user';
    
    // Logic Check: If the user typed in EITHER password field
    if ($password !== '' || $confirm !== '') {
        if ($password === '') throw new Exception("Please enter the new password (you only filled the confirmation).");
        if ($confirm === '')  throw new Exception("Please confirm the new password.");
        if ($password !== $confirm) throw new Exception("Passwords do not match.");

        // Apply Strong Password Policy
        if ($msg = validate_password_strong($password, $username)) {
            throw new Exception($msg);
        }
    }

    // Duplicate username check (excluding current user ID)
    $du = $db->prepare("SELECT COUNT(*) FROM `user` WHERE `username`=:u AND `id`<>:id");
    $du->execute([':u'=>$username, ':id'=>$uid]);
    if ((int)$du->fetchColumn() > 0) throw new Exception("Username already exists.");

    // Dynamic Update Query
    if ($password !== '') {
      // Update ALL fields (Password changed)
      $sql = "UPDATE `user` SET `username`=:u, `password`=MD5(:p), `user_role`=:r, `photo`=:ph, `last_update_at`=NOW() 
              WHERE `id`=:id";
      $params = [':u'=>$username, ':p'=>$password, ':r'=>$role, ':ph'=>$photo ?: null, ':id'=>$uid];
    } else {
      // Update fields EXCEPT password
      $sql = "UPDATE `user` SET `username`=:u, `user_role`=:r, `photo`=:ph, `last_update_at`=NOW() 
              WHERE `id`=:id";
      $params = [':u'=>$username, ':r'=>$role, ':ph'=>$photo ?: null, ':id'=>$uid];
    }
    
    $up = $db->prepare($sql);
    $up->execute($params);

    $flash = ['type'=>'success','msg'=>"User <strong>".h($username)."</strong> (ID: ".h($uid).") updated."];
    
    // Refresh user data for display
    $st = $db->prepare("SELECT `id`,`username`,`user_role`,`photo` FROM `user` WHERE `id`=:id");
    $st->execute([':id'=>$uid]);
    $u = $st->fetch(PDO::FETCH_ASSOC);

  } catch (Throwable $e) {
    $flash = ['type'=>'danger','msg'=>$e->getMessage()];
  }
}
?>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <h1 class="m-0 text-dark">Edit User #<?= h($uid) ?></h1>
      <div>
        <a class="btn btn-sm btn-outline-secondary" href="index.php?page=users_list">
          <i class="fas fa-arrow-left"></i> Back to Users
        </a>
        <a class="btn btn-sm btn-outline-primary" href="index.php?page=user_view&id=<?= urlencode($uid) ?>">
          <i class="fas fa-eye"></i> View
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
        <div class="alert alert-<?= h($flash['type']) ?>"><?= $flash['msg'] ?></div>
      <?php endif; ?>

      <div class="card">
        <div class="card-body">
          <form method="post" action="">
            <div class="form-row">
              <div class="form-group col-md-3">
                <label for="id">ID</label>
                <input type="text" class="form-control" id="id" value="<?= h($u['id']) ?>" disabled>
                <small class="form-text text-muted">User IDs cannot be changed.</small>
              </div>
              <div class="form-group col-md-5">
                <label for="username">Username</label>
                <input type="text" class="form-control" id="username" name="username" value="<?= h($u['username']) ?>" required>
              </div>
              <div class="form-group col-md-4">
                <label for="user_role">Role</label>
                <select class="form-control" id="user_role" name="user_role">
                  <option value="user" <?= strtolower($u['user_role'])==='user'?'selected':''; ?>>user</option>
                  <option value="admin" <?= strtolower($u['user_role'])==='admin'?'selected':''; ?>>admin</option>
                </select>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group col-md-6">
                <label for="password">New Password (optional)</label>
                <input type="password" class="form-control" id="password" name="password" placeholder="Leave blank to keep current">
                <small class="form-text text-muted">
                  If provided: Min 8 chars, 1 upper, 1 lower, 1 number, 1 symbol. No spaces.
                </small>
              </div>
              <div class="form-group col-md-6">
                <label for="confirm_password">Confirm New Password</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Retype new password">
              </div>
            </div>

            <div class="form-row">
              <div class="form-group col-md-12">
                <label for="photo">Photo URL (optional)</label>
                <input type="url" class="form-control" id="photo" name="photo" value="<?= h($u['photo'] ?? '') ?>" placeholder="https://...">
              </div>
            </div>

            <div class="text-right">
              <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </section>
</div>