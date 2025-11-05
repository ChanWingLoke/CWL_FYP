<?php
// pages/users_list.php (wired to config/connection; routed links)
if (!isset($_SESSION)) { session_start(); }
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

$isAdmin = isset($_SESSION['user_role']) && strtolower($_SESSION['user_role']) === 'admin';
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// Bring DB into scope
$included = [];
$paths = [
  __DIR__ . '/../config.php',
  __DIR__ . '/../connection.php',
  __DIR__ . '/../../config.php',
  __DIR__ . '/../../connection.php',
];
foreach ($paths as $inc) { if (is_file($inc)) { include_once $inc; $included[] = $inc; } }
if (!isset($db) && isset($pdo) && ($pdo instanceof PDO)) { $db = $pdo; }

$q  = isset($_GET['q']) ? trim($_GET['q']) : '';
$pg = isset($_GET['pg']) ? (int)$_GET['pg'] : (isset($_GET['page_num']) ? (int)$_GET['page_num'] : 1);
if ($pg < 1) $pg = 1;
$pp  = 20;
$off = ($pg - 1) * $pp;
$rows = []; $total = 0; $pages = 1; $db_error = null;

if (isset($db) && $db instanceof PDO) {
  try {
    $where = "1=1"; $params = [];
    if ($q !== '') { $where .= " AND (CAST(`id` AS CHAR) LIKE :kw OR `username` LIKE :kw OR `user_role` LIKE :kw)"; $params[':kw'] = "%{$q}%"; }
    $stmtC = $db->prepare("SELECT COUNT(*) FROM `user` WHERE $where"); $stmtC->execute($params); $total = (int)$stmtC->fetchColumn();
    $lim = (int)$pp; $ofs = (int)$off;
    $sql = "SELECT `id`,`username`,`user_role`,`added_date`,`last_update_at`,`photo` FROM `user` WHERE $where ORDER BY `added_date` DESC, `id` DESC LIMIT $lim OFFSET $ofs";
    $stmt = $db->prepare($sql); foreach ($params as $k=>$v) { $stmt->bindValue($k, $v, PDO::PARAM_STR); } $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC); $pages = max(1, (int)ceil($total / $pp));
  } catch (Throwable $e) { $db_error = $e->getMessage(); }
} else { $db_error = "Database connection not found. Tried: ".h(implode(', ', $included)); }
?>
<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <h1 class="m-0 text-dark">Users</h1>
      <form class="form-inline" method="get" action="">
        <input type="hidden" name="page" value="users_list.php">
        <div class="input-group">
          <input type="text" name="q" class="form-control" placeholder="Search ID / username / role" value="<?=h($q)?>">
          <div class="input-group-append">
            <button class="btn btn-primary" type="submit">Search</button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <section class="content">
    <div class="container-fluid">
      <?php if ($db_error): ?><div class="alert alert-danger mt-2"><?= h($db_error) ?></div><?php endif; ?>

      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <div><strong><?=number_format($total)?></strong> user<?= $total==1?'':'s' ?></div>
          <?php if ($isAdmin): ?>
            <a href="index.php?page=user_add" class="btn btn-sm btn-success">
              <i class="fas fa-user-plus"></i> Add User
            </a>
          <?php endif; ?>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-striped mb-0">
              <thead>
                <tr>
                  <th style="width:60px">#</th>
                  <th>ID</th>
                  <th>Username</th>
                  <th>Role</th>
                  <th>Added</th>
                  <th>Updated</th>
                  <th>Photo</th>
                  <?php if ($isAdmin): ?><th class="text-right">Actions</th><?php endif; ?>
                </tr>
              </thead>
              <tbody>
              <?php if (!$rows): ?>
                <tr><td colspan="<?= $isAdmin ? 8 : 7 ?>" class="text-center text-muted p-4"><?= $db_error ? '—' : 'No users found.' ?></td></tr>
              <?php else: ?>
                <?php $i = 0; foreach ($rows as $r): $i++; ?>
                  <tr>
                    <td><?= ($off + $i) ?></td>
                    <td><?= h($r['id']) ?></td>
                    <td><?= h($r['username']) ?></td>
                    <td>
                      <?php if (strtolower($r['user_role']) === 'admin'): ?>
                        <span class="badge badge-danger">admin</span>
                      <?php else: ?>
                        <span class="badge badge-secondary"><?= h($r['user_role'] ?: 'user') ?></span>
                      <?php endif; ?>
                    </td>
                    <td><?= h($r['added_date']) ?></td>
                    <td><?= h($r['last_update_at'] ?? '') ?></td>
                    <td>
                      <?php if (!empty($r['photo'])): ?>
                        <img src="<?= h($r['photo']) ?>" alt="photo" style="height:36px;width:36px;object-fit:cover;border-radius:50%;">
                      <?php else: ?>
                        <span class="text-muted small">—</span>
                      <?php endif; ?>
                    </td>
                    <?php if ($isAdmin): ?>
                    <td class="text-right">
                      <a class="btn btn-sm btn-outline-primary" href="index.php?page=user_view&id=<?= urlencode($r['id']) ?>">View</a>
                      <a class="btn btn-sm btn-outline-secondary" href="index.php?page=user_edit&id=<?= urlencode($r['id']) ?>">Edit</a>
                      <a class="btn btn-sm btn-outline-danger" href="index.php?page=user_delete&id=<?= urlencode($r['id']) ?>">Delete</a>
                    </td>
                    <?php endif; ?>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
        <?php if ($pages > 1): ?>
        <div class="card-footer d-flex justify-content-between align-items-center">
          <div>Page <?= (int)$pg ?> / <?= (int)$pages ?></div>
          <nav>
            <ul class="pagination mb-0">
              <?php
              $baseQs = http_build_query(['page' => 'users_list.php', 'q' => $q]);
              $prev = max(1, $pg-1); $next = min($pages, $pg+1);
              ?>
              <li class="page-item <?= $pg<=1?'disabled':'' ?>">
                <a class="page-link" href="?<?= $baseQs ?>&pg=<?= $prev ?>">&laquo;</a>
              </li>
              <?php for ($i=max(1,$pg-2); $i<=min($pages,$pg+2); $i++): ?>
                <li class="page-item <?= $i==$pg?'active':'' ?>">
                  <a class="page-link" href="?<?= $baseQs ?>&pg=<?= $i ?>"><?= $i ?></a>
                </li>
              <?php endfor; ?>
              <li class="page-item <?= $pg>=$pages?'disabled':'' ?>">
                <a class="page-link" href="?<?= $baseQs ?>&pg=<?= $next ?>">&raquo;</a>
              </li>
            </ul>
          </nav>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </section>
</div>
