<?php
// pages/user_add.php (Admin-only; strong password policy; min length 8; wired to config/connection; router-friendly links)
if (!isset($_SESSION)) { session_start(); }
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

$isAdmin = isset($_SESSION['user_role']) && strtolower($_SESSION['user_role']) === 'admin';
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
if (!$isAdmin) {
  echo "<div class='content-wrapper'><section class='content'><div class='container-fluid'><div class='alert alert-danger mt-3'>You do not have permission to add users.</div></div></section></div>";
  return;
}

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

$flash = ['type'=>null,'msg'=>null];

// Strong password validator: 8+ chars, upper, lower, digit, special, no spaces, not containing username
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    if (!($db instanceof PDO)) { throw new Exception("Database connection not found. Tried: ".implode(', ', $included)); }

    $id       = (int)($_POST['id'] ?? 0);
    $username = trim($_POST['username'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    $confirm  = (string)($_POST['confirm_password'] ?? '');
    $role     = strtolower(trim($_POST['user_role'] ?? 'user'));
    $photo    = trim($_POST['photo'] ?? '');

    if (!$id || $id < 1)                 throw new Exception("Please provide a valid numeric ID.");
    if ($username === '')                throw new Exception("Username is required.");
    if ($password === '')                throw new Exception("Password is required.");
    if ($password !== $confirm)          throw new Exception("Passwords do not match.");
    if (!in_array($role, ['admin','user'])) $role = 'user';

    // Strong password policy
    if ($msg = validate_password_strong($password, $username)) {
      throw new Exception($msg);
    }

    // Duplicate check
    $st = $db->prepare("SELECT COUNT(*) FROM `user` WHERE `id`=:id OR `username`=:u");
    $st->execute([':id'=>$id, ':u'=>$username]);
    if ((int)$st->fetchColumn() > 0) throw new Exception("ID or Username already exists.");

    // Insert (MD5 to match existing login flow)
    $ins = $db->prepare("INSERT INTO `user` (`id`,`username`,`password`,`user_role`,`photo`) VALUES (:id,:u,MD5(:p),:r,:ph)");
    $ins->execute([':id'=>$id, ':u'=>$username, ':p'=>$password, ':r'=>$role, ':ph'=>$photo ?: null]);

    $flash = ['type'=>'success','msg'=>"User created: <strong>".h($username)."</strong> (ID: ".h($id).")"];
  } catch (Throwable $e) {
    $flash = ['type'=>'danger','msg'=>$e->getMessage()];
  }
}
?>
<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <h1 class="m-0 text-dark">Add User</h1>
      <a class="btn btn-sm btn-outline-secondary" href="index.php?page=users_list">Back to Users</a>
    </div>
  </div>

  <section class="content">
    <div class="container-fluid">
      <?php if ($flash['type'] && $flash['msg']): ?>
        <div class="alert alert-<?=h($flash['type'])?>"><?= $flash['msg'] ?></div>
      <?php endif; ?>

      <div class="card">
        <div class="card-body">
          <form method="post" action="">
            <div class="form-row">
              <div class="form-group col-md-3">
                <label for="id">ID</label>
                <input type="number" class="form-control" id="id" name="id" min="1" required>
              </div>
              <div class="form-group col-md-4">
                <label for="username">Username</label>
                <input type="text" class="form-control" id="username" name="username" required>
              </div>
              <div class="form-group col-md-5">
                <label for="password">Password</label>
                <input type="password" class="form-control" id="password" name="password" minlength="8" required>
                <small class="form-text text-muted">
                  Must be at least 8 characters with upper, lower, number, and symbol. No spaces. Must not contain the username.
                </small>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group col-md-5">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="8" required>
              </div>
              <div class="form-group col-md-3">
                <label for="user_role">Role</label>
                <select class="form-control" id="user_role" name="user_role">
                  <option value="user" selected>user</option>
                  <option value="admin">admin</option>
                </select>
              </div>
              <div class="form-group col-md-4">
                <label for="photo">Photo URL (optional)</label>
                <input type="url" class="form-control" id="photo" name="photo" placeholder="https://...">
              </div>
            </div>

            <div class="text-right">
              <button type="submit" class="btn btn-success">Create User</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </section>
</div>
