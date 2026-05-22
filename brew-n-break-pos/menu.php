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

$orders   = [];
$username = $_SESSION['username'] ?? 'Admin';
$userRole = ucfirst(strtolower($_SESSION['role'] ?? 'admin'));

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if (!$conn->connect_error) {
        $r = $conn->query("SELECT o.id, o.order_code, GROUP_CONCAT(p.name SEPARATOR ', ') as products, o.total_amount, o.status, o.type, o.created_at FROM orders o LEFT JOIN order_items oi ON oi.order_id=o.id LEFT JOIN products p ON p.id=oi.product_id GROUP BY o.id ORDER BY o.created_at DESC");
        if ($r) while ($row = $r->fetch_assoc()) $orders[] = $row;
        $conn->close();
    }
} catch (Throwable $e) {}

if (empty($orders)) { $orders = []; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<?php if($userRole==='Staff'):?><base href="/brew-n-break-pos/"><?php endif;?>
<title>Cafe Management – Brew n' Break</title>
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
body{font-family:'Lato',sans-serif;background:var(--page-bg);display:flex;flex-direction:column;height:100vh;overflow:hidden;color:var(--text-dark);}
.topnav{background:var(--dark);display:flex;align-items:center;justify-content:space-between;padding:0 24px 0 16px;height:64px;position:sticky;top:0;z-index:100;box-shadow:0 2px 12px rgba(0,0,0,0.4);}
.topnav-left{display:flex;align-items:center;gap:14px;}
.logo-circle{width:44px;height:44px;border-radius:50%;border:2px solid var(--gold);background:var(--darker);display:flex;align-items:center;justify-content:center;font-size:18px;}
.brand{font-family:'Playfair Display',serif;font-size:20px;color:var(--cream);}
.topnav-right{display:flex;align-items:center;gap:12px;}
.user-label{font-size:14px;color:var(--cream);font-weight:300;}
.user-avatar{width:36px;height:36px;border-radius:50%;background:rgba(255,255,255,0.12);border:1.5px solid var(--gold);display:flex;align-items:center;justify-content:center;color:var(--cream);font-size:18px;}
.layout{display:flex;flex:1;overflow:hidden;}
.sidebar{width:68px;background:var(--darker);display:flex;flex-direction:column;align-items:center;padding:12px 0;gap:4px;flex-shrink:0;border-right:1px solid rgba(255,255,255,0.05);z-index:10;}
.nav-item{width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;color:var(--muted);font-size:20px;cursor:pointer;text-decoration:none;transition:background .2s,color .2s;position:relative;}
.nav-item:hover{background:rgba(255,255,255,0.08);color:var(--cream);}
.nav-item.active{background:var(--gold);color:var(--dark);}
.nav-item .tip{position:absolute;left:58px;background:var(--dark);color:var(--cream);font-size:11px;padding:4px 8px;border-radius:6px;white-space:nowrap;pointer-events:none;opacity:0;transition:opacity .2s;border:1px solid rgba(255,255,255,0.1);z-index:200;}
.nav-item:hover .tip{opacity:1;}
.nav-spacer{flex:1;}
.main{flex:1;padding:28px;display:flex;flex-direction:column;gap:20px;overflow:hidden;animation:fadeUp .5s ease both;}
@keyframes fadeUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
.page-header{display:flex;align-items:center;justify-content:space-between;}
.page-title{font-family:'Playfair Display',serif;font-size:30px;color:var(--text-dark);}
.page-time{font-size:13px;color:var(--text-mid);display:flex;align-items:center;gap:6px;}
.card{background:var(--card-bg);border-radius:14px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,0.1);}
.toolbar{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;gap:12px;flex-wrap:wrap;}
.toolbar-left{display:flex;align-items:center;gap:10px;}
.tabs{display:flex;gap:0;background:rgba(0,0,0,0.08);border-radius:8px;padding:3px;}
.tab-btn{padding:7px 18px;border:none;background:transparent;border-radius:6px;font-size:13px;font-family:'Lato',sans-serif;color:var(--text-mid);cursor:pointer;font-weight:400;transition:background .2s,color .2s;}
.tab-btn.active{background:var(--dark);color:var(--cream);font-weight:700;}
.manage-btn{padding:7px 14px;border-radius:8px;background:var(--gold);color:var(--dark);border:none;font-size:12px;font-family:'Lato',sans-serif;font-weight:700;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:5px;transition:background .2s;}
.manage-btn:hover{background:#b8994e;}
.toolbar-right{display:flex;align-items:center;gap:8px;}
.search-wrap{display:flex;align-items:center;gap:6px;background:rgba(255,255,255,0.5);border-radius:8px;padding:6px 12px;border:1px solid rgba(0,0,0,0.1);}
.search-wrap input{border:none;background:transparent;outline:none;font-size:13px;color:var(--text-dark);width:140px;font-family:'Lato',sans-serif;}
.search-wrap input::placeholder{color:var(--muted);}
.icon-btn{width:34px;height:34px;border-radius:8px;border:1px solid rgba(0,0,0,0.15);background:rgba(255,255,255,0.4);display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:16px;transition:background .2s;}
.icon-btn:hover{background:rgba(255,255,255,0.7);}
.tbl-wrap{overflow:visible;}
#tableBody tr{display:none;}
table{width:100%;border-collapse:collapse;font-size:13px;}
thead tr{border-bottom:2px solid rgba(0,0,0,0.15);}
thead th{text-align:left;padding:10px 16px;color:var(--text-mid);font-size:12px;letter-spacing:.6px;text-transform:uppercase;font-weight:700;}
tbody tr:nth-child(odd){background:var(--row-odd);}
tbody tr:nth-child(even){background:var(--row-even);}
tbody tr{transition:background .15s;}
tbody tr:hover{background:rgba(200,169,110,0.3);}
tbody td{padding:11px 16px;color:var(--text-dark);}
.status-done{color:var(--muted);font-weight:700;}
.status-pending{color:#856404;font-weight:700;}
.status-cancelled{color:#721c24;font-weight:700;}
.action-wrap{position:relative;display:inline-block;}
.action-btn{background:var(--dark);color:var(--cream);border:none;border-radius:8px;padding:5px 14px;cursor:pointer;font-size:14px;transition:background .2s;letter-spacing:2px;}
.action-btn:hover{background:#3a3020;}
.dropdown{display:none;position:fixed;background:var(--dark);border-radius:10px;min-width:160px;box-shadow:0 8px 24px rgba(0,0,0,0.4);z-index:9999;overflow:hidden;}
.dropdown a{display:block;padding:11px 16px;color:var(--cream);font-size:13px;text-decoration:none;transition:background .15s;}
.dropdown a:hover{background:rgba(255,255,255,0.1);}
.dropdown a.danger{color:#e07070;}
.action-wrap.open .dropdown{display:block;}

/* MODAL */
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:300;align-items:center;justify-content:center;}
.modal-overlay.open{display:flex;}
.modal{background:var(--card-bg);border-radius:16px;padding:32px;width:min(500px,92vw);box-shadow:0 20px 60px rgba(0,0,0,0.4);animation:fadeUp .3s ease both;max-height:90vh;overflow-y:auto;}
.modal h2{font-family:'Playfair Display',serif;font-size:22px;margin-bottom:20px;color:var(--text-dark);}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
.form-group{display:flex;flex-direction:column;gap:5px;margin-bottom:14px;}
.form-group label{font-size:11px;letter-spacing:1px;text-transform:uppercase;color:var(--muted);}
.form-group input,.form-group select{background:rgba(255,255,255,0.55);border:1px solid rgba(0,0,0,0.15);border-radius:8px;padding:10px 12px;font-size:14px;font-family:'Lato',sans-serif;color:var(--text-dark);outline:none;transition:border-color .2s;width:100%;}
.form-group input:focus,.form-group select:focus{border-color:var(--gold);}

/* PRODUCT ITEM ROW with autocomplete */
.items-label{font-size:11px;letter-spacing:1px;text-transform:uppercase;color:var(--muted);margin-bottom:8px;font-weight:700;}
.item-row{display:grid;grid-template-columns:1fr 70px 90px 28px;gap:6px;margin-bottom:8px;align-items:start;}
.item-row .prod-field-wrap{position:relative;}
.item-row .prod-input{width:100%;background:rgba(255,255,255,0.55);border:1px solid rgba(0,0,0,0.15);border-radius:8px;padding:9px 10px;font-size:13px;font-family:'Lato',sans-serif;color:var(--text-dark);outline:none;transition:border-color .2s;}
.item-row .prod-input:focus{border-color:var(--gold);}
.item-row input[type=number]{background:rgba(255,255,255,0.55);border:1px solid rgba(0,0,0,0.15);border-radius:8px;padding:9px 8px;font-size:13px;font-family:'Lato',sans-serif;color:var(--text-dark);outline:none;width:100%;}
.remove-item{background:none;border:none;color:#c0392b;font-size:18px;cursor:pointer;padding:0;line-height:1;margin-top:8px;}

/* AUTOCOMPLETE DROPDOWN */
.autocomplete-list{position:absolute;top:100%;left:0;right:0;background:#fff;border:1px solid rgba(0,0,0,0.15);border-radius:0 0 8px 8px;box-shadow:0 8px 20px rgba(0,0,0,0.12);z-index:100;max-height:180px;overflow-y:auto;display:none;}
.autocomplete-list.open{display:block;}
.ac-item{padding:9px 12px;cursor:pointer;font-size:13px;display:flex;justify-content:space-between;align-items:center;transition:background .15s;}
.ac-item:hover,.ac-item.selected{background:#f5eedc;}
.ac-item .ac-price{color:var(--muted);font-size:12px;}
.ac-item .ac-cat{font-size:10px;color:var(--gold);text-transform:uppercase;letter-spacing:.5px;}
.ac-no-result{padding:9px 12px;font-size:13px;color:var(--muted);font-style:italic;}

.add-item-btn{background:none;border:1px dashed var(--muted);border-radius:8px;padding:7px 14px;font-size:12px;color:var(--muted);cursor:pointer;margin-bottom:14px;transition:border-color .2s,color .2s;width:100%;}
.add-item-btn:hover{border-color:var(--gold);color:var(--text-dark);}
.modal-actions{display:flex;gap:10px;justify-content:flex-end;margin-top:8px;}
.btn{padding:10px 22px;border-radius:8px;border:none;font-size:14px;font-family:'Lato',sans-serif;font-weight:700;cursor:pointer;transition:background .2s;}
.btn-primary{background:var(--dark);color:var(--cream);}
.btn-primary:hover{background:#3a3020;}
.btn-secondary{background:rgba(0,0,0,0.1);color:var(--text-dark);}
.error-msg{color:#c0392b;font-size:12px;min-height:14px;margin-bottom:8px;}
.total-preview{background:rgba(0,0,0,0.06);border-radius:8px;padding:10px 14px;display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;font-weight:700;}
/* DELETE ORDER MODAL */
.del-order-modal{background:var(--card-bg);border-radius:16px;padding:28px 32px;width:min(360px,92vw);box-shadow:0 20px 60px rgba(0,0,0,0.4);animation:fadeUp .3s ease both;}
.del-order-icon{font-size:40px;text-align:center;margin-bottom:12px;}
.del-order-modal h2{font-family:'Playfair Display',serif;font-size:20px;color:var(--text-dark);text-align:center;margin-bottom:8px;}
.del-order-modal p{font-size:13px;color:var(--muted);text-align:center;margin-bottom:22px;line-height:1.5;}
.del-order-actions{display:flex;gap:10px;justify-content:center;}
.btn-del-cancel{padding:9px 20px;border-radius:8px;border:none;font-size:13px;font-family:'Lato',sans-serif;font-weight:700;cursor:pointer;background:rgba(0,0,0,0.1);color:var(--text-dark);transition:background .2s;}
.btn-del-cancel:hover{background:rgba(0,0,0,0.18);}
.btn-del-confirm{padding:9px 20px;border-radius:8px;border:none;font-size:13px;font-family:'Lato',sans-serif;font-weight:700;cursor:pointer;background:#7a2020;color:#fff;transition:background .2s;}
.btn-del-confirm:hover{background:#5c1818;}
.btn-del-confirm:disabled{opacity:.6;cursor:not-allowed;}

/* PAGINATION */
.pg-btn{padding:5px 11px;border-radius:7px;border:1px solid rgba(0,0,0,0.15);background:rgba(255,255,255,0.4);cursor:pointer;font-size:13px;font-family:'Lato',sans-serif;font-weight:700;color:var(--text-dark);transition:background .2s;min-width:34px;}
.pg-btn:hover:not(:disabled){background:rgba(255,255,255,0.7);}
.pg-btn:disabled{opacity:.4;cursor:not-allowed;}
.pg-btn.pg-active{background:var(--dark);color:var(--cream);border-color:transparent;}
.pg-ellipsis{padding:0 4px;color:var(--muted);font-size:13px;line-height:1;display:inline-flex;align-items:center;}

/* Bell badge + popup */
#bellBadge{position:absolute;top:5px;right:5px;background:#e07070;color:#fff;font-size:9px;font-weight:700;border-radius:50%;width:16px;height:16px;display:none;align-items:center;justify-content:center;pointer-events:none;z-index:20;}
#bellPopup{display:none;position:fixed;left:76px;bottom:68px;z-index:99999;min-width:256px;max-width:310px;background:#1e1a14;color:#f5eedc;border-radius:14px;box-shadow:0 12px 40px rgba(0,0,0,0.6);border:1px solid rgba(240,192,64,0.35);overflow:hidden;animation:fadeUp .25s ease both;}
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
    <?php $sp=$userRole==='Staff'?'staff.php':''; ?>
    <a class="nav-item" href="<?=$sp?:'dashboard.php'?>"><span>🏠</span><span class="tip">Dashboard</span></a>
    <?php if ($userRole !== 'Staff'): ?><a class="nav-item" href="users.php"><span>👤</span><span class="tip">Users</span></a><?php endif; ?>
    <a class="nav-item active" href="menu.php"><span>☕</span><span class="tip">Cafe</span></a>
    <a class="nav-item" href="billiard.php"><svg viewBox="0 0 24 24" width="22" height="22" xmlns="http://www.w3.org/2000/svg"><defs><radialGradient id="bg8" cx="40%" cy="35%" r="60%"><stop offset="0%" stop-color="#3a3a3a"/><stop offset="100%" stop-color="#0a0a0a"/></radialGradient><radialGradient id="wc8" cx="40%" cy="35%" r="60%"><stop offset="0%" stop-color="#ffffff"/><stop offset="100%" stop-color="#d8d8d8"/></radialGradient></defs><circle cx="12" cy="12" r="11" fill="url(#bg8)"/><circle cx="12" cy="12" r="5.2" fill="url(#wc8)"/><text x="12" y="15.5" font-size="6.5" text-anchor="middle" fill="#111" font-family="Arial,sans-serif" font-weight="900">8</text></svg><span class="tip">Billiard</span></a>
    <a class="nav-item" href="transactions.php"><span>📋</span><span class="tip">Transactions</span></a>
    <a class="nav-item" href="bookings.php"><span>📅</span><span class="tip">Bookings</span></a>
    <?php if ($userRole !== 'Staff'): ?><a class="nav-item" href="reports.php"><span>📁</span><span class="tip">Reports</span></a><?php endif; ?>
    <div class="nav-spacer"></div>
    <a class="nav-item" href="notifications.php" id="bellNavItem"><span>🔔</span><span id="bellBadge"></span><span class="tip">Notifications</span></a>
    <a class="nav-item" href="settings.php"><span>⚙️</span><span class="tip">Settings</span></a>
  </aside>

  <main class="main">
    <div class="page-header">
      <h1 class="page-title">Cafe Management</h1>
      <div class="page-time">🕐 <span id="liveClock"></span></div>
    </div>

    <div class="card">
      <div class="toolbar">
        <div class="toolbar-left">
          <a class="manage-btn" href="<?= $userRole === 'Staff' ? '/brew-n-break-pos/products.php' : 'products.php' ?>">🛒 Manage Products</a>
        </div>
        <div class="toolbar-right">
          <div class="search-wrap">
            <span>🔍</span>
            <input type="text" id="searchInput" placeholder="Search" oninput="applyFilters()"/>
          </div>
          <button class="icon-btn" title="Add Order" onclick="openModal()">➕</button>
        </div>
      </div>

      <div class="tbl-wrap">
        <table>
          <thead>
            <tr>
              <th>Order ID</th><th>Product</th><th>Amount</th><th>Status</th><th>Date and Time</th><th>Action</th>
            </tr>
          </thead>
          <tbody id="tableBody">
            <?php foreach ($orders as $o):
              $statusClass = match(strtolower($o['status'])) {
                'done'      => 'status-done',
                'pending'   => 'status-pending',
                'cancelled' => 'status-cancelled',
                default     => 'status-pending'
              };
              $type = strtolower($o['type'] ?? 'all');
              $dt   = date('h:i A F j, Y', strtotime($o['created_at']));
            ?>
            <tr data-type="<?= htmlspecialchars($type) ?>"
                data-search="<?= strtolower(htmlspecialchars($o['order_code'].' '.($o['products']??''))) ?>">
              <td><?= htmlspecialchars($o['order_code']) ?></td>
              <td><?= htmlspecialchars($o['products'] ?? '–') ?></td>
              <td>₱<?= number_format($o['total_amount'], 2) ?></td>
              <td><span class="<?= $statusClass ?>"><?= htmlspecialchars($o['status']) ?></span></td>
              <td><?= $dt ?></td>
              <td>
                <div class="action-wrap" id="wrap-<?= $o['id'] ?>">
                  <button class="action-btn" onclick="toggleDD(<?= $o['id'] ?>, this)">•••</button>
                  <div class="dropdown" id="dd-<?= $o['id'] ?>">
                    <a href="#" onclick="editOrder(<?= $o['id'] ?>,'<?= htmlspecialchars($o['status']) ?>');return false;">✏️ Edit Status</a>
                    <a href="#" class="danger" onclick="deleteOrder(<?= $o['id'] ?>);return false;">🗑️ Delete</a>
                  </div>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div style="display:flex;align-items:center;justify-content:space-between;margin-top:16px;flex-wrap:wrap;gap:10px;">
        <div id="pageInfo" style="font-size:12px;color:var(--muted);"></div>
        <div id="pageControls" style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;"></div>
      </div>
    </div>
  </main>
</div>

<!-- ADD ORDER MODAL -->
<div class="modal-overlay" id="modalOverlay" onclick="closeModalOutside(event)">
  <div class="modal" onclick="event.stopPropagation()">
    <h2>Add Order</h2>

    <div class="form-row">
      <div class="form-group">
        <label>Order ID <span style="font-size:10px;color:var(--gold);letter-spacing:.5px;">AUTO</span></label>
        <input type="text" id="fOrderCode" readonly style="background:rgba(0,0,0,0.06);cursor:default;color:var(--muted);"/>
      </div>
      <div class="form-group">
        <label>Type</label>
        <select id="fType">
          <option value="" disabled>Select type…</option>
          <option value="Coffee">Drinks</option>
          <option value="Foods">Foods</option>
        </select>
      </div>
    </div>

    <div class="items-label">Products</div>
    <div id="itemsContainer"></div>
    <button class="add-item-btn" onclick="addItemRow()">+ Add Product</button>

    <div class="total-preview">
      <span>Total</span>
      <span id="totalPreview">₱0.00</span>
    </div>

    <div class="form-row">
      <div class="form-group" style="grid-column:1/-1">
        <label>Status</label>
        <select id="fStatus">
          <option value="Pending">Pending</option>
          <option value="Done">Done</option>
          <option value="Cancelled">Cancelled</option>
        </select>
      </div>
    </div>

    <div class="error-msg" id="modalError"></div>
    <div class="modal-actions">
      <button class="btn btn-secondary" onclick="closeModal()">Cancel</button>
      <button class="btn btn-primary" onclick="submitOrder()">Save Order</button>
    </div>
  </div>
</div>

<!-- DELETE ORDER MODAL -->
<div class="modal-overlay" id="deleteOrderOverlay" onclick="closeDeleteOrder(event)">
  <div class="del-order-modal" onclick="event.stopPropagation()">
    <div class="del-order-icon">🗑️</div>
    <h2>Delete Order</h2>
    <p>Are you sure you want to delete this order?<br/>This action cannot be undone.</p>
    <div class="del-order-actions">
      <button class="btn-del-cancel" onclick="cancelDeleteOrder()">Cancel</button>
      <button class="btn-del-confirm" id="confirmOrderDeleteBtn">Delete</button>
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
        <option value="Pending">Pending</option>
        <option value="Done">Done</option>
        <option value="Cancelled">Cancelled</option>
      </select>
    </div>
    <div class="error-msg" id="editModalError"></div>
    <div class="modal-actions">
      <button class="btn btn-secondary" onclick="document.getElementById('editModalOverlay').classList.remove('open')">Cancel</button>
      <button class="btn btn-primary" onclick="submitEditStatus()">Save</button>
    </div>
  </div>
</div>

<script>
function toggleAvatarMenu(e){e.stopPropagation();var m=document.getElementById('avatarMenu');m.style.display=m.style.display==='none'?'block':'none';}
document.addEventListener('click',function(){var m=document.getElementById('avatarMenu');if(m)m.style.display='none';});
// Clock
function updateClock(){
  const now=new Date();
  document.getElementById('liveClock').textContent=
    now.toLocaleTimeString('en-PH',{hour:'2-digit',minute:'2-digit',second:'2-digit',hour12:true})+' '+
    now.toLocaleDateString('en-PH',{month:'long',day:'numeric',year:'numeric'});
}
updateClock(); setInterval(updateClock,1000);

// All products from DB (loaded when modal opens)
let allProducts = [];
async function loadProducts(){
  if(allProducts.length) return;
  try {
    const res  = await fetch('product_action.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'list'})});
    const data = await res.json();
    if(data.success) allProducts = data.products;
  } catch(e){}
  // Fallback sample if DB empty
  if(!allProducts.length) allProducts=[
    {id:1,name:'Matcha Latte',price:150,category:'Coffee'},
    {id:2,name:'Oat Honey Latte',price:160,category:'Coffee'},
    {id:3,name:'Iced Biscoff Latte',price:170,category:'Coffee'},
    {id:4,name:'Fries',price:80,category:'Foods'},
    {id:5,name:'Nachos',price:120,category:'Foods'},
  ];
}

const PER_PAGE = 13;
let currentPage = 1;
let _filteredRows = [];

function applyFilters(){
  const q = document.getElementById('searchInput').value.toLowerCase();
  _filteredRows = [];
  document.querySelectorAll('#tableBody tr').forEach(row => {
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
  if(total === 0){ info.textContent = 'No orders found'; }
  else { info.textContent = 'Showing '+(start+1)+'–'+Math.min(end, total)+' of '+total+' orders'; }
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

// Dropdown — fixed positioning to avoid table clipping
function toggleDD(id, btn){
  const wrap = document.getElementById('wrap-'+id);
  const dd   = wrap.querySelector('.dropdown');
  const isOpen = wrap.classList.contains('open');

  // Close all first
  document.querySelectorAll('.action-wrap').forEach(w=>w.classList.remove('open'));
  document.querySelectorAll('.dropdown').forEach(d=>{ d.style.top=''; d.style.left=''; });

  if(!isOpen){
    const rect = btn.getBoundingClientRect();
    dd.style.top  = (rect.bottom + window.scrollY + 4) + 'px';
    dd.style.left = (rect.right  - 160) + 'px';
    wrap.classList.add('open');
  }
}
document.addEventListener('click',e=>{
  if(!e.target.closest('.action-wrap')){
    document.querySelectorAll('.action-wrap').forEach(w=>w.classList.remove('open'));
  }
});

// Add item row with autocomplete
let rowCount=0;
function addItemRow(defaultName='', defaultPrice='', defaultQty=1){
  const id='row-'+(rowCount++);
  const wrap=document.createElement('div');
  wrap.className='item-row';
  wrap.id=id;
  wrap.innerHTML=`
    <div class="prod-field-wrap">
      <input type="text" class="prod-input" placeholder="Search product…" autocomplete="off"
        oninput="handleSearch(this,'${id}')" onfocus="handleSearch(this,'${id}')"
        onblur="setTimeout(()=>document.getElementById('ac-${id}').classList.remove('open'),150)"
        value="${defaultName}"/>
      <div class="autocomplete-list" id="ac-${id}"></div>
    </div>
    <input type="number" class="qty-input" placeholder="Qty" min="1" value="${defaultQty}" oninput="calcTotal()"/>
    <input type="number" class="price-input" placeholder="Price" min="0" step="0.01" value="${defaultPrice}" oninput="calcTotal()"/>
    <button class="remove-item" onclick="removeItem('${id}')">×</button>
  `;
  document.getElementById('itemsContainer').appendChild(wrap);
  calcTotal();
}

function handleSearch(input, rowId){
  const q=input.value.toLowerCase().trim();
  const list=document.getElementById('ac-'+rowId);
  // Close all other lists
  document.querySelectorAll('.autocomplete-list').forEach(l=>{ if(l.id!=='ac-'+rowId) l.classList.remove('open'); });

  const filtered = q ? allProducts.filter(p=>p.name.toLowerCase().includes(q)) : allProducts;
  if(!filtered.length){
    list.innerHTML='<div class="ac-no-result">No products found</div>';
  } else {
    list.innerHTML = filtered.map(p=>`
      <div class="ac-item" onmousedown="selectProduct(event,'${rowId}',${p.id},'${p.name.replace(/'/g,"\\'")}',${p.price})">
        <div>
          <span>${p.name}</span><br/>
          <span class="ac-cat">${p.category==='Coffee'?'Drinks':p.category}</span>
        </div>
        <span class="ac-price">₱${parseFloat(p.price).toLocaleString('en-PH',{minimumFractionDigits:2})}</span>
      </div>
    `).join('');
  }
  list.classList.add('open');
}

function selectProduct(e, rowId, id, name, price){
  e.preventDefault();
  const row=document.getElementById(rowId);
  row.querySelector('.prod-input').value=name;
  row.querySelector('.price-input').value=parseFloat(price).toFixed(2);
  row.dataset.productId=id;
  document.getElementById('ac-'+rowId).classList.remove('open');
  calcTotal();
}

document.addEventListener('click', e=>{
  if(!e.target.closest('.prod-field-wrap')) document.querySelectorAll('.autocomplete-list').forEach(l=>l.classList.remove('open'));
});

function removeItem(id){ document.getElementById(id)?.remove(); calcTotal(); }

function calcTotal(){
  let total=0;
  document.querySelectorAll('.item-row').forEach(row=>{
    const qty  = parseFloat(row.querySelector('.qty-input')?.value)||0;
    const price= parseFloat(row.querySelector('.price-input')?.value)||0;
    total+=qty*price;
  });
  document.getElementById('totalPreview').textContent='₱'+total.toLocaleString('en-PH',{minimumFractionDigits:2});
  document.getElementById('fTotal') && (document.getElementById('fTotal').value=total.toFixed(2));
}

// Modal
async function openModal(){
  await loadProducts();
  document.getElementById('fOrderCode').value='…';
  document.getElementById('fType').value='';
  document.getElementById('fStatus').value='Done';
  document.getElementById('modalError').textContent='';
  document.getElementById('itemsContainer').innerHTML='';
  rowCount=0;
  addItemRow();
  document.getElementById('modalOverlay').classList.add('open');
  // Fetch next auto code
  try {
    const r = await fetch('cafe_action.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'next_code'})});
    const d = await r.json();
    if(d.success) document.getElementById('fOrderCode').value=d.code;
  } catch(e){}
}
function closeModal(){ document.getElementById('modalOverlay').classList.remove('open'); }
function closeModalOutside(e){ if(e.target===document.getElementById('modalOverlay')) closeModal(); }

function editOrder(id, status){
  document.getElementById('editStatusId').value=id;
  document.getElementById('fEditStatus').value=status;
  document.getElementById('editModalError').textContent='';
  document.getElementById('editModalOverlay').classList.add('open');
  document.querySelectorAll('.action-wrap').forEach(w=>w.classList.remove('open'));
}

async function submitOrder(){
  const orderCode=document.getElementById('fOrderCode').value.trim();
  const type     =document.getElementById('fType').value;
  const status   =document.getElementById('fStatus').value;
  const errEl    =document.getElementById('modalError');

  if(!orderCode||orderCode==='…'){ errEl.textContent='Auto-code loading, please wait.'; return; }
  if(!type){ errEl.textContent='Please select a type.'; return; }

  // Collect items
  const items=[];
  let total=0;
  document.querySelectorAll('.item-row').forEach(row=>{
    const name =row.querySelector('.prod-input').value.trim();
    const qty  =parseFloat(row.querySelector('.qty-input').value)||0;
    const price=parseFloat(row.querySelector('.price-input').value)||0;
    if(name&&qty>0){ items.push({name,qty,price}); total+=qty*price; }
  });
  if(!items.length){ errEl.textContent='Add at least one product.'; return; }

  const res  = await fetch('cafe_action.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'add',orderCode,type,status,total,items})});
  const data = await res.json();
  if(data.success){ closeModal(); location.reload(); }
  else errEl.textContent=data.message||'Something went wrong.';
}

async function submitEditStatus(){
  const id    =document.getElementById('editStatusId').value;
  const status=document.getElementById('fEditStatus').value;
  const res   = await fetch('cafe_action.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'edit_status',id,status})});
  const data  = await res.json();
  if(data.success){ document.getElementById('editModalOverlay').classList.remove('open'); location.reload(); }
  else document.getElementById('editModalError').textContent=data.message||'Failed.';
}

let _deleteOrderId = null;
function deleteOrder(id){
  _deleteOrderId = id;
  document.getElementById('deleteOrderOverlay').classList.add('open');
  document.querySelectorAll('.action-wrap').forEach(w=>w.classList.remove('open'));
}
function cancelDeleteOrder(){ document.getElementById('deleteOrderOverlay').classList.remove('open'); _deleteOrderId=null; }
function closeDeleteOrder(e){ if(e.target===document.getElementById('deleteOrderOverlay')) cancelDeleteOrder(); }

document.getElementById('confirmOrderDeleteBtn').addEventListener('click', async function(){
  if(_deleteOrderId===null) return;
  this.disabled=true; this.textContent='Deleting…';
  const res  = await fetch('cafe_action.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'delete',id:_deleteOrderId})});
  const data = await res.json();
  if(data.success){ cancelDeleteOrder(); location.reload(); }
  else{ this.disabled=false; this.textContent='Delete'; alert(data.message||'Delete failed.'); }
});
applyFilters();
</script>
<div id="bellPopup">
  <div class="bp-header">
    <span class="bp-title">⚠️ Session Expiring Soon</span>
    <button class="bp-close" onclick="closeBellPopup()">×</button>
  </div>
  <div id="bellPopupItems"></div>
</div>
<script>
// Bell 5-min popup
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


