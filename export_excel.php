<?php
require_once 'config.php';
require_once 'auth.php';

requireAdmin();
$db = Database::getInstance()->getConnection();

// Default values
$year = isset($_GET['year']) ? $_GET['year'] : date('Y');
$facility_id = isset($_GET['facility_id']) ? $_GET['facility_id'] : '';

// Headers for download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=healthcare_report_' . date('Ymd_His') . '.csv');

// Create file pointer connected to output stream
$output = fopen('php://output', 'w');

// Output Column Headings
fputcsv($output, array('Facility Name', 'Facility Code', 'Month', 'Year', 'Allocation Received', 'Total Utilized', 'Balance', 'Date Submitted'));

// Build Query
$where = "r.year = ?";
$params = array($year);

if ($facility_id) {
    $where .= " AND r.facility_id = ?";
    $params[] = $facility_id;
}

$query = "SELECT r.*, f.facility_name, f.facility_code 
          FROM returns r 
          JOIN facilities f ON r.facility_id = f.id 
          WHERE $where AND r.status = 'Submitted' 
          ORDER BY f.facility_name, r.month DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);

while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    // Calculate utilization
    $s = $db->prepare("SELECT SUM(amount) FROM utilizations WHERE return_id = ?");
    $s->execute(array($row['id']));
    $utilized = $s->fetchColumn() ?: 0;
    $balance = $row['amount_received'] - $utilized;
    
    fputcsv($output, array(
        $row['facility_name'],
        $row['facility_code'],
        $row['month'],
        $row['year'],
        $row['amount_received'],
        $utilized,
        $balance,
        $row['created_at']
    ));
}

fclose($output);
exit();
?>
