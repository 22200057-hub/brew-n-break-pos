<?php
// billiard_action.php
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

    if ($action === 'add') {
        $code   = trim($input['code']   ?? '');
        $name   = trim($input['name']   ?? '');
        $table  = trim($input['table']  ?? '');
        $amount = floatval($input['amount'] ?? 0);
        $start  = $input['start']  ?? '';
        $end    = $input['end']    ?? '';
        $status = $input['status'] ?? 'Start';

        if (!$code || !$name || !$start || !$end) {
            echo json_encode(['success'=>false,'message'=>'Required fields missing.']); exit;
        }

        $stmt = $conn->prepare("INSERT INTO billiard_sessions (session_code, customer_name, table_name, start_time, end_time, amount, status, created_at) VALUES (?,?,?,?,?,?,?,NOW())");
        $stmt->bind_param('sssssds', $code, $name, $table, $start, $end, $amount, $status);
        $stmt->execute();
        echo json_encode(['success' => true]);

    } elseif ($action === 'edit_status') {
        $id     = intval($input['id'] ?? 0);
        $status = $input['status'] ?? 'Start';
        $stmt   = $conn->prepare("UPDATE billiard_sessions SET status=? WHERE id=?");
        $stmt->bind_param('si', $status, $id);
        $stmt->execute();

        // Update billiard_tables status too
        $r = $conn->query("SELECT table_name FROM billiard_sessions WHERE id=$id");
        if ($r) {
            $row = $r->fetch_assoc();
            $tbl = $conn->real_escape_string($row['table_name']);
            $tblStatus = match($status) {
                'Start'    => 'Occupied',
                'Ongoing'  => 'Occupied',
                'Reserved' => 'Reserved',
                default    => 'Available',
            };
            $conn->query("UPDATE billiard_tables SET status='$tblStatus' WHERE table_name='$tbl'");
        }
        echo json_encode(['success' => true]);

    } elseif ($action === 'delete') {
        $id   = intval($input['id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM billiard_sessions WHERE id=?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        echo json_encode(['success' => true]);

    } elseif ($action === 'next_code') {
        $r    = $conn->query("SELECT session_code FROM billiard_sessions ORDER BY id DESC LIMIT 1");
        $last = ($r && $r->num_rows) ? $r->fetch_row()[0] : '';
        if (preg_match('/^([A-Za-z]+)(\d+)$/', $last, $m)) {
            $code = $m[1] . str_pad(intval($m[2]) + 1, strlen($m[2]), '0', STR_PAD_LEFT);
        } else {
            $code = 'BT001';
        }
        echo json_encode(['success'=>true,'code'=>$code]);

    } else {
        echo json_encode(['success'=>false,'message'=>'Invalid action.']);
    }

    $conn->close();
} catch (Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Server error.']);
}
