<?php
require_once 'config.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Check if column exists
    $check = $db->query("SHOW COLUMNS FROM `returns` LIKE 'program'");
    if ($check->rowCount() == 0) {
        $sql = "ALTER TABLE `returns` ADD `program` VARCHAR(50) DEFAULT NULL AFTER `amount_received`";
        $db->exec($sql);
        echo "Successfully added 'program' column to 'returns' table.\n";
    } else {
        echo "Column 'program' already exists.\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
