<?php
// app/action/export_report.php
require_once __DIR__ . '/../init.php';

if (!isset($_SESSION['user_id'])) { http_response_code(403); exit('Forbidden'); }
$isAdmin = isset($_SESSION['user_role']) && strtolower($_SESSION['user_role']) === 'admin';
// Keep exports admin-only (remove this gate if you want to open it)
if (!$isAdmin) { http_response_code(403); exit('Admins only'); }

$pdo = $pdo ?? ($obj->pdo ?? null);
if (!$pdo) { http_response_code(500); exit('DB not ready'); }

$module = strtolower(trim($_GET['module'] ?? ''));
$format = strtolower(trim($_GET['format'] ?? 'csv'));
$from   = $_GET['from'] ?? null;
$to     = $_GET['to']   ?? null;
$status = trim($_GET['status'] ?? '');
$user   = trim($_GET['user'] ?? '');

// validate dates (YYYY-MM-DD)
$from = ($from && preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) ? $from : null;
$to   = ($to   && preg_match('/^\d{4}-\d{2}-\d{2}$/', $to))   ? $to   : null;

function whereBetween(&$params, $col, $from, $to) {
  $w = [];
  if ($from) { $w[] = "$col >= :from"; $params[':from'] = $from; }
  if ($to)   { $w[] = "$col <= :to";   $params[':to']   = $to;   }
  return $w;
}

switch ($module) {
  case 'assets': {
    // Build WHERE for date range using last_update_at (DATE)
    $params = [];
    $where  = [];
    if ($from) { $where[] = "DATE(p.last_update_at) >= :from"; $params[':from'] = $from; }
    if ($to)   { $where[] = "DATE(p.last_update_at) <= :to";   $params[':to']   = $to;   }

    // Select columns that exist in your schema
    $sql = "SELECT
                p.id                             AS asset_id,
                p.product_name                   AS product_name,
                p.product_id                     AS product_code,
                p.brand_name                     AS brand,
                p.catagory_name                  AS category,   -- note the spelling in table
                p.sku                            AS sku,
                p.quantity                       AS quantity,
                p.buy_price                      AS buy_price,
                p.sell_price                     AS sell_price,
                p.last_update_at                 AS last_update,
                p.added_time                     AS added_time
            FROM products p";

    if ($where) $sql .= " WHERE " . implode(' AND ', $where);
    $sql .= " ORDER BY p.id DESC";

    // Column headers for CSV/PDF in the same order as SELECT
    $columns = [
        'Asset ID','Product Name','Product Code','Brand','Category',
        'SKU','Quantity','Buy Price','Sell Price','Last Update','Added Time'
    ];

    // run & fetch
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    break;
  }

  case 'bookings': {
    $params = []; $where = [];
    // filter by end date window (common reporting need)
    $where = array_merge($where, whereBetween($params, 'DATE(b.end_time)', $from, $to));
    if ($status !== '') { $where[] = 'b.status = :status'; $params[':status'] = $status; }
    if ($user   !== '') { $where[] = 'u.username = :user'; $params[':user']   = $user;   }

    $sql = "SELECT b.id AS booking_id, u.username, b.status,
                   DATE(b.start_time) AS start_date, DATE(b.end_time) AS end_date
            FROM bookings b
            JOIN user u ON u.id = b.user_id";
    if ($where) $sql .= " WHERE " . implode(' AND ', $where);
    $sql .= " ORDER BY b.end_time DESC";
    $columns = ['Booking ID','User','Status','Start Date','End Date'];
    break;
  }

  case 'warranties': {
    $params = []; $where = [];
    $where = array_merge($where, whereBetween($params, 'DATE(w.end_date)', $from, $to));
    if ($status !== '') { $where[] = 'w.warranty_status = :status'; $params[':status'] = $status; }

    $sql = "SELECT w.id AS warranty_id,
                   DATE(w.start_date) AS start_date,
                   DATE(w.end_date)   AS end_date,
                   w.warranty_status AS status
            FROM warranties w";
    if ($where) $sql .= " WHERE " . implode(' AND ', $where);
    $sql .= " ORDER BY w.end_date ASC";
    $columns = ['Warranty ID','Start Date','End Date','Status'];
    break;
  }

  case 'maintenance': {
    $params = []; $where = [];
    // created_at window; change to updated_at if you prefer
    $where = array_merge($where, whereBetween($params, 'DATE(m.created_at)', $from, $to));
    if ($status !== '') { $where[] = 'm.status = :status'; $params[':status'] = $status; }

    $sql = "SELECT m.id AS ticket_id, m.title, m.asset_id, m.priority, m.status,
                   DATE(m.created_at) AS created_on, DATE(m.updated_at) AS updated_on
            FROM maintenance_orders m";
    if ($where) $sql .= " WHERE " . implode(' AND ', $where);
    $sql .= " ORDER BY m.id DESC";
    $columns = ['Ticket ID','Title','Asset ID','Priority','Status','Created On','Updated On'];
    break;
  }

  default:
    http_response_code(400); exit('Unknown module');
}

// run & fetch
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- CSV (always available) ---
if ($format === 'csv') {
  $filename = $module . "_export_" . date('Ymd_His') . ".csv";
  header('Content-Type: text/csv; charset=UTF-8');
  header('Content-Disposition: attachment; filename="'.$filename.'"');
  $out = fopen('php://output', 'w');
  // UTF-8 BOM for Excel
  fwrite($out, "\xEF\xBB\xBF");
  fputcsv($out, $columns);
  foreach ($rows as $r) {
    // Keep same order as columns
    fputcsv($out, array_values($r));
  }
  fclose($out);
  exit;
}

// --- PDF (requires Dompdf in plugins/dompdf) ---
if ($format === 'pdf') {
  $autoload = __DIR__ . '/../../plugins/dompdf/autoload.inc.php';
  if (!file_exists($autoload)) {
    http_response_code(500);
    exit('PDF export requires Dompdf (plugins/dompdf).');
  }
  require_once $autoload;

  $html = '<html><head><meta charset="UTF-8"><style>
    body{font-family:DejaVu Sans, Arial, sans-serif; font-size:12px;}
    h2{margin:0 0 10px 0;}
    table{border-collapse:collapse; width:100%;}
    th,td{border:1px solid #ddd; padding:6px; text-align:left;}
    th{background:#f5f5f5;}
  </style></head><body>';
  $html .= '<h2>'.htmlspecialchars(ucfirst($module)).' Report</h2>';
  $html .= '<table><thead><tr>';
  foreach ($columns as $c) { $html .= '<th>'.htmlspecialchars($c).'</th>'; }
  $html .= '</tr></thead><tbody>';
  foreach ($rows as $r) {
    $html .= '<tr>';
    foreach ($r as $v) { $html .= '<td>'.htmlspecialchars((string)$v).'</td>'; }
    $html .= '</tr>';
  }
  $html .= '</tbody></table></body></html>';

  $dompdf = new Dompdf\Dompdf(['isRemoteEnabled' => true]);
  $dompdf->loadHtml($html, 'UTF-8');
  $dompdf->setPaper('A4', 'landscape');
  $dompdf->render();
  $dompdf->stream($module . "_report_" . date('Ymd_His') . ".pdf");
  exit;
}

http_response_code(400); echo 'Unsupported format';
