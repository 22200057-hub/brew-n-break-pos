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

$products = [];
$username = $_SESSION['username'] ?? 'Admin';
$userRole = ucfirst(strtolower($_SESSION['role'] ?? 'admin'));

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if (!$conn->connect_error) {
        $conn->query("ALTER TABLE products ADD COLUMN IF NOT EXISTS category VARCHAR(60) DEFAULT 'Coffee'");
        $conn->query("ALTER TABLE products ADD COLUMN IF NOT EXISTS description VARCHAR(255) DEFAULT ''");
        $conn->query("ALTER TABLE products ADD COLUMN IF NOT EXISTS available TINYINT(1) DEFAULT 1");
        $r = $conn->query("SELECT * FROM products ORDER BY category, name");
        if ($r) while ($row = $r->fetch_assoc()) $products[] = $row;
        $conn->close();
    }
} catch (Throwable $e) {}

if (empty($products)) {
    $products = [
        ['id'=>1,'name'=>'Matcha Latte','price'=>150,'category'=>'Coffee','description'=>'','available'=>1],
        ['id'=>2,'name'=>'Oat Honey Latte','price'=>160,'category'=>'Coffee','description'=>'','available'=>1],
        ['id'=>3,'name'=>'Iced Biscoff Latte','price'=>170,'category'=>'Coffee','description'=>'','available'=>1],
        ['id'=>4,'name'=>'Fries','price'=>80,'category'=>'Foods','description'=>'','available'=>1],
        ['id'=>5,'name'=>'Nachos','price'=>120,'category'=>'Foods','description'=>'','available'=>1],
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Products – Brew n' Break</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Lato:wght@300;400;700&display=swap" rel="stylesheet"/>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --dark:#1e1a14;--darker:#17140f;
  --card-bg:#e8dcc8;--page-bg:#c8b89a;
  --cream:#f5eedc;--muted:#7a6e5f;
  --text-dark:#1e1a14;--text-mid:#4a3f30;--gold:#c8a96e;
  --row-even:#d8ccb4;--row-odd:#cfc3aa;
  --green:#3a6b4a;
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
.tabs{display:flex;gap:0;background:rgba(0,0,0,0.08);border-radius:8px;padding:3px;}
.tab-btn{padding:7px 18px;border:none;background:transparent;border-radius:6px;font-size:13px;font-family:'Lato',sans-serif;color:var(--text-mid);cursor:pointer;font-weight:400;transition:background .2s,color .2s;}
.tab-btn.active{background:var(--dark);color:var(--cream);font-weight:700;}
.toolbar-right{display:flex;align-items:center;gap:8px;}
.search-wrap{display:flex;align-items:center;gap:6px;background:rgba(255,255,255,0.5);border-radius:8px;padding:6px 12px;border:1px solid rgba(0,0,0,0.1);}
.search-wrap input{border:none;background:transparent;outline:none;font-size:13px;color:var(--text-dark);width:140px;font-family:'Lato',sans-serif;}
.search-wrap input::placeholder{color:var(--muted);}
.icon-btn{width:34px;height:34px;border-radius:8px;border:1px solid rgba(0,0,0,0.15);background:rgba(255,255,255,0.4);display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:16px;transition:background .2s;}
.icon-btn:hover{background:rgba(255,255,255,0.7);}

#tableCard{flex:1;display:flex;flex-direction:column;overflow:hidden;min-height:0;}
#productScrollArea{flex:1;overflow-y:auto;min-height:0;padding-bottom:4px;}
.product-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:14px;}
.product-card{background:rgba(255,255,255,0.45);border-radius:12px;padding:16px;border:1px solid rgba(0,0,0,0.08);transition:box-shadow .2s,transform .15s;position:relative;}
.product-card:hover{box-shadow:0 4px 16px rgba(0,0,0,0.12);transform:translateY(-2px);}
.product-card.unavailable{opacity:.55;}
.prod-category{font-size:10px;letter-spacing:1.2px;text-transform:uppercase;color:var(--muted);margin-bottom:6px;}
.prod-name{font-family:'Playfair Display',serif;font-size:16px;color:var(--text-dark);margin-bottom:4px;}
.prod-desc{font-size:11px;color:var(--muted);margin-bottom:10px;min-height:14px;}
.prod-footer{display:flex;align-items:center;justify-content:space-between;}
.prod-price{font-size:15px;font-weight:700;color:var(--text-dark);}
.prod-actions{display:flex;gap:6px;}
.prod-btn{width:28px;height:28px;border-radius:7px;border:none;background:rgba(0,0,0,0.08);cursor:pointer;font-size:13px;display:flex;align-items:center;justify-content:center;transition:background .2s;}
.prod-btn:hover{background:rgba(0,0,0,0.16);}
.prod-btn.danger:hover{background:#f8d7da;}
.avail-badge{position:absolute;top:10px;right:10px;font-size:10px;padding:2px 8px;border-radius:10px;font-weight:700;}
.avail-yes{background:#d4edda;color:#2d6a4f;}
.avail-no {background:#f8d7da;color:#721c24;}

.tbl-wrap{overflow-x:auto;display:none;}
table{width:100%;border-collapse:collapse;font-size:13px;}
thead tr{border-bottom:2px solid rgba(0,0,0,0.15);}
thead th{text-align:left;padding:10px 16px;color:var(--text-mid);font-size:12px;letter-spacing:.6px;text-transform:uppercase;font-weight:700;}
tbody tr:nth-child(odd){background:var(--row-odd);}
tbody tr:nth-child(even){background:var(--row-even);}
tbody tr:hover{background:rgba(200,169,110,0.3);}
tbody td{padding:10px 16px;}

.view-toggle{display:flex;gap:4px;}
.view-btn{width:32px;height:32px;border-radius:7px;border:1px solid rgba(0,0,0,0.15);background:rgba(255,255,255,0.4);display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:14px;transition:background .2s;}
.view-btn.active{background:var(--dark);color:var(--cream);}

.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:300;align-items:center;justify-content:center;}
.modal-overlay.open{display:flex;}
.modal{background:var(--card-bg);border-radius:16px;padding:32px;width:min(420px,92vw);box-shadow:0 20px 60px rgba(0,0,0,0.4);animation:fadeUp .3s ease both;}
.modal h2{font-family:'Playfair Display',serif;font-size:22px;margin-bottom:20px;color:var(--text-dark);}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
.form-group{display:flex;flex-direction:column;gap:5px;margin-bottom:14px;}
.form-group label{font-size:11px;letter-spacing:1px;text-transform:uppercase;color:var(--muted);}
.form-group input,.form-group select,.form-group textarea{background:rgba(255,255,255,0.55);border:1px solid rgba(0,0,0,0.15);border-radius:8px;padding:10px 12px;font-size:14px;font-family:'Lato',sans-serif;color:var(--text-dark);outline:none;transition:border-color .2s;width:100%;}
.form-group input:focus,.form-group select:focus,.form-group textarea:focus{border-color:var(--gold);}
.form-group textarea{resize:vertical;min-height:60px;}
.toggle-row{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;}
.toggle-label{font-size:13px;color:var(--text-mid);font-weight:600;}
.toggle{position:relative;width:44px;height:24px;}
.toggle input{opacity:0;width:0;height:0;}
.slider{position:absolute;inset:0;background:#ccc;border-radius:24px;cursor:pointer;transition:.3s;}
.slider:before{content:'';position:absolute;height:18px;width:18px;left:3px;bottom:3px;background:#fff;border-radius:50%;transition:.3s;}
.toggle input:checked+.slider{background:var(--green);}
.toggle input:checked+.slider:before{transform:translateX(20px);}
.modal-actions{display:flex;gap:10px;justify-content:flex-end;margin-top:6px;}
.btn{padding:10px 22px;border-radius:8px;border:none;font-size:14px;font-family:'Lato',sans-serif;font-weight:700;cursor:pointer;transition:background .2s;}
.btn-primary{background:var(--dark);color:var(--cream);}
.btn-primary:hover{background:#3a3020;}
.btn-secondary{background:rgba(0,0,0,0.1);color:var(--text-dark);}
.error-msg{color:#c0392b;font-size:12px;min-height:14px;margin-bottom:8px;}
.empty-state{text-align:center;padding:40px;color:var(--muted);font-size:14px;}
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
  <?php $sp = $userRole === 'Staff' ? '/brew-n-break-pos/staff.php' : ''; ?>
  <aside class="sidebar">
    <a class="nav-item" href="<?= $sp ?: '/brew-n-break-pos/dashboard.php' ?>"><span>🏠</span><span class="tip">Dashboard</span></a>
    <?php if ($userRole !== 'Staff'): ?><a class="nav-item" href="users.php"><span>👤</span><span class="tip">Users</span></a><?php endif; ?>
    <a class="nav-item active" href="<?= $sp ? $sp.'/menu' : 'menu.php' ?>"><span>☕</span><span class="tip">Cafe</span></a>
    <a class="nav-item" href="<?= $sp ? $sp.'/billiard' : 'billiard.php' ?>"><svg viewBox="0 0 24 24" width="22" height="22" xmlns="http://www.w3.org/2000/svg"><defs><radialGradient id="bg8" cx="40%" cy="35%" r="60%"><stop offset="0%" stop-color="#3a3a3a"/><stop offset="100%" stop-color="#0a0a0a"/></radialGradient><radialGradient id="wc8" cx="40%" cy="35%" r="60%"><stop offset="0%" stop-color="#ffffff"/><stop offset="100%" stop-color="#d8d8d8"/></radialGradient></defs><circle cx="12" cy="12" r="11" fill="url(#bg8)"/><circle cx="12" cy="12" r="5.2" fill="url(#wc8)"/><text x="12" y="15.5" font-size="6.5" text-anchor="middle" fill="#111" font-family="Arial,sans-serif" font-weight="900">8</text></svg><span class="tip">Billiard</span></a>
    <a class="nav-item" href="<?= $sp ? $sp.'/transactions' : 'transactions.php' ?>"><span>📋</span><span class="tip">Transactions</span></a>
    <a class="nav-item" href="<?= $sp ? $sp.'/bookings' : 'bookings.php' ?>"><span>📅</span><span class="tip">Bookings</span></a>
    <?php if ($userRole !== 'Staff'): ?><a class="nav-item" href="reports.php"><span>📁</span><span class="tip">Reports</span></a><?php endif; ?>
    <div class="nav-spacer"></div>
    <a class="nav-item" href="<?= $sp ? $sp.'/notifications' : 'notifications.php' ?>" id="bellNavItem"><span>🔔</span><span id="bellBadge"></span><span class="tip">Notifications</span></a>
    <a class="nav-item" href="<?= $sp ? $sp.'/settings' : 'settings.php' ?>"><span>⚙️</span><span class="tip">Settings</span></a>
  </aside>

  <main class="main">
    <div class="page-header">
      <h1 class="page-title">Product Management</h1>
      <div class="page-time">🕐 <span id="liveClock"></span></div>
    </div>

    <!-- Sub-nav -->
    <div style="display:flex;gap:10px;align-items:center;">
      <a href="<?= $sp ? $sp.'/menu' : 'menu.php' ?>" style="font-size:13px;color:var(--muted);text-decoration:none;padding:6px 14px;border-radius:8px;background:rgba(0,0,0,0.08);">← Back to Orders</a>
    </div>

    <div class="card" id="tableCard">
      <div class="toolbar">
        <div class="tabs">
          <button class="tab-btn active" onclick="filterCat('all',this)">All</button>
          <button class="tab-btn" onclick="filterCat('foods',this)">Foods</button>
          <button class="tab-btn" onclick="filterCat('coffee',this)">Drinks</button>
        </div>
        <div class="toolbar-right">
          <div class="search-wrap">
            <span>🔍</span>
            <input type="text" id="searchInput" placeholder="Search products" oninput="applyFilters()"/>
          </div>
          <div class="view-toggle">
            <button class="view-btn active" id="gridViewBtn" onclick="setView('grid')" title="Grid view">⊞</button>
            <button class="view-btn" id="listViewBtn" onclick="setView('list')" title="List view">☰</button>
          </div>
          <button class="icon-btn" title="Add Product" onclick="openModal()">➕</button>
        </div>
      </div>

      <div id="productScrollArea">
      <!-- Grid View -->
      <div class="product-grid" id="productGrid">
        <?php foreach ($products as $p): ?>
        <div class="product-card <?= $p['available'] ? '' : 'unavailable' ?>"
             data-cat="<?= strtolower(htmlspecialchars($p['category'])) ?>"
             data-name="<?= strtolower(htmlspecialchars($p['name'])) ?>">
          <span class="avail-badge <?= $p['available'] ? 'avail-yes' : 'avail-no' ?>">
            <?= $p['available'] ? 'Available' : 'Unavailable' ?>
          </span>
          <div class="prod-category"><?= htmlspecialchars($p['category'] === 'Coffee' ? 'Drinks' : $p['category']) ?></div>
          <div class="prod-name"><?= htmlspecialchars($p['name']) ?></div>
          <div class="prod-desc"><?= htmlspecialchars($p['description'] ?? '') ?></div>
          <div class="prod-footer">
            <span class="prod-price">₱<?= number_format($p['price'], 2) ?></span>
            <div class="prod-actions">
              <button class="prod-btn" title="Edit"
                onclick="editProduct(<?= $p['id'] ?>,'<?= htmlspecialchars(addslashes($p['name'])) ?>','<?= $p['price'] ?>','<?= htmlspecialchars($p['category']) ?>','<?= htmlspecialchars(addslashes($p['description'] ?? '')) ?>',<?= $p['available'] ?>)">✏️</button>
              <button class="prod-btn danger" title="Delete" onclick="deleteProduct(<?= $p['id'] ?>)">🗑️</button>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
        <?php if (empty($products)): ?>
        <div class="empty-state" style="grid-column:1/-1">No products yet. Click ➕ to add one.</div>
        <?php endif; ?>
      </div>

      <!-- List View -->
      <div class="tbl-wrap" id="productList">
        <table>
          <thead><tr><th>Name</th><th>Category</th><th>Price</th><th>Status</th><th>Action</th></tr></thead>
          <tbody id="productListBody">
            <?php foreach ($products as $p): ?>
            <tr data-cat="<?= strtolower(htmlspecialchars($p['category'])) ?>"
                data-name="<?= strtolower(htmlspecialchars($p['name'])) ?>">
              <td><?= htmlspecialchars($p['name']) ?></td>
              <td><?= htmlspecialchars($p['category'] === 'Coffee' ? 'Drinks' : $p['category']) ?></td>
              <td>₱<?= number_format($p['price'], 2) ?></td>
              <td><?= $p['available'] ? '<span style="color:#2d6a4f;font-weight:700">Available</span>' : '<span style="color:#721c24;font-weight:700">Unavailable</span>' ?></td>
              <td style="display:flex;gap:6px;">
                <button class="prod-btn" onclick="editProduct(<?= $p['id'] ?>,'<?= htmlspecialchars(addslashes($p['name'])) ?>','<?= $p['price'] ?>','<?= htmlspecialchars($p['category']) ?>','<?= htmlspecialchars(addslashes($p['description'] ?? '')) ?>',<?= $p['available'] ?>)">✏️</button>
                <button class="prod-btn danger" onclick="deleteProduct(<?= $p['id'] ?>)">🗑️</button>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      </div><!-- /#productScrollArea -->

      <!-- Pagination -->
      <div style="display:flex;align-items:center;justify-content:space-between;margin-top:12px;flex-wrap:wrap;gap:10px;flex-shrink:0;">
        <div id="pageInfo" style="font-size:12px;color:var(--muted);"></div>
        <div id="pageControls" style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;"></div>
      </div>
    </div>
  </main>
</div>

<!-- ADD/EDIT MODAL -->
<div class="modal-overlay" id="modalOverlay" onclick="closeOutside(event)">
  <div class="modal" onclick="event.stopPropagation()">
    <h2 id="modalTitle">Add Product</h2>
    <input type="hidden" id="editId"/>

    <div class="form-row">
      <div class="form-group" style="grid-column:1/-1">
        <label>Product Name</label>
        <input type="text" id="fName" placeholder="e.g. Matcha Latte"/>
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label>Category</label>
        <select id="fCategory">
          <option value="Coffee">Drinks</option>
          <option value="Foods">Foods</option>
        </select>
      </div>
      <div class="form-group">
        <label>Price (₱)</label>
        <input type="number" id="fPrice" placeholder="0.00" min="0" step="0.01"/>
      </div>
    </div>
    <div class="form-group">
      <label>Description (optional)</label>
      <textarea id="fDesc" placeholder="Brief description of the product…"></textarea>
    </div>
    <div class="toggle-row">
      <span class="toggle-label">Available</span>
      <label class="toggle">
        <input type="checkbox" id="fAvailable" checked/>
        <span class="slider"></span>
      </label>
    </div>

    <div class="error-msg" id="modalError"></div>
    <div class="modal-actions">
      <button class="btn btn-secondary" onclick="closeModal()">Cancel</button>
      <button class="btn btn-primary" onclick="submitProduct()">Save</button>
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
let currentView='grid';
const PER_PAGE_GRID=12, PER_PAGE_LIST=13;
let currentPage=1;
let _filteredItems=[];

function setView(v){
  currentView=v;
  document.getElementById('productGrid').style.display = v==='grid'?'':'none';
  document.getElementById('productList').style.display = v==='list'?'block':'none';
  document.getElementById('gridViewBtn').classList.toggle('active',v==='grid');
  document.getElementById('listViewBtn').classList.toggle('active',v==='list');
  applyFilters();
}
let currentCat='all';
function filterCat(cat,btn){
  currentCat=cat;
  document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('active'));
  btn.classList.add('active');
  applyFilters();
}
function applyFilters(){
  const q=document.getElementById('searchInput').value.toLowerCase();
  _filteredItems=[];
  if(currentView==='grid'){
    document.querySelectorAll('.product-card').forEach(el=>{
      const matchCat=currentCat==='all'||el.dataset.cat===currentCat;
      const matchSearch=!q||el.dataset.name.includes(q);
      el.style.display='none';
      if(matchCat&&matchSearch) _filteredItems.push(el);
    });
  } else {
    document.querySelectorAll('#productListBody tr').forEach(el=>{
      const matchCat=currentCat==='all'||el.dataset.cat===currentCat;
      const matchSearch=!q||el.dataset.name.includes(q);
      el.style.display='none';
      if(matchCat&&matchSearch) _filteredItems.push(el);
    });
  }
  currentPage=1;
  renderPage();
  renderPagination();
}
function renderPage(){
  const perPage=currentView==='grid'?PER_PAGE_GRID:PER_PAGE_LIST;
  const start=(currentPage-1)*perPage, end=start+perPage;
  if(currentView==='grid') document.querySelectorAll('.product-card').forEach(el=>el.style.display='none');
  else document.querySelectorAll('#productListBody tr').forEach(el=>el.style.display='none');
  _filteredItems.forEach((el,i)=>{ el.style.display=(i>=start&&i<end)?'':'none'; });
  const total=_filteredItems.length;
  const info=document.getElementById('pageInfo');
  if(total===0){ info.textContent='No products found'; }
  else { info.textContent='Showing '+(start+1)+'–'+Math.min(end,total)+' of '+total+' products'; }
}
function renderPagination(){
  const perPage=currentView==='grid'?PER_PAGE_GRID:PER_PAGE_LIST;
  const total=_filteredItems.length;
  const pages=Math.ceil(total/perPage);
  const ctrl=document.getElementById('pageControls');
  if(pages<=1){ ctrl.innerHTML=''; return; }
  let html=`<button class="pg-btn" onclick="goToPage(${currentPage-1})" ${currentPage===1?'disabled':''}>‹</button>`;
  for(let i=1;i<=pages;i++){
    if(i===1||i===pages||(i>=currentPage-2&&i<=currentPage+2)){
      html+=`<button class="pg-btn${i===currentPage?' pg-active':''}" onclick="goToPage(${i})">${i}</button>`;
    } else if(i===currentPage-3||i===currentPage+3){
      html+=`<span class="pg-ellipsis">…</span>`;
    }
  }
  html+=`<button class="pg-btn" onclick="goToPage(${currentPage+1})" ${currentPage===pages?'disabled':''}>›</button>`;
  ctrl.innerHTML=html;
}
function goToPage(n){
  const perPage=currentView==='grid'?PER_PAGE_GRID:PER_PAGE_LIST;
  const pages=Math.ceil(_filteredItems.length/perPage);
  if(n<1||n>pages) return;
  currentPage=n;
  renderPage();
  renderPagination();
}
function openModal(){
  document.getElementById('modalTitle').textContent='Add Product';
  document.getElementById('editId').value='';
  document.getElementById('fName').value='';
  document.getElementById('fCategory').value='Coffee';
  document.getElementById('fPrice').value='';
  document.getElementById('fDesc').value='';
  document.getElementById('fAvailable').checked=true;
  document.getElementById('modalError').textContent='';
  document.getElementById('modalOverlay').classList.add('open');
}
function editProduct(id,name,price,cat,desc,avail){
  document.getElementById('modalTitle').textContent='Edit Product';
  document.getElementById('editId').value=id;
  document.getElementById('fName').value=name;
  document.getElementById('fCategory').value=cat;
  document.getElementById('fPrice').value=price;
  document.getElementById('fDesc').value=desc;
  document.getElementById('fAvailable').checked=!!avail;
  document.getElementById('modalError').textContent='';
  document.getElementById('modalOverlay').classList.add('open');
}
function closeModal(){ document.getElementById('modalOverlay').classList.remove('open'); }
function closeOutside(e){ if(e.target===document.getElementById('modalOverlay')) closeModal(); }

async function submitProduct(){
  const id       = document.getElementById('editId').value;
  const name     = document.getElementById('fName').value.trim();
  const category = document.getElementById('fCategory').value;
  const price    = document.getElementById('fPrice').value;
  const desc     = document.getElementById('fDesc').value.trim();
  const available= document.getElementById('fAvailable').checked ? 1 : 0;
  const errEl    = document.getElementById('modalError');

  if(!name||!price){ errEl.textContent='Name and price are required.'; return; }

  const res  = await fetch('/brew-n-break-pos/product_action.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:id?'edit':'add',id,name,category,price,desc,available})});
  const data = await res.json();
  if(data.success){ closeModal(); location.reload(); }
  else errEl.textContent=data.message||'Something went wrong.';
}

async function deleteProduct(id){
  if(!confirm('Delete this product?')) return;
  const res  = await fetch('/brew-n-break-pos/product_action.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'delete',id})});
  const data = await res.json();
  if(data.success) location.reload();
  else alert(data.message||'Delete failed.');
}
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
(function(){
  const STORAGE_KEY = 'bellDismissed';
  const canonId = id => String(id).replace(/^done_/, '');
  function getDismissed(){ try{ return new Set(JSON.parse(sessionStorage.getItem(STORAGE_KEY)||'[]')); }catch(e){ return new Set(); } }
  function saveDismissed(s){ try{ sessionStorage.setItem(STORAGE_KEY, JSON.stringify([...s])); }catch(e){} }
  let currentAlertIds = [];
  async function pollBell(){
    try {
      const res  = await fetch('/brew-n-break-pos/notification_check.php');
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

