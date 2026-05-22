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

$users    = [];
$username = $_SESSION['username'] ?? 'Admin';
$userRole = ucfirst(strtolower($_SESSION['role'] ?? 'admin'));

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if (!$conn->connect_error) {
        $r = $conn->query("SELECT id, name, username, role, status FROM users ORDER BY id");
        if ($r) while ($row = $r->fetch_assoc()) $users[] = $row;
        $conn->close();
    }
} catch (Throwable $e) {}
if (empty($users)) { $users = []; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>User Management – Brew n' Break</title>
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
.tab-btn{padding:7px 20px;border:none;background:transparent;border-radius:6px;font-size:13px;font-family:'Lato',sans-serif;color:var(--text-mid);cursor:pointer;font-weight:400;transition:background .2s,color .2s;}
.tab-btn.active{background:var(--dark);color:var(--cream);font-weight:700;}
.toolbar-right{display:flex;align-items:center;gap:8px;}
.search-wrap{display:flex;align-items:center;gap:6px;background:rgba(255,255,255,0.5);border-radius:8px;padding:6px 12px;border:1px solid rgba(0,0,0,0.1);}
.search-wrap input{border:none;background:transparent;outline:none;font-size:13px;color:var(--text-dark);width:160px;font-family:'Lato',sans-serif;}
.search-wrap input::placeholder{color:var(--muted);}
.icon-btn{width:34px;height:34px;border-radius:8px;border:1px solid rgba(0,0,0,0.15);background:rgba(255,255,255,0.4);display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:16px;transition:background .2s;}
.icon-btn:hover{background:rgba(255,255,255,0.7);}

.tbl-wrap{overflow:visible;}
table{width:100%;border-collapse:collapse;font-size:13px;}
thead tr{position:sticky;top:0;z-index:5;background:var(--card-bg);border-bottom:2px solid rgba(0,0,0,0.15);}
thead th{text-align:left;padding:10px 16px;color:var(--text-mid);font-size:12px;letter-spacing:.6px;text-transform:uppercase;font-weight:700;background:var(--card-bg);}
#tableCard{}
tbody tr:nth-child(odd) {background:var(--row-odd);}
tbody tr:nth-child(even){background:var(--row-even);}
tbody tr{transition:background .15s;}
tbody tr:hover{background:rgba(200,169,110,0.3);}
tbody td{padding:11px 16px;color:var(--text-dark);}
.status-active  {color:#2d6a4f;font-weight:700;}
.status-inactive{color:#856404;font-weight:700;}
.status-deactivated{color:#721c24;font-weight:700;}

.action-btn{background:var(--dark);color:var(--cream);border:none;border-radius:8px;padding:5px 10px;cursor:pointer;font-size:14px;transition:background .2s;position:relative;}
.action-btn:hover{background:#3a3020;}
.dropdown{display:none;position:fixed;background:var(--dark);border-radius:10px;min-width:160px;box-shadow:0 8px 24px rgba(0,0,0,0.4);z-index:9999;overflow:hidden;}
.dropdown a{display:block;padding:10px 16px;color:var(--cream);font-size:13px;text-decoration:none;transition:background .15s;}
.dropdown a:hover{background:rgba(255,255,255,0.1);}
.dropdown a.danger{color:#e07070;}
.action-wrap{position:relative;}
.action-wrap.open .dropdown{display:block;}

.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:300;align-items:center;justify-content:center;}
.modal-overlay.open{display:flex;}
.modal{background:var(--card-bg);border-radius:16px;padding:32px;width:min(440px,90vw);box-shadow:0 20px 60px rgba(0,0,0,0.4);animation:fadeUp .3s ease both;}
.modal h2{font-family:'Playfair Display',serif;font-size:22px;margin-bottom:20px;color:var(--text-dark);}
.form-group{display:flex;flex-direction:column;gap:5px;margin-bottom:16px;}
.form-group label{font-size:11px;letter-spacing:1px;text-transform:uppercase;color:var(--muted);}
.form-group input,.form-group select{background:rgba(255,255,255,0.5);border:1px solid rgba(0,0,0,0.15);border-radius:8px;padding:10px 12px;font-size:14px;font-family:'Lato',sans-serif;color:var(--text-dark);outline:none;transition:border-color .2s;}
.form-group input:focus,.form-group select:focus{border-color:var(--gold);}
.modal-actions{display:flex;gap:10px;justify-content:flex-end;margin-top:20px;}
.btn{padding:10px 22px;border-radius:8px;border:none;font-size:14px;font-family:'Lato',sans-serif;font-weight:700;cursor:pointer;transition:background .2s;}
.btn-primary{background:var(--dark);color:var(--cream);}
.btn-primary:hover{background:#3a3020;}
.btn-secondary{background:rgba(0,0,0,0.1);color:var(--text-dark);}
.btn-secondary:hover{background:rgba(0,0,0,0.18);}
.error-msg{color:#c0392b;font-size:12px;min-height:16px;margin-top:-8px;margin-bottom:8px;}
.empty-row td{text-align:center;color:var(--muted);font-style:italic;padding:24px;}

.del-user-modal{background:var(--card-bg);border-radius:16px;padding:28px 32px;width:min(360px,92vw);box-shadow:0 20px 60px rgba(0,0,0,0.4);animation:fadeUp .3s ease both;}
.del-user-icon{font-size:40px;text-align:center;margin-bottom:12px;}
.del-user-modal h2{font-family:'Playfair Display',serif;font-size:20px;color:var(--text-dark);text-align:center;margin-bottom:8px;}
.del-user-modal p{font-size:13px;color:var(--muted);text-align:center;margin-bottom:22px;line-height:1.5;}
.del-user-name{font-weight:700;color:var(--text-dark);}
.del-user-actions{display:flex;gap:10px;justify-content:center;}
.btn-del-cancel{padding:9px 20px;border-radius:8px;border:none;font-size:13px;font-family:'Lato',sans-serif;font-weight:700;cursor:pointer;background:rgba(0,0,0,0.1);color:var(--text-dark);transition:background .2s;}
.btn-del-cancel:hover{background:rgba(0,0,0,0.18);}
.btn-del-confirm{padding:9px 20px;border-radius:8px;border:none;font-size:13px;font-family:'Lato',sans-serif;font-weight:700;cursor:pointer;background:#7a2020;color:#fff;transition:background .2s;}
.btn-del-confirm:hover{background:#5c1818;}
.btn-del-confirm:disabled{opacity:.6;cursor:not-allowed;}

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
    <?php if ($userRole !== 'Staff'): ?><a class="nav-item active" href="users.php"><span>👤</span><span class="tip">Users</span></a><?php endif; ?>
    <a class="nav-item" href="menu.php"><span>☕</span><span class="tip">Cafe</span></a>
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
      <h1 class="page-title">User Management</h1>
      <div class="page-time">🕐 <span id="liveClock"></span></div>
    </div>

    <div class="card" id="tableCard">
      <!-- Toolbar -->
      <div class="toolbar">
        <div class="tabs">
          <button class="tab-btn active" onclick="filterTab('all',this)">All Users</button>
          <button class="tab-btn" onclick="filterTab('active',this)">Active</button>
          <button class="tab-btn" onclick="filterTab('deactivated',this)">Deactivated</button>
        </div>
        <div class="toolbar-right">
          <div class="search-wrap">
            <span>🔍</span>
            <input type="text" id="searchInput" placeholder="Search" oninput="applyFilters()"/>
          </div>
          <button class="icon-btn" title="Add User" onclick="openModal()">➕</button>
        </div>
      </div>

      <!-- Table -->
      <div class="tbl-wrap">
        <table id="userTable">
          <thead>
            <tr>
              <th>Name</th>
              <th>Username</th>
              <th>Role</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody id="tableBody">
            <?php foreach ($users as $u): ?>
            <tr data-status="<?= strtolower(htmlspecialchars($u['status'])) ?>"
                data-name="<?= strtolower(htmlspecialchars($u['name'])) ?>"
                data-username="<?= strtolower(htmlspecialchars($u['username'])) ?>">
              <td><?= htmlspecialchars($u['name']) ?></td>
              <td><?= htmlspecialchars($u['username']) ?></td>
              <td><?= htmlspecialchars($u['role']) ?></td>
              <td>
                <?php
                  $s = strtolower($u['status']);
                  $cls = $s === 'active' ? 'status-active' : ($s === 'deactivated' ? 'status-deactivated' : 'status-inactive');
                ?>
                <span class="<?= $cls ?>"><?= htmlspecialchars($u['status']) ?></span>
              </td>
              <td>
                <div class="action-wrap" id="wrap-<?= $u['id'] ?>">
                  <button class="action-btn" onclick="toggleDropdown(<?= $u['id'] ?>)">•••</button>
                  <div class="dropdown" id="dd-<?= $u['id'] ?>">
                    <a href="#" onclick="editUser(<?= $u['id'] ?>,'<?= htmlspecialchars($u['name']) ?>','<?= htmlspecialchars($u['username']) ?>','<?= htmlspecialchars($u['role']) ?>','<?= htmlspecialchars($u['status']) ?>');return false;">✏️ Edit</a>
                    <a href="#" class="danger" onclick="deleteUser(<?= $u['id'] ?>,'<?= htmlspecialchars($u['name'], ENT_QUOTES) ?>');return false;">🗑️ Delete</a>
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

<!-- DELETE USER MODAL -->
<div class="modal-overlay" id="deleteUserOverlay" onclick="closeDeleteUser(event)">
  <div class="del-user-modal" onclick="event.stopPropagation()">
    <div class="del-user-icon">🗑️</div>
    <h2>Delete User</h2>
    <p>Are you sure you want to delete<br/><span class="del-user-name" id="deleteUserName"></span>?<br/>This action cannot be undone.</p>
    <div class="del-user-actions">
      <button class="btn-del-cancel" onclick="cancelDeleteUser()">Cancel</button>
      <button class="btn-del-confirm" id="confirmUserDeleteBtn">Delete</button>
    </div>
  </div>
</div>

<!-- ADD/EDIT MODAL -->
<div class="modal-overlay" id="modalOverlay" onclick="closeModalOutside(event)">
  <div class="modal">
    <h2 id="modalTitle">Add User</h2>
    <input type="hidden" id="editId" value=""/>

    <div class="form-group">
      <label>Full Name</label>
      <input type="text" id="fName" placeholder="e.g. Juan Dela Cruz"/>
    </div>
    <div class="form-group">
      <label>Username</label>
      <input type="text" id="fUsername" placeholder="e.g. juan123"/>
    </div>
    <div class="form-group">
      <label>Password <span id="passHint" style="font-size:10px;color:var(--muted)">(leave blank to keep current)</span></label>
      <input type="password" id="fPassword" placeholder="••••••••"/>
    </div>
    <div class="form-group">
      <label>Role</label>
      <select id="fRole">
        <option value="">Select role…</option>
        <option value="Staff">Staff</option>
        <option value="Admin">Admin</option>
      </select>
    </div>
    <div class="form-group">
      <label>Status</label>
      <select id="fStatus">
        <option value="Active">Active</option>
        <option value="Deactivated">Deactivated</option>
      </select>
    </div>
    <div class="error-msg" id="modalError"></div>
    <div class="modal-actions">
      <button class="btn btn-secondary" onclick="closeModal()">Cancel</button>
      <button class="btn btn-primary" onclick="submitUser()">Save</button>
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
let currentTab = 'all';
function filterTab(tab, btn) {
  currentTab = tab;
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  applyFilters();
}

const PER_PAGE = 13;
let currentPage = 1;
let _filteredRows = [];

function applyFilters() {
  const q = document.getElementById('searchInput').value.toLowerCase();
  _filteredRows = [];
  document.querySelectorAll('#tableBody tr[data-status]').forEach(row => {
    const matchTab = currentTab === 'all' || row.dataset.status === currentTab;
    const matchSearch = !q || row.dataset.name.includes(q) || row.dataset.username.includes(q);
    if(matchTab && matchSearch) _filteredRows.push(row);
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
  if(total === 0){ info.textContent = 'No users found'; }
  else { info.textContent = 'Showing '+(start+1)+'–'+Math.min(end, total)+' of '+total+' users'; }
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
function toggleDropdown(id) {
  document.querySelectorAll('.action-wrap').forEach(w => { if (w.id !== 'wrap-'+id) w.classList.remove('open'); });
  document.getElementById('wrap-'+id).classList.toggle('open');
}
document.addEventListener('click', e => {
  if (!e.target.closest('.action-wrap')) document.querySelectorAll('.action-wrap').forEach(w => w.classList.remove('open'));
});
function openModal() {
  document.getElementById('modalTitle').textContent = 'Add User';
  document.getElementById('editId').value = '';
  document.getElementById('fName').value = '';
  document.getElementById('fUsername').value = '';
  document.getElementById('fPassword').value = '';
  document.getElementById('fRole').value = 'Staff';
  document.getElementById('fStatus').value = 'Active';
  document.getElementById('passHint').style.display = 'none';
  document.getElementById('modalError').textContent = '';
  document.getElementById('modalOverlay').classList.add('open');
}
function editUser(id, name, username, role, status) {
  document.getElementById('modalTitle').textContent = 'Edit User';
  document.getElementById('editId').value = id;
  document.getElementById('fName').value = name;
  document.getElementById('fUsername').value = username;
  document.getElementById('fPassword').value = '';
  document.getElementById('fRole').value = role;
  document.getElementById('fStatus').value = status;
  document.getElementById('passHint').style.display = '';
  document.getElementById('modalError').textContent = '';
  document.getElementById('modalOverlay').classList.add('open');
  document.querySelectorAll('.action-wrap').forEach(w => w.classList.remove('open'));
}
function closeModal(){ document.getElementById('modalOverlay').classList.remove('open'); }
function closeModalOutside(e){ if(e.target===document.getElementById('modalOverlay')) closeModal(); }

async function submitUser() {
  const id       = document.getElementById('editId').value;
  const name     = document.getElementById('fName').value.trim();
  const username = document.getElementById('fUsername').value.trim();
  const password = document.getElementById('fPassword').value;
  const role     = document.getElementById('fRole').value;
  const status   = document.getElementById('fStatus').value;
  const errEl    = document.getElementById('modalError');

  if (!name || !username) { errEl.textContent = 'Name and username are required.'; return; }
  if (!role)              { errEl.textContent = 'Please select a role.'; return; }
  if (!id && !password)   { errEl.textContent = 'Password is required for new users.'; return; }

  const res  = await fetch('user_action.php', {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify({action: id ? 'edit' : 'add', id, name, username, password, role, status})
  });
  const data = await res.json();
  if (data.success) { closeModal(); location.reload(); }
  else errEl.textContent = data.message || 'Something went wrong.';
}

let _deleteUserId = null;
function deleteUser(id, name){
  _deleteUserId = id;
  document.getElementById('deleteUserName').textContent = name || 'this user';
  document.getElementById('deleteUserOverlay').classList.add('open');
  document.querySelectorAll('.action-wrap').forEach(w=>w.classList.remove('open'));
}
function cancelDeleteUser(){ document.getElementById('deleteUserOverlay').classList.remove('open'); _deleteUserId=null; }
function closeDeleteUser(e){ if(e.target===document.getElementById('deleteUserOverlay')) cancelDeleteUser(); }

document.getElementById('confirmUserDeleteBtn').addEventListener('click', async function(){
  if(_deleteUserId===null) return;
  this.disabled=true; this.textContent='Deleting…';
  const res  = await fetch('user_action.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'delete',id:_deleteUserId})});
  const data = await res.json();
  if(data.success){ cancelDeleteUser(); location.reload(); }
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

