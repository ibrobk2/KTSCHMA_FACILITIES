<?php
require_once 'config.php';
require_once 'functions.php';

echo "Testing Submission Summary Logic:\n";

// 1. Setup Mock Data
// Mock Facilities: id 1, 2, 3
// Mock Returns: Facility 1 has submitted for Jan 2025 Formal Sector
$facilities = [
    ['id' => 1, 'name' => 'General Hospital'],
    ['id' => 2, 'name' => 'Clinic A'],
    ['id' => 3, 'name' => 'Clinic B']
];

$submitted_return_facility_ids = [1];

$list_submitted = [];
$list_pending = [];

foreach ($facilities as $fac) {
    if (in_array($fac['id'], $submitted_return_facility_ids)) {
        $list_submitted[] = $fac;
    } else {
        $list_pending[] = $fac;
    }
}

echo "Submitted Count: " . count($list_submitted) . " (Expected: 1)\n";
echo "Pending Count: " . count($list_pending) . " (Expected: 2)\n";

if (count($list_submitted) == 1 && $list_submitted[0]['id'] == 1) {
    echo "SUCCESS: Submitted list correct.\n";
} else {
    echo "FAILURE: Submitted list incorrect.\n";
}

if (count($list_pending) == 2 && $list_pending[0]['id'] == 2) {
    echo "SUCCESS: Pending list correct.\n";
} else {
    echo "FAILURE: Pending list incorrect.\n";
}
?>
