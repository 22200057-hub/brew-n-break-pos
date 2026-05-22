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

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) throw new Exception('DB error');
    $conn->query("ALTER TABLE products ADD COLUMN IF NOT EXISTS category VARCHAR(60) DEFAULT 'Coffee'");
    $conn->query("ALTER TABLE products ADD COLUMN IF NOT EXISTS description VARCHAR(255) DEFAULT ''");
    $conn->query("ALTER TABLE products ADD COLUMN IF NOT EXISTS available TINYINT(1) DEFAULT 1");

    if ($action === 'add') {
        $name      = trim($input['name'] ?? '');
        $category  = $input['category'] ?? 'Coffee';
        $price     = floatval($input['price'] ?? 0);
        $desc      = trim($input['desc'] ?? '');
        $available = intval($input['available'] ?? 1);

        if (!$name) { echo json_encode(['success'=>false,'message'=>'Name required.']); exit; }

        $stmt = $conn->prepare("INSERT INTO products (name, category, price, description, available) VALUES (?,?,?,?,?)");
        $stmt->bind_param('ssdsi', $name, $category, $price, $desc, $available);
        $stmt->execute();
        echo json_encode(['success'=>true]);

    } elseif ($action === 'edit') {
        $id        = intval($input['id'] ?? 0);
        $name      = trim($input['name'] ?? '');
        $category  = $input['category'] ?? 'Coffee';
        $price     = floatval($input['price'] ?? 0);
        $desc      = trim($input['desc'] ?? '');
        $available = intval($input['available'] ?? 1);

        $stmt = $conn->prepare("UPDATE products SET name=?, category=?, price=?, description=?, available=? WHERE id=?");
        $stmt->bind_param('ssdsii', $name, $category, $price, $desc, $available, $id);
        $stmt->execute();
        echo json_encode(['success'=>true]);

    } elseif ($action === 'delete') {
        $id   = intval($input['id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM products WHERE id=?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        echo json_encode(['success'=>true]);

    } elseif ($action === 'list') {
        $r = $conn->query("SELECT id, name, price, category FROM products WHERE available=1 ORDER BY category, name");
        $products = [];
        if ($r) while ($row = $r->fetch_assoc()) $products[] = $row;
        echo json_encode(['success'=>true,'products'=>$products]);

    } else {
        echo json_encode(['success'=>false,'message'=>'Invalid action.']);
    }

    $conn->close();
} catch (Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Server error.']);
}
