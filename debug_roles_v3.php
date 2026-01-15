<?php
require_once 'config.php';

try {
    $db = Database::getInstance()->getConnection();

    echo "--- USERS (id, full_name, role) ---\n";
    $users = $db->query("SELECT id, full_name, role FROM users")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($users as $u) {
        echo "ID: " . $u['id'] . " | Name: " . $u['full_name'] . " | Role: '" . $u['role'] . "'\n";
    }
    
    echo "\n--- ADMINS ---\n";
    $admins = $db->query("SELECT id FROM users WHERE role = 'admin'")->fetchAll(PDO::FETCH_COLUMN);
    echo "Found " . count($admins) . " admin(s).\n";
    if (count($admins) > 0) {
        echo "IDs: " . implode(", ", $admins) . "\n";
        
        // Try inserting a test notification
        $test_msg = "Debug Test Notification " . time();
        $admin_id = $admins[0];
        echo "Inserting test notification for Admin ID $admin_id...\n";
        
        $stmt_n = $db->prepare("INSERT INTO notifications (user_id, message, created_at) VALUES (?, ?, NOW())");
        $stmt_n->execute([$admin_id, $test_msg]);
        echo "Inserted.\n";
        
        // Verify it exists
        $check = $db->query("SELECT * FROM notifications WHERE message = '$test_msg'")->fetch(PDO::FETCH_ASSOC);
        if ($check) {
            echo "VERIFIED: Notification found in DB with ID " . $check['id'] . "\n";
        } else {
            echo "FAILED: Notification not found in DB.\n";
        }
    } else {
        echo "NO ADMINS FOUND! Logic will fail.\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
