<?php
// user_action.php — handles add, edit, delete users
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
    if ($conn->connect_error) throw new Exception('DB connection failed');

    if ($action === 'add') {
        $name     = trim($input['name'] ?? '');
        $username = trim($input['username'] ?? '');
        $password = $input['password'] ?? '';
        $role     = $input['role'] ?? 'Staff';
        $status   = $input['status'] ?? 'Active';

        if (!$name || !$username || !$password) {
            echo json_encode(['success'=>false,'message'=>'All fields are required.']); exit;
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("INSERT INTO users (name, username, password, role, status) VALUES (?,?,?,?,?)");
        $stmt->bind_param('sssss', $name, $username, $hash, $role, $status);
        $stmt->execute();
        echo json_encode(['success'=>true]);

    } elseif ($action === 'edit') {
        $id       = (int)($input['id'] ?? 0);
        $name     = trim($input['name'] ?? '');
        $username = trim($input['username'] ?? '');
        $password = $input['password'] ?? '';
        $role     = $input['role'] ?? 'Staff';
        $status   = $input['status'] ?? 'Active';

        if ($password) {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("UPDATE users SET name=?, username=?, password=?, role=?, status=? WHERE id=?");
            $stmt->bind_param('sssssi', $name, $username, $hash, $role, $status, $id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET name=?, username=?, role=?, status=? WHERE id=?");
            $stmt->bind_param('ssssi', $name, $username, $role, $status, $id);
        }
        $stmt->execute();
        echo json_encode(['success'=>true]);

    } elseif ($action === 'delete') {
        $id   = (int)($input['id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
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
