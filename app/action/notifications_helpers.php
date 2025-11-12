<?php
// app/action/notifications_helpers.php
// Helper functions to insert and fetch notifications.

if (!function_exists('notif_insert')) {
  function notif_insert(PDO $db, int $userId, string $title, string $body = '', ?string $link = null, ?string $type = null, ?int $refId = null): void {
    // Avoid duplicate inserts for same (user,type,ref_id)
    if ($type !== null && $refId !== null) {
      $stmt = $db->prepare("SELECT 1 FROM notifications WHERE user_id=:uid AND type=:type AND ref_id=:rid LIMIT 1");
      $stmt->execute([':uid'=>$userId, ':type'=>$type, ':rid'=>$refId]);
      if ($stmt->fetchColumn()) return;
    }

    $stmt = $db->prepare("INSERT INTO notifications (user_id,title,body,link,is_read,type,ref_id,created_at)
                          VALUES (:uid,:title,:body,:link,0,:type,:rid,NOW())");
    $stmt->execute([
      ':uid'=>$userId,
      ':title'=>$title,
      ':body'=>$body,
      ':link'=>$link,
      ':type'=>$type,
      ':rid'=>$refId
    ]);
  }
}

if (!function_exists('notif_admins')) {
  function notif_admins(PDO $db): array {
    $rows = $db->query("SELECT id FROM user WHERE LOWER(user_role)='admin'")->fetchAll(PDO::FETCH_COLUMN);
    return array_map('intval', $rows ?: []);
  }
}
?>
