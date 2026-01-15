<?php
require_once 'config.php';

$title = "Debug"; // Mock getHeader variable if needed? No, directly using DB.

try {
    $db = Database::getInstance()->getConnection();

    echo "--- USERS ---\n";
    $users = $db->query("SELECT id, username, role FROM users")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($users as $u) {
        echo "ID: " . $u['id'] . " | Name: " . $u['username'] . " | Role: '" . $u['role'] . "'\n";
    }
    
    echo "\n--- QUERY CHECK ---\n";
    $admins = $db->query("SELECT id FROM users WHERE role = 'admin'")->fetchAll(PDO::FETCH_COLUMN);
    echo "Found admins with lowercase check: " . count($admins) . "\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
