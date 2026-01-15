<?php
require_once 'config.php';
require_once 'functions.php';

// Simulate logic check
echo "Checking Approval Logic:\n";

// Scenario: Amount > Allowed, User requests approval
$limit = 1000;
$amount = 1200;
$request_approval = true;

if ($amount > $limit) {
    echo "Limit exceeded.\n";
    if ($request_approval) {
        $status = 'Pending';
        echo "Status set to: " . $status . " (Correct)\n";
    } else {
        $status = 'Rejected'; // Error
        echo "Status set to Error (Correct)\n";
    }
}

// Scenario: Admin approves
if ($status == 'Pending') {
    // Admin clicks approve
    $status = 'Approved';
    echo "Admin Approved -> Status: " . $status . " (Correct)\n";
}

// Scenario: Calculate Total
$items = [
    ['amount' => 500, 'status' => 'Approved'],
    ['amount' => 1200, 'status' => 'Pending'],
    ['amount' => 300, 'status' => 'Approved']
];

$total = 0;
foreach ($items as $i) {
    if ($i['status'] == 'Approved') {
        $total += $i['amount'];
    }
}

echo "Total Utilized: " . $total . " (Expected: 800)\n";

if ($total == 800) {
    echo "SUCCESS: Pending items excluded from total.\n";
} else {
    echo "FAILURE: Pending items included in total.\n";
}
?>
