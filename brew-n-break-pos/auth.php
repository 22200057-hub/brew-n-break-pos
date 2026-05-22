<?php
// Included at the top of every admin page
if (session_status() === PHP_SESSION_NONE) session_start();

// Prevent browser from caching admin pages
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');

if (empty($_SESSION['user_id'])) {
    header('Location: ../login.html');
    exit;
}

// Keep session photo in sync with DB on every page load
$_auth_conn = new mysqli('localhost', 'root', '', 'brew_n_break');
if (!$_auth_conn->connect_error) {
    $_auth_stmt = $_auth_conn->prepare("SELECT photo FROM users WHERE id = ? LIMIT 1");
    $_auth_stmt->bind_param('i', $_SESSION['user_id']);
    $_auth_stmt->execute();
    $_auth_stmt->bind_result($_auth_photo);
    if ($_auth_stmt->fetch()) {
        $_SESSION['photo'] = $_auth_photo ?? '';
    }
    $_auth_stmt->close();
    $_auth_conn->close();
}
unset($_auth_conn, $_auth_stmt, $_auth_photo);
