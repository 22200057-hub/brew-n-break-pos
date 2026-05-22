<?php
session_start();
require_once __DIR__.'/auth.php';
error_reporting(0);
ini_set('display_errors', 0);
mysqli_report(MYSQLI_REPORT_OFF);

if (strtolower($_SESSION['role'] ?? '') !== 'staff') { header('Location: dashboard.php'); exit; }

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'brew_n_break');

$totalOrders   = 0;
$totalHours    = 0;
$totalBookings = 0;
$totalRevenue  = 0;
$tableRows     = [];
$topSelling    = [];
$ordersRev      = 0;
$billiardRev    = 0;
$bookingRev     = 0;
$airbnbBookings = [];
$bookedRanges   = [];
$username       = $_SESSION['username'] ?? 'Admin';
$userRole       = ucfirst(strtolower($_SESSION['role'] ?? 'admin'));

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if (!$conn->connect_error) {
        $r = $conn->query("SELECT COUNT(*) FROM orders WHERE DATE(created_at)=CURDATE()"); if ($r) $totalOrders = $r->fetch_row()[0];
        $r = $conn->query("SELECT COALESCE(SUM(TIME_TO_SEC(TIMEDIFF(end_time,start_time))/3600),0) FROM billiard_sessions WHERE DATE(created_at)=CURDATE() AND status IN ('Done','Ongoing','Start')"); if ($r) $totalHours = round($r->fetch_row()[0], 1);
        $r = $conn->query("SELECT COUNT(*) FROM bookings WHERE DATE(created_at)=CURDATE()"); if ($r) $totalBookings = $r->fetch_row()[0];
        $r = $conn->query("SELECT COALESCE((SELECT SUM(total_amount) FROM orders WHERE DATE(created_at)=CURDATE()),0) + COALESCE((SELECT SUM(amount) FROM billiard_sessions WHERE DATE(created_at)=CURDATE()),0) + COALESCE((SELECT SUM(DATEDIFF(check_out,check_in)*3500) FROM bookings WHERE DATE(created_at)=CURDATE()),0) AS total"); if ($r) $totalRevenue = $r->fetch_row()[0];
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $chk = $conn->query("SELECT id FROM reports WHERE type='Daily Report' AND date_from='$yesterday' LIMIT 1");
        if ($chk && $chk->num_rows === 0) {
            $hasData = $conn->query("SELECT 1 FROM orders WHERE DATE(created_at)='$yesterday' UNION SELECT 1 FROM billiard_sessions WHERE DATE(created_at)='$yesterday' LIMIT 1");
            if ($hasData && $hasData->num_rows > 0) {
                $rc   = $conn->query("SELECT COUNT(*) FROM reports"); $rn = ($rc ? $rc->fetch_row()[0] : 0) + 1;
                $code = 'RID'.str_pad($rn, 4, '0', STR_PAD_LEFT);
                $conn->query("INSERT INTO reports (report_code,type,date_from,date_to,created_at) VALUES ('$code','Daily Report','$yesterday','$yesterday',NOW())");
            }
        }
        $allTables = ['Outdoor 1','Outdoor 2','Outdoor 3','Indoor 1'];
        $r = $conn->query("
            SELECT
                bs.table_name,
                bs.status,
                bs.start_time,
                bs.end_time,
                bs.customer_name,
                GREATEST(0, TIME_TO_SEC(TIMEDIFF(bs.end_time, TIME(NOW())))) AS secs_left
            FROM billiard_sessions bs
            INNER JOIN (
                SELECT table_name, MAX(id) as mid FROM billiard_sessions GROUP BY table_name
            ) latest ON bs.id = latest.mid
        ");
        $liveMap = [];
        if ($r) while ($row = $r->fetch_assoc()) $liveMap[$row['table_name']] = $row;
        foreach ($allTables as $tbl) {
            if (isset($liveMap[$tbl])) {
                $s = $liveMap[$tbl];
                $statusLower = strtolower($s['status']);
                if (in_array($statusLower, ['ongoing', 'start'])) {
                    $secs = intval($s['secs_left']);
                    $hoursLeft = $secs > 0 ? sprintf('%02d:%02d:%02d', floor($secs/3600), floor(($secs%3600)/60), $secs%60) : 'Overtime';
                    $tableRows[] = ['table_name'=>$tbl,'status'=>$s['status'],'hours_left'=>$hoursLeft,'customer'=>$s['customer_name']];
                } elseif ($statusLower === 'reserved') {
                    $tableRows[] = ['table_name'=>$tbl,'status'=>'Reserved','hours_left'=>'–','customer'=>$s['customer_name']];
                } else {
                    $tableRows[] = ['table_name'=>$tbl,'status'=>'Available','hours_left'=>'–','customer'=>''];
                }
            } else {
                $tableRows[] = ['table_name'=>$tbl,'status'=>'Available','hours_left'=>'–','customer'=>''];
            }
        }
        $r = $conn->query("SELECT p.name, SUM(oi.quantity) as qty FROM order_items oi JOIN products p ON p.id=oi.product_id GROUP BY p.id ORDER BY qty DESC LIMIT 5"); if ($r) { while ($row = $r->fetch_assoc()) $topSelling[] = $row; }
        $r = $conn->query("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE DATE(created_at)=CURDATE()"); if ($r) $ordersRev = (float)$r->fetch_row()[0];
        $r = $conn->query("SELECT COALESCE(SUM(amount),0) FROM billiard_sessions WHERE DATE(created_at)=CURDATE()"); if ($r) $billiardRev = (float)$r->fetch_row()[0];
        $r = $conn->query("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE type='booking' AND DATE(created_at)=CURDATE()"); if ($r) $bookingRev = (float)$r->fetch_row()[0];
        $billiardSchedule = [];
        $r = $conn->query("
            SELECT session_code, table_name, customer_name, start_time, end_time, status
            FROM billiard_sessions
            WHERE status = 'Reserved'
            ORDER BY FIELD(table_name,'Outdoor 1','Outdoor 2','Outdoor 3','Indoor 1'), start_time ASC
        ");
        if ($r) while ($row = $r->fetch_assoc()) $billiardSchedule[] = $row;
        $airbnbBookings = [];
        $r = $conn->query("SELECT guest_name, check_in, check_out, status FROM bookings WHERE room='Airbnb' ORDER BY check_in ASC LIMIT 8");
        if ($r) while ($row = $r->fetch_assoc()) $airbnbBookings[] = $row;

        $bookedRanges = [];
        foreach ($airbnbBookings as $b) {
            if (in_array(strtolower($b['status']), ['confirmed', 'pending'])) {
                $bookedRanges[] = ['from' => $b['check_in'], 'to' => $b['check_out']];
            }
        }

        $conn->close();
    }
} catch (Throwable $e) {  }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<base href="/brew-n-break-pos/">
<title>Dashboard – Brew n' Break</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Lato:wght@300;400;700&display=swap" rel="stylesheet"/>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --sidebar-w:68px;--topnav-h:64px;
  --dark:#1e1a14;--darker:#17140f;
  --card-bg:#e8dcc8;--page-bg:#c8b89a;
  --cream:#f5eedc;--muted:#7a6e5f;
  --text-dark:#1e1a14;--text-mid:#4a3f30;--gold:#c8a96e;
}
html,body{height:100%;overflow:hidden;}
body{font-family:'Lato',sans-serif;background:var(--page-bg);display:flex;flex-direction:column;color:var(--text-dark);}
.topnav{background:var(--dark);display:flex;align-items:center;justify-content:space-between;padding:0 24px 0 16px;height:var(--topnav-h);flex-shrink:0;z-index:100;box-shadow:0 2px 12px rgba(0,0,0,0.4);}
.topnav-left{display:flex;align-items:center;gap:14px;}
.logo-circle{width:44px;height:44px;border-radius:50%;border:2px solid var(--gold);background:var(--darker);display:flex;align-items:center;justify-content:center;font-size:18px;}
.brand{font-family:'Playfair Display',serif;font-size:20px;color:var(--cream);}
.topnav-right{display:flex;align-items:center;gap:12px;}
.user-label{font-size:14px;color:var(--cream);font-weight:300;}
.user-avatar{width:36px;height:36px;border-radius:50%;background:rgba(255,255,255,0.12);border:1.5px solid var(--gold);display:flex;align-items:center;justify-content:center;color:var(--cream);font-size:18px;}

.layout{display:flex;flex:1;height:calc(100vh - var(--topnav-h));overflow:hidden;}
.sidebar{width:var(--sidebar-w);background:var(--darker);display:flex;flex-direction:column;align-items:center;padding:12px 0;gap:4px;flex-shrink:0;border-right:1px solid rgba(255,255,255,0.05);z-index:10;}
.nav-item{width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;color:var(--muted);font-size:20px;cursor:pointer;text-decoration:none;transition:background .2s,color .2s;position:relative;}
.nav-item:hover{background:rgba(255,255,255,0.08);color:var(--cream);}
.nav-item.active{background:var(--gold);color:var(--dark);}
.nav-item .tip{position:absolute;left:58px;background:var(--dark);color:var(--cream);font-size:11px;padding:4px 8px;border-radius:6px;white-space:nowrap;pointer-events:none;opacity:0;transition:opacity .2s;border:1px solid rgba(255,255,255,0.1);z-index:200;}
.nav-item:hover .tip{opacity:1;}
.nav-spacer{flex:1;}

@keyframes fadeUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}

.main{
  flex:1;
  display:flex;
  flex-direction:column;
  gap:20px;
  padding:28px;
  overflow:hidden;
  min-height:0;
  animation:fadeUp .5s ease both;
}

.page-header{display:flex;align-items:center;justify-content:space-between;flex-shrink:0;}
.page-title{font-family:'Playfair Display',serif;font-size:30px;color:var(--text-dark);}
.page-time{font-size:13px;color:var(--text-mid);display:flex;align-items:center;gap:6px;}

.stat-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;flex-shrink:0;}
.stat-card{background:var(--card-bg);border-radius:14px;padding:20px 24px;box-shadow:0 2px 8px rgba(0,0,0,0.1);}
.stat-label{font-size:12px;color:var(--muted);letter-spacing:.8px;text-transform:uppercase;margin-bottom:10px;}
.stat-value{font-family:'Playfair Display',serif;font-size:34px;color:var(--text-dark);line-height:1;}

.mid-row{display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;flex:1;min-height:0;}
.widget{background:var(--card-bg);border-radius:12px;padding:14px;box-shadow:0 2px 6px rgba(0,0,0,0.08);display:flex;flex-direction:column;overflow:hidden;min-height:0;}
.widget-title{font-size:12px;font-weight:700;color:var(--text-mid);letter-spacing:.5px;margin-bottom:8px;flex-shrink:0;}

.donut-wrap{display:flex;align-items:center;justify-content:center;gap:20px;flex:1;min-height:0;}
.donut-canvas{flex-shrink:0;}
.legend-wrap{display:flex;flex-direction:column;justify-content:center;}
.legend-item{display:flex;align-items:center;gap:8px;font-size:11px;color:var(--text-mid);margin-bottom:8px;}
.legend-dot{width:10px;height:10px;border-radius:50%;flex-shrink:0;}

.bar-canvas-wrap{flex:1;min-height:0;position:relative;width:100%;}

.cal-header{text-align:center;font-family:'Playfair Display',serif;font-size:13px;color:var(--text-dark);margin-bottom:6px;flex-shrink:0;}
.cal-grid{display:grid;grid-template-columns:repeat(7,1fr);gap:0;text-align:center;flex:1;}
.cal-day-label{font-size:9px;color:var(--muted);padding:3px 0;font-weight:700;}
.cal-day{font-size:11px;padding:0;border-radius:0;cursor:default;color:var(--text-mid);display:flex;align-items:center;justify-content:center;position:relative;height:28px;}
.cal-day.other-month{color:#bbb;}
.cal-day .dn{position:relative;z-index:1;width:24px;height:24px;display:flex;align-items:center;justify-content:center;border-radius:50%;}
.cal-day.booked-start::before,.cal-day.booked-end::before,.cal-day.booked-mid::before{content:'';position:absolute;top:50%;transform:translateY(-50%);height:22px;background:rgba(200,169,110,0.45);z-index:0;}
.cal-day.booked-start::before{left:50%;right:-1px;}
.cal-day.booked-end::before{left:-1px;right:50%;}
.cal-day.booked-mid::before{left:-1px;right:-1px;}
.cal-day.booked-start .dn,.cal-day.booked-end .dn,.cal-day.booked-single .dn{background:var(--gold);color:var(--darker);font-weight:700;}
.cal-day.booked-mid .dn{color:var(--darker);font-weight:600;}

.bottom-row{display:grid;grid-template-columns:1.6fr 1fr;gap:10px;flex:1;min-height:0;}
.bottom-widget{background:var(--card-bg);border-radius:12px;padding:14px;box-shadow:0 2px 6px rgba(0,0,0,0.08);overflow:hidden;display:flex;flex-direction:column;}

.status-table{width:100%;border-collapse:collapse;font-size:12px;flex:1;}
.status-table th{text-align:left;padding:8px 12px;color:var(--muted);font-size:10px;letter-spacing:.6px;text-transform:uppercase;border-bottom:1px solid rgba(0,0,0,0.1);}
.status-table td{padding:10px 12px;border-bottom:1px solid rgba(0,0,0,0.04);}
.status-badge{display:inline-block;padding:3px 12px;border-radius:20px;font-size:11px;font-weight:700;}
.badge-available{background:#d4edda;color:#2d6a4f;}
.badge-reserved{background:#fff3cd;color:#856404;}
.badge-occupied{background:#f8d7da;color:#721c24;}
.badge-confirmed{background:#d4edda;color:#2d6a4f;}
.badge-pending{background:#fff3cd;color:#856404;}
.badge-done{background:#e2e3e5;color:#4a4e54;}
.badge-cancelled{background:#f8d7da;color:#721c24;}
.status-table{width:100%;border-collapse:collapse;font-size:12px;}
.status-table th{text-align:left;padding:6px 10px;color:var(--muted);font-size:10px;letter-spacing:.6px;text-transform:uppercase;border-bottom:1px solid rgba(0,0,0,0.1);}
.status-table td{padding:8px 10px;}
.status-badge{display:inline-block;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;}

.sched-scroll{flex:1;overflow-y:auto;min-height:0;}
.sched-scroll::-webkit-scrollbar{width:4px;}
.sched-scroll::-webkit-scrollbar-thumb{background:rgba(0,0,0,0.15);border-radius:4px;}
.sched-table{width:100%;border-collapse:collapse;font-size:11px;}
.sched-table thead tr{border-bottom:1px solid rgba(0,0,0,0.12);}
.sched-table th{padding:5px 8px;color:var(--muted);font-size:9px;letter-spacing:.7px;text-transform:uppercase;font-weight:700;text-align:left;}
.sched-table tbody tr{border-bottom:1px solid rgba(0,0,0,0.04);transition:background .12s;}
.sched-table tbody tr:hover{background:rgba(200,169,110,0.15);}
.sched-table td{padding:7px 8px;color:var(--text-dark);vertical-align:middle;}
.sched-time{font-size:10px;color:var(--muted);white-space:nowrap;}
.sched-badge{display:inline-block;padding:2px 8px;border-radius:10px;font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;}
.sched-available{background:#d4edda;color:#2d6a4f;}
.sched-reserved{background:#c8b89a;color:#4a3020;}
.sched-occupied {background:#f8d7da;color:#721c24;}
.sched-pending  {background:#fff3cd;color:#856404;}
.sched-done     {background:#e2e3e5;color:#4a4e54;}
.sched-empty{text-align:center;padding:24px 0;color:var(--muted);font-size:12px;font-style:italic;}
#bellPopup{display:none;position:fixed;bottom:24px;right:24px;width:320px;background:#1e1a14;border-radius:12px;border:1px solid rgba(240,192,64,0.3);box-shadow:0 8px 32px rgba(0,0,0,0.55);z-index:99999;overflow:hidden;color:#f5eedc;}
.bp-header{background:rgba(240,192,64,0.1);padding:11px 14px;border-bottom:1px solid rgba(240,192,64,0.2);display:flex;align-items:center;justify-content:space-between;gap:8px;}
.bp-title{font-size:12px;font-weight:700;color:#f0c040;display:flex;align-items:center;gap:5px;}
.bp-close{background:none;border:none;color:rgba(255,255,255,0.45);cursor:pointer;font-size:18px;line-height:1;padding:0;transition:color .2s;}
.bp-close:hover{color:#fff;}
.bp-item{padding:10px 14px;border-bottom:1px solid rgba(255,255,255,0.06);font-size:12px;}
.bp-item:last-child{border-bottom:none;}
.bp-item-title{font-weight:700;color:#f0c040;margin-bottom:3px;}
.bp-item-msg{color:rgba(255,255,255,0.6);line-height:1.4;}
</style>
<!-- React CDN -->
<script src="https://unpkg.com/react@18/umd/react.development.js"></script>
<script src="https://unpkg.com/react-dom@18/umd/react-dom.development.js"></script>
<script src="https://unpkg.com/@babel/standalone/babel.min.js"></script>
<style id="responsive-overrides">
@media (max-width:900px){
  .stat-grid{grid-template-columns:repeat(2,1fr)!important;}
  .mid-row{grid-template-columns:1fr!important;overflow-y:auto;}
  .bottom-row{grid-template-columns:1fr!important;overflow-y:auto;}
  .main{overflow-y:auto!important;padding:16px!important;}
  .layout{overflow-y:auto!important;}
  body{height:auto!important;overflow:auto!important;}
}
@media (max-width:768px){
  .sidebar{width:52px!important;}
  .topnav{padding:0 12px 0 10px!important;}
  .brand{font-size:16px!important;}
  .page-title{font-size:22px!important;}
  .stat-value{font-size:26px!important;}
  .card,.widget,.bottom-widget{padding:14px!important;border-radius:10px!important;}
  .tbl-wrap{overflow-x:auto!important;}
  table{min-width:600px!important;}
  .toolbar{flex-direction:column!important;align-items:flex-start!important;}
  .tabs{width:100%!important;}
  .toolbar-right{width:100%!important;justify-content:flex-end!important;}
}
@media (max-width:480px){
  .stat-grid{grid-template-columns:1fr!important;}
  .sidebar{display:none!important;}
  .main{padding:12px!important;}
  .topnav{height:54px!important;}
  .brand{font-size:15px!important;}
  .logo-circle{width:36px!important;height:36px!important;}
  .page-title{font-size:20px!important;}
  .stat-value{font-size:22px!important;}
  .stat-card{padding:14px 16px!important;}
  .tab-btn{padding:6px 10px!important;font-size:12px!important;}
}
</style>
</head>
<body>

<nav class="topnav">
  <div class="topnav-left">
    <div class="logo-circle" style="overflow:hidden;padding:0;"><img src="../img/logo.png" style="width:100%;height:100%;object-fit:cover;display:block;border-radius:50%;"/></div>
    <span class="brand">Brew n' Break</span>
  </div>
  <div class="topnav-right">
    <span class="user-label"><?= htmlspecialchars($username) ?></span>
    <div style="position:relative;">
      <div class="user-avatar" style="overflow:hidden;padding:0;cursor:pointer;" onclick="toggleAvatarMenu(event)"><?php $up=$_SESSION['photo']??'';if($up):?><img src="<?=htmlspecialchars($up)?>" style="width:100%;height:100%;object-fit:cover;border-radius:50%;display:block;" alt=""/><?php else:?><span style="font-size:18px">👤</span><?php endif;?></div>
      <div id="avatarMenu" style="display:none;position:absolute;top:44px;right:0;background:#1e1a14;border-radius:10px;min-width:150px;box-shadow:0 8px 24px rgba(0,0,0,0.4);z-index:9999;overflow:hidden;border:1px solid rgba(255,255,255,0.1);">
        <a href="<?= $userRole==='Staff'?'/brew-n-break-pos/settings.php':'settings.php' ?>" style="display:block;padding:11px 16px;color:#f5eedc;font-size:13px;text-decoration:none;" onmouseover="this.style.background='rgba(255,255,255,0.08)'" onmouseout="this.style.background=''">⚙️ Settings</a>
        <a href="/brew-n-break-pos/logout.php" style="display:block;padding:11px 16px;color:#e07070;font-size:13px;text-decoration:none;border-top:1px solid rgba(255,255,255,0.08);" onmouseover="this.style.background='rgba(192,57,43,0.15)'" onmouseout="this.style.background=''">🚪 Log out</a>
      </div>
    </div>
  </div>
</nav>

<div class="layout">
  <aside class="sidebar">
    <a class="nav-item active" href="/brew-n-break-pos/staff.php"><span>🏠</span><span class="tip">Dashboard</span></a>
    <a class="nav-item" href="/brew-n-break-pos/menu.php"><span>☕</span><span class="tip">Cafe</span></a>
    <a class="nav-item" href="/brew-n-break-pos/billiard.php"><svg viewBox="0 0 24 24" width="22" height="22" xmlns="http://www.w3.org/2000/svg"><defs><radialGradient id="bg8" cx="40%" cy="35%" r="60%"><stop offset="0%" stop-color="#3a3a3a"/><stop offset="100%" stop-color="#0a0a0a"/></radialGradient><radialGradient id="wc8" cx="40%" cy="35%" r="60%"><stop offset="0%" stop-color="#ffffff"/><stop offset="100%" stop-color="#d8d8d8"/></radialGradient></defs><circle cx="12" cy="12" r="11" fill="url(#bg8)"/><circle cx="12" cy="12" r="5.2" fill="url(#wc8)"/><text x="12" y="15.5" font-size="6.5" text-anchor="middle" fill="#111" font-family="Arial,sans-serif" font-weight="900">8</text></svg><span class="tip">Billiard</span></a>
    <a class="nav-item" href="/brew-n-break-pos/transactions.php"><span>📋</span><span class="tip">Transactions</span></a>
    <a class="nav-item" href="/brew-n-break-pos/bookings.php"><span>📅</span><span class="tip">Bookings</span></a>
    <div class="nav-spacer"></div>
    <a class="nav-item" href="/brew-n-break-pos/notifications.php" id="bellNavItem"><span>🔔</span><span id="bellBadge"></span><span class="tip">Notifications</span></a>
    <a class="nav-item" href="/brew-n-break-pos/settings.php"><span>⚙️</span><span class="tip">Settings</span></a>
  </aside>

  <main class="main">
    <div class="page-header">
      <h1 class="page-title">Dashboard</h1>
      <div class="page-time">🕐 <span id="liveClock"></span></div>
    </div>

    <div class="stat-grid">
      <div class="stat-card"><div class="stat-label">Today's Orders</div><div class="stat-value"><?= number_format($totalOrders) ?></div></div>
      <div class="stat-card"><div class="stat-label">Today's Table Hours</div><div class="stat-value"><?= number_format($totalHours, 1) ?></div></div>
      <div class="stat-card"><div class="stat-label">Today's Bookings</div><div class="stat-value"><?= number_format($totalBookings) ?></div></div>
      <div class="stat-card"><div class="stat-label">Today's Revenue</div><div class="stat-value">₱<?= number_format($totalRevenue, 2) ?></div></div>
    </div>

    <div class="mid-row">
      <div class="widget">
        <div class="widget-title">Revenue Chart</div>
        <div class="donut-wrap">
          <div style="position:relative;flex-shrink:0;width:160px;height:160px;">
            <canvas id="donutChart"></canvas>
          </div>
          <?php
            $totalRev = $ordersRev + $billiardRev + $bookingRev;
            $oPct = $totalRev > 0 ? round($ordersRev  / $totalRev * 100) : 0;
            $bPct = $totalRev > 0 ? round($billiardRev / $totalRev * 100) : 0;
            $kPct = $totalRev > 0 ? round($bookingRev  / $totalRev * 100) : 0;
          ?>
          <div class="legend-wrap">
            <div class="legend-item"><div class="legend-dot" style="background:#7a6250"></div>Orders <?= $oPct ?>%</div>
            <div class="legend-item"><div class="legend-dot" style="background:#a89070"></div>Billiard <?= $bPct ?>%</div>
            <div class="legend-item"><div class="legend-dot" style="background:#d4c0a0"></div>Booking <?= $kPct ?>%</div>
          </div>
        </div>
      </div>

      <div class="widget">
        <div class="widget-title">Top-Selling</div>
        <div class="bar-canvas-wrap"><canvas id="barChart"></canvas></div>
      </div>

      <div class="widget">
        <div class="widget-title">Room Booking Calendar</div>
        <div class="cal-header" id="calHeader"></div>
        <div class="cal-grid" id="calGrid"></div>
      </div>
    </div>

    <div class="bottom-row">
      <div class="bottom-widget">
        <div class="widget-title">Billiard Table Status</div>
        <div id="billiard-react-root" style="flex:1;overflow:hidden;display:flex;flex-direction:column;"></div>
        <script type="text/babel">
          const { useState, useEffect } = React;

          function BilliardTableStatus() {
            const [tables, setTables] = useState(<?= json_encode($tableRows) ?>);
            const [lastUpdated, setLastUpdated] = useState(new Date());

            function getBadgeClass(status) {
              const s = status.toLowerCase();
              if (s === 'reserved') return 'badge-reserved';
              if (s === 'ongoing' || s === 'start') return 'badge-occupied';
              return 'badge-available';
            }

            function getDisplayStatus(status) {
              const s = status.toLowerCase();
              if (s === 'ongoing' || s === 'start') return 'Occupied';
              return status;
            }

            function fetchTables() {
              const update = data => { if(data.success && data.tables){ setTables(data.tables); setLastUpdated(new Date()); } };
              fetch('http://localhost:5000/api/billiard-status')
                .then(res => res.json()).then(update)
                .catch(() => {
                  fetch('/brew-n-break-pos/billiard_status.php')
                    .then(res => res.json()).then(update).catch(()=>{});
                });
            }

            useEffect(() => {
              const interval = setInterval(fetchTables, 30000);
              return () => clearInterval(interval);
            }, []);

            return (
              <div style={{display:'flex',flexDirection:'column',flex:1}}>
                <table className="status-table" style={{width:'100%',flex:1}}>
                  <thead>
                    <tr>
                      <th>Table Name</th>
                      <th>Customer</th>
                      <th>Status</th>
                      <th>Hours Left</th>
                    </tr>
                  </thead>
                  <tbody id="billiardStatusBody">
                    {tables.map((t, i) => (
                      <tr key={i}>
                        <td style={{fontWeight:600}}>{t.table_name}</td>
                        <td style={{color:'var(--muted)'}}>{t.customer || '–'}</td>
                        <td>
                          <span className={`status-badge ${getBadgeClass(t.status)}`}>
                            {getDisplayStatus(t.status)}
                          </span>
                        </td>
                        <td className="hours-left-cell" data-endtime={t.hours_left}>
                          {t.hours_left}
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
                <div style={{fontSize:'10px',color:'var(--muted)',textAlign:'right',marginTop:'6px'}}>
                  Live · Updated {lastUpdated.toLocaleTimeString()}
                </div>
              </div>
            );
          }

          const root = ReactDOM.createRoot(document.getElementById('billiard-react-root'));
          root.render(<BilliardTableStatus />);
        </script>
      </div>
      <div class="bottom-widget">
        <div class="widget-title">Billiard Reservations</div>
        <div class="sched-scroll">
          <?php if (empty($billiardSchedule)): ?>
            <div class="sched-empty">No reservations found.</div>
          <?php else: ?>
          <table class="sched-table">
            <thead>
              <tr>
                <th>Table</th>
                <th>Customer</th>
                <th>Time</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($billiardSchedule as $s):
                $stl = strtolower($s['status']);
                $bc  = match($stl) {
                  'done'      => 'sched-done',
                  'start', 'ongoing' => 'sched-occupied',
                  'pending'   => 'sched-pending',
                  'reserved'  => 'sched-reserved',
                  default     => 'sched-available'
                };
                $label = match($stl) {
                  'start', 'ongoing' => 'Occupied',
                  default => ucfirst($s['status'])
                };
                $startFmt = $s['start_time'] ? date('h:i A', strtotime($s['start_time'])) : '–';
                $endFmt   = $s['end_time']   ? date('h:i A', strtotime($s['end_time']))   : '–';
              ?>
              <tr>
                <td style="font-weight:700;white-space:nowrap"><?= htmlspecialchars($s['table_name']) ?></td>
                <td style="color:var(--text-mid)"><?= htmlspecialchars($s['customer_name'] ?: '–') ?></td>
                <td class="sched-time"><?= $startFmt ?> – <?= $endFmt ?></td>
                <td><span class="sched-badge <?= $bc ?>"><?= $label ?></span></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </main>
</div>

<script>
function toggleAvatarMenu(e){e.stopPropagation();var m=document.getElementById('avatarMenu');m.style.display=m.style.display==='none'?'block':'none';}
document.addEventListener('click',function(){var m=document.getElementById('avatarMenu');if(m)m.style.display='none';});
function updateClock(){
  const now=new Date();
  document.getElementById('liveClock').textContent=
    now.toLocaleTimeString('en-PH',{hour:'2-digit',minute:'2-digit',second:'2-digit',hour12:true})+' '+
    now.toLocaleDateString('en-PH',{month:'long',day:'numeric',year:'numeric'});
}
updateClock(); setInterval(updateClock,1000);
function tickCountdowns(){
  document.querySelectorAll('.hours-left-cell').forEach(cell=>{
    const val = cell.dataset.endtime;
    if(!val || !/^\d{2}:\d{2}:\d{2}$/.test(val)) return;
    const parts = val.split(':').map(Number);
    let total = parts[0]*3600 + parts[1]*60 + parts[2] - 1;
    if(total < 0){ cell.textContent='Overtime'; return; }
    const hh = String(Math.floor(total/3600)).padStart(2,'0');
    const mm = String(Math.floor((total%3600)/60)).padStart(2,'0');
    const ss = String(total%60).padStart(2,'0');
    const newVal = `${hh}:${mm}:${ss}`;
    cell.dataset.endtime = newVal;
    cell.textContent = newVal;
  });
}
setInterval(tickCountdowns, 1000);
const badgeClass = s => { const sl=s.toLowerCase(); if(sl==='reserved') return 'badge-reserved'; if(sl==='occupied'||sl==='ongoing'||sl==='start') return 'badge-occupied'; return 'badge-available'; };
const displayStatus = s => { const sl=s.toLowerCase(); return (sl==='ongoing'||sl==='start')?'Occupied':s.charAt(0).toUpperCase()+s.slice(1); };
function applyBilliardRows(tables) {
  const tbody = document.getElementById('billiardStatusBody');
  if (!tbody) return;
  tbody.innerHTML = tables.map(t => `
    <tr>
      <td style="font-weight:600">${t.table_name}</td>
      <td style="color:var(--muted)">${t.customer || '–'}</td>
      <td><span class="status-badge ${badgeClass(t.status)}">${displayStatus(t.status)}</span></td>
      <td class="hours-left-cell" data-endtime="${t.hours_left}">${t.hours_left}</td>
    </tr>`).join('');
}
async function refreshBilliardStatus() {
  try {
    const res  = await fetch('http://localhost:5000/api/billiard-status');
    const data = await res.json();
    if (data.success && data.tables) { applyBilliardRows(data.tables); return; }
    throw new Error();
  } catch(e) {
    try {
      const res2  = await fetch('/brew-n-break-pos/billiard_status.php');
      const data2 = await res2.json();
      if (data2.success && data2.tables) applyBilliardRows(data2.tables);
    } catch(e2) {}
  }
}
refreshBilliardStatus();
setInterval(refreshBilliardStatus, 5000);

new Chart(document.getElementById('donutChart'),{
  type:'doughnut',
  data:{labels:['Orders','Billiard','Booking'],datasets:[{data:[<?= $ordersRev ?>,<?= $billiardRev ?>,<?= $bookingRev ?>],backgroundColor:['#7a6250','#a89070','#d4c0a0'],borderWidth:0}]},
  options:{cutout:'65%',plugins:{legend:{display:false}},responsive:true,maintainAspectRatio:false}
});

const topLabels=<?= json_encode(array_column($topSelling,'name')) ?>;
const topData=<?= json_encode(array_column($topSelling,'qty')) ?>;
new Chart(document.getElementById('barChart'),{
  type:'bar',
  data:{labels:topLabels,datasets:[{data:topData,backgroundColor:'#8a7055',borderRadius:4}]},
  options:{
    responsive:true,
    maintainAspectRatio:false,
    plugins:{legend:{display:false}},
    scales:{
      x:{ticks:{font:{size:10,weight:'600'},color:'#3a3020',maxRotation:0,minRotation:0,autoSkip:false},grid:{display:false}},
      y:{ticks:{color:'#7a6e5f',font:{size:10}},grid:{color:'rgba(0,0,0,0.06)'}}
    }
  }
});

(function(){
  const now=new Date(),year=now.getFullYear(),month=now.getMonth();
  const months=['January','February','March','April','May','June','July','August','September','October','November','December'];
  document.getElementById('calHeader').textContent=months[month]+' '+year;
  const grid=document.getElementById('calGrid');

  const bookedRanges=<?= json_encode($bookedRanges) ?>;
  const dayRole={};
  bookedRanges.forEach(r=>{
    const from=new Date(r.from),to=new Date(r.to);
    const visible=[];
    for(let cur=new Date(from);cur<=to;cur.setDate(cur.getDate()+1)){
      if(cur.getFullYear()===year&&cur.getMonth()===month) visible.push(cur.getDate());
    }
    visible.forEach((day,i)=>{
      const dow=new Date(year,month,day).getDay();
      const isFirst=i===0, isLast=i===visible.length-1;
      const effStart=isFirst||(dow===1&&i>0);
      const effEnd  =isLast;
      dayRole[day]=(effStart&&effEnd)?(i===0?'single':'end'):effStart?'start':effEnd?'end':'mid';
    });
  });

  ['Mo','Tu','We','Th','Fr','Sa','Su'].forEach(d=>{const el=document.createElement('div');el.className='cal-day-label';el.textContent=d;grid.appendChild(el);});
  const first=new Date(year,month,1).getDay();
  const offset=(first===0)?6:first-1;
  const prev=new Date(year,month,0).getDate();
  const days=new Date(year,month+1,0).getDate();
  for(let i=offset-1;i>=0;i--){const el=document.createElement('div');el.className='cal-day other-month';el.innerHTML=`<span class="dn">${prev-i}</span>`;grid.appendChild(el);}
  for(let d=1;d<=days;d++){
    const el=document.createElement('div');
    const role=dayRole[d];
    el.className='cal-day'+(role?' booked-'+role:'');
    el.innerHTML=`<span class="dn">${d}</span>`;
    grid.appendChild(el);
  }
  const rem=42-(offset+days);
  for(let d=1;d<=rem;d++){const el=document.createElement('div');el.className='cal-day other-month';el.innerHTML=`<span class="dn">${d}</span>`;grid.appendChild(el);}
})();
</script>
<div id="bellPopup">
  <div class="bp-header">
    <span class="bp-title">⚠️ Session Expiring Soon</span>
    <button class="bp-close" onclick="closeBellPopup()">×</button>
  </div>
  <div id="bellPopupItems"></div>
</div>
<script>
(function(){
  const STORAGE_KEY = 'bellDismissed';
  const canonId = id => String(id).replace(/^done_/, '');
  function getDismissed(){ try{ return new Set(JSON.parse(sessionStorage.getItem(STORAGE_KEY)||'[]')); }catch(e){ return new Set(); } }
  function saveDismissed(s){ try{ sessionStorage.setItem(STORAGE_KEY, JSON.stringify([...s])); }catch(e){} }

  let currentAlertIds = [];
  async function pollBell(){
    try {
      const res  = await fetch('http://localhost:5000/api/notifications');
      const data = await res.json();
      const alerts = (data.alerts || []).filter(a => (a.secs_left ?? 999) <= 300);
      const badge = document.getElementById('bellBadge');
      const popup = document.getElementById('bellPopup');
      const items = document.getElementById('bellPopupItems');
      if (!badge || !popup || !items) return;
      const dismissed = getDismissed();
      const undismissed = alerts.filter(a => !dismissed.has(canonId(a.id)));
      if (undismissed.length > 0) {
        badge.textContent = undismissed.length;
        badge.style.display = 'flex';
        items.innerHTML = undismissed.map(a =>
          `<div class="bp-item"><div class="bp-item-title">${a.title}</div><div class="bp-item-msg">${a.message}</div></div>`
        ).join('');
        currentAlertIds = undismissed.map(a => canonId(a.id));
        if (undismissed.length) {
          popup.style.display = 'block';
        }
      } else {
        badge.style.display = 'none';
      }
    } catch(e) {}
  }
  window.closeBellPopup = function(){
    document.getElementById('bellPopup').style.display = 'none';
    const dismissed = getDismissed();
    currentAlertIds.forEach(id => dismissed.add(id));
    saveDismissed(dismissed);
  };
  pollBell();
  setInterval(pollBell, 30000);
})();
</script>
</body>
</html>
