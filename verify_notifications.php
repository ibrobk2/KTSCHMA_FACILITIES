<?php
require_once 'config.php';
require_once 'functions.php';

// Simulate Logic

// 1. Create Notification
$db = Database::getInstance()->getConnection();
$user_id = 1; // Assuming Admin
$msg = "Test Notification " . time();

$stmt = $db->prepare("INSERT INTO notifications (user_id, message, created_at) VALUES (?, ?, NOW())");
$stmt->execute([$user_id, $msg]);
echo "Notification created.\n";

// 2. Count Unread
$stmt_n = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt_n->execute([$user_id]);
$unread_count = $stmt_n->fetchColumn();

echo "Unread Count: $unread_count\n";
if ($unread_count > 0) {
    echo "SUCCESS: Unread count correct.\n";
} else {
    echo "FAILURE: Unread count incorrect.\n";
}

// 3. Mark Read
$stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
$stmt->execute([$user_id]);

// 4. Verify Count
$stmt_n->execute([$user_id]);
$unread_count_new = $stmt_n->fetchColumn();

echo "New Unread Count: $unread_count_new\n";
if ($unread_count_new == 0) {
    echo "SUCCESS: Mark as read works.\n";
} else {
    echo "FAILURE: Mark as read failed.\n";
}
?>
