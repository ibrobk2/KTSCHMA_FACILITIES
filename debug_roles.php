<?php
require_once 'config.php';
require_once 'functions.php';

$db = Database::getInstance()->getConnection();

echo "--- USERS ---\n";
$users = $db->query("SELECT id, username, role FROM users")->fetchAll(PDO::FETCH_ASSOC);
foreach ($users as $u) {
    echo "ID: " . $u['id'] . " | Name: " . $u['username'] . " | Role: '" . $u['role'] . "'\n";
}

echo "\n--- NOTIFICATIONS ---\n";
$notes = $db->query("SELECT * FROM notifications ORDER BY id DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
foreach ($notes as $n) {
    echo "ID: " . $n['id'] . " | UserID: " . $n['user_id'] . " | Msg: " . substr($n['message'], 0, 30) . "...\n";
}

echo "\n--- ADMIN QUERY CHECK ---\n";
$admins_lower = $db->query("SELECT id FROM users WHERE role = 'admin'")->fetchAll(PDO::FETCH_COLUMN);
echo "Count (role='admin'): " . count($admins_lower) . "\n";

$admins_title = $db->query("SELECT id FROM users WHERE role = 'Admin'")->fetchAll(PDO::FETCH_COLUMN);
echo "Count (role='Admin'): " . count($admins_title) . "\n";
?>
