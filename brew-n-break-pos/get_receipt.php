<?php
session_start();
error_reporting(0);
ini_set('display_errors', 0);
mysqli_report(MYSQLI_REPORT_OFF);
header('Content-Type: application/json');

$input  = json_decode(file_get_contents('php://input'), true);
$orders = $input['orders'] ?? [];

if (empty($orders)) { echo json_encode(['success' => false]); exit; }

$conn = new mysqli('localhost', 'root', '', 'brew_n_break');
if ($conn->connect_error) { echo json_encode(['success' => false, 'message' => 'DB error']); exit; }

$result = [];

foreach ($orders as $o) {
    $id     = (int)($o['id'] ?? 0);
    $source = $o['source'] ?? '';
    if (!$id) continue;

    if ($source === 'cafe') {
        $stmt = $conn->prepare("SELECT order_code, total_amount, type, created_at FROM orders WHERE id = ? LIMIT 1");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $order = $stmt->get_result()->fetch_assoc();
        if (!$order) continue;

        $stmt2 = $conn->prepare("
            SELECT p.name, oi.quantity,
                   COALESCE(p.price, 0) AS price,
                   (oi.quantity * COALESCE(p.price, 0)) AS line_total
            FROM order_items oi
            JOIN products p ON p.id = oi.product_id
            WHERE oi.order_id = ?
            ORDER BY p.name
        ");
        $stmt2->bind_param('i', $id);
        $stmt2->execute();
        $items = [];
        $r2 = $stmt2->get_result();
        while ($row = $r2->fetch_assoc()) $items[] = $row;

        $result[] = [
            'source' => 'cafe',
            'code'   => $order['order_code'],
            'type'   => $order['type'],
            'total'  => (float)$order['total_amount'],
            'dt'     => $order['created_at'],
            'items'  => $items,
        ];

    } elseif ($source === 'billiard') {
        $stmt = $conn->prepare("SELECT session_code, table_name, customer_name, amount, created_at FROM billiard_sessions WHERE id = ? LIMIT 1");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $bs = $stmt->get_result()->fetch_assoc();
        if (!$bs) continue;

        $result[] = [
            'source'   => 'billiard',
            'code'     => $bs['session_code'],
            'type'     => 'Billiards',
            'total'    => (float)$bs['amount'],
            'dt'       => $bs['created_at'],
            'customer' => $bs['customer_name'] ?? '',
            'items'    => [[
                'name'       => $bs['table_name'] ?? 'Billiard Session',
                'quantity'   => 1,
                'price'      => (float)$bs['amount'],
                'line_total' => (float)$bs['amount'],
            ]],
        ];
    }
}

$conn->close();
echo json_encode(['success' => true, 'orders' => $result]);
