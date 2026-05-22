<?php
// report_action.php
session_start();
error_reporting(0);
ini_set('display_errors', 0);
mysqli_report(MYSQLI_REPORT_OFF);
header('Content-Type: application/json');

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'brew_n_break');

$input  = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) throw new Exception('DB error');

    if ($action === 'generate') {
        $type  = $input['type']  ?? 'Transaction Report';
        $start = $input['start'] ?? '';
        $end   = $input['end']   ?? '';

        if (!$start || !$end) { echo json_encode(['success'=>false,'message'=>'Dates required.']); exit; }

        // Auto-generate report code
        $r    = $conn->query("SELECT COUNT(*) FROM reports");
        $count= $r ? $r->fetch_row()[0] + 1 : 1;
        $code = 'RID'.str_pad($count, 4, '0', STR_PAD_LEFT);

        $stmt = $conn->prepare("INSERT INTO reports (report_code, type, date_from, date_to, created_at) VALUES (?,?,?,?,NOW())");
        $stmt->bind_param('ssss', $code, $type, $start, $end);
        $stmt->execute();
        echo json_encode(['success'=>true, 'code'=>$code]);

    } elseif ($action === 'fetch') {
        $start = $conn->real_escape_string($input['start'] ?? '');
        $end   = $conn->real_escape_string($input['end']   ?? '');
        $type  = $input['type'] ?? 'Transaction Report';
        $transactions = [];

        // Always include cafe orders
        if (in_array($type, ['Transaction Report','Daily Report','Cafe Report','Revenue Report'])) {
            $r = $conn->query("SELECT o.order_code as code, GROUP_CONCAT(p.name SEPARATOR ', ') as product, o.type, o.total_amount as amount, o.status, DATE_FORMAT(o.created_at,'%h:%i %p %M %d, %Y') as date FROM orders o LEFT JOIN order_items oi ON oi.order_id=o.id LEFT JOIN products p ON p.id=oi.product_id WHERE DATE(o.created_at) BETWEEN '$start' AND '$end' GROUP BY o.id");
            if ($r) while ($row = $r->fetch_assoc()) $transactions[] = $row;
        }

        // Include billiard sessions
        if ($type === 'Transaction Report' || $type === 'Daily Report' || $type === 'Billiard Report' || $type === 'Revenue Report') {
            $r = $conn->query("SELECT bs.session_code as code, CONCAT(bs.start_time,' – ',bs.end_time) as product, 'Billiards' as type, bs.amount, bs.status, DATE_FORMAT(bs.created_at,'%h:%i %p %M %d, %Y') as date FROM billiard_sessions bs WHERE DATE(bs.created_at) BETWEEN '$start' AND '$end'");
            if ($r) while ($row = $r->fetch_assoc()) $transactions[] = $row;
        }

        // Include bookings
        if ($type === 'Transaction Report' || $type === 'Daily Report' || $type === 'Booking Report' || $type === 'Revenue Report') {
            $r = $conn->query("SELECT b.booking_code as code, CONCAT(b.room,' – ',b.guest_name) as product, 'Booking' as type, 0 as amount, b.status, DATE_FORMAT(b.created_at,'%h:%i %p %M %d, %Y') as date FROM bookings b WHERE DATE(b.created_at) BETWEEN '$start' AND '$end'");
            if ($r) while ($row = $r->fetch_assoc()) $transactions[] = $row;
        }

        echo json_encode(['success'=>true, 'transactions'=>$transactions]);

    } elseif ($action === 'delete') {
        $id   = intval($input['id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM reports WHERE id=?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        echo json_encode(['success'=>true]);

    } else {
        echo json_encode(['success'=>false,'message'=>'Invalid action.']);
    }

    $conn->close();
} catch (Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Server error.']);
}
