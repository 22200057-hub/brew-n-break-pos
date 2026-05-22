<?php
session_start();
require_once __DIR__.'/auth.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('DB_HOST','localhost');
define('DB_USER','root');
define('DB_PASS','');
define('DB_NAME','brew_n_break');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) die('DB error: '.$conn->connect_error);

$conn->query("ALTER TABLE products ADD COLUMN IF NOT EXISTS category    VARCHAR(60)  DEFAULT 'Coffee'");
$conn->query("ALTER TABLE products ADD COLUMN IF NOT EXISTS description VARCHAR(255) DEFAULT ''");
$conn->query("ALTER TABLE products ADD COLUMN IF NOT EXISTS available   TINYINT(1)   DEFAULT 1");

$items = [
    ['Pork & Shrimp Siomai',      150, 'Snacks & Bites',    '6 pcs. large pork-shrimp siomai'],
    ['Mojos',                      180, 'Snacks & Bites',    'Thick-cut potato mojos'],
    ['Chicken Poppers',            220, 'Snacks & Bites',    'Served with fries'],
    ['Classic Cheeseburger',       190, 'Snacks & Bites',    'Served with fries'],
    ['Toasted Ham & Egg Sandwich', 190, 'Snacks & Bites',    'Served with fries'],
    ['Solo Fries',                  95, 'Snacks & Bites',    'Good for 2 persons'],
    ['Barkada Fries',              150, 'Snacks & Bites',    'Good for 3-4 persons'],
    ['Cheese Flavour',              15, 'Snacks & Bites',    'Add-on flavour'],
    ['Sour Cream Flavour',          15, 'Snacks & Bites',    'Add-on flavour'],
    ['Cornsilog',  220, 'All Day Breakfast', 'Corned beef, garlic rice, 2 fried eggs'],
    ['Tosilog',    220, 'All Day Breakfast', 'Sweet pork tocino, garlic rice, 2 fried eggs'],
    ['Tapsilog',   220, 'All Day Breakfast', 'Beef tapa, garlic rice, 2 fried eggs'],
    ['Longsilog',  220, 'All Day Breakfast', 'Garlic longgganisa, garlic rice, 2 fried eggs'],
    ['Hungsilog',  220, 'All Day Breakfast', 'Hungarian sausage, garlic rice, 2 fried eggs'],
];

$stmt = $conn->prepare("INSERT INTO products (name, price, category, description, available) VALUES (?,?,?,?,1)");
$inserted = 0; $skipped = 0;

foreach ($items as [$name, $price, $cat, $desc]) {
    $chk = $conn->query("SELECT id FROM products WHERE name='".addslashes($name)."' LIMIT 1");
    if ($chk && $chk->num_rows > 0) { $skipped++; continue; }
    $stmt->bind_param('sdss', $name, $price, $cat, $desc);
    $stmt->execute();
    $inserted++;
}

$conn->close();
echo "<h2>Done!</h2>";
echo "<p>Inserted: <strong>$inserted</strong> items &nbsp;|&nbsp; Skipped (already exist): <strong>$skipped</strong></p>";
echo "<p><a href='products.php'>Go to Products</a> &nbsp;|&nbsp; <strong>Delete this file after use.</strong></p>";
