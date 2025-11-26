<?php
// pages/notifications.php
require_once 'app/init.php';
if (!isset($_SESSION['user_id'])) { header("location:login.php"); exit; }

$db = $pdo ?? ($obj->pdo ?? null);
$userId = (int)$_SESSION['user_id'];

// --- Handle Actions ---
if (isset($_GET['read'])) {
    $st = $db->prepare("UPDATE notifications SET is_read=1 WHERE id=:id AND user_id=:u");
    $st->execute([':id'=>(int)$_GET['read'], ':u'=>$userId]);
    header("Location: index.php?page=notifications"); exit;
}
if (isset($_GET['mark']) && $_GET['mark']==='all') {
    $st = $db->prepare("UPDATE notifications SET is_read=1 WHERE user_id=:u");
    $st->execute([':u'=>$userId]);
    header("Location: index.php?page=notifications"); exit;
}

// --- Pagination ---
$page = max(1, (int)($_GET['p'] ?? 1));
$per  = 10;
$off  = ($page-1) * $per;

$total = (int)$db->query("SELECT COUNT(*) FROM notifications WHERE user_id = {$userId}")->fetchColumn();
$pages = max(1, (int)ceil($total / $per));

// --- Fetch Data ---
$st = $db->prepare("SELECT id, title, body, link, is_read, created_at FROM notifications 
                    WHERE user_id = :u ORDER BY created_at DESC LIMIT :per OFFSET :off");
$st->bindValue(':u', $userId, PDO::PARAM_INT);
$st->bindValue(':per', $per, PDO::PARAM_INT);
$st->bindValue(':off', $off, PDO::PARAM_INT);
$st->execute();
$rows = $st->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid d-flex justify-content-between align-items-center">
            <h1 class="m-0 text-dark">Notifications</h1>
            <?php if($total > 0): ?>
                <a href="index.php?page=notifications&mark=all" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-check-double mr-1"></i> Mark all read
                </a>
            <?php endif; ?>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <?php if ($rows): foreach ($rows as $r): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-start <?= $r['is_read'] ? '' : 'bg-light' ?>">
                                <div class="ms-2 me-auto">
                                    <div class="fw-bold">
                                        <?= htmlspecialchars($r['title']) ?>
                                        <?php if (!$r['is_read']): ?>
                                            <span class="badge badge-primary badge-pill ml-2">New</span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!empty($r['body'])): ?>
                                        <div class="text-muted small mb-1"><?= nl2br(htmlspecialchars($r['body'])) ?></div>
                                    <?php endif; ?>
                                    <div class="small text-secondary">
                                        <i class="far fa-clock mr-1"></i> <?= htmlspecialchars($r['created_at']) ?>
                                        <?php if (!empty($r['link'])): ?>
                                            &bull; <a href="<?= htmlspecialchars($r['link']) ?>" class="text-primary ml-1">View Details</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php if (!$r['is_read']): ?>
                                    <a href="index.php?page=notifications&read=<?= $r['id'] ?>" class="btn btn-xs btn-outline-secondary" title="Mark as read">
                                        <i class="fas fa-check"></i>
                                    </a>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; else: ?>
                            <li class="list-group-item text-center text-muted p-4">
                                <i class="far fa-bell-slash fa-2x mb-2 d-block text-secondary"></i>
                                No notifications found.
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <?php if ($pages > 1): ?>
                <div class="card-footer d-flex justify-content-between">
                    <div>Page <?= $page ?> of <?= $pages ?></div>
                    <div class="btn-group">
                        <a class="btn btn-sm btn-outline-secondary <?= $page<=1 ? 'disabled':'' ?>" href="index.php?page=notifications&p=<?= $page-1 ?>">Prev</a>
                        <a class="btn btn-sm btn-outline-secondary <?= $page>=$pages ? 'disabled':'' ?>" href="index.php?page=notifications&p=<?= $page+1 ?>">Next</a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
</div>