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

$username = $_SESSION['username'] ?? 'Admin';
$userRole = ucfirst(strtolower($_SESSION['role'] ?? 'admin'));
$user     = ['id'=>0,'first_name'=>'Admin','last_name'=>'1','username'=>'Admin','phone'=>'+63 969-524-5378','role'=>'Admin','photo'=>''];

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if (!$conn->connect_error) {
        $u = $conn->real_escape_string($username);
        $r = $conn->query("SELECT * FROM users WHERE username='$u' LIMIT 1");
        if ($r && $r->num_rows > 0) {
            $row = $r->fetch_assoc();
            $user['id']         = $row['id'];
            $user['first_name'] = $row['first_name'] ?? explode(' ',$row['name'])[0] ?? 'Admin';
            $user['last_name']  = $row['last_name']  ?? (explode(' ',$row['name'])[1] ?? '');
            $user['username']   = $row['username'];
            $user['phone']      = $row['phone'] ?? '';
            $user['role']       = ucfirst($row['role'] ?? 'Admin');
            $user['photo']      = $row['photo'] ?? '';
        }
        $conn->close();
    }
} catch (Throwable $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<?php if($userRole==='Staff'):?><base href="/brew-n-break-pos/"><?php endif;?>
<title>Settings – Brew n' Break</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Lato:wght@300;400;700&display=swap" rel="stylesheet"/>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --dark:#1e1a14;--darker:#17140f;
  --card-bg:#e8dcc8;--page-bg:#c8b89a;
  --cream:#f5eedc;--muted:#7a6e5f;
  --text-dark:#1e1a14;--text-mid:#4a3f30;--gold:#c8a96e;
  --green:#3a6b4a;--input-bg:rgba(255,255,255,0.55);
}
body{font-family:'Lato',sans-serif;background:var(--page-bg);display:flex;flex-direction:column;min-height:100vh;color:var(--text-dark);}
.topnav{background:var(--dark);display:flex;align-items:center;justify-content:space-between;padding:0 24px 0 16px;height:64px;position:sticky;top:0;z-index:100;box-shadow:0 2px 12px rgba(0,0,0,0.4);}
.topnav-left{display:flex;align-items:center;gap:14px;}
.logo-circle{width:44px;height:44px;border-radius:50%;border:2px solid var(--gold);background:var(--darker);display:flex;align-items:center;justify-content:center;font-size:18px;}
.brand{font-family:'Playfair Display',serif;font-size:20px;color:var(--cream);}
.topnav-right{display:flex;align-items:center;gap:12px;}
.user-label{font-size:14px;color:var(--cream);font-weight:300;}
.user-avatar{width:36px;height:36px;border-radius:50%;background:rgba(255,255,255,0.12);border:1.5px solid var(--gold);display:flex;align-items:center;justify-content:center;color:var(--cream);font-size:18px;overflow:hidden;padding:0;}
.user-avatar img{width:100%;height:100%;object-fit:cover;border-radius:50%;display:block;}
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

.settings-card{background:var(--card-bg);border-radius:14px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.1);}
.settings-tab-bar{background:rgba(0,0,0,0.06);padding:10px 20px 0;border-bottom:2px solid rgba(0,0,0,0.1);}
.settings-tab{display:inline-block;padding:9px 22px;font-size:13px;font-weight:700;color:var(--text-mid);border-radius:8px 8px 0 0;background:var(--card-bg);border:1px solid rgba(0,0,0,0.1);border-bottom:none;cursor:pointer;}
.settings-body{padding:28px 32px 32px;}

.photo-section{margin-bottom:28px;}
.photo-label{font-size:13px;color:var(--text-mid);margin-bottom:10px;font-weight:600;}
.photo-box{
  width:90px;height:90px;border-radius:50%;
  border:3px solid var(--gold);
  background:rgba(255,255,255,0.4);
  display:flex;flex-direction:column;
  align-items:center;justify-content:center;
  gap:4px;cursor:pointer;
  transition:all .2s;
  position:relative;overflow:hidden;
}
.photo-box:hover{filter:brightness(0.85);}
.photo-box:hover::after{content:'✎';position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:22px;background:rgba(0,0,0,0.35);color:#fff;border-radius:50%;}
.photo-box span{font-size:11px;color:var(--muted);text-align:center;line-height:1.3;}
.photo-box .photo-icon{font-size:26px;}
.photo-preview{width:100%;height:100%;object-fit:cover;position:absolute;inset:0;border-radius:50%;}

.photo-modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.6);z-index:9999;align-items:center;justify-content:center;backdrop-filter:blur(4px);}
.photo-modal-overlay.open{display:flex;}
.photo-modal{background:var(--card-bg);border-radius:20px;padding:32px 36px;text-align:center;width:min(340px,92vw);box-shadow:0 20px 60px rgba(0,0,0,0.4);animation:popIn .22s ease both;}
.photo-modal-img{width:120px;height:120px;border-radius:50%;object-fit:cover;border:3px solid var(--gold);margin-bottom:18px;}
.photo-modal-placeholder{width:120px;height:120px;border-radius:50%;background:rgba(0,0,0,0.1);border:3px solid var(--gold);display:flex;align-items:center;justify-content:center;font-size:48px;margin:0 auto 18px;}
.photo-modal-title{font-family:'Playfair Display',serif;font-size:18px;color:var(--text-dark);margin-bottom:6px;}
.photo-modal-sub{font-size:12px;color:var(--muted);margin-bottom:22px;}
.photo-modal-btns{display:flex;flex-direction:column;gap:10px;}
.btn-change-photo{padding:11px 0;border-radius:10px;border:none;background:var(--dark);color:var(--cream);font-size:14px;font-family:'Lato',sans-serif;font-weight:700;cursor:pointer;transition:background .2s;}
.btn-change-photo:hover{background:#3a3020;}
.btn-remove-photo{padding:11px 0;border-radius:10px;border:1.5px solid rgba(192,57,43,0.4);background:transparent;color:#c0392b;font-size:14px;font-family:'Lato',sans-serif;font-weight:700;cursor:pointer;transition:all .2s;}
.btn-remove-photo:hover{background:rgba(192,57,43,0.08);}
.btn-close-photo{padding:9px 0;border-radius:10px;border:none;background:transparent;color:var(--muted);font-size:13px;font-family:'Lato',sans-serif;cursor:pointer;transition:color .2s;}
.btn-close-photo:hover{color:var(--text-dark);}

.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:18px 28px;}
.form-group{display:flex;flex-direction:column;gap:6px;}
.form-group label{font-size:12px;color:var(--text-mid);font-weight:600;}
.form-group input{
  background:var(--input-bg);
  border:1px solid rgba(0,0,0,0.12);
  border-radius:8px;
  padding:10px 14px;
  font-size:14px;
  font-family:'Lato',sans-serif;
  color:var(--text-dark);
  outline:none;
  transition:border-color .2s;
  width:100%;
}
.form-group input:focus{border-color:var(--gold);}
.form-group input[readonly]{background:rgba(255,255,255,0.3);color:var(--muted);cursor:not-allowed;}

.phone-wrap{display:flex;gap:0;}
.phone-prefix{
  background:var(--input-bg);border:1px solid rgba(0,0,0,0.12);
  border-right:none;border-radius:8px 0 0 8px;
  padding:10px 12px;font-size:14px;color:var(--text-mid);
  white-space:nowrap;
}
.phone-wrap input{border-radius:0 8px 8px 0;}

.divider{border:none;border-top:1px solid rgba(0,0,0,0.1);margin:24px 0;}

.settings-footer{display:flex;align-items:center;justify-content:space-between;margin-top:4px;}
.btn-logout{
  display:flex;align-items:center;gap:8px;
  background:none;border:none;
  font-size:14px;font-family:'Lato',sans-serif;
  color:var(--text-mid);cursor:pointer;font-weight:600;
  transition:color .2s;
}
.btn-logout:hover{color:#721c24;}
.btn-logout .logout-icon{font-size:18px;}
.footer-right{display:flex;gap:10px;}
.btn{padding:10px 24px;border-radius:8px;border:none;font-size:14px;font-family:'Lato',sans-serif;font-weight:700;cursor:pointer;transition:background .2s,transform .1s;}
.btn:active{transform:translateY(1px);}
.btn-reset{background:rgba(0,0,0,0.1);color:var(--text-dark);}
.btn-reset:hover{background:rgba(0,0,0,0.18);}
.btn-update{background:var(--green);color:#fff;}
.btn-update:hover{background:#2d5438;}

.toast{
  position:fixed;bottom:28px;right:28px;
  background:var(--dark);color:var(--cream);
  padding:12px 22px;border-radius:10px;
  font-size:13px;font-weight:600;
  box-shadow:0 8px 24px rgba(0,0,0,0.3);
  opacity:0;transform:translateY(10px);
  transition:opacity .3s,transform .3s;
  pointer-events:none;z-index:999;
}
.toast.show{opacity:1;transform:translateY(0);}

.error-msg{color:#c0392b;font-size:12px;margin-top:8px;min-height:16px;}
#logoutModal,#updateModal,#resetModal{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.55);z-index:9999;align-items:center;justify-content:center;}
#logoutModal.active,#updateModal.active,#resetModal.active{display:flex;}
.lo-box{background:var(--card-bg);border-radius:18px;padding:32px 36px;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,0.5);max-width:320px;width:90%;animation:popIn .22s ease both;}
.lo-icon{font-size:42px;margin-bottom:14px;}
.lo-title{font-family:'Playfair Display',serif;font-size:20px;color:var(--text-dark);margin-bottom:8px;}
.lo-sub{font-size:13px;color:var(--muted);margin-bottom:24px;line-height:1.5;}
.lo-btns{display:flex;gap:10px;justify-content:center;}
.lo-cancel{flex:1;padding:10px 0;border-radius:10px;border:1.5px solid rgba(0,0,0,0.15);background:transparent;color:var(--text-mid);font-size:13px;font-weight:600;cursor:pointer;transition:background .2s;}
.lo-cancel:hover{background:rgba(0,0,0,0.06);}
.lo-confirm{flex:1;padding:10px 0;border-radius:10px;border:none;background:var(--dark);color:var(--cream);font-size:13px;font-weight:700;cursor:pointer;transition:background .2s;}
.lo-confirm:hover{background:#2e2820;}
@keyframes popIn{from{opacity:0;transform:scale(.9)}to{opacity:1;transform:scale(1)}}
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
    <span class="user-label" id="navUsername"><?= htmlspecialchars($username) ?></span>
    <div class="user-avatar" id="navAvatar">
      <?php if($user['photo']): ?>
      <img id="navAvatarImg" src="<?= htmlspecialchars($user['photo']) ?>?v=<?= time() ?>" alt=""/>
      <?php else: ?>
      <span id="navAvatarEmoji">👤</span>
      <?php endif; ?>
    </div>
  </div>
</nav>

<div class="layout">
  <aside class="sidebar">
    <?php $sp=$userRole==='Staff'?'staff.php':''; ?>
    <a class="nav-item" href="<?=$sp?:'dashboard.php'?>"><span>🏠</span><span class="tip">Dashboard</span></a>
    <?php if ($userRole !== 'Staff'): ?><a class="nav-item" href="users.php"><span>👤</span><span class="tip">Users</span></a><?php endif; ?>
    <a class="nav-item" href="menu.php"><span>☕</span><span class="tip">Cafe</span></a>
    <a class="nav-item" href="billiard.php"><svg viewBox="0 0 24 24" width="22" height="22" xmlns="http://www.w3.org/2000/svg"><defs><radialGradient id="bg8" cx="40%" cy="35%" r="60%"><stop offset="0%" stop-color="#3a3a3a"/><stop offset="100%" stop-color="#0a0a0a"/></radialGradient><radialGradient id="wc8" cx="40%" cy="35%" r="60%"><stop offset="0%" stop-color="#ffffff"/><stop offset="100%" stop-color="#d8d8d8"/></radialGradient></defs><circle cx="12" cy="12" r="11" fill="url(#bg8)"/><circle cx="12" cy="12" r="5.2" fill="url(#wc8)"/><text x="12" y="15.5" font-size="6.5" text-anchor="middle" fill="#111" font-family="Arial,sans-serif" font-weight="900">8</text></svg><span class="tip">Billiard</span></a>
    <a class="nav-item" href="transactions.php"><span>📋</span><span class="tip">Transactions</span></a>
    <a class="nav-item" href="bookings.php"><span>📅</span><span class="tip">Bookings</span></a>
    <?php if ($userRole !== 'Staff'): ?><a class="nav-item" href="reports.php"><span>📁</span><span class="tip">Reports</span></a><?php endif; ?>
    <div class="nav-spacer"></div>
    <a class="nav-item" href="notifications.php" id="bellNavItem"><span>🔔</span><span id="bellBadge"></span><span class="tip">Notifications</span></a>
    <a class="nav-item active" href="settings.php"><span>⚙️</span><span class="tip">Settings</span></a>
  </aside>

  <main class="main">
    <div class="page-header">
      <h1 class="page-title">Settings</h1>
      <div class="page-time">🕐 <span id="liveClock"></span></div>
    </div>

    <div class="settings-card">
      <div class="settings-tab-bar">
        <span class="settings-tab">Account Settings</span>
      </div>

      <div class="settings-body">
        <!-- Photo -->
        <div class="photo-section">
          <div class="photo-label">Your Profile Picture</div>
          <div class="photo-box" id="photoBox" onclick="openPhotoModal()">
            <input type="file" id="photoInput" accept="image/*" onchange="previewPhoto(this)" style="display:none"/>
            <?php if ($user['photo']): ?>
            <img class="photo-preview" id="photoPreview" src="<?= htmlspecialchars($user['photo']) ?>" alt="Profile"/>
            <?php else: ?>
            <img class="photo-preview" id="photoPreview" src="" alt="" style="display:none"/>
            <span class="photo-icon">🖼️</span>
            <?php endif; ?>
          </div>

          <!-- Photo Modal -->
          <div class="photo-modal-overlay" id="photoModalOverlay" onclick="if(event.target===this)closePhotoModal()">
            <div class="photo-modal">
              <div id="photoModalPreview">
                <?php if ($user['photo']): ?>
                <img class="photo-modal-img" id="photoModalImg" src="<?= htmlspecialchars($user['photo']) ?>" alt=""/>
                <?php else: ?>
                <div class="photo-modal-placeholder" id="photoModalImg">🖼️</div>
                <?php endif; ?>
              </div>
              <div class="photo-modal-title">Profile Picture</div>
              <div class="photo-modal-sub">Choose an action for your profile photo</div>
              <div class="photo-modal-btns">
                <button class="btn-change-photo" onclick="document.getElementById('photoInput').click()">📷 Change Photo</button>
                <button class="btn-remove-photo" id="removePhotoBtn" onclick="removePhoto()" <?= $user['photo'] ? '' : 'style="display:none"' ?>>🗑️ Remove Photo</button>
                <button class="btn-close-photo" onclick="closePhotoModal()">Cancel</button>
              </div>
            </div>
          </div>
        </div>

        <hr class="divider"/>

        <!-- Form -->
        <div class="form-grid">
          <div class="form-group">
            <label>First Name</label>
            <input type="text" id="fFirstName" value="<?= htmlspecialchars($user['first_name']) ?>" placeholder="First name"/>
          </div>
          <div class="form-group">
            <label>Last Name</label>
            <input type="text" id="fLastName" value="<?= htmlspecialchars($user['last_name']) ?>" placeholder="Last name"/>
          </div>
          <div class="form-group">
            <label>Username</label>
            <input type="text" id="fUsername" value="<?= htmlspecialchars($user['username']) ?>" placeholder="Username"/>
          </div>
          <div class="form-group">
            <label>Phone number</label>
            <div class="phone-wrap">
              <span class="phone-prefix">+63</span>
              <input type="text" id="fPhone" value="<?= htmlspecialchars(ltrim($user['phone'], '+63 ')) ?>" placeholder="9XX-XXX-XXXX"/>
            </div>
          </div>
          <div class="form-group">
            <label>Password</label>
            <input type="password" id="fPassword" placeholder="Leave blank to keep current"/>
          </div>
          <div class="form-group">
            <label>Role</label>
            <input type="text" id="fRole" value="<?= htmlspecialchars($user['role']) ?>" readonly/>
          </div>
        </div>

        <div class="error-msg" id="errorMsg"></div>

        <hr class="divider"/>

        <!-- Footer -->
        <div class="settings-footer">
          <button class="btn-logout" onclick="confirmLogout()">
            <span class="logout-icon">🚪</span> Log out
          </button>
          <div class="footer-right">
            <button class="btn btn-reset" onclick="confirmReset()">Reset</button>
            <button class="btn btn-update" onclick="confirmUpdate()">Update Profile</button>
          </div>
        </div>
      </div>
    </div>
  </main>
</div>

<div class="toast" id="toast"></div>

<div id="logoutModal" onclick="if(event.target===this)hideLogoutModal()">
  <div class="lo-box">
    <div class="lo-icon">🚪</div>
    <div class="lo-title">Log Out?</div>
    <div class="lo-sub">Are you sure you want to log out of Brew n' Break?</div>
    <div class="lo-btns">
      <button class="lo-cancel" onclick="hideLogoutModal()">Cancel</button>
      <button class="lo-confirm" onclick="window.location.href='logout.php'">Log Out</button>
    </div>
  </div>
</div>

<div id="updateModal" onclick="if(event.target===this)hideUpdateModal()">
  <div class="lo-box">
    <div class="lo-icon">✏️</div>
    <div class="lo-title">Save Changes?</div>
    <div class="lo-sub">Are you sure you want to update your profile information?</div>
    <div class="lo-btns">
      <button class="lo-cancel" onclick="hideUpdateModal()">Cancel</button>
      <button class="lo-confirm" onclick="hideUpdateModal();updateProfile()">Save</button>
    </div>
  </div>
</div>

<div id="resetModal" onclick="if(event.target===this)hideResetModal()">
  <div class="lo-box">
    <div class="lo-icon">🔄</div>
    <div class="lo-title">Reset Form?</div>
    <div class="lo-sub">This will discard all unsaved changes and restore the original values.</div>
    <div class="lo-btns">
      <button class="lo-cancel" onclick="hideResetModal()">Cancel</button>
      <button class="lo-confirm" onclick="hideResetModal();resetForm()">Reset</button>
    </div>
  </div>
</div>

<script>
const original = {
  firstName: document.getElementById('fFirstName').value,
  lastName:  document.getElementById('fLastName').value,
  username:  document.getElementById('fUsername').value,
  phone:     document.getElementById('fPhone').value,
};
function updateClock(){
  const now=new Date();
  document.getElementById('liveClock').textContent=
    now.toLocaleTimeString('en-PH',{hour:'2-digit',minute:'2-digit',second:'2-digit',hour12:true})+' '+
    now.toLocaleDateString('en-PH',{month:'long',day:'numeric',year:'numeric'});
}
updateClock(); setInterval(updateClock,1000);

let _pendingPhoto = '';
let _removePhoto  = false;

function openPhotoModal(){ document.getElementById('photoModalOverlay').classList.add('open'); }
function closePhotoModal(){ document.getElementById('photoModalOverlay').classList.remove('open'); }

function removePhoto(){
  _removePhoto  = true;
  _pendingPhoto = '';
  const prev = document.getElementById('photoPreview');
  prev.src=''; prev.style.display='none';
  const icon = document.querySelector('.photo-box .photo-icon');
  if(icon) icon.style.display='';
  const mp = document.getElementById('photoModalPreview');
  mp.innerHTML='<div class="photo-modal-placeholder">🖼️</div>';
  document.getElementById('removePhotoBtn').style.display='none';
  updateNavAvatar(null);
  closePhotoModal();
}
function previewPhoto(input){
  const file=input.files[0];
  if(!file) return;
  _removePhoto = false;
  const reader=new FileReader();
  reader.onload=e=>{
    _pendingPhoto = e.target.result;
    const img=document.getElementById('photoPreview');
    img.src=_pendingPhoto; img.style.display='block';
    const icon=document.querySelector('.photo-box .photo-icon');
    if(icon) icon.style.display='none';
    const mp=document.getElementById('photoModalPreview');
    mp.innerHTML=`<img class="photo-modal-img" src="${_pendingPhoto}" alt=""/>`;
    document.getElementById('removePhotoBtn').style.display='';
    updateNavAvatar(_pendingPhoto);
    closePhotoModal();
  };
  reader.readAsDataURL(file);
}

function updateNavAvatar(src){
  const wrap = document.getElementById('navAvatar');
  if(!wrap) return;
  if(!src){
    wrap.innerHTML='<span id="navAvatarEmoji" style="font-size:18px">👤</span>';
    return;
  }
  let img = document.getElementById('navAvatarImg');
  if(!img){
    wrap.innerHTML='';
    img=document.createElement('img');
    img.id='navAvatarImg';
    wrap.appendChild(img);
  }
  img.src=src;
  const emoji=document.getElementById('navAvatarEmoji');
  if(emoji) emoji.style.display='none';
}
function resetForm(){
  document.getElementById('fFirstName').value = original.firstName;
  document.getElementById('fLastName').value  = original.lastName;
  document.getElementById('fUsername').value  = original.username;
  document.getElementById('fPhone').value     = original.phone;
  document.getElementById('fPassword').value  = '';
  document.getElementById('errorMsg').textContent = '';
  showToast('Form reset to original values.');
}
async function updateProfile(){
  const firstName = document.getElementById('fFirstName').value.trim();
  const lastName  = document.getElementById('fLastName').value.trim();
  const username  = document.getElementById('fUsername').value.trim();
  const phone     = document.getElementById('fPhone').value.trim();
  const password  = document.getElementById('fPassword').value;
  const errEl     = document.getElementById('errorMsg');

  if(!firstName||!username){ errEl.textContent='First name and username are required.'; return; }
  errEl.textContent='';

  const res  = await fetch('settings_action.php',{
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body:JSON.stringify({action:'update',firstName,lastName,username,phone,password,photo:_pendingPhoto||'',removePhoto:_removePhoto})
  });
  const data = await res.json();

  if(data.success){
    showToast('✅ Profile updated successfully!');
    document.getElementById('navUsername').textContent=username;
    if(data.photo) updateNavAvatar(data.photo+'?v='+Date.now());
    _pendingPhoto='';
    original.firstName=firstName;
    original.lastName=lastName;
    original.username=username;
    original.phone=phone;
  } else {
    errEl.textContent=data.message||'Update failed.';
  }
}
function confirmLogout(){ document.getElementById('logoutModal').classList.add('active'); }
function hideLogoutModal(){ document.getElementById('logoutModal').classList.remove('active'); }
function confirmUpdate(){ document.getElementById('updateModal').classList.add('active'); }
function hideUpdateModal(){ document.getElementById('updateModal').classList.remove('active'); }
function confirmReset(){ document.getElementById('resetModal').classList.add('active'); }
function hideResetModal(){ document.getElementById('resetModal').classList.remove('active'); }
function showToast(msg){
  const t=document.getElementById('toast');
  t.textContent=msg;
  t.classList.add('show');
  setTimeout(()=>t.classList.remove('show'),3000);
}
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

