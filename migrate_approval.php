<?php
require_once 'config.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Check if column exists
    $check = $db->query("SHOW COLUMNS FROM `utilizations` LIKE 'status'");
    if ($check->rowCount() == 0) {
        $sql = "ALTER TABLE `utilizations` 
                ADD `status` ENUM('Approved','Pending','Rejected') DEFAULT 'Approved' AFTER `expenditure_type`,
                ADD `request_note` TEXT AFTER `status`";
        $db->exec($sql);
        echo "Successfully added 'status' and 'request_note' columns to 'utilizations' table.\n";
    } else {
        echo "Columns 'status' and 'request_note' already exist.\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
