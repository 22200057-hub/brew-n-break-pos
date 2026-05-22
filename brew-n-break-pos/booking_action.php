<?php
// booking_action.php
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
        $code     = trim($input['code']    ?? '');
        $guest    = trim($input['guest']   ?? '');
        $room     = trim($input['room']    ?? '');
        $status   = $input['status']   ?? 'Confirmed';
        $checkIn  = $input['checkIn']  ?? '';
        $checkOut = $input['checkOut'] ?? '';

        if (!$code || !$guest || !$checkIn || !$checkOut) {
            echo json_encode(['success'=>false,'message'=>'Required fields missing.']); exit;
        }

        $stmt = $conn->prepare("INSERT INTO bookings (booking_code, guest_name, room, check_in, check_out, status, created_at) VALUES (?,?,?,?,?,?,NOW())");
        $stmt->bind_param('ssssss', $code, $guest, $room, $checkIn, $checkOut, $status);
        $stmt->execute();
        echo json_encode(['success' => true]);

    } elseif ($action === 'edit_status') {
        $id     = intval($input['id'] ?? 0);
        $status = $input['status'] ?? 'Confirmed';
        $stmt   = $conn->prepare("UPDATE bookings SET status=? WHERE id=?");
        $stmt->bind_param('si', $status, $id);
        $stmt->execute();
        echo json_encode(['success' => true]);

    } elseif ($action === 'delete') {
        $id   = intval($input['id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM bookings WHERE id=?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        echo json_encode(['success' => true]);

    } elseif ($action === 'next_code') {
        $r    = $conn->query("SELECT booking_code FROM bookings ORDER BY id DESC LIMIT 1");
        $last = ($r && $r->num_rows) ? $r->fetch_row()[0] : '';
        if (preg_match('/^([A-Za-z]+)(\d+)$/', $last, $m)) {
            $code = $m[1] . str_pad(intval($m[2]) + 1, strlen($m[2]), '0', STR_PAD_LEFT);
        } else {
            $code = 'BK001';
        }
        echo json_encode(['success'=>true,'code'=>$code]);

    } else {
        echo json_encode(['success'=>false,'message'=>'Invalid action.']);
    }

    $conn->close();
} catch (Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Server error.']);
}
