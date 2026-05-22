<?php
session_start();
require_once __DIR__.'/auth.php';
error_reporting(0);
ini_set('display_errors', 0);
mysqli_report(MYSQLI_REPORT_OFF);

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'brew_n_break');

$sessions = [];
$username = $_SESSION['username'] ?? 'Admin';
$userRole = ucfirst(strtolower($_SESSION['role'] ?? 'admin'));
$currentTable = $_GET['table'] ?? 'all';

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if (!$conn->connect_error) {
        $conn->query("UPDATE billiard_sessions SET status='Expired' WHERE status IN ('Start','Ongoing') AND TIME(NOW())>end_time");

        if ($currentTable === 'all') {
            $r = $conn->query("SELECT bs.id, bs.session_code, bs.customer_name, bs.table_name, bs.start_time, bs.end_time, bs.amount, bs.status, bs.created_at FROM billiard_sessions bs ORDER BY bs.created_at DESC");
        } else {
            $t = $conn->real_escape_string($currentTable);
            $r = $conn->query("SELECT bs.id, bs.session_code, bs.customer_name, bs.table_name, bs.start_time, bs.end_time, bs.amount, bs.status, bs.created_at FROM billiard_sessions bs WHERE bs.table_name='$t' ORDER BY bs.created_at DESC");
        }
        if ($r) while ($row = $r->fetch_assoc()) $sessions[] = $row;
        $conn->close();
    }
} catch (Throwable $e) {}

if (empty($sessions)) { $sessions = []; }

$tables = ['Outdoor 1','Outdoor 2','Outdoor 3','Indoor 1'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<?php if($userRole==='Staff'):?><base href="/brew-n-break-pos/"><?php endif;?>
<title>Billiard Table Management – Brew n' Break</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Lato:wght@300;400;700&display=swap" rel="stylesheet"/>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --dark:#1e1a14;--darker:#17140f;
  --card-bg:#e8dcc8;--page-bg:#c8b89a;
  --cream:#f5eedc;--muted:#7a6e5f;
  --text-dark:#1e1a14;--text-mid:#4a3f30;--gold:#c8a96e;
  --row-even:#d8ccb4;--row-odd:#cfc3aa;
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
.tabs{display:flex;gap:0;background:rgba(0,0,0,0.08);border-radius:8px;padding:3px;}
.tab-btn{padding:7px 18px;border:none;background:transparent;border-radius:6px;font-size:13px;font-family:'Lato',sans-serif;color:var(--text-mid);cursor:pointer;font-weight:400;transition:background .2s,color .2s;text-decoration:none;display:inline-block;}
.tab-btn.active{background:var(--dark);color:var(--cream);font-weight:700;}
.toolbar-right{display:flex;align-items:center;gap:8px;}
.search-wrap{display:flex;align-items:center;gap:6px;background:rgba(255,255,255,0.5);border-radius:8px;padding:6px 12px;border:1px solid rgba(0,0,0,0.1);}
.search-wrap input{border:none;background:transparent;outline:none;font-size:13px;color:var(--text-dark);width:140px;font-family:'Lato',sans-serif;}
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
.status-ongoing{color:#2d6a4f;font-weight:700;}
.status-done{color:var(--muted);font-weight:700;}
.status-reserved{color:#856404;font-weight:700;}
.status-cancelled{color:#721c24;font-weight:700;}
.action-wrap{position:relative;display:inline-block;}
.action-btn{background:var(--dark);color:var(--cream);border:none;border-radius:8px;padding:5px 14px;cursor:pointer;font-size:14px;transition:background .2s;letter-spacing:2px;}
.action-btn:hover{background:#3a3020;}
.dropdown{display:none;position:fixed;background:var(--dark);border-radius:10px;min-width:160px;box-shadow:0 8px 24px rgba(0,0,0,0.4);z-index:9999;overflow:hidden;}
.dropdown a{display:block;padding:10px 16px;color:var(--cream);font-size:13px;text-decoration:none;transition:background .15s;}
.dropdown a:hover{background:rgba(255,255,255,0.1);}
.dropdown a.danger{color:#e07070;}
.action-wrap.open .dropdown{display:block;}

.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:300;align-items:center;justify-content:center;}
.modal-overlay.open{display:flex;}
.modal{background:var(--card-bg);border-radius:16px;padding:32px;width:min(460px,92vw);box-shadow:0 20px 60px rgba(0,0,0,0.4);animation:fadeUp .3s ease both;}
.modal h2{font-family:'Playfair Display',serif;font-size:22px;margin-bottom:20px;color:var(--text-dark);}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
.form-group{display:flex;flex-direction:column;gap:5px;margin-bottom:14px;}
.form-group label{font-size:11px;letter-spacing:1px;text-transform:uppercase;color:var(--muted);}
.form-group input,.form-group select{background:rgba(255,255,255,0.5);border:1px solid rgba(0,0,0,0.15);border-radius:8px;padding:10px 12px;font-size:14px;font-family:'Lato',sans-serif;color:var(--text-dark);outline:none;transition:border-color .2s;}
.form-group input:focus,.form-group select:focus{border-color:var(--gold);}
.modal-actions{display:flex;gap:10px;justify-content:flex-end;margin-top:16px;}
.btn{padding:10px 22px;border-radius:8px;border:none;font-size:14px;font-family:'Lato',sans-serif;font-weight:700;cursor:pointer;transition:background .2s;}
.btn-primary{background:var(--dark);color:var(--cream);}
.btn-primary:hover{background:#3a3020;}
.btn-secondary{background:rgba(0,0,0,0.1);color:var(--text-dark);}
.error-msg{color:#c0392b;font-size:12px;min-height:16px;margin-bottom:8px;}
#delBilliardOverlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.55);z-index:9999;align-items:center;justify-content:center;}
#delBilliardOverlay.open{display:flex;}
.del-modal{background:var(--card-bg);border-radius:18px;padding:32px 36px;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,0.5);max-width:320px;width:90%;animation:popIn .22s ease both;}
.del-modal-icon{font-size:42px;margin-bottom:14px;}
.del-modal-title{font-family:'Playfair Display',serif;font-size:20px;color:var(--text-dark);margin-bottom:8px;}
.del-modal-sub{font-size:13px;color:var(--muted);margin-bottom:24px;line-height:1.5;}
.del-modal-btns{display:flex;gap:10px;justify-content:center;}
.del-modal-cancel{flex:1;padding:10px 0;border-radius:10px;border:1.5px solid rgba(0,0,0,0.15);background:transparent;color:var(--text-mid);font-size:13px;font-weight:600;cursor:pointer;transition:background .2s;}
.del-modal-cancel:hover{background:rgba(0,0,0,0.06);}
.del-modal-confirm{flex:1;padding:10px 0;border-radius:10px;border:none;background:#7b1c1c;color:#fff;font-size:13px;font-weight:700;cursor:pointer;transition:background .2s;}
.del-modal-confirm:hover{background:#9b2c2c;}
@keyframes popIn{from{opacity:0;transform:scale(.9)}to{opacity:1;transform:scale(1)}}

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
.hamburger{display:none;background:none;border:none;color:var(--cream);font-size:22px;cursor:pointer;padding:6px 10px;border-radius:8px;line-height:1;align-items:center;justify-content:center;}
.hamburger:hover{background:rgba(255,255,255,0.12);}
.sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:150;}
.sidebar-overlay.active{display:block;}
.mobile-nav{display:none;position:fixed;bottom:0;left:0;right:0;height:58px;background:var(--darker);border-top:1px solid rgba(255,255,255,0.08);z-index:120;align-items:stretch;justify-content:space-around;}
.mn-item{display:flex;flex-direction:column;align-items:center;justify-content:center;flex:1;color:var(--muted);text-decoration:none;font-size:10px;gap:3px;padding:6px 4px;transition:color .2s;}
.mn-item span:first-child{font-size:20px;}
.mn-item.active,.mn-item:hover{color:var(--gold);}
</style>
<!-- React CDN -->
<script src="https://unpkg.com/react@18/umd/react.development.js"></script>
<script src="https://unpkg.com/react-dom@18/umd/react-dom.development.js"></script>
<script src="https://unpkg.com/@babel/standalone/babel.min.js"></script>
<style id="responsive-overrides">
@media (max-width:900px){
  .stat-grid{grid-template-columns:repeat(2,1fr)!important;}
  .mid-row{grid-template-columns:1fr!important;}
  .bottom-row{grid-template-columns:1fr!important;}
  .main{overflow-y:auto!important;padding:16px!important;}
  .layout{overflow-y:auto!important;}
  body{height:auto!important;overflow:auto!important;}
}
@media (max-width:768px){
  .hamburger{display:flex!important;}
  .sidebar{position:fixed!important;left:0!important;top:64px!important;height:calc(100vh - 64px)!important;z-index:200!important;transform:translateX(-100%)!important;width:220px!important;transition:transform .25s ease!important;border-right:1px solid rgba(255,255,255,0.08)!important;}
  .sidebar.open{transform:translateX(0)!important;}
  .topnav{padding:0 16px 0 12px!important;}
  .brand{font-size:16px!important;}
  .user-label{display:none!important;}
  .page-title{font-size:22px!important;}
  .card,.widget,.bottom-widget{padding:14px!important;border-radius:10px!important;}
  .tbl-wrap{overflow-x:auto!important;}
  table{min-width:560px!important;}
  .toolbar{flex-direction:column!important;align-items:flex-start!important;gap:8px!important;}
  .tabs{width:100%!important;overflow-x:auto!important;}
  .toolbar-right{width:100%!important;justify-content:space-between!important;}
  .search-wrap input{width:100px!important;}
  .modal-overlay > *{width:94vw!important;max-height:88vh!important;overflow-y:auto!important;}
}
@media (max-width:480px){
  .stat-grid{grid-template-columns:1fr!important;}
  .hamburger{display:flex!important;}
  .sidebar{display:none!important;}
  .sidebar-overlay{display:none!important;}
  .mobile-nav{display:flex!important;}
  body{padding-bottom:62px!important;}
  .main{padding:10px!important;}
  .topnav{height:54px!important;padding:0 10px!important;}
  .brand{font-size:15px!important;}
  .logo-circle{width:36px!important;height:36px!important;font-size:15px!important;}
  .page-title{font-size:19px!important;}
  .page-header{flex-wrap:wrap!important;gap:4px!important;}
  .page-time{font-size:11px!important;}
  .tab-btn{padding:6px 10px!important;font-size:12px!important;}
  .tx-stat,.sum-card{min-width:120px!important;}
  table{min-width:480px!important;}
}
</style>
</head>
<body>

<nav class="topnav">
  <div class="topnav-left">
    <div class="logo-circle" style="overflow:hidden;padding:0;"><img src="../img/logo.png" style="width:100%;height:100%;object-fit:cover;display:block;border-radius:50%;"/></div>
    <span class="brand">Brew n' Break</span>
  </div>
  <button class="hamburger" id="hamburgerBtn" onclick="toggleSidebar()" aria-label="Menu">☰</button>
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
  <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
  <aside class="sidebar">
    <?php $sp=$userRole==='Staff'?'staff.php':''; ?>
    <a class="nav-item" href="<?=$sp?:'dashboard.php'?>"><span>🏠</span><span class="tip">Dashboard</span></a>
    <?php if ($userRole !== 'Staff'): ?><a class="nav-item" href="users.php"><span>👤</span><span class="tip">Users</span></a><?php endif; ?>
    <a class="nav-item" href="menu.php"><span>☕</span><span class="tip">Cafe</span></a>
    <a class="nav-item active" href="billiard.php"><svg viewBox="0 0 24 24" width="22" height="22" xmlns="http://www.w3.org/2000/svg"><defs><radialGradient id="bg8" cx="40%" cy="35%" r="60%"><stop offset="0%" stop-color="#3a3a3a"/><stop offset="100%" stop-color="#0a0a0a"/></radialGradient><radialGradient id="wc8" cx="40%" cy="35%" r="60%"><stop offset="0%" stop-color="#ffffff"/><stop offset="100%" stop-color="#d8d8d8"/></radialGradient></defs><circle cx="12" cy="12" r="11" fill="url(#bg8)"/><circle cx="12" cy="12" r="5.2" fill="url(#wc8)"/><text x="12" y="15.5" font-size="6.5" text-anchor="middle" fill="#111" font-family="Arial,sans-serif" font-weight="900">8</text></svg><span class="tip">Billiard</span></a>
    <a class="nav-item" href="transactions.php"><span>📋</span><span class="tip">Transactions</span></a>
    <a class="nav-item" href="bookings.php"><span>📅</span><span class="tip">Bookings</span></a>
    <?php if ($userRole !== 'Staff'): ?><a class="nav-item" href="reports.php"><span>📁</span><span class="tip">Reports</span></a><?php endif; ?>
    <div class="nav-spacer"></div>
    <a class="nav-item" href="notifications.php" id="bellNavItem"><span>🔔</span><span id="bellBadge"></span><span class="tip">Notifications</span></a>
    <a class="nav-item" href="settings.php"><span>⚙️</span><span class="tip">Settings</span></a>
  </aside>

  <main class="main">
    <div class="page-header">
      <h1 class="page-title">Billiard Table Management</h1>
      <div class="page-time">🕐 <span id="liveClock"></span></div>
    </div>

    <div class="card" id="tableCard">
      <div class="toolbar">
        <!-- Table Tabs -->
        <div class="tabs">
          <a class="tab-btn <?= $currentTable === 'all' ? 'active' : '' ?>" href="billiard.php?table=all">All</a>
          <?php foreach ($tables as $t): ?>
          <a class="tab-btn <?= $t === $currentTable ? 'active' : '' ?>"
             href="billiard.php?table=<?= urlencode($t) ?>">
            <?= htmlspecialchars($t) ?>
          </a>
          <?php endforeach; ?>
        </div>
        <div class="toolbar-right">
          <div class="search-wrap">
            <span>🔍</span>
            <input type="text" id="searchInput" placeholder="Search" oninput="applySearch()"/>
          </div>
          <button class="icon-btn" title="Add Session" onclick="openModal()">➕</button>
        </div>
      </div>

      <div class="tbl-wrap">
        <div id="sessions-react-root"></div>
      </div>
      <script type="text/babel">
        const sessionData = <?= json_encode($sessions) ?>;

        function getStatusClass(status) {
          const s = status.toLowerCase();
          if (s === 'ongoing')  return 'status-ongoing';
          if (s === 'done')     return 'status-done';
          if (s === 'expired')  return 'status-cancelled';
          if (s === 'reserved') return 'status-reserved';
          if (s === 'cancelled') return 'status-cancelled';
          return 'status-ongoing';
        }

        function formatTime(t) {
          if (!t) return '';
          const [h, m] = t.split(':');
          const d = new Date(); d.setHours(+h, +m);
          return d.toLocaleTimeString('en-US', {hour:'numeric', minute:'2-digit'});
        }

        function formatDT(dt) {
          return new Date(dt).toLocaleString('en-US', {hour:'2-digit',minute:'2-digit',month:'long',day:'numeric',year:'numeric'});
        }

        const { useEffect } = React;

        function SessionTable({ sessions }) {
          const thStyle = {textAlign:'left',padding:'10px 16px',color:'var(--muted)',fontSize:'10px',letterSpacing:'.6px',textTransform:'uppercase',fontWeight:700,background:'var(--card-bg)'};
          const theadRowStyle = {position:'sticky',top:0,zIndex:5,background:'var(--card-bg)',borderBottom:'2px solid rgba(0,0,0,0.15)'};

          useEffect(() => {
            if (typeof window.applySearch === 'function') window.applySearch();
          }, []);

          return (
            <table style={{width:'100%',borderCollapse:'collapse',fontSize:'13px'}}>
              <thead>
                <tr style={theadRowStyle}>
                  <th style={thStyle}>Table ID</th>
                  <th style={thStyle}>Name</th>
                  <th style={thStyle}>Table Duration</th>
                  <th style={thStyle}>Amount</th>
                  <th style={thStyle}>Status</th>
                  <th style={thStyle}>Date and Time</th>
                  <th style={thStyle}>Action</th>
                </tr>
              </thead>
              <tbody id="tableBody">
                {sessions.map((s, i) => (
                  <tr key={s.id}
                    data-search={(s.session_code+' '+s.customer_name).toLowerCase()}
                    style={{background: i%2===0 ? 'var(--row-odd)' : 'var(--row-even)'}}>
                    <td style={{padding:'11px 16px'}}>{s.session_code}</td>
                    <td style={{padding:'11px 16px'}}>{s.customer_name}</td>
                    <td style={{padding:'11px 16px'}}>{formatTime(s.start_time)} – {formatTime(s.end_time)}</td>
                    <td style={{padding:'11px 16px'}}>₱{parseFloat(s.amount).toLocaleString('en-PH',{minimumFractionDigits:2})}</td>
                    <td style={{padding:'11px 16px'}}><span className={getStatusClass(s.status)}>{s.status}</span></td>
                    <td style={{padding:'11px 16px'}}>{formatDT(s.created_at)}</td>
                    <td style={{padding:'11px 16px'}}>
                      <div className="action-wrap" id={`wrap-${s.id}`}>
                        <button className="action-btn" onClick={() => window.toggleDD(s.id, document.querySelector(`#wrap-${s.id} .action-btn`))}>•••</button>
                        <div className="dropdown" id={`dd-${s.id}`}>
                          <a href="#" onClick={e => { e.preventDefault(); window.editSession(s.id, s.status); }}>✏️ Edit Status</a>
                          <a href="#" className="danger" onClick={e => { e.preventDefault(); window.deleteSession(s.id); }}>🗑️ Delete</a>
                        </div>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          );
        }

        ReactDOM.createRoot(document.getElementById('sessions-react-root')).render(<SessionTable sessions={sessionData} />);
      </script>

      <!-- Pagination -->
      <div style="display:flex;align-items:center;justify-content:space-between;margin-top:12px;flex-wrap:wrap;gap:10px;flex-shrink:0;">
        <div id="pageInfo" style="font-size:12px;color:var(--muted);"></div>
        <div id="pageControls" style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;"></div>
      </div>
    </div>
  </main>
</div>

<!-- ADD SESSION MODAL -->
<div class="modal-overlay" id="modalOverlay" onclick="closeModalOutside(event)">
  <div class="modal">
    <h2 id="modalTitle">Add Session</h2>
    <input type="hidden" id="editId"/>

    <div class="form-row">
      <div class="form-group">
        <label>Session ID <span style="font-size:10px;color:var(--gold);letter-spacing:.5px;">AUTO</span></label>
        <input type="text" id="fCode" readonly style="background:rgba(0,0,0,0.06);cursor:default;color:var(--muted);"/>
      </div>
      <div class="form-group">
        <label>Customer Name</label>
        <input type="text" id="fName" placeholder="e.g. Juan"/>
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label>Table</label>
        <select id="fTable" onchange="setRateByTable(this.value)">
          <?php foreach ($tables as $t): ?>
          <option value="<?= htmlspecialchars($t) ?>" <?= $t===$currentTable?'selected':'' ?>><?= htmlspecialchars($t) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Rate per Hour (₱)</label>
        <input type="number" id="fRate" placeholder="e.g. 180" min="0" step="0.01" value="180" oninput="calcAmount()"/>
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label>Start Time</label>
        <input type="time" id="fStart" oninput="calcAmount()"/>
      </div>
      <div class="form-group">
        <label>End Time</label>
        <input type="time" id="fEnd" oninput="calcAmount()"/>
      </div>
    </div>

    <!-- Auto-calculated amount -->
    <div style="background:rgba(0,0,0,0.06);border-radius:10px;padding:12px 16px;margin-bottom:14px;display:flex;justify-content:space-between;align-items:center;">
      <div>
        <div style="font-size:11px;text-transform:uppercase;letter-spacing:1px;color:var(--muted);">Duration</div>
        <div id="fDuration" style="font-size:14px;font-weight:700;color:var(--text-dark);">–</div>
      </div>
      <div style="text-align:right;">
        <div style="font-size:11px;text-transform:uppercase;letter-spacing:1px;color:var(--muted);">Total Amount</div>
        <div id="fAmountDisplay" style="font-family:'Playfair Display',serif;font-size:22px;color:var(--text-dark);">₱0.00</div>
        <input type="hidden" id="fAmount" value="0"/>
      </div>
    </div>

    <div class="form-group">
      <label>Status</label>
      <select id="fStatus">
        <option value="Start">Start</option>
        <option value="Reserved">Reserved</option>
        <option value="Done">Done</option>
      </select>
    </div>

    <div class="error-msg" id="modalError"></div>
    <div class="modal-actions">
      <button class="btn btn-secondary" onclick="closeModal()">Cancel</button>
      <button class="btn btn-primary" id="saveBilliardBtn" onclick="submitSession()">Save</button>
    </div>
  </div>
</div>

<!-- EDIT STATUS MODAL -->
<div class="modal-overlay" id="editModalOverlay" onclick="this.classList.remove('open')">
  <div class="modal" style="max-width:320px" onclick="event.stopPropagation()">
    <h2>Edit Status</h2>
    <input type="hidden" id="editStatusId"/>
    <div class="form-group">
      <label>Status</label>
      <select id="fEditStatus">
        <option value="Start">Start</option>
        <option value="Reserved">Reserved</option>
        <option value="Done">Done</option>
      </select>
    </div>
    <div class="error-msg" id="editModalError"></div>
    <div class="modal-actions">
      <button class="btn btn-secondary" onclick="document.getElementById('editModalOverlay').classList.remove('open')">Cancel</button>
      <button class="btn btn-primary" onclick="submitEditStatus()">Save</button>
    </div>
  </div>
</div>

<div id="delBilliardOverlay" onclick="if(event.target===this)this.classList.remove('open')">
  <div class="del-modal">
    <div class="del-modal-icon">🗑️</div>
    <div class="del-modal-title">Delete Session?</div>
    <div class="del-modal-sub">This action cannot be undone. The session will be permanently removed.</div>
    <div class="del-modal-btns">
      <button class="del-modal-cancel" onclick="document.getElementById('delBilliardOverlay').classList.remove('open')">Cancel</button>
      <button class="del-modal-confirm" id="confirmDelBilliardBtn">Delete</button>
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
function toggleSidebar(){
  const sb = document.querySelector('.sidebar');
  const ov = document.getElementById('sidebarOverlay');
  if(!sb||!ov) return;
  sb.classList.toggle('open');
  ov.classList.toggle('active');
}
const PER_PAGE = 13;
let currentPage = 1;
let _filteredRows = [];

function applySearch(){
  const q = document.getElementById('searchInput').value.toLowerCase();
  _filteredRows = [];
  document.querySelectorAll('#tableBody tr[data-search]').forEach(row => {
    const match = !q || row.dataset.search.includes(q);
    if(match) _filteredRows.push(row);
    row.style.display = 'none';
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
  if(total === 0){ info.textContent = 'No sessions found'; }
  else { info.textContent = 'Showing '+(start+1)+'–'+Math.min(end, total)+' of '+total+' sessions'; }
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
function setRateByTable(table){
  const rate = table === 'Indoor 1' ? 180 : 100;
  document.getElementById('fRate').value = rate;
  calcAmount();
}
function calcAmount(){
  const start = document.getElementById('fStart').value;
  const end   = document.getElementById('fEnd').value;
  const rate  = parseFloat(document.getElementById('fRate').value) || 0;

  if(!start || !end || !rate){ 
    document.getElementById('fDuration').textContent='–';
    document.getElementById('fAmountDisplay').textContent='₱0.00';
    document.getElementById('fAmount').value='0';
    return; 
  }

  const [sh,sm] = start.split(':').map(Number);
  const [eh,em] = end.split(':').map(Number);
  let mins = (eh*60+em) - (sh*60+sm);
  if(mins < 0) mins += 24 * 60; // Handle overnight sessions crossing midnight
  if(mins === 0){
    document.getElementById('fDuration').textContent='Invalid time range';
    document.getElementById('fAmountDisplay').textContent='₱0.00';
    document.getElementById('fAmount').value='0';
    return;
  }

  const hours  = mins / 60;
  const amount = (hours * rate).toFixed(2);
  const hh     = Math.floor(mins/60);
  const mm     = mins % 60;

  document.getElementById('fDuration').textContent    = `${hh}h ${mm}m`;
  document.getElementById('fAmountDisplay').textContent = '₱'+parseFloat(amount).toLocaleString('en-PH',{minimumFractionDigits:2});
  document.getElementById('fAmount').value             = amount;
}
async function openModal(){
  document.getElementById('modalTitle').textContent='Add Session';
  document.getElementById('editId').value='';
  document.getElementById('fCode').value='…';
  document.getElementById('fName').value='';
  const defaultTable = document.getElementById('fTable').value;
  document.getElementById('fRate').value = defaultTable === 'Indoor 1' ? 180 : 100;
  document.getElementById('fStart').value='';
  document.getElementById('fEnd').value='';
  document.getElementById('fAmount').value='0';
  document.getElementById('fDuration').textContent='–';
  document.getElementById('fAmountDisplay').textContent='₱0.00';
  document.getElementById('fStatus').value='Start';
  document.getElementById('modalError').textContent='';
  document.getElementById('modalOverlay').classList.add('open');
  try {
    const r = await fetch('http://localhost:5000/api/session-action',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'next_code'})});
    const d = await r.json();
    if(d.success) { document.getElementById('fCode').value=d.code; return; }
    throw new Error();
  } catch(e){
    try {
      const r2 = await fetch('billiard_action.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'next_code'})});
      const d2 = await r2.json();
      if(d2.success) document.getElementById('fCode').value=d2.code;
      else document.getElementById('fCode').value='BT001';
    } catch(e2){ document.getElementById('fCode').value='BT001'; }
  }
}
function closeModal(){
  document.getElementById('modalOverlay').classList.remove('open');
  const btn=document.getElementById('saveBilliardBtn');
  if(btn){btn.disabled=false;btn.textContent='Save';}
}
function closeModalOutside(e){ if(e.target===document.getElementById('modalOverlay')) closeModal(); }

function editSession(id, status){
  document.getElementById('editStatusId').value=id;
  document.getElementById('fEditStatus').value=status;
  document.getElementById('editModalError').textContent='';
  document.getElementById('editModalOverlay').classList.add('open');
  document.querySelectorAll('.action-wrap').forEach(w=>w.classList.remove('open'));
}

async function submitSession(){
  const code   = document.getElementById('fCode').value.trim();
  const name   = document.getElementById('fName').value.trim();
  const table  = document.getElementById('fTable').value;
  const amount = document.getElementById('fAmount').value;
  const start  = document.getElementById('fStart').value;
  const end    = document.getElementById('fEnd').value;
  const status = document.getElementById('fStatus').value;
  const errEl  = document.getElementById('modalError');
  const saveBtn= document.getElementById('saveBilliardBtn');

  if(!code||code==='…'||!name||!start||!end){ errEl.textContent='Please fill in all required fields.'; return; }

  saveBtn.disabled=true; saveBtn.textContent='Saving…';

  try {
    const res  = await fetch('http://localhost:5000/api/session-action',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'add',code,name,table,amount,start,end,status})});
    const data = await res.json();
    if(data.success){ closeModal(); location.reload(); return; }
    throw new Error(data.message||'');
  } catch(e){
    try {
      const res2 = await fetch('billiard_action.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'add',code,name,table,amount,start,end,status})});
      const data2 = await res2.json();
      if(data2.success){ closeModal(); location.reload(); return; }
      else errEl.textContent=data2.message||'Something went wrong.';
    } catch(e2){ errEl.textContent='Could not save session. Is the server running?'; }
  }
  saveBtn.disabled=false; saveBtn.textContent='Save';
}

async function submitEditStatus(){
  const id     = document.getElementById('editStatusId').value;
  const status = document.getElementById('fEditStatus').value;
  const payload= JSON.stringify({action:'edit_status',id,status});
  const hdrs   = {method:'POST',headers:{'Content-Type':'application/json'},body:payload};
  try {
    const res  = await fetch('http://localhost:5000/api/session-action',hdrs);
    const data = await res.json();
    if(data.success){ document.getElementById('editModalOverlay').classList.remove('open'); location.reload(); return; }
    throw new Error();
  } catch(e){
    try {
      const res2 = await fetch('billiard_action.php',hdrs);
      const data2= await res2.json();
      if(data2.success){ document.getElementById('editModalOverlay').classList.remove('open'); location.reload(); }
      else document.getElementById('editModalError').textContent=data2.message||'Failed.';
    } catch(e2){ document.getElementById('editModalError').textContent='Server error.'; }
  }
}

let _delBilliardId = null;
function deleteSession(id){
  _delBilliardId = id;
  document.getElementById('delBilliardOverlay').classList.add('open');
  document.querySelectorAll('.dropdown').forEach(d=>d.classList.remove('open'));
}
document.getElementById('confirmDelBilliardBtn').addEventListener('click', async function(){
  if(!_delBilliardId) return;
  document.getElementById('delBilliardOverlay').classList.remove('open');
  const payload = JSON.stringify({action:'delete',id:_delBilliardId});
  const hdrs = {method:'POST',headers:{'Content-Type':'application/json'},body:payload};
  try {
    const res  = await fetch('http://localhost:5000/api/session-action',hdrs);
    const data = await res.json();
    if(data.success){ location.reload(); return; }
    throw new Error();
  } catch(e){
    try {
      const res2  = await fetch('billiard_action.php',hdrs);
      const data2 = await res2.json();
      if(data2.success) location.reload();
      else alert(data2.message||'Delete failed.');
    } catch(e2){ alert('Server error. Could not delete.'); }
  }
});
</script>
<nav class="mobile-nav" id="mobileNav">
  <a href="/brew-n-break-pos/dashboard.php" class="mn-item"><span>🏠</span><span>Home</span></a>
  <a href="/brew-n-break-pos/menu.php" class="mn-item"><span>☕</span><span>Cafe</span></a>
  <a href="/brew-n-break-pos/billiard.php" class="mn-item active"><span>🎱</span><span>Billiard</span></a>
  <a href="/brew-n-break-pos/transactions.php" class="mn-item"><span>📋</span><span>Transactions</span></a>
  <a href="/brew-n-break-pos/bookings.php" class="mn-item"><span>📅</span><span>Bookings</span></a>
</nav>
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
      let data;
      try { const r = await fetch('http://localhost:5000/api/notifications'); data = await r.json(); }
      catch(e) { const r2 = await fetch('notification_check.php'); data = await r2.json(); }
      if(!data) return;
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

