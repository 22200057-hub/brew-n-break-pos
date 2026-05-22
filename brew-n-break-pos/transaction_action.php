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

$input  = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$source = $input['source'] ?? '';
$id     = intval($input['id'] ?? 0);

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) throw new Exception('DB error');

    if ($action === 'delete') {
        if ($source === 'cafe') {
            $stmt = $conn->prepare("DELETE FROM order_items WHERE order_id=?");
            $stmt->bind_param('i', $id); $stmt->execute();
            $stmt = $conn->prepare("DELETE FROM orders WHERE id=?");
            $stmt->bind_param('i', $id); $stmt->execute();
        } elseif ($source === 'billiard') {
            $stmt = $conn->prepare("DELETE FROM billiard_sessions WHERE id=?");
            $stmt->bind_param('i', $id); $stmt->execute();
        }
        echo json_encode(['success' => true]);

    } elseif ($action === 'edit') {
        $status = $input['status'] ?? '';
        $amount = floatval($input['amount'] ?? 0);
        if ($source === 'cafe') {
            $stmt = $conn->prepare("UPDATE orders SET status=?, total_amount=? WHERE id=?");
            $stmt->bind_param('sdi', $status, $amount, $id);
            $stmt->execute();
        } elseif ($source === 'billiard') {
            $stmt = $conn->prepare("UPDATE billiard_sessions SET status=?, amount=? WHERE id=?");
            $stmt->bind_param('sdi', $status, $amount, $id);
            $stmt->execute();
        }
        echo json_encode(['success' => true]);

    } elseif ($action === 'checkout') {
        $items = $input['items'] ?? [];
        foreach ($items as $item) {
            $itemId     = intval($item['id'] ?? 0);
            $itemSource = $item['source'] ?? '';
            if ($itemSource === 'cafe' && $itemId) {
                $stmt = $conn->prepare("UPDATE orders SET status='Done' WHERE id=?");
                $stmt->bind_param('i', $itemId);
                $stmt->execute();
            } elseif ($itemSource === 'billiard' && $itemId) {
                $sr = $conn->prepare("SELECT status, table_name FROM billiard_sessions WHERE id=?");
                $sr->bind_param('i', $itemId);
                $sr->execute();
                $sr->bind_result($curStatus, $tableName);
                $sr->fetch();
                $sr->close();

                if ($curStatus !== 'Reserved') {
                    $stmt = $conn->prepare("UPDATE billiard_sessions SET status='Done' WHERE id=?");
                    $stmt->bind_param('i', $itemId);
                    $stmt->execute();
                    if ($tableName) {
                        $u = $conn->prepare("UPDATE billiard_tables SET status='Available' WHERE table_name=?");
                        $u->bind_param('s', $tableName);
                        $u->execute();
                    }
                }
            }
        }
        echo json_encode(['success' => true]);

    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
    }

    $conn->close();
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Server error.']);
}
