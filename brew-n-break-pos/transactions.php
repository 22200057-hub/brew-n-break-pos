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

$transactions = [];
$username     = $_SESSION['username'] ?? 'Admin';
$userRole     = ucfirst(strtolower($_SESSION['role'] ?? 'admin'));

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if (!$conn->connect_error) {
        $r = $conn->query("
            SELECT
                o.id,
                o.order_code AS transaction_code,
                GROUP_CONCAT(CONCAT(oi.quantity,'x ',p.name) ORDER BY p.name SEPARATOR ', ') AS description,
                o.total_amount,
                o.status,
                o.type,
                o.created_at,
                'cafe' AS source
            FROM orders o
            LEFT JOIN order_items oi ON oi.order_id = o.id
            LEFT JOIN products p ON p.id = oi.product_id
            GROUP BY o.id
        ");
        if ($r) while ($row = $r->fetch_assoc()) $transactions[] = $row;
        $r = $conn->query("
            SELECT
                bs.id,
                bs.session_code AS transaction_code,
                CONCAT(bs.table_name, ' — ', bs.customer_name, ' (',
                    DATE_FORMAT(CAST(bs.start_time AS TIME),'%h:%i %p'),
                    ' to ',
                    DATE_FORMAT(CAST(bs.end_time AS TIME),'%h:%i %p'),
                ') ') AS description,
                bs.amount AS total_amount,
                bs.status,
                'Billiards' AS type,
                bs.created_at,
                'billiard' AS source
            FROM billiard_sessions bs
        ");
        if ($r) while ($row = $r->fetch_assoc()) $transactions[] = $row;
        $r = $conn->query("
            SELECT
                b.id,
                b.booking_code AS transaction_code,
                CONCAT(b.room, ' — ', b.guest_name, ' (',
                    DATE_FORMAT(b.check_in,'%b %d'),
                    ' to ',
                    DATE_FORMAT(b.check_out,'%b %d, %Y'),
                ')') AS description,
                DATEDIFF(b.check_out, b.check_in) * 3500 AS total_amount,
                b.status,
                'Booking' AS type,
                b.created_at,
                'booking' AS source
            FROM bookings b
        ");
        if ($r) while ($row = $r->fetch_assoc()) $transactions[] = $row;

        $conn->close();
    }
} catch (Throwable $e) {}

usort($transactions, fn($a,$b) => strtotime($b['created_at']) - strtotime($a['created_at']));
if (empty($transactions)) { $transactions = []; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<?php if($userRole==='Staff'):?><base href="/brew-n-break-pos/"><?php endif;?>
<title>Transaction Management – Brew n' Break</title>
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
.tabs{display:flex;gap:0;background:rgba(0,0,0,0.08);border-radius:8px;padding:3px;}
.tab-btn{padding:7px 16px;border:none;background:transparent;border-radius:6px;font-size:13px;font-family:'Lato',sans-serif;color:var(--text-mid);cursor:pointer;font-weight:400;transition:background .2s,color .2s;}
.tab-btn.active{background:var(--dark);color:var(--cream);font-weight:700;}
.toolbar-right{display:flex;align-items:center;gap:8px;}
.search-wrap{display:flex;align-items:center;gap:6px;background:rgba(255,255,255,0.5);border-radius:8px;padding:6px 12px;border:1px solid rgba(0,0,0,0.1);}
.search-wrap input{border:none;background:transparent;outline:none;font-size:13px;color:var(--text-dark);width:130px;font-family:'Lato',sans-serif;}
.search-wrap input::placeholder{color:var(--muted);}
.icon-btn{padding:7px 14px;border-radius:8px;border:1px solid rgba(0,0,0,0.15);background:rgba(255,255,255,0.4);cursor:pointer;font-size:13px;font-family:'Lato',sans-serif;font-weight:700;transition:background .2s;display:flex;align-items:center;gap:5px;}
.icon-btn:hover{background:rgba(255,255,255,0.7);}
.icon-btn.checkout-btn{background:var(--gold);color:var(--dark);border-color:transparent;}
.icon-btn.checkout-btn:hover{background:#b8994e;}
.icon-btn.checkout-btn:disabled{opacity:.5;cursor:not-allowed;}

.select-bar{display:none;align-items:center;justify-content:space-between;background:var(--dark);color:var(--cream);border-radius:10px;padding:10px 16px;margin-bottom:12px;font-size:13px;}
.select-bar.visible{display:flex;}
.select-bar-left{display:flex;align-items:center;gap:10px;}
.select-count{font-weight:700;font-size:14px;}
.select-clear{background:none;border:none;color:rgba(255,255,255,0.6);cursor:pointer;font-size:12px;text-decoration:underline;}

.tbl-wrap{overflow:visible;}
table{width:100%;border-collapse:collapse;font-size:13px;}
thead tr{border-bottom:2px solid rgba(0,0,0,0.15);}
thead th{text-align:left;padding:10px 12px;color:var(--text-mid);font-size:12px;letter-spacing:.6px;text-transform:uppercase;font-weight:700;}
tbody tr:nth-child(odd){background:var(--row-odd);}
tbody tr:nth-child(even){background:var(--row-even);}
tbody tr{transition:background .15s;}
tbody tr:hover{background:rgba(200,169,110,0.3);}
tbody tr.selected{background:rgba(200,169,110,0.45)!important;outline:2px solid var(--gold);outline-offset:-2px;}
tbody td{padding:10px 12px;color:var(--text-dark);}

.row-check{width:18px;height:18px;accent-color:var(--dark);cursor:pointer;}
.th-check{width:36px;}

.type-badge{display:inline-flex;align-items:center;gap:4px;font-size:11px;font-weight:700;padding:2px 8px;border-radius:10px;text-transform:uppercase;letter-spacing:.5px;}
.badge-cafe    {background:#e8f4fd;color:#1a5276;}
.badge-billiards{background:#fdf2e9;color:#784212;}
.badge-foods   {background:#eafaf1;color:#1e8449;}
.badge-booking {background:#f3eafd;color:#6c3483;}

.desc-main{font-size:13px;color:var(--text-dark);}
.desc-sub{font-size:11px;color:var(--muted);margin-top:2px;}

.status-done{color:var(--muted);font-weight:700;}
.status-pending{color:#856404;font-weight:700;}
.status-cancelled{color:#721c24;font-weight:700;}
.status-ongoing{color:#2d6a4f;font-weight:700;}
.paid-badge{display:inline-flex;align-items:center;gap:3px;background:#d4edda;color:#2d6a4f;border:1px solid #b7dfca;border-radius:20px;font-size:10px;font-weight:700;padding:2px 8px;margin-left:6px;letter-spacing:.3px;vertical-align:middle;}
.unpaid-badge{display:inline-flex;align-items:center;gap:3px;background:#fff3cd;color:#856404;border:1px solid #ffd97d;border-radius:20px;font-size:10px;font-weight:700;padding:2px 8px;margin-left:6px;letter-spacing:.3px;vertical-align:middle;}
.status-cell{display:flex;align-items:center;gap:4px;flex-wrap:wrap;}

.action-wrap{position:relative;display:inline-block;}
.action-btn{background:var(--dark);color:var(--cream);border:none;border-radius:8px;padding:5px 14px;cursor:pointer;font-size:14px;transition:background .2s;letter-spacing:2px;}
.action-btn:hover{background:#3a3020;}
.dropdown{display:none;position:fixed;background:var(--dark);border-radius:10px;min-width:160px;box-shadow:0 8px 24px rgba(0,0,0,0.4);z-index:9999;overflow:hidden;}
.dropdown a{display:block;padding:11px 16px;color:var(--cream);font-size:13px;text-decoration:none;transition:background .15s;}
.dropdown a:hover{background:rgba(255,255,255,0.1);}
.action-wrap.open .dropdown{display:block;}
.dropdown a.dd-danger{color:#c0392b;}
.dropdown a.dd-danger:hover{background:rgba(192,57,43,0.15);}

.edit-modal{background:var(--card-bg);border-radius:16px;width:min(420px,94vw);box-shadow:0 20px 60px rgba(0,0,0,0.4);animation:fadeUp .3s ease both;}
.edit-body{padding:28px 32px;}
.edit-title{font-family:'Playfair Display',serif;font-size:20px;color:var(--text-dark);margin-bottom:20px;}
.edit-field{margin-bottom:14px;}
.edit-field label{display:block;font-size:11px;letter-spacing:1px;text-transform:uppercase;color:var(--muted);font-weight:700;margin-bottom:6px;}
.edit-input{width:100%;background:#fff;border:1.5px solid rgba(0,0,0,0.15);border-radius:8px;padding:9px 12px;font-size:14px;font-family:'Lato',sans-serif;color:var(--text-dark);outline:none;transition:border-color .2s;}
.edit-input:focus{border-color:var(--gold);}
.edit-actions{display:flex;gap:10px;justify-content:flex-end;margin-top:22px;}

.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.55);z-index:300;align-items:center;justify-content:center;}
.modal-overlay.open{display:flex;}

.checkout-modal{background:#fff;border-radius:16px;width:min(560px,95vw);max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,0.4);animation:fadeUp .3s ease both;}
.co-body{padding:36px 40px;font-family:'Lato',sans-serif;color:#1a1410;}
.co-header{display:flex;justify-content:space-between;align-items:flex-start;padding-bottom:18px;border-bottom:2px solid #e8dcc8;margin-bottom:22px;}
.co-brand{display:flex;align-items:center;gap:12px;}
.co-brand-icon{width:44px;height:44px;border-radius:50%;background:#1a1410;border:2px solid #c8a96e;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0;}
.co-brand-name{font-family:'Playfair Display',serif;font-size:22px;font-weight:700;color:#1a1410;}
.co-brand-sub{font-size:11px;color:#7a6e5f;letter-spacing:.5px;text-transform:uppercase;margin-top:2px;}
.co-meta{text-align:right;font-size:13px;color:#7a6e5f;line-height:2;}
.co-meta strong{color:#1a1410;}
.co-section-title{font-size:11px;letter-spacing:1.5px;text-transform:uppercase;color:#7a6e5f;font-weight:700;margin-bottom:10px;}
.co-table{width:100%;border-collapse:collapse;font-size:13px;margin-bottom:16px;}
.co-table thead tr{background:#f5eedc;}
.co-table th{text-align:left;padding:9px 12px;color:#4a3f30;font-size:11px;letter-spacing:.6px;text-transform:uppercase;font-weight:700;border-bottom:1px solid #ddd0b4;}
.co-table th:last-child{text-align:right;}
.co-table tbody tr{background:#ede4cc;}
.co-table td{padding:12px 12px;vertical-align:top;}
.co-table td:last-child{text-align:right;font-weight:700;white-space:nowrap;}
.co-type-pill{display:inline-block;font-size:10px;font-weight:700;padding:2px 9px;border-radius:10px;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px;border:1px solid currentColor;}
.pill-cafe    {background:#eaf4fd;color:#1a5276;border-color:#aad4f0;}
.pill-billiards{background:#fdf2e9;color:#784212;border-color:#f0c898;}
.pill-foods   {background:#eafaf1;color:#1e8449;border-color:#a2dbb8;}
.co-desc-main{font-size:13px;color:#1a1410;line-height:1.4;}
.co-total-box{background:#f5eedc;border-radius:10px;padding:16px 18px;display:flex;justify-content:space-between;align-items:center;margin-bottom:22px;}
.co-total-label{font-size:14px;font-weight:700;color:#4a3f30;}
.co-total-amt{font-family:'Playfair Display',serif;font-size:30px;font-weight:700;color:#1a1410;}
.co-footer{text-align:center;font-size:12px;color:#7a6e5f;padding-top:18px;border-top:1px solid #e8dcc8;line-height:1.8;}
.co-actions{display:flex;gap:10px;justify-content:flex-end;padding:0 40px 28px;}
.btn{padding:10px 24px;border-radius:8px;border:1.5px solid #ccc;font-size:14px;font-family:'Lato',sans-serif;font-weight:700;cursor:pointer;transition:background .2s,border-color .2s;}
.btn-primary{background:#1a1410;color:#f5eedc;border-color:#1a1410;}
.btn-primary:hover{background:#3a3020;border-color:#3a3020;}
.btn-secondary{background:#fff;color:#1a1410;border-color:#ccc;}
.btn-secondary:hover{background:#f5f5f5;}
.co-cash-section{margin-bottom:20px;background:#f9f5ee;border-radius:10px;padding:14px 18px;border:1.5px solid #ddd0b4;}
.co-cash-label{font-size:11px;letter-spacing:1px;text-transform:uppercase;color:#7a6e5f;font-weight:700;margin-bottom:8px;}
.co-cash-input{width:100%;background:#fff;border:1.5px solid #ddd0b4;border-radius:8px;padding:10px 14px;font-size:18px;font-family:'Playfair Display',serif;color:#1a1410;outline:none;text-align:right;}
.co-cash-input:focus{border-color:#c8a96e;}
.co-change-row{display:flex;justify-content:space-between;align-items:center;margin-top:10px;padding-top:10px;border-top:1px solid #ddd0b4;font-size:13px;color:#4a3f30;font-weight:700;}
.co-change-amt{font-family:'Playfair Display',serif;font-size:20px;color:#1a1410;}
.co-discount-wrap{margin-bottom:18px;}
.co-discount-btn{width:100%;padding:11px 16px;border-radius:8px;border:1.5px dashed #c8a96e;background:rgba(200,169,110,0.08);color:#7a5c1e;font-size:13px;font-family:'Lato',sans-serif;font-weight:700;cursor:pointer;transition:all .2s;display:flex;align-items:center;justify-content:center;gap:7px;}
.co-discount-btn:hover{background:rgba(200,169,110,0.18);border-style:solid;}
.co-discount-btn.applied{background:rgba(30,132,73,0.08);border-color:#1e8449;border-style:solid;color:#155c32;}
.co-discount-detail{margin-top:10px;background:#f9f5ee;border-radius:8px;padding:12px 16px;border:1px solid #e8dcc8;font-size:13px;}
.co-discount-row{display:flex;justify-content:space-between;align-items:center;color:#4a3f30;padding:3px 0;}
.co-discount-row.subtotal-row{padding-bottom:7px;}
.co-discount-row.discount-line{font-weight:700;color:#1e8449;padding:7px 0 3px;border-top:1px solid #ddd0b4;margin-top:2px;}
.co-discount-note{font-size:10px;color:#7a6e5f;text-align:center;margin-top:7px;letter-spacing:.3px;}

.del-modal{background:var(--card-bg);border-radius:16px;padding:28px 32px;width:min(360px,92vw);box-shadow:0 20px 60px rgba(0,0,0,0.4);animation:fadeUp .3s ease both;}
.del-modal-icon{font-size:40px;text-align:center;margin-bottom:12px;}
.del-modal h2{font-family:'Playfair Display',serif;font-size:20px;color:var(--text-dark);text-align:center;margin-bottom:8px;}
.del-modal p{font-size:13px;color:var(--muted);text-align:center;margin-bottom:22px;line-height:1.5;}
.del-modal-code{font-weight:700;color:var(--text-dark);}
.del-actions{display:flex;gap:10px;justify-content:center;}
.btn-del-cancel{padding:9px 20px;border-radius:8px;border:none;font-size:13px;font-family:'Lato',sans-serif;font-weight:700;cursor:pointer;background:rgba(0,0,0,0.1);color:var(--text-dark);transition:background .2s;}
.btn-del-cancel:hover{background:rgba(0,0,0,0.18);}
.btn-del-confirm{padding:9px 20px;border-radius:8px;border:none;font-size:13px;font-family:'Lato',sans-serif;font-weight:700;cursor:pointer;background:#7a2020;color:#fff;transition:background .2s;}
.btn-del-confirm:hover{background:#5c1818;}
.btn-del-confirm:disabled{opacity:.6;cursor:not-allowed;}

.inv-modal{background:#fff;border-radius:16px;width:min(500px,94vw);max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,0.4);animation:fadeUp .3s ease both;}
.invoice{padding:36px 40px;font-family:'Lato',sans-serif;color:#1a1410;}
.inv-header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:24px;padding-bottom:18px;border-bottom:2px solid #e8dcc8;}
.inv-brand{font-family:'Playfair Display',serif;font-size:20px;color:#1a1410;}
.inv-sub{font-size:11px;color:#7a6e5f;letter-spacing:1px;text-transform:uppercase;margin-top:2px;}
.inv-meta{text-align:right;font-size:12px;color:#7a6e5f;line-height:1.9;}
.inv-meta strong{color:#1a1410;}
.inv-section-title{font-size:11px;letter-spacing:1.5px;text-transform:uppercase;color:#7a6e5f;font-weight:700;margin-bottom:10px;}
.inv-table{width:100%;border-collapse:collapse;font-size:13px;}
.inv-table th{text-align:left;padding:8px 10px;background:#f5eedc;color:#4a3f30;font-size:11px;letter-spacing:.5px;text-transform:uppercase;}
.inv-table th:last-child,.inv-table td:last-child{text-align:right;}
.inv-table td{padding:9px 10px;border-bottom:1px solid #e8dcc8;}
.inv-table tr:last-child td{border-bottom:none;}
.inv-total{display:flex;justify-content:space-between;align-items:center;padding:14px 10px;background:#f5eedc;border-radius:8px;margin-top:16px;}
.inv-total-label{font-size:13px;font-weight:700;color:#4a3f30;}
.inv-total-amount{font-family:'Playfair Display',serif;font-size:22px;color:#1a1410;}
.inv-footer{text-align:center;font-size:11px;color:#7a6e5f;margin-top:20px;padding-top:16px;border-top:1px solid #e8dcc8;}
.inv-actions{display:flex;gap:10px;justify-content:flex-end;padding:0 40px 28px;}

.pg-btn{padding:5px 11px;border-radius:7px;border:1px solid rgba(0,0,0,0.15);background:rgba(255,255,255,0.4);cursor:pointer;font-size:13px;font-family:'Lato',sans-serif;font-weight:700;color:var(--text-dark);transition:background .2s;min-width:34px;}
.pg-btn:hover:not(:disabled){background:rgba(255,255,255,0.7);}
.pg-btn:disabled{opacity:.4;cursor:not-allowed;}
.pg-btn.pg-active{background:var(--dark);color:var(--cream);border-color:transparent;}
.pg-ellipsis{padding:0 4px;color:var(--muted);font-size:13px;line-height:1;display:inline-flex;align-items:center;}
.bp-header{background:rgba(240,192,64,0.1);padding:11px 14px;border-bottom:1px solid rgba(240,192,64,0.2);display:flex;align-items:center;justify-content:space-between;gap:8px;}
.bp-title{font-size:12px;font-weight:700;color:#f0c040;display:flex;align-items:center;gap:5px;}
.bp-close{background:none;border:none;color:rgba(255,255,255,0.45);cursor:pointer;font-size:18px;line-height:1;padding:0;transition:color .2s;}
.bp-close:hover{color:#fff;}
.bp-item{padding:10px 14px;border-bottom:1px solid rgba(255,255,255,0.06);font-size:12px;}
.bp-item:last-child{border-bottom:none;}
.bp-item-title{font-weight:700;color:#f0c040;margin-bottom:3px;}
.bp-item-msg{color:rgba(255,255,255,0.6);line-height:1.4;}
.tx-stat{background:var(--card-bg);border-radius:10px;padding:10px 18px;font-size:12px;color:var(--text-mid);box-shadow:0 1px 4px rgba(0,0,0,0.08);display:flex;flex-direction:column;gap:2px;}
.tx-stat strong{font-family:'Playfair Display',serif;font-size:20px;color:var(--text-dark);}
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
    <?php $sp=$userRole==='Staff'?'staff.php':''; ?>
    <a class="nav-item" href="<?=$sp?:'dashboard.php'?>"><span>🏠</span><span class="tip">Dashboard</span></a>
    <?php if ($userRole !== 'Staff'): ?><a class="nav-item" href="users.php"><span>👤</span><span class="tip">Users</span></a><?php endif; ?>
    <a class="nav-item" href="menu.php"><span>☕</span><span class="tip">Cafe</span></a>
    <a class="nav-item" href="billiard.php"><svg viewBox="0 0 24 24" width="22" height="22" xmlns="http://www.w3.org/2000/svg"><defs><radialGradient id="bg8" cx="40%" cy="35%" r="60%"><stop offset="0%" stop-color="#3a3a3a"/><stop offset="100%" stop-color="#0a0a0a"/></radialGradient><radialGradient id="wc8" cx="40%" cy="35%" r="60%"><stop offset="0%" stop-color="#ffffff"/><stop offset="100%" stop-color="#d8d8d8"/></radialGradient></defs><circle cx="12" cy="12" r="11" fill="url(#bg8)"/><circle cx="12" cy="12" r="5.2" fill="url(#wc8)"/><text x="12" y="15.5" font-size="6.5" text-anchor="middle" fill="#111" font-family="Arial,sans-serif" font-weight="900">8</text></svg><span class="tip">Billiard</span></a>
    <a class="nav-item active" href="transactions.php"><span>📋</span><span class="tip">Transactions</span></a>
    <a class="nav-item" href="bookings.php"><span>📅</span><span class="tip">Bookings</span></a>
    <?php if ($userRole !== 'Staff'): ?><a class="nav-item" href="reports.php"><span>📁</span><span class="tip">Reports</span></a><?php endif; ?>
    <div class="nav-spacer"></div>
    <a class="nav-item" href="notifications.php" id="bellNavItem"><span>🔔</span><span id="bellBadge"></span><span class="tip">Notifications</span></a>
    <a class="nav-item" href="settings.php"><span>⚙️</span><span class="tip">Settings</span></a>
  </aside>

  <main class="main">
    <div class="page-header">
      <h1 class="page-title">Transaction Management</h1>
      <div class="page-time">🕐 <span id="liveClock"></span></div>
    </div>

    <div id="tx-summary-root"></div>
    <script type="text/babel">
      (function() {
        const txData = <?= json_encode($transactions) ?>;

        function TransactionSummary({ data }) {
          const total     = data.length;
          const revenue   = data.reduce((s, t) => s + parseFloat(t.total_amount || 0), 0);
          const pending   = data.filter(t => !['done','confirmed','cancelled'].includes(t.status?.toLowerCase())).length;
          const done      = data.filter(t => ['done','confirmed'].includes(t.status?.toLowerCase())).length;

          return (
            <div id="tx-summary-root" style={{display:'flex',gap:'10px',marginBottom:'14px',flexWrap:'wrap'}}>
              <div className="tx-stat"><span>Total Transactions</span><strong>{total}</strong></div>
              <div className="tx-stat"><span>Total Revenue</span><strong>₱{revenue.toLocaleString('en-PH',{minimumFractionDigits:2})}</strong></div>
              <div className="tx-stat"><span>Completed</span><strong>{done}</strong></div>
              <div className="tx-stat"><span>Unpaid</span><strong>{pending}</strong></div>
            </div>
          );
        }

        ReactDOM.createRoot(document.getElementById('tx-summary-root')).render(<TransactionSummary data={txData} />);
      })();
    </script>

    <div class="card">
      <div class="toolbar">
        <div class="tabs">
          <button class="tab-btn active" onclick="filterTab('all',this)">All</button>
          <button class="tab-btn" onclick="filterTab('foods',this)">Foods</button>
          <button class="tab-btn" onclick="filterTab('coffee',this)">Drinks</button>
          <button class="tab-btn" onclick="filterTab('billiards',this)">Billiards</button>
          <button class="tab-btn" onclick="filterTab('booking',this)">Booking</button>
        </div>
        <div class="toolbar-right">
          <div class="search-wrap">
            <span>🔍</span>
            <input type="text" id="searchInput" placeholder="Search" oninput="applyFilters()"/>
          </div>
          <button class="icon-btn checkout-btn" id="checkoutBtn" onclick="openCheckout()" disabled>
            🧾 Checkout (<span id="selectedCount">0</span>)
          </button>
        </div>
      </div>

      <!-- Selection info bar -->
      <div class="select-bar" id="selectBar">
        <div class="select-bar-left">
          <span class="select-count" id="selectBarCount">0 selected</span>
          <span style="color:rgba(255,255,255,0.5)">·</span>
          <span style="color:rgba(255,255,255,0.8);font-size:12px;">Total: <strong id="selectBarTotal">₱0.00</strong></span>
        </div>
        <button class="select-clear" onclick="clearAll()">Clear selection</button>
      </div>

      <div class="tbl-wrap" style="overflow:visible;">
        <table>
          <thead>
            <tr>
              <th class="th-check">
                <input type="checkbox" class="row-check" id="selectAll" onchange="toggleSelectAll(this)"/>
              </th>
              <th>Transaction ID</th>
              <th>Type</th>
              <th>Description</th>
              <th>Amount</th>
              <th>Status</th>
              <th>Date and Time</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody id="tableBody">
            <?php foreach ($transactions as $t):
              $sc = match(strtolower($t['status'])) {
                'done'      => 'status-done',
                'confirmed' => 'status-done',
                'pending'   => 'status-pending',
                'cancelled' => 'status-cancelled',
                'ongoing'   => 'status-ongoing',
                default     => 'status-pending'
              };
              $typeRaw = strtolower($t['type'] ?? '');
              $badgeClass = match($typeRaw) {
                'billiards' => 'badge-billiards',
                'foods'     => 'badge-foods',
                'booking'   => 'badge-booking',
                default     => 'badge-cafe'
              };
              $typeLabel = strtolower($t['type'] ?? '') === 'coffee' ? 'Drinks' : ucfirst($t['type'] ?? 'Cafe');
              $dt = date('h:i A F j, Y', strtotime($t['created_at']));
              $desc = htmlspecialchars($t['description'] ?? '–');
              $code = htmlspecialchars($t['transaction_code']);
            ?>
            <tr data-type="<?= $typeRaw ?>"
                data-search="<?= strtolower(htmlspecialchars($t['transaction_code'].' '.($t['description']??''))) ?>"
                data-id="<?= $t['id'] ?>"
                data-source="<?= htmlspecialchars($t['source']) ?>"
                data-code="<?= $code ?>"
                data-desc="<?= $desc ?>"
                data-type-label="<?= htmlspecialchars($typeLabel) ?>"
                data-amount="<?= $t['total_amount'] ?>"
                data-status="<?= htmlspecialchars($t['status']) ?>"
                data-dt="<?= $dt ?>">
              <td><input type="checkbox" class="row-check row-selector" onchange="onRowCheck(this)"/></td>
              <td style="font-weight:700"><?= $code ?></td>
              <td><span class="type-badge <?= $badgeClass ?>"><?= $typeLabel ?></span></td>
              <td>
                <div class="desc-main"><?= $desc ?></div>
                <div class="desc-sub"><?= $dt ?></div>
              </td>
              <td style="font-weight:700">₱<?= number_format($t['total_amount'] ?? 0, 2) ?></td>
              <td>
                <div class="status-cell">
                  <span class="<?= $sc ?>"><?= htmlspecialchars($t['status']) ?></span>
                  <?php if(in_array(strtolower($t['status']),['done','confirmed'])): ?>
                    <span class="paid-badge">✓ Paid</span>
                  <?php elseif(strtolower($t['status']) !== 'cancelled'): ?>
                    <span class="unpaid-badge">⏳ Pending</span>
                  <?php endif; ?>
                </div>
              </td>
              <td style="font-size:12px;color:var(--muted)"><?= $dt ?></td>
              <td>
                <div class="action-wrap" id="wrap-<?= $t['id'].'-'.$t['source'] ?>">
                  <button class="action-btn" onclick="toggleDD('<?= $t['id'].'-'.$t['source'] ?>', this)">•••</button>
                  <div class="dropdown">
                    <a href="#" onclick="viewInvoice(this.closest('tr'));return false;">🧾 View Invoice</a>
                    <a href="#" onclick="printInvoiceRow(this.closest('tr'));return false;">🖨️ Print Invoice</a>
                    <a href="#" onclick="openEdit(this.closest('tr'));return false;">✏️ Edit</a>
                    <a href="#" class="dd-danger" onclick="deleteTransaction(this.closest('tr'));return false;">🗑️ Delete</a>
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

<!-- DELETE CONFIRM MODAL -->
<div class="modal-overlay" id="deleteTxOverlay" onclick="closeDeleteTx(event)">
  <div class="del-modal" onclick="event.stopPropagation()">
    <div class="del-modal-icon">🗑️</div>
    <h2>Delete Transaction</h2>
    <p>Are you sure you want to delete<br/><span class="del-modal-code" id="deleteTxCode"></span>?<br/>This action cannot be undone.</p>
    <div class="del-actions">
      <button class="btn-del-cancel" onclick="cancelDeleteTx()">Cancel</button>
      <button class="btn-del-confirm" id="confirmTxDeleteBtn">Delete</button>
    </div>
  </div>
</div>

<!-- CHECKOUT MODAL -->
<div class="modal-overlay" id="checkoutOverlay" onclick="closeOverlay('checkoutOverlay',event)">
  <div class="checkout-modal" onclick="event.stopPropagation()">
    <div class="co-body" id="coBody">
      <div class="co-header">
        <div class="co-brand">
          <div class="co-brand-icon"><img src="../img/logo.png" style="width:100%;height:100%;object-fit:cover;border-radius:50%;display:block;"/></div>
          <div>
            <div class="co-brand-name">Brew n' Break</div>
            <div class="co-brand-sub">By Bamboo Court – Checkout</div>
          </div>
        </div>
        <div class="co-meta">
          <div><strong>Date:</strong> <span id="coDate"></span></div>
          <div><strong>Items:</strong> <span id="coCount"></span></div>
        </div>
      </div>

      <div class="co-section-title">Selected Transactions</div>
      <table class="co-table">
        <thead>
          <tr><th>ID</th><th>Description</th><th>Amount</th></tr>
        </thead>
        <tbody id="coRows"></tbody>
      </table>

      <!-- PWD / Senior Discount -->
      <div class="co-discount-wrap" id="coDiscountWrap" style="display:none">
        <button class="co-discount-btn" id="coDiscountBtn" onclick="toggleDiscount()">
          🏷️ Apply PWD / Senior Discount (20%)
        </button>
        <div class="co-discount-detail" id="coDiscountDetail" style="display:none">
          <div class="co-discount-row subtotal-row">
            <span>Subtotal</span>
            <span id="coSubtotalAmt"></span>
          </div>
          <div class="co-discount-row discount-line">
            <span>PWD / Senior Discount (20%)</span>
            <span id="coDiscountAmt" style="color:#1e8449"></span>
          </div>
          <div class="co-discount-note">Applies to food &amp; beverage items only</div>
        </div>
      </div>

      <div class="co-total-box">
        <span class="co-total-label">Grand Total</span>
        <span class="co-total-amt" id="coTotal"></span>
      </div>

      <div class="co-cash-section">
        <div class="co-cash-label">Cash Received</div>
        <input type="number" id="coCashInput" class="co-cash-input" placeholder="0.00" step="0.01" min="0" oninput="updateChange()"/>
        <div class="co-change-row" id="coChangeRow" style="display:none">
          <span>Change</span>
          <span class="co-change-amt" id="coChangeAmt">₱0.00</span>
        </div>
      </div>

      <div class="co-footer">
        Thank you for visiting Brew n' Break! 🎱☕<br/>
        facebook.com/brewnbreak
      </div>
    </div>
    <div class="co-actions">
      <button class="btn btn-secondary" onclick="closeOverlay('checkoutOverlay')">Close</button>
      <button class="btn btn-primary" onclick="printCheckout()">🖨️ Print</button>
      <button class="btn btn-primary" id="confirmCheckoutBtn" onclick="confirmCheckout()" style="background:#2d6a4f;border-color:#2d6a4f;">✅ Confirm Checkout</button>
    </div>
  </div>
</div>

<!-- INVOICE MODAL -->
<div class="modal-overlay" id="invoiceOverlay" onclick="closeOverlay('invoiceOverlay',event)">
  <div class="inv-modal" onclick="event.stopPropagation()">
    <div class="invoice" id="invoiceContent">
      <div class="inv-header">
        <div>
          <div class="inv-brand">🎱 Brew n' Break</div>
          <div class="inv-sub">By Bamboo Court</div>
        </div>
        <div class="inv-meta">
          <div><strong>Invoice #</strong> <span id="invCode"></span></div>
          <div><strong>Date:</strong> <span id="invDate"></span></div>
          <div><strong>Status:</strong> <span id="invStatus"></span></div>
        </div>
      </div>
      <div class="inv-section-title">Transaction Details</div>
      <table class="inv-table">
        <thead><tr><th>Description</th><th>Amount</th></tr></thead>
        <tbody id="invItems"></tbody>
      </table>
      <div class="inv-total">
        <span class="inv-total-label">Total</span>
        <span class="inv-total-amount" id="invTotal"></span>
      </div>
      <div class="inv-footer">
        Thank you for visiting Brew n' Break! 🎱☕<br/>
        facebook.com/brewnbreak
      </div>
    </div>
    <div class="inv-actions">
      <button class="btn btn-secondary" onclick="closeOverlay('invoiceOverlay')">Close</button>
      <button class="btn btn-primary" onclick="printInvoice()">🖨️ Print</button>
    </div>
  </div>
</div>

<!-- EDIT MODAL -->
<div class="modal-overlay" id="editOverlay" onclick="closeOverlay('editOverlay',event)">
  <div class="edit-modal" onclick="event.stopPropagation()">
    <div class="edit-body">
      <h3 class="edit-title">Edit Transaction</h3>
      <input type="hidden" id="editId"/>
      <input type="hidden" id="editSrc"/>
      <div class="edit-field">
        <label>Transaction ID</label>
        <div id="editCode" style="font-weight:700;font-size:15px;padding:4px 0;color:var(--text-dark)"></div>
      </div>
      <div class="edit-field">
        <label>Status</label>
        <select id="editStatus" class="edit-input">
          <option value="Done">Done</option>
          <option value="Pending">Pending</option>
          <option value="Cancelled">Cancelled</option>
          <option value="Ongoing">Ongoing</option>
        </select>
      </div>
      <div class="edit-field">
        <label>Amount (₱)</label>
        <input type="number" id="editAmount" class="edit-input" step="0.01" min="0" placeholder="0.00"/>
      </div>
      <div class="edit-actions">
        <button class="btn btn-secondary" onclick="closeOverlay('editOverlay')">Cancel</button>
        <button class="btn btn-primary" onclick="submitEdit()">Save Changes</button>
      </div>
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
let currentTab='all';
function filterTab(tab,btn){
  currentTab=tab;
  document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('active'));
  btn.classList.add('active');
  applyFilters();
}
const PER_PAGE = 13;
let currentPage = 1;
let _filteredRows = [];

function applyFilters(){
  const q = document.getElementById('searchInput').value.toLowerCase();
  _filteredRows = [];
  document.querySelectorAll('#tableBody tr').forEach(row => {
    const matchTab = currentTab === 'all' || row.dataset.type === currentTab;
    const matchSearch = !q || row.dataset.search.includes(q);
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
  if(total === 0){
    info.textContent = 'No transactions found';
  } else {
    info.textContent = 'Showing '+(start+1)+'–'+Math.min(end, total)+' of '+total+' transactions';
  }
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
function onRowCheck(cb){
  const row = cb.closest('tr');
  row.classList.toggle('selected', cb.checked);
  updateSelectionUI();
}

function toggleSelectAll(cb){
  document.querySelectorAll('.row-selector').forEach(r=>{
    const row=r.closest('tr');
    if(row.style.display==='none') return;
    r.checked=cb.checked;
    row.classList.toggle('selected',cb.checked);
  });
  updateSelectionUI();
}

function updateSelectionUI(){
  const selected = getSelectedRows();
  const count    = selected.length;
  const total    = selected.reduce((s,r)=>s+parseFloat(r.dataset.amount||0),0);
  const fmt      = '₱'+total.toLocaleString('en-PH',{minimumFractionDigits:2});

  document.getElementById('selectedCount').textContent=count;
  document.getElementById('checkoutBtn').disabled = count===0;
  document.getElementById('selectBar').classList.toggle('visible', count>0);
  document.getElementById('selectBarCount').textContent=count+' selected';
  document.getElementById('selectBarTotal').textContent=fmt;
}

function getSelectedRows(){
  return [...document.querySelectorAll('.row-selector:checked')].map(cb=>cb.closest('tr'));
}

function clearAll(){
  document.querySelectorAll('.row-selector').forEach(cb=>{ cb.checked=false; cb.closest('tr').classList.remove('selected'); });
  document.getElementById('selectAll').checked=false;
  updateSelectionUI();
}
function toggleDD(id,btn){
  const wrap=document.getElementById('wrap-'+id);
  const dd=wrap.querySelector('.dropdown');
  const isOpen=wrap.classList.contains('open');
  document.querySelectorAll('.action-wrap').forEach(w=>w.classList.remove('open'));
  document.querySelectorAll('.dropdown').forEach(d=>{d.style.top='';d.style.left='';});
  if(!isOpen){
    const rect=btn.getBoundingClientRect();
    dd.style.top=(rect.bottom+window.scrollY+4)+'px';
    dd.style.left=(rect.right-160)+'px';
    wrap.classList.add('open');
  }
}
document.addEventListener('click',e=>{if(!e.target.closest('.action-wrap'))document.querySelectorAll('.action-wrap').forEach(w=>w.classList.remove('open'));});
function closeOverlay(id,e){
  if(e && e.target!==document.getElementById(id)) return;
  document.getElementById(id).classList.remove('open');
}
let _discountApplied = false;
let _cafeSubtotal    = 0;
let _fullTotal       = 0;
let _payableTotal    = 0;

function openCheckout(){
  const rows=getSelectedRows();
  if(!rows.length) return;

  _discountApplied = false;
  _cafeSubtotal    = 0;
  _fullTotal       = 0;

  const now=new Date().toLocaleDateString('en-PH',{month:'long',day:'numeric',year:'numeric'});
  document.getElementById('coDate').textContent=now;
  document.getElementById('coCount').textContent=rows.length+' item'+(rows.length>1?'s':'');

  let html='';
  rows.forEach(r=>{
    const amt=parseFloat(r.dataset.amount)||0;
    const typeLabel=r.dataset.typeLabel||'';
    const tl=typeLabel.toLowerCase();
    const pillClass=tl==='billiards'?'pill-billiards':tl==='foods'?'pill-foods':'pill-cafe';
    _fullTotal+=amt;
    if(r.dataset.source==='cafe') _cafeSubtotal+=amt;
    html+=`<tr>
      <td style="font-weight:700;white-space:nowrap">${r.dataset.code}</td>
      <td>
        <div><span class="co-type-pill ${pillClass}">${typeLabel}</span></div>
        <div class="co-desc-main">${r.dataset.desc}</div>
      </td>
      <td>₱${amt.toLocaleString('en-PH',{minimumFractionDigits:2})}</td>
    </tr>`;
  });

  _payableTotal = _fullTotal;
  document.getElementById('coRows').innerHTML=html;
  document.getElementById('coTotal').textContent='₱'+_payableTotal.toLocaleString('en-PH',{minimumFractionDigits:2});
  document.getElementById('coCashInput').value='';
  document.getElementById('coChangeRow').style.display='none';
  const discWrap = document.getElementById('coDiscountWrap');
  const discBtn  = document.getElementById('coDiscountBtn');
  const discDetail = document.getElementById('coDiscountDetail');
  discWrap.style.display = _cafeSubtotal > 0 ? '' : 'none';
  discBtn.classList.remove('applied');
  discBtn.textContent='🏷️ Apply PWD / Senior Discount (20%)';
  discDetail.style.display='none';

  document.getElementById('checkoutOverlay').classList.add('open');
}

function toggleDiscount(){
  _discountApplied = !_discountApplied;
  const discountAmt = _cafeSubtotal * 0.20;
  const discBtn   = document.getElementById('coDiscountBtn');
  const discDetail= document.getElementById('coDiscountDetail');

  if(_discountApplied){
    _payableTotal = _fullTotal - discountAmt;
    discBtn.classList.add('applied');
    discBtn.innerHTML='✅ PWD / Senior Discount Applied — <span style="font-size:11px;opacity:.8">click to remove</span>';
    document.getElementById('coSubtotalAmt').textContent='₱'+_fullTotal.toLocaleString('en-PH',{minimumFractionDigits:2});
    document.getElementById('coDiscountAmt').textContent='–₱'+discountAmt.toLocaleString('en-PH',{minimumFractionDigits:2});
    discDetail.style.display='block';
  } else {
    _payableTotal = _fullTotal;
    discBtn.classList.remove('applied');
    discBtn.textContent='🏷️ Apply PWD / Senior Discount (20%)';
    discDetail.style.display='none';
  }

  document.getElementById('coTotal').textContent='₱'+_payableTotal.toLocaleString('en-PH',{minimumFractionDigits:2});
  updateChange();
}
function updateChange(){
  const cash = parseFloat(document.getElementById('coCashInput').value)||0;
  const row  = document.getElementById('coChangeRow');
  if(cash>0 && _payableTotal>0){
    const chg = cash - _payableTotal;
    document.getElementById('coChangeAmt').textContent = '₱'+chg.toLocaleString('en-PH',{minimumFractionDigits:2});
    row.style.display = 'flex';
  } else {
    row.style.display = 'none';
  }
}

async function confirmCheckout(){
  const rows = getSelectedRows();
  if(!rows.length) return;
  const btn = document.getElementById('confirmCheckoutBtn');
  btn.disabled = true;
  btn.textContent = 'Processing…';
  const items = rows.map(r => ({id: r.dataset.id, source: r.dataset.source}));
  try {
    const res  = await fetch('transaction_action.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'checkout',items})});
    const data = await res.json();
    if(data.success){
      rows.forEach(r => {
        const statusCell = r.querySelector('.status-cell');
        if(r.dataset.source === 'billiard' && r.dataset.status !== 'Reserved'){
          r.dataset.status = 'Done';
          if(statusCell){
            statusCell.innerHTML = '<span class="status-done">Done</span><span class="paid-badge">✓ Paid</span>';
          }
        } else {
          if(statusCell){
            const unpaid = statusCell.querySelector('.unpaid-badge'); // removes "Pending"
            if(unpaid) unpaid.remove();
            if(!statusCell.querySelector('.paid-badge')){
              statusCell.insertAdjacentHTML('beforeend','<span class="paid-badge">✓ Paid</span>');
            }
          }
        }
      });
      clearAll();
      document.getElementById('checkoutOverlay').classList.remove('open');
    } else {
      alert(data.message || 'Checkout failed.');
    }
  } catch(e){ alert('Server error.'); }
  btn.disabled = false;
  btn.textContent = '✅ Confirm Checkout';
}
const THERMAL_CSS = `
  *{margin:0;padding:0;box-sizing:border-box;}
  body{font-family:'Courier New',Courier,monospace;font-size:13px;color:#000;background:#fff;max-width:360px;margin:0 auto;padding:20px 16px;}
  .c{text-align:center;}
  .b{font-weight:bold;}
  .lg{font-size:18px;}
  .row{display:flex;justify-content:space-between;align-items:baseline;}
  .dash{border:none;border-top:1px dashed #000;margin:7px 0;}
  .eq{text-align:center;white-space:nowrap;overflow:hidden;margin:4px 0;letter-spacing:1px;}
  .item-name{margin-top:5px;}
  .item-detail{display:flex;justify-content:space-between;padding-left:14px;}
  .sp{height:8px;}
  .total-line{display:flex;justify-content:space-between;font-weight:bold;font-size:18px;margin:6px 0;}
  @media print{body{padding:0 8px;}}
`;

function buildThermalHtml(orders, cash, discountAmt=0) {
  const now = new Date();
  const dd   = String(now.getDate()).padStart(2,'0');
  const mm   = String(now.getMonth()+1).padStart(2,'0');
  const yyyy = now.getFullYear();
  const hh   = String(now.getHours()).padStart(2,'0');
  const min  = String(now.getMinutes()).padStart(2,'0');

  const codes = orders.map(o => o.code).join(', ');
  let allItems = [];
  orders.forEach(o => { if(o.items) allItems = allItems.concat(o.items); });

  const subtotal  = orders.reduce((s,o) => s + (parseFloat(o.total)||0), 0);
  const finalTotal= subtotal - discountAmt;
  const totalQty  = allItems.reduce((s,i) => s + (parseFloat(i.quantity)||0), 0);

  let itemsHtml = '';
  allItems.forEach(item => {
    const qty  = parseFloat(item.quantity||1).toFixed(1);
    const price= parseFloat(item.price||0).toFixed(2);
    const line = parseFloat(item.line_total||0).toFixed(2);
    itemsHtml += `<div class="item-name">${item.name}</div>
<div class="item-detail"><span>${qty} x &nbsp;&nbsp;${price}</span><span>${line}</span></div>`;
  });

  const discountHtml = discountAmt > 0 ? `
<div class="row"><span>SUBTOTAL</span><span>${subtotal.toFixed(2)}</span></div>
<div class="row"><span>PWD/SENIOR DISC (20%)</span><span>-${discountAmt.toFixed(2)}</span></div>` : `
<div class="row"><span>SUBTOTAL</span><span>${subtotal.toFixed(2)}</span></div>`;

  let paymentHtml = '';
  if(cash > 0){
    const change = cash - finalTotal;
    paymentHtml = `<hr class="dash"/>
<div class="row"><span>PAYMENT RECEIVED:</span><span>${cash.toLocaleString('en-PH',{minimumFractionDigits:2})}</span></div>
<div class="item-detail"><span>Cash</span><span>${cash.toLocaleString('en-PH',{minimumFractionDigits:2})}</span></div>
<div class="row"><span>CHANGE AMOUNT:</span><span>${change.toLocaleString('en-PH',{minimumFractionDigits:2})}</span></div>`;
  }

  return `<div class="c lg b">Brew n Break by Bamboo Court</div>
<div class="c">#17</div>
<div class="c">Contact Number</div>
<hr class="dash"/>
<div>INV#: ${codes}</div>
<div class="row"><span>DATE: ${mm}/${dd}/${yyyy}</span><span>TIME: ${hh}:${min}</span></div>
<hr class="dash"/>
${itemsHtml}
<hr class="dash"/>
<div class="row"><span>${totalQty.toFixed(1)}</span><span>Item(s)</span></div>
<div class="eq">================================</div>
${discountHtml}
<div class="sp"></div>
<div class="total-line"><span>TOTAL</span><span>${finalTotal.toFixed(2)}</span></div>
${paymentHtml}
<div class="sp"></div><div class="sp"></div>
<div class="c">Acknowledgement Receipt</div>
<div class="c b">Thank you!</div>`;
}

function openPrintWindow(bodyHtml) {
  const win = window.open('', '_blank', 'width=440,height=750');
  win.document.write(`<!DOCTYPE html><html><head><meta charset="UTF-8"/><title>Receipt</title><style>${THERMAL_CSS}
.bp-header{background:rgba(240,192,64,0.1);padding:11px 14px;border-bottom:1px solid rgba(240,192,64,0.2);display:flex;align-items:center;justify-content:space-between;gap:8px;}
.bp-title{font-size:12px;font-weight:700;color:#f0c040;display:flex;align-items:center;gap:5px;}
.bp-close{background:none;border:none;color:rgba(255,255,255,0.45);cursor:pointer;font-size:18px;line-height:1;padding:0;transition:color .2s;}
.bp-close:hover{color:#fff;}
.bp-item{padding:10px 14px;border-bottom:1px solid rgba(255,255,255,0.06);font-size:12px;}
.bp-item:last-child{border-bottom:none;}
.bp-item-title{font-weight:700;color:#f0c040;margin-bottom:3px;}
.bp-item-msg{color:rgba(255,255,255,0.6);line-height:1.4;}
</style><style id="responsive-overrides">
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
</head><body>${bodyHtml}<div id="bellPopup">
  <div class="bp-header">
    <span class="bp-title">⚠️ Session Expiring Soon</span>
    <button class="bp-close" onclick="closeBellPopup()">×</button>
  </div>
  <div id="bellPopupItems"></div>
</div>
<${'script'}>
(function(){
  const shownIds = new Set();
  async function pollBell(){
    try {
      const res  = await fetch('notification_check.php');
      const data = await res.json();
      const alerts = (data.alerts || []).filter(a => (a.secs_left ?? 999) <= 300);
      const badge = document.getElementById('bellBadge');
      const popup = document.getElementById('bellPopup');
      const items = document.getElementById('bellPopupItems');
      if (!badge || !popup || !items) return;
      if (alerts.length > 0) {
        badge.textContent = alerts.length;
        badge.style.display = 'flex';
        items.innerHTML = alerts.map(a =>
          '<div class="bp-item"><div class="bp-item-title">'+a.title+'</div><div class="bp-item-msg">'+a.message+'</div></div>'
        ).join('');
        const fresh = alerts.filter(a => !shownIds.has(String(a.id)));
        if (fresh.length) {
          popup.style.display = 'block';
          fresh.forEach(a => shownIds.add(String(a.id)));
        }
      } else {
        badge.style.display = 'none';
      }
    } catch(e) {}
  }
  window.closeBellPopup = function(){ document.getElementById('bellPopup').style.display = 'none'; };
  pollBell();
  setInterval(pollBell, 30000);
})();
<${'/'+'script'}>
</body></html>`);
  win.document.close();
  win.onload = () => { win.focus(); win.print(); win.close(); };
}

async function printCheckout() {
  const rows = getSelectedRows();
  if(!rows.length) return;
  const cash       = parseFloat(document.getElementById('coCashInput').value)||0;
  const discountAmt= _discountApplied ? _cafeSubtotal * 0.20 : 0;
  const refs = rows.map(r => ({id: r.dataset.id, source: r.dataset.source}));

  try {
    const res  = await fetch('get_receipt.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({orders:refs})});
    const data = await res.json();
    if(data.success && data.orders?.length){
      openPrintWindow(buildThermalHtml(data.orders, cash, discountAmt)); return;
    }
  } catch(e){}
  const fallback = rows.map(r => ({
    code: r.dataset.code, total: parseFloat(r.dataset.amount)||0,
    items:[{name:r.dataset.desc, quantity:1, price:parseFloat(r.dataset.amount)||0, line_total:parseFloat(r.dataset.amount)||0}]
  }));
  openPrintWindow(buildThermalHtml(fallback, cash, discountAmt));
}

let _invoiceRow = null;
function viewInvoice(row){
  _invoiceRow = row;
  document.getElementById('invCode').textContent=row.dataset.code;
  document.getElementById('invDate').textContent=row.dataset.dt;
  document.getElementById('invStatus').textContent=row.dataset.status;
  const amt=parseFloat(row.dataset.amount)||0;
  document.getElementById('invTotal').textContent='₱'+amt.toLocaleString('en-PH',{minimumFractionDigits:2});
  document.getElementById('invItems').innerHTML=
    `<tr><td>${row.dataset.typeLabel||''} — ${row.dataset.desc}</td><td>₱${amt.toLocaleString('en-PH',{minimumFractionDigits:2})}</td></tr>`;
  document.getElementById('invoiceOverlay').classList.add('open');
  document.querySelectorAll('.action-wrap').forEach(w=>w.classList.remove('open'));
}

async function printInvoice() {
  const r = _invoiceRow;
  if(!r) return;
  try {
    const res  = await fetch('get_receipt.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({orders:[{id:r.dataset.id,source:r.dataset.source}]})});
    const data = await res.json();
    if(data.success && data.orders?.length){
      openPrintWindow(buildThermalHtml(data.orders, 0)); return;
    }
  } catch(e){}
  openPrintWindow(buildThermalHtml([{
    code:r.dataset.code, total:parseFloat(r.dataset.amount)||0,
    items:[{name:r.dataset.desc, quantity:1, price:parseFloat(r.dataset.amount)||0, line_total:parseFloat(r.dataset.amount)||0}]
  }], 0));
}

function printInvoiceRow(row){
  viewInvoice(row);
  setTimeout(()=>printInvoice(),300);
}

let _editRow=null;
function openEdit(row){
  _editRow=row;
  document.getElementById('editId').value=row.dataset.id;
  document.getElementById('editSrc').value=row.dataset.source;
  document.getElementById('editCode').textContent=row.dataset.code;
  document.getElementById('editStatus').value=row.dataset.status||'Done';
  document.getElementById('editAmount').value=parseFloat(row.dataset.amount||0).toFixed(2);
  document.getElementById('editOverlay').classList.add('open');
  document.querySelectorAll('.action-wrap').forEach(w=>w.classList.remove('open'));
}

async function submitEdit(){
  const id=document.getElementById('editId').value;
  const source=document.getElementById('editSrc').value;
  const status=document.getElementById('editStatus').value;
  const amount=parseFloat(document.getElementById('editAmount').value)||0;
  try{
    const res=await fetch('transaction_action.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'edit',id,source,status,amount})});
    const data=await res.json();
    if(data.success){
      if(_editRow){
        _editRow.dataset.status=status;
        _editRow.dataset.amount=amount;
        const sc={done:'status-done',pending:'status-pending',cancelled:'status-cancelled',ongoing:'status-ongoing'}[status.toLowerCase()]||'status-pending';
        const statusCell=_editRow.querySelector('td:nth-child(6) span');
        if(statusCell){statusCell.className=sc;statusCell.textContent=status;}
        const amtCell=_editRow.querySelector('td:nth-child(5)');
        if(amtCell) amtCell.textContent='₱'+amount.toLocaleString('en-PH',{minimumFractionDigits:2});
      }
      document.getElementById('editOverlay').classList.remove('open');
    } else {
      alert(data.message||'Error saving changes.');
    }
  } catch(e){ alert('Server error.'); }
}
applyFilters();

let _deleteTxRow = null;
function deleteTransaction(row){
  _deleteTxRow = row;
  document.getElementById('deleteTxCode').textContent = row.dataset.code;
  document.getElementById('deleteTxOverlay').classList.add('open');
  document.querySelectorAll('.action-wrap').forEach(w=>w.classList.remove('open'));
}
function cancelDeleteTx(){ document.getElementById('deleteTxOverlay').classList.remove('open'); _deleteTxRow=null; }
function closeDeleteTx(e){ if(e.target===document.getElementById('deleteTxOverlay')) cancelDeleteTx(); }

document.getElementById('confirmTxDeleteBtn').addEventListener('click', async function(){
  if(!_deleteTxRow) return;
  this.disabled=true; this.textContent='Deleting…';
  try{
    const res=await fetch('transaction_action.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'delete',id:_deleteTxRow.dataset.id,source:_deleteTxRow.dataset.source})});
    const data=await res.json();
    if(data.success){ _deleteTxRow.remove(); cancelDeleteTx(); clearAll(); }
    else{ this.disabled=false; this.textContent='Delete'; alert(data.message||'Error deleting transaction.'); }
  } catch(e){ this.disabled=false; this.textContent='Delete'; alert('Server error.'); }
});
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

