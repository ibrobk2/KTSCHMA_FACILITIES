<?php
require_once 'config.php';

try {
    $db = Database::getInstance()->getConnection();
    echo "Starting historical DMSA backfill...\n";
    
    // Select records that need updating
    // We update records where dmsa_amount is 0 but capitation is greater than 0
    $stmt = $db->query("SELECT id, capitation, fee_for_service, amount_received FROM returns WHERE dmsa_amount = 0 AND capitation > 0");
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $count = 0;
    foreach ($records as $row) {
        $id = $row['id'];
        $capitation = (float)$row['capitation'];
        $fee = (float)$row['fee_for_service'];
        
        $dmsa = $capitation * 0.50;
        // The new Net Spendable should be (Capitation * 0.35) + Fee
        $net_spendable = ($capitation * 0.35) + $fee;
        
        $upd = $db->prepare("UPDATE returns SET dmsa_amount = ?, amount_received = ? WHERE id = ?");
        if ($upd->execute([$dmsa, $net_spendable, $id])) {
            $count++;
        }
    }
    
    echo "Successfully updated $count records.\n";
    echo "Backfill completed.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
