<?php
require_once 'config.php';
$db = Database::getInstance()->getConnection();
$stmt = $db->query("SELECT id, capitation, fee_for_service, amount_received, dmsa_amount FROM returns WHERE dmsa_amount = 0 AND capitation > 0 LIMIT 10");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($rows, JSON_PRETTY_PRINT);
?>
