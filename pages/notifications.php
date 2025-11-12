<?php
// pages/notifications.php
require_once 'app/init.php';
if ($Ouser->is_login() == false) { header("location:login.php"); exit; }

$db = $pdo ?? ($obj->pdo ?? null);
$userId = (int)($_SESSION['user_id'] ?? 0);
if (!$db || !$userId) {
  echo '<div class="content-wrapper"><section class="content p-3"><div class="alert alert-danger">DB or session not available.</div></section></div>';
  exit;
}

// mark single/all
if (isset($_GET['read'])) {
  $id = (int)$_GET['read'];
  $st = $db->prepare("UPDATE notifications SET is_read=1 WHERE id=:id AND user_id=:u");
  $st->execute([':id'=>$id, ':u'=>$userId]);
  header("Location: index.php?page=notifications"); exit;
}
if (isset($_GET['mark']) && $_GET['mark']==='all') {
  $st = $db->prepare("UPDATE notifications SET is_read=1 WHERE user_id=:u AND is_read=0");
  $st->execute([':u'=>$userId]);
  header("Location: index.php?page=notifications"); exit;
}

$page = max(1, (int)($_GET['p'] ?? 1));
$per  = 10;
$off  = ($page-1) * $per;

$total = (int)$db->query("SELECT COUNT(*) FROM notifications WHERE user_id = {$userId}")->fetchColumn();
$pages = max(1, (int)ceil($total / $per));

$st = $db->prepare("SELECT id,title,body,link,is_read,created_at
                    FROM notifications
                    WHERE user_id = :u
                    ORDER BY created_at DESC
                    LIMIT :per OFFSET :off");
$st->bindValue(':u', $userId, PDO::PARAM_INT);
$st->bindValue(':per', $per, PDO::PARAM_INT);
$st->bindValue(':off', $off, PDO::PARAM_INT);
$st->execute();
$rows = $st->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="content-wrapper">
  <section class="content-header">
    <div class="container-fluid">
      <h1 class="m-0 text-dark">Notifications</h1>
      <a href="index.php?page=notifications&mark=all" class="btn btn-sm btn-outline-primary mt-2">Mark all as read</a>
    </div>
  </section>
  <section class="content">
    <div class="container-fluid">
      <div class="card">
        <div class="card-body p-0">
          <ul class="list-group list-group-flush">
            <?php foreach ($rows as $r): ?>
              <li class="list-group-item d-flex justify-content-between align-items-start <?= $r['is_read'] ? '' : 'bg-light' ?>">
                <div class="ms-2 me-auto">
                  <div class="fw-bold"><?= htmlspecialchars($r['title']) ?></div>
                  <?php if (!empty($r['body'])): ?>
                    <div class="text-muted small mb-1"><?= nl2br(htmlspecialchars($r['body'])) ?></div>
                  <?php endif; ?>
                  <div class="small text-secondary"><?= htmlspecialchars($r['created_at']) ?></div>
                  <?php if (!empty($r['link'])): ?>
                    <a class="small" href="<?= htmlspecialchars($r['link']) ?>">Open</a>
                  <?php endif; ?>
                </div>
                <div>
                  <?php if (!$r['is_read']): ?>
                    <a href="index.php?page=notifications&read=<?= (int)$r['id'] ?>" class="btn btn-sm btn-outline-secondary">Mark read</a>
                  <?php endif; ?>
                </div>
              </li>
            <?php endforeach; ?>
            <?php if (!$rows): ?>
              <li class="list-group-item text-center text-muted">No notifications</li>
            <?php endif; ?>
          </ul>
        </div>
        <?php if ($pages > 1): ?>
        <div class="card-footer d-flex justify-content-between">
          <div>Page <?= $page ?> of <?= $pages ?></div>
          <div>
            <?php if ($page > 1): ?><a class="btn btn-sm btn-outline-secondary" href="index.php?page=notifications&p=<?= $page-1 ?>">Prev</a><?php endif; ?>
            <?php if ($page < $pages): ?><a class="btn btn-sm btn-outline-secondary" href="index.php?page=notifications&p=<?= $page+1 ?>">Next</a><?php endif; ?>
          </div>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </section>
</div>
