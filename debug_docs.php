<?php
require_once 'config.php';
$db = Database::getInstance()->getConnection();
$docs = $db->query("SELECT id, verify_by, status FROM supporting_documents LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
foreach($docs as $d) {
    echo "ID: {$d['id']}, VerifyBy: '{$d['verify_by']}', Status: '{$d['status']}'\n";
}
?>
