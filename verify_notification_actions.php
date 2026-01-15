<?php
require_once 'config.php';
require_once 'functions.php';

$db = Database::getInstance()->getConnection();

echo "1. Checking Schema...\n";
$cols = $db->query("SHOW COLUMNS FROM notifications LIKE 'link'")->fetch();
if ($cols) {
    echo "SUCCESS: 'link' column exists.\n";
} else {
    echo "FAILURE: 'link' column missing.\n";
    exit();
}

echo "\n2. Testing Insert with Link...\n";
$user_id = 999; 
$msg = "Link Test Link";
$link = "view_return_detail.php?id=123";

$stmt = $db->prepare("INSERT INTO notifications (user_id, message, link, created_at) VALUES (?, ?, ?, NOW())");
if ($stmt->execute([$user_id, $msg, $link])) {
    echo "SUCCESS: Link inserted.\n";
} else {
    echo "FAILURE: Insert failed.\n";
    print_r($stmt->errorInfo());
}

echo "\n3. Verifying Data...\n";
$row = $db->query("SELECT * FROM notifications WHERE message = '$msg'")->fetch(PDO::FETCH_ASSOC);
if ($row && $row['link'] == $link) {
    echo "SUCCESS: Link retrieved correctly: " . $row['link'] . "\n";
} else {
    echo "FAILURE: Link data mismatch.\n";
}

// Cleanup
$db->exec("DELETE FROM notifications WHERE user_id = 999");
?>
