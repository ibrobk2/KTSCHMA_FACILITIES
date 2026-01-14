<?php
require_once 'config.php';
require_once 'auth.php';

requireLogin();

header('Content-Type: application/json');

if (!isset($_GET['return_id'])) {
    echo json_encode(array('error' => 'Missing return_id'));
    exit();
}

$return_id = $_GET['return_id'];
$db = Database::getInstance()->getConnection();

$stmt = $db->prepare("SELECT amount_received FROM returns WHERE id = ?");
$stmt->execute(array($return_id));
$return = $stmt->fetch(PDO::FETCH_ASSOC);

if ($return) {
    $stmt_util = $db->prepare("SELECT SUM(amount) as total FROM utilizations WHERE return_id = ?");
    $stmt_util->execute(array($return_id));
    $total = $stmt_util->fetch(PDO::FETCH_ASSOC);
    
    $utilized = $total['total'] ?: 0;
    $balance = $return['amount_received'] - $utilized;
    
    echo json_encode(array(
        'amount_received' => $return['amount_received'],
        'total_utilized' => $utilized,
        'balance' => $balance
    ));
} else {
    echo json_encode(array('error' => 'Return not found'));
}
?>
