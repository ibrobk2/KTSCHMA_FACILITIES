<?php
require_once 'config.php';
require_once 'auth.php';

requireLogin();

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $user_id = $_SESSION['user_id'];
    
    $db = Database::getInstance()->getConnection();
    
    // Secure delete: Ensure the notification belongs to the logged-in user
    $stmt = $db->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
    if ($stmt->execute([$id, $user_id])) {
        $_SESSION['success'] = "Notification deleted.";
    } else {
        $_SESSION['error'] = "Failed to delete notification.";
    }
}

header("Location: notifications.php");
exit();
?>
