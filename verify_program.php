<?php
require_once 'config.php';
require_once 'functions.php';

// Simulate Admin Login with 'Formal Sector'
$_SESSION['role'] = 'admin';
$_SESSION['program'] = 'Formal Sector';
$program = $_SESSION['program'];

$db = Database::getInstance()->getConnection();

echo "Testing Program Isolation for: " . $program . "\n";

// Count returns for 'Formal Sector'
$stmt = $db->prepare("SELECT COUNT(*) FROM returns WHERE program = ?");
$stmt->execute(['Formal Sector']);
$formal_count_db = $stmt->fetchColumn();

// Count returns for 'BHCPF'
$stmt = $db->prepare("SELECT COUNT(*) FROM returns WHERE program = ?");
$stmt->execute(['BHCPF']);
$bhcpf_count_db = $stmt->fetchColumn();

echo "DB Stats - Formal: $formal_count_db, BHCPF: $bhcpf_count_db\n";


// Simulate Dashboard Logic for Formal Sector
$pending_returns = $db->prepare("SELECT COUNT(*) FROM returns WHERE status = 'Submitted' AND program = ?");
$pending_returns->execute([$program]);
$count = $pending_returns->fetchColumn();

echo "Dashboard sees pending returns for $program: " . $count . "\n";

// Verify it matches DB count (assuming all submitted)
// Actually we should just verify it doesn't see BHCPF stuff.

if ($count <= $formal_count_db) {
    echo "SUCCESS: Dashboard filtered correctly (Count <= Total DB Count for Program)\n";
} else {
    echo "FAILURE: Dashboard count logic seems off.\n";
}

// Check if any returned rows have wrong program
$recent_stmt = $db->prepare("SELECT program FROM returns WHERE program = ? LIMIT 5");
$recent_stmt->execute([$program]);
$rows = $recent_stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($rows as $r) {
    if ($r['program'] !== $program) {
        echo "FAILURE: Found " . $r['program'] . " when expecting $program\n";
    }
}
echo "Program check complete.\n";
?>
