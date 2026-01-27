<?php
require_once 'config.php';

try {
    $db = Database::getInstance()->getConnection();
    echo "Starting migration for DMSA Drug Fund...\n";
    
    $stmt = $db->query("SHOW COLUMNS FROM `returns` LIKE 'dmsa_amount'");
    if ($stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE `returns` ADD `dmsa_amount` DECIMAL(15,2) DEFAULT '0.00' AFTER `reserved_amount` ");
        echo "Added 'dmsa_amount' to 'returns' table.\n";
    } else {
        echo "'dmsa_amount' already exists in 'returns' table.\n";
    }
    
    echo "Migration completed successfully.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
