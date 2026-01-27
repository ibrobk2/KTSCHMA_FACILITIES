<?php
require_once 'config.php';

try {
    $db = Database::getInstance()->getConnection();
    
    echo "Starting backfill of 'verify_by' names...\n";
    
    // Update verify_by with full_name from users where verify_by matches a user ID
    // We use REGEXP '^[0-9]+$' to target records that were likely inserted as IDs before the migration
    $sql = "UPDATE supporting_documents sd
            JOIN users u ON sd.verify_by = CAST(u.id AS CHAR)
            SET sd.verify_by = u.full_name
            WHERE sd.verify_by REGEXP '^[0-9]+$'";
            
    $affected = $db->exec($sql);
    
    // 2. For legacy records that have NO name/ID but are already approved/rejected, 
    // set them to 'System Administrator' as a fallback.
    $sql2 = "UPDATE supporting_documents 
             SET verify_by = 'System Administrator' 
             WHERE (verify_by IS NULL OR verify_by = '') 
             AND status IN ('Approved', 'Rejected')";
    $affected2 = $db->exec($sql2);
    echo "Successfully updated $affected2 legacy records with 'System Administrator'.\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
