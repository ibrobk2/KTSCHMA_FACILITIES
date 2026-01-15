<?php
require_once 'config.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Check if column exists
    $check = $db->query("SHOW COLUMNS FROM `utilizations` LIKE 'expenditure_type'");
    if ($check->rowCount() == 0) {
        $sql = "ALTER TABLE `utilizations` ADD `expenditure_type` VARCHAR(50) DEFAULT NULL AFTER `amount`";
        $db->exec($sql);
        echo "Successfully added 'expenditure_type' column to 'utilizations' table.\n";
    } else {
        echo "Column 'expenditure_type' already exists.\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
