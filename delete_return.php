<?php
require_once 'config.php';
require_once 'auth.php';

requireLogin();
$db = Database::getInstance()->getConnection();

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $user_id = $_SESSION['user_id'];
    
    // Admin can delete any, User can delete only Draft own
    if (isAdmin()) {
        $stmt = $db->prepare("DELETE FROM returns WHERE id =?");
        $stmt->execute(array($id));
        $redirect = "view_returns.php";
    } else {
        $stmt = $db->prepare("DELETE FROM returns WHERE id = ? AND user_id = ? AND status = 'Draft'");
        $stmt->execute(array($id, $user_id));
        $redirect = "my_returns.php";
    }
} else {
    $redirect = "dashboard.php";
}

header("Location: " . $redirect);
exit();
?>
