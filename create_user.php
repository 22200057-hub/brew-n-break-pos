<?php
$conn = new mysqli('localhost', 'root', '', 'brew_n_break');
if ($conn->connect_error) die("DB Error: " . $conn->connect_error);

// Add missing columns if they don't exist
$conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS name VARCHAR(120) DEFAULT ''");
$conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS first_name VARCHAR(60) DEFAULT ''");
$conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS last_name VARCHAR(60) DEFAULT ''");
$conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS phone VARCHAR(30) DEFAULT ''");
$conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS photo VARCHAR(255) DEFAULT ''");
$conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS status ENUM('Active','Deactivated') DEFAULT 'Active'");

$hash = password_hash('Admin@1234', PASSWORD_BCRYPT);
$conn->query("DELETE FROM users WHERE username='admin'");
$stmt = $conn->prepare("INSERT INTO users (name, first_name, last_name, username, password, role, status) VALUES (?,?,?,?,?,?,?)");
$n='Admin User';$f='Admin';$l='User';$u='admin';$r='admin';$s='Active';
$stmt->bind_param('sssssss',$n,$f,$l,$u,$hash,$r,$s);
$stmt->execute();
echo "✅ Done! <a href='login.html'>Go to Login</a>";
?>