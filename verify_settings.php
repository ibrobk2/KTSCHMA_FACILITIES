<?php
require_once 'config.php';
require_once 'functions.php';

// Test getSetting with default
echo "Testing Default (Non-existent): " . getSetting('non_existent', 'default_value') . "\n";

// Test DB values (seeded)
echo "Testing Admin Limit (seeded): " . getSetting('limit_admin') . "\n";
echo "Testing Lab Limit (seeded): " . getSetting('limit_lab') . "\n";

// Simulate Update
$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("UPDATE settings SET setting_value = '20' WHERE setting_key = 'limit_admin'");
$stmt->execute();

echo "Testing Admin Limit (Updated to 20): " . getSetting('limit_admin') . "\n";

// Revert
$stmt = $db->prepare("UPDATE settings SET setting_value = '10' WHERE setting_key = 'limit_admin'");
$stmt->execute();
?>
