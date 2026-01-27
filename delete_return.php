<?php
require_once 'config.php';
require_once 'auth.php';

requireLogin();
$db = Database::getInstance()->getConnection();

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $user_id = $_SESSION['user_id'];
    
    // Fetch return details first to revert funds
    $stmt_fetch = $db->prepare("SELECT * FROM returns WHERE id = ?");
    $stmt_fetch->execute([$id]);
    $return = $stmt_fetch->fetch(PDO::FETCH_ASSOC);
    
    if ($return) {
        $can_delete = false;
        if (isAdmin()) {
            $can_delete = true;
            $redirect = "view_returns.php";
        } elseif ($return['user_id'] == $user_id) {
            $can_delete = true;
            $redirect = "my_returns.php";
        }
        
        if ($can_delete) {
            // 1. Revert Facility Reserved Funds (only the 15% portion)
            $revert_amount = $return['capitation'] * 0.15;
            $updFac = $db->prepare("UPDATE facilities SET reserved_funds = reserved_funds - ? WHERE id = ?");
            $updFac->execute([$revert_amount, $return['facility_id']]);
            
            // 2. Delete Associated Utilizations
            $stmt_util = $db->prepare("DELETE FROM utilizations WHERE return_id = ?");
            $stmt_util->execute([$id]);
            
            // 3. Delete the Return
            $stmt = $db->prepare("DELETE FROM returns WHERE id = ?");
            $stmt->execute([$id]);
        }
    } else {
        $redirect = "dashboard.php";
    }
} else {
    $redirect = "dashboard.php";
}

header("Location: " . $redirect);
exit();
?>
