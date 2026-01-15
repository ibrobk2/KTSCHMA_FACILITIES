<?php
require_once 'config.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Create Table
    $sql = "CREATE TABLE IF NOT EXISTS `settings` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `setting_key` varchar(50) NOT NULL,
      `setting_value` varchar(255) NOT NULL,
      `created_at` datetime NOT NULL,
      PRIMARY KEY (`id`),
      UNIQUE KEY `setting_key` (`setting_key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    $db->exec($sql);
    echo "Settings table created or exists.\n";
    
    // Seed Defaults
    $defaults = [
        'limit_admin' => '10',
        'limit_hr' => '10',
        'limit_lab' => '15',
        'limit_reserve' => '15'
    ];
    
    foreach ($defaults as $key => $val) {
        $stmt = $db->prepare("INSERT IGNORE INTO settings (setting_key, setting_value, created_at) VALUES (?, ?, NOW())");
        $stmt->execute([$key, $val]);
    }
    echo "Default settings seeded.\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
