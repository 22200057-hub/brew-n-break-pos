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

    if ($action === 'update') {
        $userId    = $_SESSION['user_id'] ?? 0;
        $firstName = trim($input['firstName'] ?? '');
        $lastName  = trim($input['lastName']  ?? '');
        $username  = trim($input['username']  ?? '');
        $phone     = '+63 '.trim($input['phone'] ?? '');
        $password  = $input['password'] ?? '';
        $photoData = $input['photo'] ?? '';

        if (!$firstName || !$username) {
            echo json_encode(['success'=>false,'message'=>'First name and username are required.']); exit;
        }

        $name        = trim("$firstName $lastName");
        $photoPath   = '';
        $removePhoto = $input['removePhoto'] ?? false;

        if ($removePhoto) {
            $photoPath = '__REMOVE__';
        } elseif ($photoData && preg_match('/^data:image\/(jpeg|jpg|png|gif|webp);base64,/', $photoData, $m)) {
            $ext      = $m[1] === 'jpeg' ? 'jpg' : $m[1];
            $imgData  = base64_decode(preg_replace('/^data:image\/\w+;base64,/', '', $photoData));
            $dir      = __DIR__.'/../uploads/profiles/';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            $filename = 'user_'.$userId.'.'.$ext;
            file_put_contents($dir.$filename, $imgData);
            $photoPath = '/uploads/profiles/'.$filename;
        }

        $dbPhoto = $photoPath === '__REMOVE__' ? '' : $photoPath;

        if ($password && $photoPath) {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("UPDATE users SET name=?, first_name=?, last_name=?, username=?, phone=?, password=?, photo=? WHERE id=?");
            $stmt->bind_param('sssssssi', $name, $firstName, $lastName, $username, $phone, $hash, $dbPhoto, $userId);
        } elseif ($password) {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("UPDATE users SET name=?, first_name=?, last_name=?, username=?, phone=?, password=? WHERE id=?");
            $stmt->bind_param('ssssssi', $name, $firstName, $lastName, $username, $phone, $hash, $userId);
        } elseif ($photoPath) {
            $stmt = $conn->prepare("UPDATE users SET name=?, first_name=?, last_name=?, username=?, phone=?, photo=? WHERE id=?");
            $stmt->bind_param('ssssssi', $name, $firstName, $lastName, $username, $phone, $dbPhoto, $userId);
        } else {
            $stmt = $conn->prepare("UPDATE users SET name=?, first_name=?, last_name=?, username=?, phone=? WHERE id=?");
            $stmt->bind_param('sssssi', $name, $firstName, $lastName, $username, $phone, $userId);
        }
        $stmt->execute();
        $_SESSION['username'] = $username;
        if ($photoPath === '__REMOVE__') { $_SESSION['photo'] = ''; }
        elseif ($photoPath) { $_SESSION['photo'] = $photoPath; }
        echo json_encode(['success' => true, 'photo' => ($photoPath && $photoPath !== '__REMOVE__') ? $photoPath : null]);
    }

    $conn->close();
} catch (Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Server error.']);
}
