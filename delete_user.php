<?php
require_once 'config.php';
require_once 'auth.php';

requireAdmin();

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $db = Database::getInstance()->getConnection();
    
    // Prevent deleting self (though list filters out admins usually, good safety)
    if ($id == $_SESSION['user_id']) {
        $_SESSION['error'] = "You cannot delete your own account.";
    } else {
        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        if ($stmt->execute(array($id))) {
            $_SESSION['success'] = "User deleted successfully.";
        } else {
            $_SESSION['error'] = "Failed to delete user.";
        }
    }
}

header("Location: users.php");
exit();
?>
