<?php
session_start();
require_once __DIR__.'/auth.php';
error_reporting(0);
ini_set('display_errors', 0);
mysqli_report(MYSQLI_REPORT_OFF);

if (strtolower($_SESSION['role'] ?? '') === 'staff') { header('Location: dashboard.php'); exit; }

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'brew_n_break');

$id = intval($_GET['id'] ?? 0);
if (!$id) { echo 'Invalid report ID.'; exit; }

$report       = null;
$transactions = [];

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) throw new Exception('DB error');

    $stmt = $conn->prepare("SELECT * FROM reports WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $report = $stmt->get_result()->fetch_assoc();
    if (!$report) { echo 'Report not found.'; exit; }

    $start = $conn->real_escape_string($report['date_from']);
    $end   = $conn->real_escape_string($report['date_to']);
    $type  = $report['type'];

    if (in_array($type, ['Transaction Report','Daily Report','Cafe Report','Revenue Report'])) {
        $r = $conn->query("
            SELECT o.order_code AS code,
                   GROUP_CONCAT(p.name SEPARATOR ', ') AS product,
                   o.type, o.total_amount AS amount, o.status,
                   DATE_FORMAT(o.created_at,'%h:%i %p · %M %d, %Y') AS date
            FROM orders o
            LEFT JOIN order_items oi ON oi.order_id = o.id
            LEFT JOIN products p ON p.id = oi.product_id
            WHERE DATE(o.created_at) BETWEEN '$start' AND '$end'
            GROUP BY o.id
            ORDER BY o.created_at DESC
        ");
        if ($r) while ($row = $r->fetch_assoc()) { $row['_src'] = 'Cafe'; $transactions[] = $row; }
    }

    if (in_array($type, ['Transaction Report','Daily Report','Billiard Report','Revenue Report'])) {
        $r = $conn->query("
            SELECT bs.session_code AS code,
                   CONCAT(bs.table_name, ' — ', bs.customer_name, ' (', bs.start_time, ' to ', bs.end_time, ')') AS product,
                   'Billiards' AS type, bs.amount, bs.status,
                   DATE_FORMAT(bs.created_at,'%h:%i %p · %M %d, %Y') AS date
            FROM billiard_sessions bs
            WHERE DATE(bs.created_at) BETWEEN '$start' AND '$end'
            ORDER BY bs.created_at DESC
        ");
        if ($r) while ($row = $r->fetch_assoc()) { $row['_src'] = 'Billiard'; $transactions[] = $row; }
    }

    if (in_array($type, ['Transaction Report','Daily Report','Booking Report','Revenue Report'])) {
        $r = $conn->query("
            SELECT b.booking_code AS code,
                   CONCAT(b.room, ' — ', b.guest_name) AS product,
                   'Booking' AS type, 0 AS amount, b.status,
                   DATE_FORMAT(b.created_at,'%h:%i %p · %M %d, %Y') AS date
            FROM bookings b
            WHERE DATE(b.created_at) BETWEEN '$start' AND '$end'
            ORDER BY b.created_at DESC
        ");
        if ($r) while ($row = $r->fetch_assoc()) { $row['_src'] = 'Booking'; $transactions[] = $row; }
    }

    $conn->close();
} catch (Throwable $e) { echo 'Server error.'; exit; }

// Aggregates
$totalRevenue   = array_sum(array_column($transactions, 'amount'));
$totalCount     = count($transactions);
$doneCount      = count(array_filter($transactions, fn($t) => strtolower($t['status']) === 'done'));
$pendingCount   = count(array_filter($transactions, fn($t) => strtolower($t['status']) === 'pending'));
$cancelledCount = count(array_filter($transactions, fn($t) => strtolower($t['status']) === 'cancelled'));

$typeCounts = [];
foreach ($transactions as $t) {
    $k = $t['_src'];
    $typeCounts[$k] = ($typeCounts[$k] ?? 0) + 1;
}

$generatedAt = date('F d, Y h:i A', strtotime($report['created_at']));
$dateFrom    = date('F d, Y', strtotime($report['date_from']));
$dateTo      = date('F d, Y', strtotime($report['date_to']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Report <?= htmlspecialchars($report['report_code']) ?> – Brew n' Break</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Lato:wght@300;400;700&display=swap" rel="stylesheet"/>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}

/* Screen styles */
body{
  font-family:'Lato',sans-serif;
  background:#d4c8b0;
  color:#1e1a14;
  min-height:100vh;
  padding:32px 16px 60px;
}
.page-wrap{
  max-width:820px;
  margin:0 auto;
  background:#fff;
  border-radius:16px;
  box-shadow:0 8px 40px rgba(0,0,0,0.18);
  overflow:hidden;
}

/* Print toolbar (screen only) */
.print-bar{
  background:#1e1a14;
  display:flex;
  align-items:center;
  justify-content:space-between;
  padding:14px 28px;
  gap:12px;
}
.print-bar-left{font-family:'Playfair Display',serif;font-size:15px;color:#f5eedc;}
.print-bar-right{display:flex;gap:10px;}
.pb-btn{
  padding:8px 20px;border-radius:8px;border:none;font-size:13px;
  font-family:'Lato',sans-serif;font-weight:700;cursor:pointer;transition:background .2s;
}
.pb-print{background:#c8a96e;color:#1e1a14;}
.pb-print:hover{background:#b8994e;}
.pb-back{background:rgba(255,255,255,0.1);color:#f5eedc;}
.pb-back:hover{background:rgba(255,255,255,0.18);}

/* Report document */
.doc{padding:44px 52px;}

/* Document header */
.doc-header{
  display:flex;justify-content:space-between;align-items:flex-start;
  padding-bottom:24px;border-bottom:2.5px solid #e8dcc8;margin-bottom:28px;
}
.brand-block{}
.brand-name{font-family:'Playfair Display',serif;font-size:26px;color:#1e1a14;}
.brand-sub{font-size:11px;color:#7a6e5f;letter-spacing:1.2px;text-transform:uppercase;margin-top:3px;}
.brand-addr{font-size:11px;color:#7a6e5f;margin-top:6px;line-height:1.7;}
.doc-meta{text-align:right;font-size:12px;color:#7a6e5f;line-height:2;}
.doc-meta strong{color:#1e1a14;font-size:13px;}
.doc-meta .report-code-badge{
  display:inline-block;background:#1e1a14;color:#c8a96e;
  font-family:'Playfair Display',serif;font-size:15px;
  padding:4px 14px;border-radius:6px;margin-bottom:6px;
}

/* Section title */
.sec-title{
  font-size:10px;letter-spacing:2px;text-transform:uppercase;
  color:#7a6e5f;font-weight:700;margin:24px 0 12px;
  display:flex;align-items:center;gap:8px;
}
.sec-title::after{content:'';flex:1;height:1px;background:#e8dcc8;}

/* Summary grid */
.summary-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:4px;}
.sum-card{
  background:#f9f5ee;border-radius:10px;padding:14px 16px;
  border:1px solid #e8dcc8;text-align:center;
}
.sum-label{font-size:9px;letter-spacing:1.2px;text-transform:uppercase;color:#7a6e5f;margin-bottom:5px;}
.sum-value{font-family:'Playfair Display',serif;font-size:22px;color:#1e1a14;}
.sum-value.revenue{font-size:18px;}

/* Status breakdown */
.status-row{display:flex;gap:10px;margin-bottom:4px;flex-wrap:wrap;}
.status-pill{
  font-size:11px;font-weight:700;padding:4px 12px;border-radius:20px;
  display:flex;align-items:center;gap:5px;
}
.sp-done    {background:#eafaf1;color:#1e8449;border:1px solid #a2dbb8;}
.sp-pending {background:#fef9e7;color:#856404;border:1px solid #f9e48a;}
.sp-cancelled{background:#fdedec;color:#921515;border:1px solid #f5b7b1;}
.sp-dot{width:6px;height:6px;border-radius:50%;background:currentColor;}

/* Type breakdown */
.type-row{display:flex;gap:10px;flex-wrap:wrap;}
.type-chip{font-size:11px;padding:3px 10px;border-radius:6px;font-weight:700;}
.tc-cafe    {background:#e8f4fd;color:#1a5276;border:1px solid #aad4f0;}
.tc-billiard{background:#fdf2e9;color:#784212;border:1px solid #f0c898;}
.tc-booking {background:#f4ecf7;color:#6c3483;border:1px solid #d2b4de;}

/* Transactions table */
.tx-table{width:100%;border-collapse:collapse;font-size:12px;margin-top:4px;}
.tx-table thead tr{background:#1e1a14;}
.tx-table th{
  text-align:left;padding:10px 12px;color:#c8a96e;
  font-size:10px;letter-spacing:.8px;text-transform:uppercase;font-weight:700;
}
.tx-table th:last-child{text-align:right;}
.tx-table tbody tr:nth-child(odd){background:#f9f5ee;}
.tx-table tbody tr:nth-child(even){background:#fff;}
.tx-table td{padding:10px 12px;border-bottom:1px solid #ede6d6;color:#1e1a14;vertical-align:top;}
.tx-table td:last-child{text-align:right;font-weight:700;}
.tx-table tbody tr:last-child td{border-bottom:none;}
.type-dot{
  display:inline-block;width:7px;height:7px;border-radius:50%;margin-right:5px;
  vertical-align:middle;
}
.dot-cafe    {background:#1a5276;}
.dot-billiard{background:#784212;}
.dot-booking {background:#6c3483;}
.dot-other   {background:#7a6e5f;}
.status-tag{
  font-size:10px;font-weight:700;padding:2px 7px;border-radius:4px;
  text-transform:uppercase;letter-spacing:.4px;
}
.st-done     {background:#eafaf1;color:#1e8449;}
.st-pending  {background:#fef9e7;color:#856404;}
.st-cancelled{background:#fdedec;color:#921515;}
.st-ongoing  {background:#eaf4fd;color:#1a5276;}
.st-start    {background:#fdf2e9;color:#784212;}
.no-data{text-align:center;padding:32px;color:#7a6e5f;font-style:italic;}

/* Total row */
.total-row td{
  background:#1e1a14!important;color:#c8a96e!important;
  font-family:'Playfair Display',serif;font-size:14px;font-weight:700;
  padding:12px!important;border-bottom:none!important;
}
.total-row td:first-child{border-radius:0 0 0 8px;}
.total-row td:last-child{border-radius:0 0 8px 0;text-align:right!important;}

/* Footer */
.doc-footer{
  margin-top:40px;padding-top:18px;border-top:1px solid #e8dcc8;
  text-align:center;font-size:11px;color:#7a6e5f;line-height:1.9;
}

/* =================== PRINT STYLES =================== */
@media print {
  body{background:#fff;padding:0;}
  .page-wrap{border-radius:0;box-shadow:none;max-width:100%;}
  .print-bar{display:none;}
  .doc{padding:28px 36px;}
  .doc-header{padding-bottom:16px;margin-bottom:20px;}
  .brand-name{font-size:20px;}
  .sum-card{background:#f9f5ee!important;-webkit-print-color-adjust:exact;print-color-adjust:exact;}
  .tx-table thead tr{background:#1e1a14!important;-webkit-print-color-adjust:exact;print-color-adjust:exact;}
  .tx-table th{color:#c8a96e!important;-webkit-print-color-adjust:exact;print-color-adjust:exact;}
  .tx-table tbody tr:nth-child(odd){background:#f9f5ee!important;-webkit-print-color-adjust:exact;print-color-adjust:exact;}
  .total-row td{background:#1e1a14!important;color:#c8a96e!important;-webkit-print-color-adjust:exact;print-color-adjust:exact;}
  .status-pill,.type-chip,.status-tag{-webkit-print-color-adjust:exact;print-color-adjust:exact;}
  .summary-grid{grid-template-columns:repeat(4,1fr);}
  tr{page-break-inside:avoid;}
  .doc-footer{margin-top:24px;}
}
</style>
</head>
<body>

<div class="page-wrap">

  <!-- Screen-only toolbar -->
  <div class="print-bar">
    <span class="print-bar-left">📁 <?= htmlspecialchars($report['report_code']) ?> — <?= htmlspecialchars($report['type']) ?></span>
    <div class="print-bar-right">
      <button class="pb-btn pb-back" onclick="window.close()">← Back</button>
      <button class="pb-btn pb-print" onclick="window.print()">🖨️ Print / Save PDF</button>
    </div>
  </div>

  <div class="doc">

    <!-- Document header -->
    <div class="doc-header">
      <div class="brand-block">
        <div class="brand-name">🎱 Brew n' Break</div>
        <div class="brand-sub">By Bamboo Court</div>
        <div class="brand-addr">
          #17 Bamboo Court, Philippines<br/>
          facebook.com/brewnbreak
        </div>
      </div>
      <div class="doc-meta">
        <div><span class="report-code-badge"><?= htmlspecialchars($report['report_code']) ?></span></div>
        <div><strong>Type:</strong> <?= htmlspecialchars($report['type']) ?></div>
        <div><strong>Period:</strong> <?= $dateFrom ?> – <?= $dateTo ?></div>
        <div><strong>Generated:</strong> <?= $generatedAt ?></div>
        <div><strong>Prepared by:</strong> <?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></div>
      </div>
    </div>

    <!-- Summary -->
    <div class="sec-title">Summary</div>
    <div class="summary-grid">
      <div class="sum-card">
        <div class="sum-label">Total Transactions</div>
        <div class="sum-value"><?= $totalCount ?></div>
      </div>
      <div class="sum-card">
        <div class="sum-label">Total Revenue</div>
        <div class="sum-value revenue">₱<?= number_format($totalRevenue, 2) ?></div>
      </div>
      <div class="sum-card">
        <div class="sum-label">Date From</div>
        <div class="sum-value" style="font-size:13px;padding-top:4px"><?= date('M d, Y', strtotime($report['date_from'])) ?></div>
      </div>
      <div class="sum-card">
        <div class="sum-label">Date To</div>
        <div class="sum-value" style="font-size:13px;padding-top:4px"><?= date('M d, Y', strtotime($report['date_to'])) ?></div>
      </div>
    </div>

    <!-- Status breakdown -->
    <div class="sec-title" style="margin-top:16px">Status Breakdown</div>
    <div class="status-row">
      <span class="status-pill sp-done"><span class="sp-dot"></span> Done: <?= $doneCount ?></span>
      <span class="status-pill sp-pending"><span class="sp-dot"></span> Pending: <?= $pendingCount ?></span>
      <span class="status-pill sp-cancelled"><span class="sp-dot"></span> Cancelled: <?= $cancelledCount ?></span>
      <?php $otherCount = $totalCount - $doneCount - $pendingCount - $cancelledCount; if($otherCount > 0): ?>
      <span class="status-pill" style="background:#f0f0f0;color:#555;border:1px solid #ccc;"><span class="sp-dot" style="background:#555"></span> Other: <?= $otherCount ?></span>
      <?php endif; ?>
    </div>

    <?php if (!empty($typeCounts)): ?>
    <div class="sec-title" style="margin-top:16px">By Category</div>
    <div class="type-row">
      <?php foreach ($typeCounts as $src => $cnt): ?>
        <?php $cls = match($src) { 'Cafe'=>'tc-cafe', 'Billiard'=>'tc-billiard', 'Booking'=>'tc-booking', default=>'tc-cafe' }; ?>
        <span class="type-chip <?= $cls ?>"><?= $src ?>: <?= $cnt ?></span>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Transactions -->
    <div class="sec-title" style="margin-top:24px">Transaction Details</div>

    <?php if (empty($transactions)): ?>
      <div class="no-data">No transactions found for this period.</div>
    <?php else: ?>
    <table class="tx-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Transaction ID</th>
          <th>Description / Details</th>
          <th>Category</th>
          <th>Date &amp; Time</th>
          <th>Amount</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($transactions as $i => $t):
          $src = $t['_src'];
          $dotClass = match($src) { 'Cafe'=>'dot-cafe', 'Billiard'=>'dot-billiard', 'Booking'=>'dot-booking', default=>'dot-other' };
          $stLower = strtolower($t['status']);
          $stClass = match($stLower) {
            'done'      => 'st-done',
            'pending'   => 'st-pending',
            'cancelled' => 'st-cancelled',
            'ongoing'   => 'st-ongoing',
            'start'     => 'st-start',
            default     => 'st-pending'
          };
        ?>
        <tr>
          <td style="color:#7a6e5f;font-size:11px"><?= $i + 1 ?></td>
          <td style="font-weight:700;white-space:nowrap"><?= htmlspecialchars($t['code']) ?></td>
          <td style="max-width:220px"><?= htmlspecialchars($t['product'] ?? '–') ?></td>
          <td style="white-space:nowrap">
            <span class="type-dot <?= $dotClass ?>"></span><?= htmlspecialchars($src) ?>
          </td>
          <td style="font-size:11px;color:#7a6e5f;white-space:nowrap"><?= htmlspecialchars($t['date']) ?></td>
          <td style="white-space:nowrap">₱<?= number_format((float)$t['amount'], 2) ?></td>
        </tr>
        <?php endforeach; ?>
        <tr class="total-row">
          <td colspan="5">Grand Total — <?= $totalCount ?> transaction<?= $totalCount !== 1 ? 's' : '' ?></td>
          <td>₱<?= number_format($totalRevenue, 2) ?></td>
        </tr>
      </tbody>
    </table>
    <?php endif; ?>

    <!-- Footer -->
    <div class="doc-footer">
      This report was generated by the Brew n' Break Admin System on <?= $generatedAt ?>.<br/>
      For inquiries, contact us at facebook.com/brewnbreak<br/>
      <strong style="color:#1e1a14">Brew n' Break by Bamboo Court</strong>
    </div>

  </div><!-- /.doc -->
</div><!-- /.page-wrap -->

</body>
</html>
