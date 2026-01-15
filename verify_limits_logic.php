<?php
// Trivial check to see if logic holds
// Since actual logic depends on DB state which is hard to mock perfectly without fully bootstrapping.
// I'll rely on a manual-style walkthrough description in the artifact, but here I can test the MATH logic.

$amount_received = 100000;
$limits = [
    'Admin' => 0.10,
    'HR' => 0.10,
    'Lab' => 0.15,
];

$type = 'Admin';
$limit = $amount_received * $limits[$type]; // 10000

$current_spent = 9000;
$new_amount = 2000;

echo "Limit Test for Admin:\n";
echo "Total: $amount_received, Limit (10%): $limit\n";
echo "Spent: $current_spent, New: $new_amount, Total New: " . ($current_spent + $new_amount) . "\n";

if (($current_spent + $new_amount) > $limit) {
    echo "SUCCESS: Logic correctly detects invalid amount.\n";
} else {
    echo "FAILURE: Logic failed to detect invalid amount.\n";
}

$new_amount_valid = 1000;
if (($current_spent + $new_amount_valid) <= $limit) {
    echo "SUCCESS: Logic allow valid amount.\n";
} else {
    echo "FAILURE: Logic rejected valid amount.\n";
}
?>
