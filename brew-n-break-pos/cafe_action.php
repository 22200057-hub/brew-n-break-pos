<?php
// cafe_action.php
session_start();
error_reporting(E_ALL);
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
    if ($conn->connect_error) {
        echo json_encode(['success'=>false,'message'=>'DB connection failed: '.$conn->connect_error]);
        exit;
    }

    // Ensure tables exist
    $conn->query("CREATE TABLE IF NOT EXISTS products (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(120) NOT NULL,
        price DECIMAL(10,2) NOT NULL DEFAULT 0,
        category VARCHAR(60) DEFAULT 'Coffee',
        description VARCHAR(255) DEFAULT '',
        available TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $conn->query("CREATE TABLE IF NOT EXISTS orders (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        order_code VARCHAR(30) NOT NULL,
        type VARCHAR(30) DEFAULT 'Coffee',
        status VARCHAR(30) DEFAULT 'Pending',
        total_amount DECIMAL(10,2) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $conn->query("CREATE TABLE IF NOT EXISTS order_items (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        order_id INT UNSIGNED NOT NULL,
        product_id INT UNSIGNED NOT NULL,
        quantity INT DEFAULT 1,
        price DECIMAL(10,2) DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    if ($action === 'add') {
        $orderCode = trim($input['orderCode'] ?? '');
        $type      = $input['type']   ?? 'Coffee';
        $status    = $input['status'] ?? 'Pending';
        $total     = floatval($input['total'] ?? 0);
        $items     = $input['items']  ?? [];

        if (!$orderCode) {
            echo json_encode(['success'=>false,'message'=>'Order ID is required.']); exit;
        }

        $stmt = $conn->prepare("INSERT INTO orders (order_code, type, status, total_amount, created_at) VALUES (?,?,?,?,NOW())");
        if (!$stmt) { echo json_encode(['success'=>false,'message'=>'Prepare failed: '.$conn->error]); exit; }
        $stmt->bind_param('sssd', $orderCode, $type, $status, $total);
        if (!$stmt->execute()) { echo json_encode(['success'=>false,'message'=>'Insert failed: '.$stmt->error]); exit; }
        $orderId = $conn->insert_id;

        foreach ($items as $item) {
            $name  = trim($item['name'] ?? '');
            $qty   = intval($item['qty']   ?? 1);
            $price = floatval($item['price'] ?? 0);
            if (!$name) continue;

            // Find or create product
            $esc = $conn->real_escape_string($name);
            $r   = $conn->query("SELECT id FROM products WHERE name='$esc' LIMIT 1");
            if ($r && $r->num_rows > 0) {
                $pid = $r->fetch_row()[0];
            } else {
                $conn->query("INSERT INTO products (name, price, category) VALUES ('$esc', $price, '".$conn->real_escape_string($type)."')");
                $pid = $conn->insert_id;
            }
            $conn->query("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES ($orderId, $pid, $qty, $price)");
        }

        echo json_encode(['success'=>true]);

    } elseif ($action === 'edit_status') {
        $id     = intval($input['id'] ?? 0);
        $status = trim($input['status'] ?? 'Pending');

        if (!$id) { echo json_encode(['success'=>false,'message'=>'Invalid order ID.']); exit; }

        $stmt = $conn->prepare("UPDATE orders SET status=? WHERE id=?");
        if (!$stmt) { echo json_encode(['success'=>false,'message'=>'Prepare failed: '.$conn->error]); exit; }
        $stmt->bind_param('si', $status, $id);
        $ok = $stmt->execute();

        if ($ok && $stmt->affected_rows >= 0) {
            echo json_encode(['success'=>true]);
        } else {
            echo json_encode(['success'=>false,'message'=>'Update failed: '.$stmt->error]);
        }

    } elseif ($action === 'delete') {
        $id = intval($input['id'] ?? 0);

        if (!$id) { echo json_encode(['success'=>false,'message'=>'Invalid order ID.']); exit; }

        // Delete items first
        $conn->query("DELETE FROM order_items WHERE order_id=$id");

        // Delete order
        $stmt = $conn->prepare("DELETE FROM orders WHERE id=?");
        if (!$stmt) { echo json_encode(['success'=>false,'message'=>'Prepare failed: '.$conn->error]); exit; }
        $stmt->bind_param('i', $id);
        $ok = $stmt->execute();

        if ($ok) {
            echo json_encode(['success'=>true]);
        } else {
            echo json_encode(['success'=>false,'message'=>'Delete failed: '.$stmt->error]);
        }

    } elseif ($action === 'next_code') {
        $r    = $conn->query("SELECT order_code FROM orders ORDER BY id DESC LIMIT 1");
        $last = ($r && $r->num_rows) ? $r->fetch_row()[0] : '';
        if (preg_match('/^([A-Za-z]+)(\d+)$/', $last, $m)) {
            $code = $m[1] . str_pad(intval($m[2]) + 1, strlen($m[2]), '0', STR_PAD_LEFT);
        } else {
            $code = 'ORDR001';
        }
        echo json_encode(['success'=>true,'code'=>$code]);

    } else {
        echo json_encode(['success'=>false,'message'=>'Invalid action: '.$action]);
    }

    $conn->close();

} catch (Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Exception: '.$e->getMessage()]);
}
