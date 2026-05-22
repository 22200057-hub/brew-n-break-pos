<?php
session_start();
error_reporting(0);
ini_set('display_errors', 0);
mysqli_report(MYSQLI_REPORT_OFF);
header('Content-Type: application/json');

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'brew_n_break');

$alerts = [];

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if (!$conn->connect_error) {
        $r = $conn->query("
            SELECT
                bs.id,
                bs.table_name,
                bs.customer_name,
                bs.end_time,
                GREATEST(0, TIME_TO_SEC(TIMEDIFF(bs.end_time, TIME(NOW())))) AS secs_left
            FROM billiard_sessions bs
            WHERE bs.status IN ('Ongoing','Start')
            HAVING secs_left <= 600
            ORDER BY secs_left ASC
        ");
        if ($r) while ($row = $r->fetch_assoc()) {
            $secs = intval($row['secs_left']);
            $end  = date('g:i A', strtotime($row['end_time']));
            $alerts[] = [
                'id'       => $row['id'],
                'type'     => $secs === 0 ? 'expired' : ($secs <= 300 ? 'warning' : 'info'),
                'title'    => $row['table_name'].($secs===0?' Session Expired':' — 5 min warning'),
                'message'  => $row['customer_name'].' · Ends '.$end,
                'secs_left'=> $secs,
            ];
        }
        $r = $conn->query("
            SELECT id, table_name, customer_name, end_time
            FROM billiard_sessions
            WHERE status = 'Done'
              AND DATE(created_at) = CURDATE()
              AND TIME(NOW()) >= end_time
              AND TIME_TO_SEC(TIMEDIFF(TIME(NOW()), end_time)) <= 600
            ORDER BY end_time DESC
        ");
        if ($r) while ($row = $r->fetch_assoc()) {
            $end = date('g:i A', strtotime($row['end_time']));
            $alerts[] = [
                'id'       => 'done_'.$row['id'],
                'type'     => 'done',
                'title'    => $row['table_name'].' Session Ended',
                'message'  => $row['customer_name'].' · Ended '.$end,
                'secs_left'=> 0,
            ];
        }

        $conn->close();
    }
} catch (Throwable $e) {}

echo json_encode(['alerts' => $alerts]);
