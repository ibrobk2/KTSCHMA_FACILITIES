<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'functions.php';

requireAdmin();
$db = Database::getInstance()->getConnection();

if (!isset($_GET['id']) || !isset($_GET['action'])) {
    header("Location: dashboard.php");
    exit();
}

$id = $_GET['id'];
$action = $_GET['action'];

// Get Return ID for redirect
$stmt = $db->prepare("SELECT return_id FROM utilizations WHERE id = ?");
$stmt->execute([$id]);
$return_id = $stmt->fetchColumn();

if (!$return_id) {
    die("Item not found.");
}

if ($action == 'approve') {
    $stmt = $db->prepare("UPDATE utilizations SET status = 'Approved' WHERE id = ?");
    $stmt->execute([$id]);
    $_SESSION['success'] = "Expenditure approved successfully.";
} elseif ($action == 'reject') {
    $stmt = $db->prepare("UPDATE utilizations SET status = 'Rejected' WHERE id = ?");
    $stmt->execute([$id]);
    $_SESSION['success'] = "Expenditure rejected.";
}

header("Location: view_return_detail.php?id=" . $return_id);
exit();
?>
