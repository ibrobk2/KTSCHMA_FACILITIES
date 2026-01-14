<?php
require_once 'config.php';
require_once 'auth.php';

requireLogin();

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'];
$facility_id = $_SESSION['facility_id'];

if (!$facility_id) {
    echo json_encode(array('balance' => 0, 'error' => 'No facility assigned'));
    exit();
}

$db = Database::getInstance()->getConnection();

// Get the latest return based on creation date or month/year logic. 
// Ideally we order by year DESC, month DESC or id DESC. ID is safe if created sequentially.
$stmt = $db->prepare("SELECT id, amount_received FROM returns WHERE facility_id = ? ORDER BY id DESC LIMIT 1");
$stmt->execute(array($facility_id));
$last_return = $stmt->fetch(PDO::FETCH_ASSOC);

if ($last_return) {
    $return_id = $last_return['id'];
    $amount_received = $last_return['amount_received'];
    
    // Calculate total utilizations for this return
    $stmt_util = $db->prepare("SELECT SUM(amount) as total_spent FROM utilizations WHERE return_id = ?");
    $stmt_util->execute(array($return_id));
    $util_result = $stmt_util->fetch(PDO::FETCH_ASSOC);
    $total_spent = $util_result['total_spent'] ?: 0;
    
    $balance = $amount_received - $total_spent;
    
    // Ensure balance is not negative (unless debt is possible, but usually returns open with 0 or positive)
    // Actually, balance can be negative if overspent. We pass it as is.
    
    echo json_encode(array('balance' => $balance));
} else {
    // No previous returns
    echo json_encode(array('balance' => 0));
}
?>
