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

// Get the latest return
$stmt = $db->prepare("SELECT id, amount_received, balance_before FROM returns WHERE facility_id = ? ORDER BY id DESC LIMIT 1");
$stmt->execute(array($facility_id));
$last_return = $stmt->fetch(PDO::FETCH_ASSOC);

if ($last_return) {
    $return_id = $last_return['id'];
    $amount_received = (float)$last_return['amount_received']; // Previous Net Spendable
    $prev_balance_before = (float)$last_return['balance_before']; // Previous Carry-Forward
    
    // Calculate total utilizations for this return
    $stmt_util = $db->prepare("SELECT SUM(amount) as total_spent FROM utilizations WHERE return_id = ? AND status = 'Approved'");
    $stmt_util->execute(array($return_id));
    $util_result = $stmt_util->fetch(PDO::FETCH_ASSOC);
    $total_spent = (float)($util_result['total_spent'] ?: 0);
    
    // New Balance Before = Previous balance_before + (Previous Net Spendable - Previous Approved Utilizations)
    $unspent_from_this_month = $amount_received - $total_spent;
    $total_carry_forward = $prev_balance_before + $unspent_from_this_month;
    
    echo json_encode(array('balance' => $total_carry_forward));
} else {
    // No previous returns
    echo json_encode(array('balance' => 0));
}
?>
