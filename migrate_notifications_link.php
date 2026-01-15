<?php
require_once 'config.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Check if column exists
    $cols = $db->query("SHOW COLUMNS FROM notifications LIKE 'link'")->fetch();
    
    if (!$cols) {
        $db->exec("ALTER TABLE notifications ADD COLUMN link VARCHAR(255) DEFAULT NULL AFTER message");
        echo "Successfully added 'link' column to notifications table.\n";
    } else {
        echo "'link' column already exists.\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
