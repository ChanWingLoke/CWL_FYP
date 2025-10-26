<?php
// app/action/booking_events.php
header('Content-Type: application/json');

// Bootstrap your app (gives us $pdo or $obj->pdo)
require_once __DIR__ . '/../../app/init.php';

// ----------------------------------------
// Get PDO from your app
// ----------------------------------------
$db = null;
if (isset($pdo) && $pdo) {
  $db = $pdo;
} elseif (isset($obj) && isset($obj->pdo)) {
  $db = $obj->pdo;
}
if (!$db) { echo json_encode([]); exit; }

// ----------------------------------------
// Detect assets table name
// ----------------------------------------
$productTable = 'product';
try {
  $db->query("SELECT 1 FROM `product` LIMIT 1");
} catch (Throwable $e) {
  foreach (['products','tbl_product','items','assets'] as $t) {
    try { $db->query("SELECT 1 FROM `{$t}` LIMIT 1"); $productTable = $t; break; }
    catch (Throwable $ignored) {}
  }
}

// ----------------------------------------
// Optional filters from client
// FullCalendar sends ?start=YYYY-MM-DD&end=YYYY-MM-DD by default
// We'll also allow ?status=approved|pending|rejected|returned|all
// ----------------------------------------
$status = isset($_GET['status']) ? strtolower(trim($_GET['status'])) : 'approved';
$fcStart = isset($_GET['start']) ? $_GET['start'] : null; // YYYY-MM-DD
$fcEnd   = isset($_GET['end'])   ? $_GET['end']   : null; // YYYY-MM-DD (exclusive)

// Build WHERE safely
$where = [];
$params = [];

if ($status && $status !== 'all') {
  $where[] = "b.status = :status";
  $params[':status'] = $status;
}

// If FullCalendar gives us a window, use it to limit rows
// (b.end_time >= start) AND (b.start_time < end)
if ($fcStart) {
  $where[] = "b.end_time >= :fcStart";
  $params[':fcStart'] = $fcStart . ' 00:00:00';
}
if ($fcEnd) {
  $where[] = "b.start_time < :fcEnd";
  $params[':fcEnd'] = $fcEnd . ' 00:00:00';
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// ----------------------------------------
// Query bookings
// ----------------------------------------
$sql = "
  SELECT b.id, b.asset_id, b.user_id, b.start_time, b.end_time, b.status, b.notes,
         p.product_name AS asset_name,
         u.username     AS user_name
  FROM bookings b
  JOIN `{$productTable}` p ON p.id = b.asset_id
  JOIN user u             ON u.id = b.user_id
  {$whereSql}
  ORDER BY b.start_time ASC
";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ----------------------------------------
// Map status to colors
// ----------------------------------------
$mapColor = [
  'pending'  => ['#f39c12', '#f39c12'], // orange
  'approved' => ['#28a745', '#28a745'], // green
  'rejected' => ['#dc3545', '#dc3545'], // red
  'returned' => ['#6c757d', '#6c757d'], // gray
];

// ----------------------------------------
// Build FullCalendar events
// Use all-day with date-only strings and an exclusive end date
// ----------------------------------------
$events = [];
foreach ($rows as $r) {
  $statusKey = strtolower($r['status'] ?? 'pending');
  $colors = $mapColor[$statusKey] ?? ['#6c757d', '#6c757d'];

  // Convert to date-only strings; make end exclusive by adding one day
  $startDate = date('Y-m-d', strtotime($r['start_time']));
  $endDateExclusive = date('Y-m-d', strtotime($r['end_time'] . ' +1 day'));

  $events[] = [
    'id'    => (string)$r['id'],
    'title' => $r['asset_name'] . ' — ' . ucfirst($statusKey),
    'start' => $startDate,           // all-day start
    'end'   => $endDateExclusive,    // all-day exclusive end
    'allDay' => true,
    'backgroundColor' => $colors[0],
    'borderColor'     => $colors[1],
    'extendedProps' => [
      'asset'  => $r['asset_name'],
      'user'   => $r['user_name'],
      'status' => $statusKey,
      'notes'  => $r['notes'] ?? '',
      'start'  => $r['start_time'],
      'end'    => $r['end_time'],
    ],
  ];
}

echo json_encode($events);
