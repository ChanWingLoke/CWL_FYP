<?php
// app/action/bookings_events.php
header('Content-Type: application/json');

// bootstrap your app
require_once __DIR__ . '/../../app/init.php';

// Get PDO from your app
$db = null;
if (isset($pdo) && $pdo) {
  $db = $pdo;
} elseif (isset($obj) && isset($obj->pdo)) {
  $db = $obj->pdo;
} else {
  echo json_encode([]); exit;
}

// Detect product table name
$productTable = 'product';
try { $db->query("SELECT 1 FROM `product` LIMIT 1"); }
catch (Throwable $e) {
  foreach (['products','tbl_product','items','assets'] as $t) {
    try { $db->query("SELECT 1 FROM `{$t}` LIMIT 1"); $productTable = $t; break; }
    catch (Throwable $ignored) {}
  }
}

// Fetch upcoming (and recent) bookings; expand time window as you like
$sql = "
  SELECT b.id, b.asset_id, b.user_id, b.start_time, b.end_time, b.status, b.notes,
         p.product_name AS asset_name, u.username AS user_name
  FROM bookings b
  JOIN `{$productTable}` p ON p.id = b.asset_id
  JOIN user u ON u.id = b.user_id
  WHERE b.end_time >= DATE_SUB(NOW(), INTERVAL 30 DAY)
  ORDER BY b.start_time ASC
";
$stmt = $db->query($sql);
$rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

$mapColor = [
  'pending'  => ['#f39c12', '#f39c12'], // orange
  'approved' => ['#28a745', '#28a745'], // green
  'rejected' => ['#dc3545', '#dc3545'], // red
  'returned' => ['#6c757d', '#6c757d'], // gray
];

$events = [];
foreach ($rows as $r) {
  $status = strtolower($r['status'] ?? 'pending');
  $colors = $mapColor[$status] ?? ['#6c757d', '#6c757d'];

  $title = $r['asset_name'] . ' — ' . ucfirst($status);
  $events[] = [
    'id'    => (string)$r['id'],
    'title' => $title,
    'start' => date('c', strtotime($r['start_time'])),
    'end'   => date('c', strtotime($r['end_time'])),
    'backgroundColor' => $colors[0],
    'borderColor'     => $colors[1],
    // Useful extra data for the modal
    'extendedProps' => [
      'asset'  => $r['asset_name'],
      'user'   => $r['user_name'],
      'status' => $status,
      'notes'  => $r['notes'] ?? '',
      'start'  => $r['start_time'],
      'end'    => $r['end_time'],
    ],
  ];
}

echo json_encode($events);
