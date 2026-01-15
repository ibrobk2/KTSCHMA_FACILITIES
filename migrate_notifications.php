<?php
require_once 'config.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Create Table
    $sql = "CREATE TABLE IF NOT EXISTS `notifications` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `user_id` int(11) NOT NULL,
      `message` text NOT NULL,
      `is_read` tinyint(1) DEFAULT 0,
      `created_at` datetime NOT NULL,
      PRIMARY KEY (`id`),
      KEY `user_id` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    $db->exec($sql);
    echo "Notifications table created or exists.\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
