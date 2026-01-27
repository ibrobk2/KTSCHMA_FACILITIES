<?php
require_once 'config.php';

try {
    $db = Database::getInstance()->getConnection();
    
    echo "Starting migration for Reserved Funds...\n";
    
    // 1. Add reserved_funds to facilities table
    $stmt = $db->query("SHOW COLUMNS FROM `facilities` LIKE 'reserved_funds'");
    if ($stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE `facilities` ADD `reserved_funds` DECIMAL(15,2) DEFAULT '0.00' AFTER `address` ");
        echo "Added 'reserved_funds' to 'facilities' table.\n";
    } else {
        echo "'reserved_funds' already exists in 'facilities' table.\n";
    }
    
    // 2. Add reserved_amount to returns table
    $stmt = $db->query("SHOW COLUMNS FROM `returns` LIKE 'reserved_amount'");
    if ($stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE `returns` ADD `reserved_amount` DECIMAL(15,2) DEFAULT '0.00' AFTER `fee_for_service` ");
        echo "Added 'reserved_amount' to 'returns' table.\n";
    } else {
        echo "'reserved_amount' already exists in 'returns' table.\n";
    }
    
    echo "Migration completed successfully.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
