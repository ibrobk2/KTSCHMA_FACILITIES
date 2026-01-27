<?php
require_once 'config.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // 1. Check if 'verified_by' exists to rename it
    $check_verified = $db->query("SHOW COLUMNS FROM `supporting_documents` LIKE 'verified_by'");
    if ($check_verified->rowCount() > 0) {
        echo "Found 'verified_by' column. Renaming...\n";
        
        // Drop foreign key if it exists (it might not in all environments but let's be safe)
        // According to schema.sql it might not have one, but ktschma.sql shows:
        // ALTER TABLE `supporting_documents` ADD CONSTRAINT `supporting_documents_ibfk_1` FOREIGN KEY (`utilization_id`) REFERENCES `utilizations` (`id`) ON DELETE CASCADE;
        // Wait, verified_by is NOT the foreign key in that constraint.
        // Let's check for any FK that might involve verified_by.
        
        // Actually the error said "Columns participating in a foreign key are renamed".
        // Let's find the constraint name.
        $stmt = $db->query("SELECT CONSTRAINT_NAME 
                            FROM information_schema.KEY_COLUMN_USAGE 
                            WHERE TABLE_NAME = 'supporting_documents' 
                            AND COLUMN_NAME = 'verified_by' 
                            AND TABLE_SCHEMA = DATABASE()");
        $fk = $stmt->fetchColumn();
        
        if ($fk) {
            echo "Dropping foreign key constraint: $fk\n";
            $db->exec("ALTER TABLE `supporting_documents` DROP FOREIGN KEY `$fk` ");
        }

        // Rename and change type
        $sql = "ALTER TABLE `supporting_documents` CHANGE `verified_by` `verify_by` VARCHAR(255) DEFAULT NULL";
        $db->exec($sql);
        echo "Successfully renamed 'verified_by' to 'verify_by' and changed type to VARCHAR(255).\n";
        
        // Re-add foreign key if it was a reference to users.id
        // (Though usually we don't want a FK if we are storing names now)
        // If it was a FK, we probably SHOULD NOT re-add it as a FK to an INT id if it's now a VARCHAR name.
    } else {
        echo "Column 'verified_by' not found or already renamed.\n";
        // Ensure 'verify_by' is VARCHAR(255)
        $check_verify = $db->query("SHOW COLUMNS FROM `supporting_documents` LIKE 'verify_by'");
        if ($check_verify->rowCount() > 0) {
            $db->exec("ALTER TABLE `supporting_documents` MODIFY `verify_by` VARCHAR(255) DEFAULT NULL");
            echo "Ensured 'verify_by' is VARCHAR(255).\n";
        } else {
            $db->exec("ALTER TABLE `supporting_documents` ADD `verify_by` VARCHAR(255) DEFAULT NULL");
            echo "Added 'verify_by' column.\n";
        }
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
