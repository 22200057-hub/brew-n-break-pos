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

$reports  = [];
$username = $_SESSION['username'] ?? 'Admin';
$userRole = ucfirst(strtolower($_SESSION['role'] ?? 'admin'));

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if (!$conn->connect_error) {
        $r = $conn->query("SELECT * FROM reports ORDER BY created_at DESC");
        if ($r) while ($row = $r->fetch_assoc()) $reports[] = $row;
        $conn->close();
    }
} catch (Throwable $e) {}

if (empty($reports)) { $reports = []; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Generate Report – Brew n' Break</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Lato:wght@300;400;700&display=swap" rel="stylesheet"/>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --dark:#1e1a14;--darker:#17140f;
  --card-bg:#e8dcc8;--page-bg:#c8b89a;
  --cream:#f5eedc;--muted:#7a6e5f;
  --text-dark:#1e1a14;--text-mid:#4a3f30;--gold:#c8a96e;
  --row-even:#d8ccb4;--row-odd:#cfc3aa;
  --green:#3a6b4a;--red:#7a2020;
}
body{font-family:'Lato',sans-serif;background:var(--page-bg);display:flex;flex-direction:column;min-height:100vh;color:var(--text-dark);}
.topnav{background:var(--dark);display:flex;align-items:center;justify-content:space-between;padding:0 24px 0 16px;height:64px;position:sticky;top:0;z-index:100;box-shadow:0 2px 12px rgba(0,0,0,0.4);}
.topnav-left{display:flex;align-items:center;gap:14px;}
.logo-circle{width:44px;height:44px;border-radius:50%;border:2px solid var(--gold);background:var(--darker);display:flex;align-items:center;justify-content:center;font-size:18px;}
.brand{font-family:'Playfair Display',serif;font-size:20px;color:var(--cream);}
.topnav-right{display:flex;align-items:center;gap:12px;}
.user-label{font-size:14px;color:var(--cream);font-weight:300;}
.user-avatar{width:36px;height:36px;border-radius:50%;background:rgba(255,255,255,0.12);border:1.5px solid var(--gold);display:flex;align-items:center;justify-content:center;color:var(--cream);font-size:18px;}
.layout{display:flex;flex:1;}
.sidebar{width:68px;background:var(--darker);display:flex;flex-direction:column;align-items:center;padding:12px 0;gap:4px;flex-shrink:0;border-right:1px solid rgba(255,255,255,0.05);z-index:10;}
.nav-item{width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;color:var(--muted);font-size:20px;cursor:pointer;text-decoration:none;transition:background .2s,color .2s;position:relative;}
.nav-item:hover{background:rgba(255,255,255,0.08);color:var(--cream);}
.nav-item.active{background:var(--gold);color:var(--dark);}
.nav-item .tip{position:absolute;left:58px;background:var(--dark);color:var(--cream);font-size:11px;padding:4px 8px;border-radius:6px;white-space:nowrap;pointer-events:none;opacity:0;transition:opacity .2s;border:1px solid rgba(255,255,255,0.1);z-index:200;}
.nav-item:hover .tip{opacity:1;}
.nav-spacer{flex:1;}
.main{flex:1;padding:28px;display:flex;flex-direction:column;gap:20px;animation:fadeUp .5s ease both;}
@keyframes fadeUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
.page-header{display:flex;align-items:center;justify-content:space-between;}
.page-title{font-family:'Playfair Display',serif;font-size:30px;color:var(--text-dark);}
.page-time{font-size:13px;color:var(--text-mid);display:flex;align-items:center;gap:6px;}
.card{background:var(--card-bg);border-radius:14px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,0.1);}
.toolbar{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;gap:12px;flex-wrap:wrap;}
.tabs{display:flex;background:rgba(0,0,0,0.08);border-radius:8px;padding:3px;}
.tab-btn{padding:7px 22px;border:none;background:transparent;border-radius:6px;font-size:13px;font-family:'Lato',sans-serif;color:var(--text-mid);cursor:pointer;font-weight:400;transition:background .2s,color .2s;}
.tab-btn.active{background:var(--dark);color:var(--cream);font-weight:700;}
.toolbar-right{display:flex;align-items:center;gap:8px;}
.search-wrap{display:flex;align-items:center;gap:6px;background:rgba(255,255,255,0.5);border-radius:8px;padding:6px 12px;border:1px solid rgba(0,0,0,0.1);}
.search-wrap input{border:none;background:transparent;outline:none;font-size:13px;color:var(--text-dark);width:130px;font-family:'Lato',sans-serif;}
.search-wrap input::placeholder{color:var(--muted);}
.icon-btn{width:34px;height:34px;border-radius:8px;border:1px solid rgba(0,0,0,0.15);background:rgba(255,255,255,0.4);display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:16px;transition:background .2s;}
.icon-btn:hover{background:rgba(255,255,255,0.7);}
.tbl-wrap{overflow:visible;}
table{width:100%;border-collapse:collapse;font-size:13px;}
thead tr{position:sticky;top:0;z-index:5;background:var(--card-bg);border-bottom:2px solid rgba(0,0,0,0.15);}
thead th{text-align:left;padding:10px 16px;color:var(--text-mid);font-size:12px;letter-spacing:.6px;text-transform:uppercase;font-weight:700;background:var(--card-bg);}
#tableCard{}
tbody tr:nth-child(odd){background:var(--row-odd);}
tbody tr:nth-child(even){background:var(--row-even);}
tbody tr{transition:background .15s;}
tbody tr:hover{background:rgba(200,169,110,0.3);}
tbody td{padding:11px 16px;color:var(--text-dark);}
.action-wrap{position:relative;display:inline-block;}
.action-btn{background:var(--dark);color:var(--cream);border:none;border-radius:8px;padding:5px 14px;cursor:pointer;font-size:14px;transition:background .2s;letter-spacing:2px;}
.action-btn:hover{background:#3a3020;}
.dropdown{display:none;position:fixed;background:var(--dark);border-radius:10px;min-width:160px;box-shadow:0 8px 24px rgba(0,0,0,0.4);z-index:9999;overflow:hidden;}
.dropdown a{display:block;padding:10px 16px;color:var(--cream);font-size:13px;text-decoration:none;transition:background .15s;}
.dropdown a:hover{background:rgba(255,255,255,0.1);}
.dropdown a.danger{color:#e07070;}
.action-wrap.open .dropdown{display:block;}

.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:300;align-items:center;justify-content:center;}
.modal-overlay.open{display:flex;}
.gen-modal{background:var(--card-bg);border-radius:16px;padding:28px 32px;width:min(380px,92vw);box-shadow:0 20px 60px rgba(0,0,0,0.4);animation:fadeUp .3s ease both;}
.gen-modal h2{font-family:'Playfair Display',serif;font-size:20px;margin-bottom:20px;color:var(--text-dark);}
.form-group{display:flex;flex-direction:column;gap:5px;margin-bottom:14px;}
.form-group label{font-size:11px;letter-spacing:1px;text-transform:uppercase;color:var(--muted);}
.form-group select,.form-group input{background:rgba(255,255,255,0.6);border:1px solid rgba(0,0,0,0.15);border-radius:8px;padding:9px 12px;font-size:13px;font-family:'Lato',sans-serif;color:var(--text-dark);outline:none;transition:border-color .2s;width:100%;}
.form-group select:focus,.form-group input:focus{border-color:var(--gold);}
.error-msg{color:#c0392b;font-size:12px;min-height:14px;margin-bottom:6px;}
.gen-actions{display:flex;gap:8px;justify-content:flex-end;margin-top:8px;}
.btn{padding:9px 20px;border-radius:8px;border:none;font-size:13px;font-family:'Lato',sans-serif;font-weight:700;cursor:pointer;transition:background .2s;}
.btn-generate{background:var(--green);color:#fff;}
.btn-generate:hover{background:#2d5438;}
.btn-clear{background:rgba(0,0,0,0.1);color:var(--text-dark);}
.btn-cancel{background:var(--red);color:#fff;}
.btn-cancel:hover{background:#5c1818;}

.report-modal{background:#fff;border-radius:16px;width:min(580px,95vw);max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,0.4);animation:fadeUp .3s ease both;}
.report-body{padding:36px 40px;color:#1a1410;font-family:'Lato',sans-serif;}
.rep-header{display:flex;justify-content:space-between;align-items:flex-start;padding-bottom:20px;border-bottom:2px solid #e8dcc8;margin-bottom:24px;}
.rep-brand{font-family:'Playfair Display',serif;font-size:22px;}
.rep-sub{font-size:11px;color:#7a6e5f;letter-spacing:1px;text-transform:uppercase;margin-top:2px;}
.rep-meta{text-align:right;font-size:12px;color:#7a6e5f;line-height:1.9;}
.rep-meta strong{color:#1a1410;}
.rep-section-title{font-size:11px;letter-spacing:1.5px;text-transform:uppercase;color:#7a6e5f;font-weight:700;margin:20px 0 10px;}
.rep-table{width:100%;border-collapse:collapse;font-size:13px;}
.rep-table th{text-align:left;padding:8px 10px;background:#f5eedc;color:#4a3f30;font-size:11px;letter-spacing:.5px;text-transform:uppercase;}
.rep-table td{padding:9px 10px;border-bottom:1px solid #e8dcc8;color:#1a1410;}
.rep-table tr:last-child td{border-bottom:none;}
.rep-summary{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin:16px 0;}
.rep-sum-card{background:#f5eedc;border-radius:8px;padding:12px 16px;text-align:center;}
.rep-sum-label{font-size:10px;letter-spacing:.8px;text-transform:uppercase;color:#7a6e5f;margin-bottom:4px;}
.rep-sum-value{font-family:'Playfair Display',serif;font-size:18px;color:#1a1410;}
.rep-footer{text-align:center;font-size:11px;color:#7a6e5f;margin-top:24px;padding-top:16px;border-top:1px solid #e8dcc8;}
.rep-actions{display:flex;gap:10px;justify-content:flex-end;padding:0 40px 28px;}
@media print{
  body *{visibility:hidden;}
  .report-body,.report-body *{visibility:visible;}
  .report-body{position:fixed;inset:0;padding:40px;}
  .rep-actions{display:none!important;}
}

.del-modal{background:var(--card-bg);border-radius:16px;padding:28px 32px;width:min(360px,92vw);box-shadow:0 20px 60px rgba(0,0,0,0.4);animation:fadeUp .3s ease both;}
.del-modal-icon{font-size:40px;text-align:center;margin-bottom:12px;}
.del-modal h2{font-family:'Playfair Display',serif;font-size:20px;color:var(--text-dark);text-align:center;margin-bottom:8px;}
.del-modal p{font-size:13px;color:var(--muted);text-align:center;margin-bottom:22px;line-height:1.5;}
.del-modal .gen-actions{justify-content:center;gap:10px;margin-top:0;}

.pg-btn{padding:5px 11px;border-radius:7px;border:1px solid rgba(0,0,0,0.15);background:rgba(255,255,255,0.4);cursor:pointer;font-size:13px;font-family:'Lato',sans-serif;font-weight:700;color:var(--text-dark);transition:background .2s;min-width:34px;}
.pg-btn:hover:not(:disabled){background:rgba(255,255,255,0.7);}
.pg-btn:disabled{opacity:.4;cursor:not-allowed;}
.pg-btn.pg-active{background:var(--dark);color:var(--cream);border-color:transparent;}
.pg-ellipsis{padding:0 4px;color:var(--muted);font-size:13px;line-height:1;display:inline-flex;align-items:center;}
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
    <a class="nav-item" href="dashboard.php"><span>🏠</span><span class="tip">Dashboard</span></a>
    <?php if ($userRole !== 'Staff'): ?><a class="nav-item" href="users.php"><span>👤</span><span class="tip">Users</span></a><?php endif; ?>
    <a class="nav-item" href="menu.php"><span>☕</span><span class="tip">Cafe</span></a>
    <a class="nav-item" href="billiard.php"><svg viewBox="0 0 24 24" width="22" height="22" xmlns="http://www.w3.org/2000/svg"><defs><radialGradient id="bg8" cx="40%" cy="35%" r="60%"><stop offset="0%" stop-color="#3a3a3a"/><stop offset="100%" stop-color="#0a0a0a"/></radialGradient><radialGradient id="wc8" cx="40%" cy="35%" r="60%"><stop offset="0%" stop-color="#ffffff"/><stop offset="100%" stop-color="#d8d8d8"/></radialGradient></defs><circle cx="12" cy="12" r="11" fill="url(#bg8)"/><circle cx="12" cy="12" r="5.2" fill="url(#wc8)"/><text x="12" y="15.5" font-size="6.5" text-anchor="middle" fill="#111" font-family="Arial,sans-serif" font-weight="900">8</text></svg><span class="tip">Billiard</span></a>
    <a class="nav-item" href="transactions.php"><span>📋</span><span class="tip">Transactions</span></a>
    <a class="nav-item" href="bookings.php"><span>📅</span><span class="tip">Bookings</span></a>
    <?php if ($userRole !== 'Staff'): ?><a class="nav-item active" href="reports.php"><span>📁</span><span class="tip">Reports</span></a><?php endif; ?>
    <div class="nav-spacer"></div>
    <a class="nav-item" href="notifications.php" id="bellNavItem"><span>🔔</span><span id="bellBadge"></span><span class="tip">Notifications</span></a>
    <a class="nav-item" href="settings.php"><span>⚙️</span><span class="tip">Settings</span></a>
  </aside>

  <main class="main">
    <div class="page-header">
      <h1 class="page-title">Generate Report</h1>
      <div class="page-time">🕐 <span id="liveClock"></span></div>
    </div>

    <div class="card" id="tableCard">
      <div class="toolbar" style="justify-content:flex-end;">
        <div class="toolbar-right">
          <div class="search-wrap">
            <span>🔍</span>
            <input type="text" id="searchInput" placeholder="Search" oninput="applySearch()"/>
          </div>
          <button class="icon-btn" title="Generate Report" onclick="openGenModal()">➕</button>
        </div>
      </div>

      <div class="tbl-wrap">
        <table>
          <thead>
            <tr>
              <th>Report ID</th>
              <th>Date</th>
              <th>Type of Report</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody id="tableBody">
            <?php foreach ($reports as $rep):
              $dateRange = date('F d, Y', strtotime($rep['date_from'])).' – '.date('F d, Y', strtotime($rep['date_to']));
            ?>
            <tr data-search="<?= strtolower(htmlspecialchars($rep['report_code'].' '.$rep['type'])) ?>"
                data-id="<?= $rep['id'] ?>"
                data-code="<?= htmlspecialchars($rep['report_code']) ?>"
                data-from="<?= htmlspecialchars($rep['date_from']) ?>"
                data-to="<?= htmlspecialchars($rep['date_to']) ?>"
                data-type="<?= htmlspecialchars($rep['type']) ?>"
                data-created="<?= htmlspecialchars($rep['created_at']) ?>">
              <td><?= htmlspecialchars($rep['report_code']) ?></td>
              <td><?= $dateRange ?></td>
              <td><?= htmlspecialchars($rep['type']) ?></td>
              <td>
                <div class="action-wrap" id="wrap-<?= $rep['id'] ?>">
                  <button class="action-btn" onclick="toggleDD(<?= $rep['id'] ?>, this)">–––</button>
                  <div class="dropdown" id="dd-<?= $rep['id'] ?>">
                    <a href="#" onclick="viewReport(this.closest('tr'));return false;">📄 View Report</a>
                    <a href="#" onclick="window.open('print_report.php?id=<?= $rep['id'] ?>','_blank');return false;">🖨️ Print Report</a>
                    <a href="#" class="danger" onclick="deleteReport(<?= $rep['id'] ?>);return false;">🗑️ Delete</a>
                  </div>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div style="display:flex;align-items:center;justify-content:space-between;margin-top:12px;flex-wrap:wrap;gap:10px;flex-shrink:0;">
        <div id="pageInfo" style="font-size:12px;color:var(--muted);"></div>
        <div id="pageControls" style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;"></div>
      </div>
    </div>
  </main>
</div>

<!-- GENERATE REPORT MODAL -->
<div class="modal-overlay" id="genOverlay" onclick="closeGenOutside(event)">
  <div class="gen-modal" onclick="event.stopPropagation()">
    <h2>Generate Report</h2>
    <div class="form-group">
      <label>Type of Report</label>
      <select id="gType">
        <option value="Daily Report">Daily Report</option>
        <option value="Transaction Report">Transaction Report</option>
        <option value="Cafe Report">Cafe Report</option>
        <option value="Billiard Report">Billiard Report</option>
        <option value="Booking Report">Booking Report</option>
        <option value="Revenue Report">Revenue Report</option>
      </select>
    </div>
    <div class="form-group">
      <label>Start Date</label>
      <input type="date" id="gStart"/>
    </div>
    <div class="form-group">
      <label>End Date</label>
      <input type="date" id="gEnd"/>
    </div>
    <div class="error-msg" id="genError"></div>
    <div class="gen-actions">
      <button class="btn btn-cancel" onclick="closeGenModal()">Cancel</button>
      <button class="btn btn-clear" onclick="clearGen()">Clear</button>
      <button class="btn btn-generate" onclick="submitReport()">Generate</button>
    </div>
  </div>
</div>

<!-- DELETE CONFIRM MODAL -->
<div class="modal-overlay" id="deleteOverlay" onclick="closeDeleteOutside(event)">
  <div class="del-modal" onclick="event.stopPropagation()">
    <div class="del-modal-icon">🗑️</div>
    <h2>Delete Report</h2>
    <p>Are you sure you want to delete this report?<br/>This action cannot be undone.</p>
    <div class="gen-actions">
      <button class="btn btn-clear" onclick="closeDeleteModal()">Cancel</button>
      <button class="btn btn-cancel" id="confirmDeleteBtn">Delete</button>
    </div>
  </div>
</div>

<!-- REPORT VIEWER MODAL -->
<div class="modal-overlay" id="reportOverlay" onclick="closeReportOutside(event)">
  <div class="report-modal">
    <div class="report-body" id="reportContent">
      <div class="rep-header">
        <div>
          <div class="rep-brand">🎱 Brew n' Break</div>
          <div class="rep-sub">By Bamboo Court</div>
        </div>
        <div class="rep-meta">
          <div><strong>Report #</strong> <span id="repCode"></span></div>
          <div><strong>Type:</strong> <span id="repType"></span></div>
          <div><strong>Period:</strong> <span id="repPeriod"></span></div>
          <div><strong>Generated:</strong> <span id="repGenDate"></span></div>
        </div>
      </div>

      <div class="rep-section-title">Summary</div>
      <div class="rep-summary" id="repSummary"></div>

      <div class="rep-section-title">Transactions</div>
      <table class="rep-table">
        <thead>
          <tr><th>ID</th><th>Description</th><th>Type</th><th>Amount</th><th>Status</th><th>Date</th></tr>
        </thead>
        <tbody id="repRows"></tbody>
      </table>

      <div class="rep-footer">
        Generated by Brew n' Break Admin System<br/>
        facebook.com/brewnbreak
      </div>
    </div>
    <div class="rep-actions">
      <button class="btn btn-clear" onclick="closeReport()">Close</button>
      <button class="btn btn-generate" id="repPrintBtn" onclick="openPrintReport()">🖨️ Print / Save PDF</button>
    </div>
  </div>
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
const PER_PAGE = 13;
let currentPage = 1;
let _filteredRows = [];

function applySearch(){
  const q = document.getElementById('searchInput').value.toLowerCase();
  _filteredRows = [];
  document.querySelectorAll('#tableBody tr[data-search]').forEach(r => {
    const match = !q || r.dataset.search.includes(q);
    if(match) _filteredRows.push(r);
    r.style.display = 'none';
  });
  currentPage = 1;
  renderPage();
  renderPagination();
}

function renderPage(){
  const start = (currentPage - 1) * PER_PAGE;
  const end   = start + PER_PAGE;
  document.querySelectorAll('#tableBody tr').forEach(r => r.style.display = 'none');
  _filteredRows.forEach((r, i) => { r.style.display = (i >= start && i < end) ? 'table-row' : 'none'; });
  const total = _filteredRows.length;
  const info  = document.getElementById('pageInfo');
  if(total === 0){ info.textContent = 'No reports found'; }
  else { info.textContent = 'Showing '+(start+1)+'–'+Math.min(end, total)+' of '+total+' reports'; }
}

function renderPagination(){
  const total = _filteredRows.length;
  const pages = Math.ceil(total / PER_PAGE);
  const ctrl  = document.getElementById('pageControls');
  if(pages <= 1){ ctrl.innerHTML = ''; return; }
  let html = `<button class="pg-btn" onclick="goToPage(${currentPage-1})" ${currentPage===1?'disabled':''}>‹</button>`;
  for(let i = 1; i <= pages; i++){
    if(i === 1 || i === pages || (i >= currentPage-2 && i <= currentPage+2)){
      html += `<button class="pg-btn${i===currentPage?' pg-active':''}" onclick="goToPage(${i})">${i}</button>`;
    } else if(i === currentPage-3 || i === currentPage+3){
      html += `<span class="pg-ellipsis">…</span>`;
    }
  }
  html += `<button class="pg-btn" onclick="goToPage(${currentPage+1})" ${currentPage===pages?'disabled':''}>›</button>`;
  ctrl.innerHTML = html;
}

function goToPage(n){
  const pages = Math.ceil(_filteredRows.length / PER_PAGE);
  if(n < 1 || n > pages) return;
  currentPage = n;
  renderPage();
  renderPagination();
}
function toggleDD(id, btn){
  const wrap = document.getElementById('wrap-'+id);
  const dd   = wrap.querySelector('.dropdown');
  const isOpen = wrap.classList.contains('open');
  document.querySelectorAll('.action-wrap').forEach(w=>w.classList.remove('open'));
  document.querySelectorAll('.dropdown').forEach(d=>{d.style.top='';d.style.left='';});
  if(!isOpen){
    const rect = btn.getBoundingClientRect();
    dd.style.top  = (rect.bottom + window.scrollY + 4) + 'px';
    dd.style.left = (rect.right  - 160) + 'px';
    wrap.classList.add('open');
  }
}
document.addEventListener('click',e=>{
  if(!e.target.closest('.action-wrap')) document.querySelectorAll('.action-wrap').forEach(w=>w.classList.remove('open'));
});
function openGenModal(){
  document.getElementById('genError').textContent='';
  const today=new Date().toISOString().split('T')[0];
  const monthAgo=new Date(Date.now()-30*864e5).toISOString().split('T')[0];
  document.getElementById('gStart').value=monthAgo;
  document.getElementById('gEnd').value=today;
  document.getElementById('genOverlay').classList.add('open');
}
function closeGenModal(){ document.getElementById('genOverlay').classList.remove('open'); }
function closeGenOutside(e){ if(e.target===document.getElementById('genOverlay')) closeGenModal(); }
function clearGen(){
  document.getElementById('gType').selectedIndex=0;
  document.getElementById('gStart').value='';
  document.getElementById('gEnd').value='';
  document.getElementById('genError').textContent='';
}

async function submitReport(){
  const type  = document.getElementById('gType').value;
  const start = document.getElementById('gStart').value;
  const end   = document.getElementById('gEnd').value;
  const errEl = document.getElementById('genError');
  if(!start||!end){ errEl.textContent='Please select both dates.'; return; }
  if(end<start){ errEl.textContent='End date must be after start date.'; return; }

  const res  = await fetch('report_action.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'generate',type,start,end})});
  const data = await res.json();
  if(data.success){ closeGenModal(); location.reload(); }
  else errEl.textContent=data.message||'Failed to generate report.';
}
let _currentReportId = null;
async function viewReport(row){
  _currentReportId = row.dataset.id;
  document.getElementById('repCode').textContent   = row.dataset.code;
  document.getElementById('repType').textContent   = row.dataset.type;
  const genDate = row.dataset.created ? new Date(row.dataset.created) : new Date();
  document.getElementById('repGenDate').textContent = genDate.toLocaleString('en-PH',{month:'long',day:'numeric',year:'numeric',hour:'2-digit',minute:'2-digit',hour12:true});

  const from = new Date(row.dataset.from).toLocaleDateString('en-PH',{month:'long',day:'numeric',year:'numeric'});
  const to   = new Date(row.dataset.to).toLocaleDateString('en-PH',{month:'long',day:'numeric',year:'numeric'});
  document.getElementById('repPeriod').textContent = `${from} – ${to}`;
  const res  = await fetch('report_action.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'fetch',start:row.dataset.from,end:row.dataset.to,type:row.dataset.type})});
  const data = await res.json();

  let rows='', total=0, count=0;
  if(data.success && data.transactions){
    data.transactions.forEach(t=>{
      total+=parseFloat(t.amount)||0;
      count++;
      rows+=`<tr><td>${t.code}</td><td>${t.product}</td><td>${t.type}</td><td>₱${parseFloat(t.amount).toLocaleString('en-PH',{minimumFractionDigits:2})}</td><td>${t.status}</td><td>${t.date}</td></tr>`;
    });
  }
  if(!rows) rows='<tr><td colspan="6" style="text-align:center;color:#7a6e5f;padding:16px">No transactions found for this period</td></tr>';

  document.getElementById('repRows').innerHTML=rows;
  document.getElementById('repSummary').innerHTML=`
    <div class="rep-sum-card"><div class="rep-sum-label">Total Transactions</div><div class="rep-sum-value">${count}</div></div>
    <div class="rep-sum-card"><div class="rep-sum-label">Total Revenue</div><div class="rep-sum-value">₱${total.toLocaleString('en-PH',{minimumFractionDigits:2})}</div></div>
    <div class="rep-sum-card"><div class="rep-sum-label">Period</div><div class="rep-sum-value" style="font-size:12px">${from}<br/>to ${to}</div></div>
  `;
  document.getElementById('reportOverlay').classList.add('open');
  document.querySelectorAll('.action-wrap').forEach(w=>w.classList.remove('open'));
}

function printReport(row){ window.open('print_report.php?id='+row.dataset.id,'_blank'); }
function openPrintReport(){ if(_currentReportId) window.open('print_report.php?id='+_currentReportId,'_blank'); }
function closeReport(){ document.getElementById('reportOverlay').classList.remove('open'); }
function closeReportOutside(e){ if(e.target===document.getElementById('reportOverlay')) closeReport(); }

let _deleteId = null;
function deleteReport(id){
  _deleteId = id;
  document.getElementById('deleteOverlay').classList.add('open');
  document.querySelectorAll('.action-wrap').forEach(w=>w.classList.remove('open'));
}
function closeDeleteModal(){ document.getElementById('deleteOverlay').classList.remove('open'); _deleteId=null; }
function closeDeleteOutside(e){ if(e.target===document.getElementById('deleteOverlay')) closeDeleteModal(); }

document.getElementById('confirmDeleteBtn').addEventListener('click', async function(){
  if(_deleteId===null) return;
  this.disabled=true; this.textContent='Deleting…';
  const res  = await fetch('report_action.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'delete',id:_deleteId})});
  const data = await res.json();
  if(data.success){ closeDeleteModal(); location.reload(); }
  else{ this.disabled=false; this.textContent='Delete'; alert(data.message||'Delete failed.'); }
});
applySearch();
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
      const res  = await fetch('notification_check.php');
      const data = await res.json();
      const alerts = (data.alerts || []).filter(a => (a.secs_left ?? 999) <= 300);
      const badge = document.getElementById('bellBadge');
      const popup = document.getElementById('bellPopup');
      const items = document.getElementById('bellPopupItems');
      if (!badge || !popup || !items) return;
      const dismissed = getDismissed();
      const undismissed = alerts.filter(a => !dismissed.has(canonId(String(a.id))));
      if (undismissed.length > 0) {
        badge.textContent = undismissed.length;
        badge.style.display = 'flex';
        items.innerHTML = undismissed.map(a =>
          `<div class="bp-item"><div class="bp-item-title">${a.title}</div><div class="bp-item-msg">${a.message}</div></div>`
        ).join('');
        currentAlertIds = undismissed.map(a => canonId(String(a.id)));
        if (undismissed.some(a => !dismissed.has(canonId(String(a.id))))) {
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

