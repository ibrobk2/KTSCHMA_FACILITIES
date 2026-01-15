<?php
require_once 'config.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Check if column exists
    $cols = $db->query("SHOW COLUMNS FROM notifications LIKE 'type'")->fetch();
    
    if (!$cols) {
        $db->exec("ALTER TABLE notifications ADD COLUMN type VARCHAR(50) DEFAULT NULL AFTER message");
        $db->exec("ALTER TABLE notifications ADD COLUMN reference_id INT DEFAULT NULL AFTER type");
        echo "Successfully added 'type' and 'reference_id' columns.\n";
    } else {
        echo "Columns already exist.\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
