<?php
require_once 'config.php';
require_once 'auth.php';

requireAdmin();

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $db = Database::getInstance()->getConnection();
    
    // Check constraints if strict (users might be linked)
    // For now, we allow delete, but it sets user's facility_id to NULL (db constraint logic?)
    // But let's just delete.
    
    $stmt = $db->prepare("DELETE FROM facilities WHERE id = ?");
    if ($stmt->execute(array($id))) {
        $_SESSION['success'] = "Facility deleted successfully.";
    } else {
        $_SESSION['error'] = "Failed to delete facility.";
    }
}

header("Location: facilities.php");
exit();
?>
