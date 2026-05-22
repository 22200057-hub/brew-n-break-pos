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

$notifications = [];
$username      = $_SESSION['username'] ?? 'Admin';
$userRole      = ucfirst(strtolower($_SESSION['role'] ?? 'admin'));

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if (!$conn->connect_error) {
        $r = $conn->query("
            SELECT
                bs.id,
                bs.session_code,
                bs.customer_name,
                bs.table_name,
                bs.end_time,
                bs.status,
                bs.created_at,
                GREATEST(0, TIME_TO_SEC(TIMEDIFF(bs.end_time, TIME(NOW())))) AS secs_left
            FROM billiard_sessions bs
            WHERE bs.status = 'Ongoing'
            ORDER BY secs_left ASC
        ");
        if ($r) while ($row = $r->fetch_assoc()) {
            $secs = intval($row['secs_left']);
            $endFormatted = date('g:i A', strtotime($row['end_time']));
            $createdAgo   = '';
            $diffMins     = round((time() - strtotime($row['created_at'])) / 60);
            if ($diffMins < 1)        $createdAgo = 'Just now';
            elseif ($diffMins < 60)   $createdAgo = $diffMins.' minute'.($diffMins>1?'s':'').' ago';
            else                      $createdAgo = round($diffMins/60).' hour'.(round($diffMins/60)>1?'s':'').' ago';

            if ($secs === 0) {
                $type    = 'expired';
                $title   = $row['table_name'].' Session Expired';
                $message = 'Session for '.$row['customer_name'].' ended at '.$endFormatted;
            } elseif ($secs <= 600) { // 10 minutes warning
                $type    = 'warning';
                $title   = $row['table_name'].' Session Time Expiring';
                $message = 'Expiry '.$endFormatted;
            } else {
                $type    = 'info';
                $title   = $row['table_name'].' Session Ongoing';
                $message = 'Expiry '.$endFormatted;
            }
            $notifications[] = [
                'id'         => $row['id'],
                'type'       => $type,
                'title'      => $title,
                'message'    => $message,
                'time_ago'   => $createdAgo,
                'secs_left'  => $secs,
                'end_time'   => $endFormatted,
                'table_name' => $row['table_name'],
                'customer'   => $row['customer_name'],
            ];
        }
        $r = $conn->query("
            SELECT
                bs.id,
                bs.customer_name,
                bs.table_name,
                bs.end_time,
                bs.created_at,
                TIME_TO_SEC(TIMEDIFF(TIME(NOW()), bs.end_time)) AS secs_since_end
            FROM billiard_sessions bs
            WHERE bs.status = 'Done'
              AND DATE(bs.created_at) = CURDATE()
              AND TIME(NOW()) >= bs.end_time
              AND TIME_TO_SEC(TIMEDIFF(TIME(NOW()), bs.end_time)) <= 7200
            ORDER BY bs.end_time DESC
            LIMIT 10
        ");
        if ($r) while ($row = $r->fetch_assoc()) {
            $endFormatted = date('g:i A', strtotime($row['end_time']));
            $secsAgo = intval($row['secs_since_end']);
            if ($secsAgo < 60)         $ago = 'Just now';
            elseif ($secsAgo < 3600)   $ago = round($secsAgo / 60).' min ago';
            else                       $ago = round($secsAgo / 3600).' hr ago';
            $notifications[] = [
                'id'         => 'done_'.$row['id'],
                'type'       => 'done',
                'title'      => $row['table_name'].' Session Ended',
                'message'    => 'Session for '.$row['customer_name'].' ended at '.$endFormatted,
                'time_ago'   => $ago,
                'secs_left'  => 0,
                'end_time'   => $endFormatted,
                'table_name' => $row['table_name'],
                'customer'   => $row['customer_name'],
            ];
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
<title>Notifications – Brew n' Break</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Lato:wght@300;400;700&display=swap" rel="stylesheet"/>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --dark:#1e1a14;--darker:#17140f;
  --card-bg:#e8dcc8;--page-bg:#c8b89a;
  --cream:#f5eedc;--muted:#7a6e5f;
  --text-dark:#1e1a14;--text-mid:#4a3f30;--gold:#c8a96e;
  --row-even:#d8ccb4;--row-odd:#cfc3aa;
  --warning:#7a4a00;--warning-bg:#fff3cd;--warning-border:#f0c040;
  --expired:#721c24;--expired-bg:#f8d7da;--expired-border:#e07070;
  --info:#1a4a3a;--info-bg:#d4edda;--info-border:#6abf8a;
  --done:#4a5568;--done-bg:#edf2f7;--done-border:#a0aec0;
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
.sidebar{width:68px;background:var(--darker);display:flex;flex-direction:column;align-items:center;padding:12px 0;gap:4px;position:sticky;top:64px;height:calc(100vh - 64px);border-right:1px solid rgba(255,255,255,0.05);z-index:10;}
.nav-item{width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;color:var(--muted);font-size:20px;cursor:pointer;text-decoration:none;transition:background .2s,color .2s;position:relative;}
.nav-item:hover{background:rgba(255,255,255,0.08);color:var(--cream);}
.nav-item.active{background:var(--gold);color:var(--dark);}
.nav-item .tip{position:absolute;left:58px;background:var(--dark);color:var(--cream);font-size:11px;padding:4px 8px;border-radius:6px;white-space:nowrap;pointer-events:none;opacity:0;transition:opacity .2s;border:1px solid rgba(255,255,255,0.1);z-index:200;}
.nav-item:hover .tip{opacity:1;}
.nav-badge{position:absolute;top:6px;right:6px;background:#e07070;color:#fff;font-size:9px;font-weight:700;border-radius:50%;width:16px;height:16px;display:flex;align-items:center;justify-content:center;}
.nav-spacer{flex:1;}
.main{flex:1;padding:28px;display:flex;flex-direction:column;gap:20px;animation:fadeUp .5s ease both;}
@keyframes fadeUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
.page-header{display:flex;align-items:center;justify-content:space-between;}
.page-title{font-family:'Playfair Display',serif;font-size:30px;color:var(--text-dark);}
.page-time{font-size:13px;color:var(--text-mid);display:flex;align-items:center;gap:6px;}

.notif-card{background:var(--card-bg);border-radius:14px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.1);}
.notif-header{background:var(--dark);display:flex;align-items:center;justify-content:space-between;padding:14px 20px;}
.notif-header-title{font-family:'Playfair Display',serif;font-size:18px;color:var(--cream);}
.mark-all-btn{background:rgba(255,255,255,0.12);border:1px solid rgba(255,255,255,0.2);color:var(--cream);font-size:12px;font-family:'Lato',sans-serif;padding:6px 14px;border-radius:20px;cursor:pointer;transition:background .2s;}
.mark-all-btn:hover{background:rgba(255,255,255,0.22);}
.mark-all-btn:disabled{opacity:.55;cursor:default;}

.notif-list{padding:0;}
.notif-item{display:flex;align-items:flex-start;justify-content:space-between;padding:16px 20px;border-bottom:1px solid rgba(0,0,0,0.07);transition:background .15s;cursor:default;animation:slideIn .3s ease both;}
@keyframes slideIn{from{opacity:0;transform:translateX(-10px)}to{opacity:1;transform:translateX(0)}}
.notif-item:last-child{border-bottom:none;}
.notif-item:hover{background:rgba(0,0,0,0.04);}
.notif-item.unread{background:rgba(200,169,110,0.15);}
.notif-item.unread:hover{background:rgba(200,169,110,0.25);}
.notif-left{display:flex;align-items:flex-start;gap:14px;flex:1;}
.notif-icon{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0;margin-top:2px;}
.notif-icon.warning{background:var(--warning-bg);border:1px solid var(--warning-border);}
.notif-icon.expired{background:var(--expired-bg);border:1px solid var(--expired-border);}
.notif-icon.info   {background:var(--info-bg);border:1px solid var(--info-border);}
.notif-icon.done   {background:var(--done-bg);border:1px solid var(--done-border);}
.notif-body{}
.notif-title{font-size:14px;font-weight:700;color:var(--text-dark);margin-bottom:3px;}
.notif-title.warning{color:var(--warning);}
.notif-title.expired{color:var(--expired);}
.notif-title.info   {color:var(--info);}
.notif-title.done   {color:var(--done);}
.notif-msg{font-size:13px;color:var(--muted);}
.notif-right{display:flex;flex-direction:column;align-items:flex-end;gap:8px;flex-shrink:0;padding-left:16px;}
.notif-time{font-size:12px;color:var(--muted);white-space:nowrap;}
.notif-countdown{font-size:11px;font-weight:700;padding:3px 10px;border-radius:12px;white-space:nowrap;}
.countdown-warning{background:var(--warning-bg);color:var(--warning);border:1px solid var(--warning-border);}
.countdown-expired{background:var(--expired-bg);color:var(--expired);border:1px solid var(--expired-border);}
.countdown-info   {background:var(--info-bg);color:var(--info);border:1px solid var(--info-border);}
.countdown-done   {background:var(--done-bg);color:var(--done);border:1px solid var(--done-border);}

.empty-notif{padding:48px;text-align:center;color:var(--muted);}
.empty-notif .empty-icon{font-size:40px;margin-bottom:12px;}
.empty-notif p{font-size:14px;}

.toast-container{position:fixed;top:80px;right:24px;z-index:9999;display:flex;flex-direction:column;gap:10px;pointer-events:none;}
.toast-notif{background:var(--dark);color:var(--cream);border-radius:12px;padding:14px 18px;min-width:280px;max-width:340px;box-shadow:0 8px 30px rgba(0,0,0,0.4);pointer-events:all;animation:toastIn .4s ease both;border-left:4px solid var(--gold);}
.toast-notif.toast-warning{border-left-color:#f0c040;}
.toast-notif.toast-expired{border-left-color:#e07070;}
.toast-notif.toast-info   {border-left-color:#6abf8a;}
@keyframes toastIn{from{opacity:0;transform:translateX(60px)}to{opacity:1;transform:translateX(0)}}
.toast-notif.hiding{animation:toastOut .3s ease forwards;}
@keyframes toastOut{to{opacity:0;transform:translateX(60px)}}
.toast-title{font-weight:700;font-size:13px;margin-bottom:4px;}
.toast-msg{font-size:12px;color:rgba(255,255,255,0.7);}
.toast-close{position:absolute;top:10px;right:12px;background:none;border:none;color:rgba(255,255,255,0.5);cursor:pointer;font-size:16px;line-height:1;}
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
    <a class="nav-item" href="menu.php"><span>☕</span><span class="tip">Cafe</span></a>
    <a class="nav-item" href="billiard.php"><svg viewBox="0 0 24 24" width="22" height="22" xmlns="http://www.w3.org/2000/svg"><defs><radialGradient id="bg8" cx="40%" cy="35%" r="60%"><stop offset="0%" stop-color="#3a3a3a"/><stop offset="100%" stop-color="#0a0a0a"/></radialGradient><radialGradient id="wc8" cx="40%" cy="35%" r="60%"><stop offset="0%" stop-color="#ffffff"/><stop offset="100%" stop-color="#d8d8d8"/></radialGradient></defs><circle cx="12" cy="12" r="11" fill="url(#bg8)"/><circle cx="12" cy="12" r="5.2" fill="url(#wc8)"/><text x="12" y="15.5" font-size="6.5" text-anchor="middle" fill="#111" font-family="Arial,sans-serif" font-weight="900">8</text></svg><span class="tip">Billiard</span></a>
    <a class="nav-item" href="transactions.php"><span>📋</span><span class="tip">Transactions</span></a>
    <a class="nav-item" href="bookings.php"><span>📅</span><span class="tip">Bookings</span></a>
    <?php if ($userRole !== 'Staff'): ?><a class="nav-item" href="reports.php"><span>📁</span><span class="tip">Reports</span></a><?php endif; ?>
    <div class="nav-spacer"></div>
    <a class="nav-item active" href="notifications.php" id="bellNavItem">
      <span>🔔</span>
      <span id="bellBadge"></span>
      <?php $urgentCount = count(array_filter($notifications, fn($n) => $n['type'] !== 'info')); ?>
      <?php if ($urgentCount > 0): ?><span class="nav-badge"><?= $urgentCount ?></span><?php endif; ?>
      <span class="tip">Notifications</span>
    </a>
    <a class="nav-item" href="settings.php"><span>⚙️</span><span class="tip">Settings</span></a>
  </aside>

  <main class="main">
    <div class="page-header">
      <h1 class="page-title">Notifications</h1>
      <div class="page-time">🕐 <span id="liveClock"></span></div>
    </div>

    <div class="notif-card">
      <div class="notif-header">
        <span class="notif-header-title">🔔 Notifications</span>
        <button class="mark-all-btn" onclick="markAllRead()">Mark all as read</button>
      </div>

      <div class="notif-list" id="notifList">
        <?php if (empty($notifications)): ?>
        <div class="empty-notif">
          <div class="empty-icon">🔔</div>
          <p>No active notifications.<br/>Ongoing billiard sessions will appear here.</p>
        </div>
        <?php else: ?>
        <?php foreach ($notifications as $i => $n): ?>
        <div class="notif-item unread" id="notif-<?= $n['id'] ?>"
             style="animation-delay:<?= $i * 0.05 ?>s">
          <div class="notif-left">
            <div class="notif-icon <?= $n['type'] ?>">
              <?= $n['type'] === 'expired' ? '🚨' : ($n['type'] === 'warning' ? '⚠️' : ($n['type'] === 'done' ? '✅' : '🎱')) ?>
            </div>
            <div class="notif-body">
              <div class="notif-title <?= $n['type'] ?>"><?= htmlspecialchars($n['title']) ?></div>
              <div class="notif-msg"><?= htmlspecialchars($n['message']) ?></div>
              <?php if ($n['type'] !== 'expired'): ?>
              <div class="notif-msg" style="margin-top:3px;font-size:12px;">
                Customer: <strong><?= htmlspecialchars($n['customer']) ?></strong>
              </div>
              <?php endif; ?>
            </div>
          </div>
          <div class="notif-right">
            <span class="notif-time"><?= htmlspecialchars($n['time_ago']) ?></span>
            <?php if ($n['type'] === 'done'): ?>
              <span class="notif-countdown countdown-done">Ended</span>
            <?php elseif ($n['type'] === 'expired'): ?>
              <span class="notif-countdown countdown-expired">Expired</span>
            <?php elseif ($n['type'] === 'warning'): ?>
              <span class="notif-countdown countdown-warning"
                    id="cd-<?= $n['id'] ?>"
                    data-secs="<?= $n['secs_left'] ?>">
                <?= sprintf('%02d:%02d', floor($n['secs_left']/60), $n['secs_left']%60) ?> left
              </span>
            <?php else: ?>
              <span class="notif-countdown countdown-info"
                    id="cd-<?= $n['id'] ?>"
                    data-secs="<?= $n['secs_left'] ?>">
                <?= sprintf('%02d:%02d:%02d', floor($n['secs_left']/3600), floor(($n['secs_left']%3600)/60), $n['secs_left']%60) ?> left
              </span>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </main>
</div>

<!-- TOAST CONTAINER -->
<div class="toast-container" id="toastContainer"></div>

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
  document.querySelectorAll('[id^="cd-"],[id^="live-cd-"]').forEach(el=>{
    let secs = parseInt(el.dataset.secs) - 1;
    if(secs < 0){ secs = 0; el.textContent='Expired'; el.className='notif-countdown countdown-expired'; return; }
    el.dataset.secs = secs;
    const hh=Math.floor(secs/3600), mm=Math.floor((secs%3600)/60), ss=secs%60;
    if(secs < 600){
      el.textContent=`${String(mm).padStart(2,'0')}:${String(ss).padStart(2,'0')} left`;
      el.className='notif-countdown countdown-warning';
    } else {
      el.textContent=`${String(hh).padStart(2,'0')}:${String(mm).padStart(2,'0')}:${String(ss).padStart(2,'0')} left`;
    }
  });
}
setInterval(tickCountdowns,1000);
const READ_KEY = 'bnb_notif_read';

function getReadIds(){
  try { return new Set(JSON.parse(localStorage.getItem(READ_KEY) || '[]')); }
  catch(e){ return new Set(); }
}

function saveReadIds(set){
  try { localStorage.setItem(READ_KEY, JSON.stringify([...set])); } catch(e){}
}

function getItemId(el){
  if (el.dataset.sid) return el.dataset.sid;
  const m = (el.id || '').match(/^notif-(.+)$/);
  return m ? m[1] : null;
}

function applyReadState(){
  const read = getReadIds();
  document.querySelectorAll('.notif-item').forEach(el => {
    const id = getItemId(el);
    if (id && read.has(id)) el.classList.remove('unread');
  });
}

function markAllRead(){
  const read = getReadIds();
  document.querySelectorAll('.notif-item').forEach(el => {
    const id = getItemId(el);
    if (id) read.add(id);
    el.classList.remove('unread');
  });
  saveReadIds(read);

  const btn = document.querySelector('.mark-all-btn');
  if (btn) {
    btn.textContent = '✓ All read';
    btn.disabled = true;
    setTimeout(() => { btn.textContent = 'Mark all as read'; btn.disabled = false; }, 2000);
  }
  try {
    const canonId = id => String(id).replace(/^done_/, '');
    const dismissed = new Set(JSON.parse(sessionStorage.getItem('bellDismissed') || '[]'));
    read.forEach(id => dismissed.add(canonId(id)));
    sessionStorage.setItem('bellDismissed', JSON.stringify([...dismissed]));
  } catch(e) {}
  const bellBadge = document.getElementById('bellBadge');
  if (bellBadge) bellBadge.style.display = 'none';
  const navBadge = document.querySelector('#bellNavItem .nav-badge');
  if (navBadge) navBadge.remove();
}
applyReadState();
(function(){
  try {
    const dismissed = new Set(JSON.parse(sessionStorage.getItem('bellDismissed') || '[]'));
    if (!dismissed.size) return;
    const canonId = id => String(id).replace(/^done_/, '');
    const ids = [...document.querySelectorAll('.notif-item')].map(el => {
      if (el.dataset.sid) return canonId(el.dataset.sid);
      const m = (el.id || '').match(/^notif-(.+)$/);
      return m ? canonId(m[1]) : null;
    }).filter(Boolean);
    const allDismissed = ids.length > 0 && ids.every(id => dismissed.has(id));
    if (allDismissed) {
      const nb = document.querySelector('#bellNavItem .nav-badge');
      if (nb) nb.remove();
    }
  } catch(e) {}
})();
function showToast(type, title, msg){
  const container = document.getElementById('toastContainer');
  const toast = document.createElement('div');
  toast.className = `toast-notif toast-${type}`;
  toast.style.position='relative';
  toast.innerHTML=`
    <button class="toast-close" onclick="dismissToast(this)">×</button>
    <div class="toast-title">${type==='expired'?'🚨':type==='warning'?'⚠️':'🎱'} ${title}</div>
    <div class="toast-msg">${msg}</div>
  `;
  container.appendChild(toast);
  setTimeout(()=>dismissToast(toast.querySelector('.toast-close')), 8000);
}
function dismissToast(btn){
  const toast=btn.closest('.toast-notif');
  toast.classList.add('hiding');
  setTimeout(()=>toast.remove(),300);
}
<?php foreach ($notifications as $n): ?>
<?php if ($n['type'] === 'warning' || $n['type'] === 'expired'): ?>
showToast('<?= $n['type'] ?>', '<?= addslashes($n['title']) ?>', '<?= addslashes($n['message']) ?>');
<?php endif; ?>
<?php endforeach; ?>
const _shownToasts = new Set();
async function pollNotifications(){
  try {
    const res  = await fetch('notification_check.php');
    const data = await res.json();
    const alerts = data.alerts || [];
    const fiveMin = alerts.filter(a => (a.secs_left ?? 999) <= 300);
    const list    = document.getElementById('notifList');
    const badge   = document.getElementById('bellBadge');
    const popup   = document.getElementById('bellPopup');
    const popItems= document.getElementById('bellPopupItems');
    if (list) {
      const activeIds = new Set(fiveMin.map(a => String(a.id)));
      list.querySelectorAll('.notif-live').forEach(el => {
        if (!activeIds.has(el.dataset.sid)) el.remove();
      });

      fiveMin.forEach(a => {
        const sid = String(a.id);
        let card = list.querySelector(`.notif-live[data-sid="${sid}"]`);
        if (!card) {
          const empty = list.querySelector('.empty-notif');
          if (empty) empty.style.display = 'none';

          card = document.createElement('div');
          const isRead = getReadIds().has(sid);
          card.className = 'notif-item notif-live' + (isRead ? '' : ' unread');
          card.dataset.sid = sid;
          card.style.borderLeft = '4px solid #f0c040';
          card.innerHTML = `
            <div class="notif-left">
              <div class="notif-icon warning">⚠️</div>
              <div class="notif-body">
                <div class="notif-title warning">${a.title}</div>
                <div class="notif-msg">${a.message}</div>
              </div>
            </div>
            <div class="notif-right">
              <span class="notif-time">Live</span>
              <span class="notif-countdown countdown-warning" id="live-cd-${sid}" data-secs="${a.secs_left}">
                ${String(Math.floor(a.secs_left/60)).padStart(2,'0')}:${String(a.secs_left%60).padStart(2,'0')} left
              </span>
            </div>`;
          list.insertBefore(card, list.firstChild);

          if (!_shownToasts.has(sid)) {
            showToast(a.type, a.title, a.message);
            _shownToasts.add(sid);
          }
        }
      });
      if (!list.querySelector('.notif-item')) {
        const empty = list.querySelector('.empty-notif');
        if (empty) empty.style.display = '';
      }
    }
    if (badge) {
      let dismissed = new Set();
      try { dismissed = new Set(JSON.parse(sessionStorage.getItem('bellDismissed') || '[]')); } catch(e){}
      const canonId = id => String(id).replace(/^done_/, '');
      const undismissed = fiveMin.filter(a => !dismissed.has(canonId(a.id)));
      if (undismissed.length > 0) {
        badge.textContent = undismissed.length;
        badge.style.display = 'flex';
      } else {
        badge.style.display = 'none';
      }
    }
    if (popup && popItems) {
      if (fiveMin.length > 0) {
        popItems.innerHTML = fiveMin.map(a =>
          `<div class="bp-item"><div class="bp-item-title">${a.title}</div><div class="bp-item-msg">${a.message}</div></div>`
        ).join('');
      }
    }

  } catch(e){}
}
pollNotifications();
setInterval(pollNotifications, 30000);
</script>
<div id="bellPopup">
  <div class="bp-header">
    <span class="bp-title">⚠️ Session Expiring Soon</span>
    <button class="bp-close" onclick="closeBellPopup()">×</button>
  </div>
  <div id="bellPopupItems"></div>
</div>
<script>
window.closeBellPopup = function(){ document.getElementById('bellPopup').style.display = 'none'; };
</script>
</body>
</html>

