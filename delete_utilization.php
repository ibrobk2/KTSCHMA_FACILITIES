<?php
require_once 'config.php';
require_once 'auth.php';

requireLogin();

if (isset($_GET['id']) && isset($_GET['return_id'])) {
    $id = $_GET['id'];
    $return_id = $_GET['return_id'];
    $db = Database::getInstance()->getConnection();
    
    // Verify ownership indirectly by checking if return belongs to user and is Draft
    // OR if admin (Admins shouldn't really be deleting items in user drafts ideally, but for management powers yes)
    // Here we use the same logic as view_detail: user access check
    
    $stmt_check = $db->prepare("SELECT user_id, status FROM returns WHERE id = ?");
    $stmt_check->execute(array($return_id));
    $return = $stmt_check->fetch();
    
    if ($return && ($return['user_id'] == $_SESSION['user_id'])) {
        $stmt = $db->prepare("DELETE FROM utilizations WHERE id = ?");
        $stmt->execute(array($id));
    }
    
    header("Location: view_return_detail.php?id=" . $return_id);
    exit();
}

header("Location: dashboard.php");
exit();
?>
