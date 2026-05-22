<?php
session_start();
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'brew_n_break');

$allTables = ['Outdoor 1','Outdoor 2','Outdoor 3','Indoor 1'];
$tableRows = [];

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) throw new Exception('DB error');

    $r = $conn->query("
        SELECT
            bs.table_name,
            bs.status,
            bs.customer_name,
            GREATEST(0, TIME_TO_SEC(TIMEDIFF(bs.end_time, TIME(NOW())))) AS secs_left
        FROM billiard_sessions bs
        INNER JOIN (
            SELECT table_name, MAX(id) as mid FROM billiard_sessions GROUP BY table_name
        ) latest ON bs.id = latest.mid
    ");

    $liveMap = [];
    if ($r) while ($row = $r->fetch_assoc()) $liveMap[$row['table_name']] = $row;

    foreach ($allTables as $tbl) {
        if (isset($liveMap[$tbl])) {
            $s = $liveMap[$tbl];
            $statusLower = strtolower($s['status']);
            if (in_array($statusLower, ['ongoing', 'start'])) {
                $secs = intval($s['secs_left']);
                $hoursLeft = $secs > 0
                    ? sprintf('%02d:%02d:%02d', floor($secs/3600), floor(($secs%3600)/60), $secs%60)
                    : 'Overtime';
                $tableRows[] = ['table_name'=>$tbl,'status'=>$s['status'],'hours_left'=>$hoursLeft,'customer'=>$s['customer_name']??''];
            } elseif ($statusLower === 'reserved') {
                $tableRows[] = ['table_name'=>$tbl,'status'=>'Reserved','hours_left'=>'–','customer'=>$s['customer_name']??''];
            } else {
                $tableRows[] = ['table_name'=>$tbl,'status'=>'Available','hours_left'=>'–','customer'=>''];
            }
        } else {
            $tableRows[] = ['table_name'=>$tbl,'status'=>'Available','hours_left'=>'–','customer'=>''];
        }
    }
    $conn->close();
} catch (Throwable $e) {}

echo json_encode(['success' => true, 'tables' => $tableRows]);
