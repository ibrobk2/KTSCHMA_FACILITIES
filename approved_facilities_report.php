<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'functions.php';

requireAdmin();
$db = Database::getInstance()->getConnection();

$selected_month_num = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$selected_year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
$selected_program = isset($_GET['program']) ? $_GET['program'] : '';
$view = isset($_GET['view']) ? $_GET['view'] : 'compliant'; // Default to compliant

// Convert month number to name for query
$selected_month_name = date('F', mktime(0, 0, 0, $selected_month_num, 10));

// Build SQL Query
$having_clause = ($view === 'compliant') ? "HAVING approved_count >= 7" : "HAVING approved_count < 7";
$sql_base = "SELECT f.facility_name, f.facility_code, f.lga, r.program, r.month, r.year, r.id as return_id,
            (SELECT COUNT(*) FROM supporting_documents WHERE return_id = r.id AND status = 'Approved') as approved_count
            FROM returns r 
            JOIN facilities f ON r.facility_id = f.id 
            WHERE r.month = ? AND r.year = ? AND r.status = 'Submitted'";

if ($selected_program) {
    $sql_base .= " AND r.program = ?";
}

$sql = "SELECT * FROM ($sql_base) as report_data $having_clause";

$params = [$selected_month_name, $selected_year];
if ($selected_program) {
    $params[] = $selected_program;
}

// Handle Export
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    $filename = ($view === 'compliant' ? 'Compliant' : 'Incomplete') . '_Facilities_' . $selected_month_name . '_' . $selected_year . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);
    $output = fopen('php://output', 'w');
    fputcsv($output, array('S/N', 'Facility Name', 'Facility Code', 'LGA', 'Program', 'Month', 'Year', 'Approved Docs Count'));

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $i = 1;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, array($i++, $row['facility_name'], $row['facility_code'], $row['lga'], $row['program'], $row['month'], $row['year'], $row['approved_count']));
    }
    fclose($output);
    exit;
}

// Fetch Records for Display
$stmt = $db->prepare($sql);
$stmt->execute($params);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

getHeader('Approved Facilities Report');
?>

<div class="row mb-4 d-print-none">
    <div class="col-md-12">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Month</label>
                        <select name="month" class="form-select">
                            <?php for($m=1; $m<=12; $m++): ?>
                                <option value="<?php echo $m; ?>" <?php echo $selected_month_num == $m ? 'selected' : ''; ?>>
                                    <?php echo date('F', mktime(0, 0, 0, $m, 10)); ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold">Year</label>
                        <select name="year" class="form-select">
                            <?php for($y=date('Y'); $y>=2020; $y--): ?>
                                <option value="<?php echo $y; ?>" <?php echo $selected_year == $y ? 'selected' : ''; ?>>
                                    <?php echo $y; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Program</label>
                        <select name="program" class="form-select">
                            <option value="">All Programs</option>
                            <option value="Formal Sector" <?php echo $selected_program == 'Formal Sector' ? 'selected' : ''; ?>>Formal Sector</option>
                            <option value="BHCPF" <?php echo $selected_program == 'BHCPF' ? 'selected' : ''; ?>>BHCPF</option>
                            <option value="Others" <?php echo $selected_program == 'Others' ? 'selected' : ''; ?>>Others</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="bi bi-filter me-1"></i> Filter
                            </button>
                            <button type="button" onclick="window.print()" class="btn btn-secondary">
                                <i class="bi bi-printer me-1"></i> Print
                            </button>
                            <a href="?export=csv&month=<?php echo $selected_month_num; ?>&year=<?php echo $selected_year; ?>&program=<?php echo urlencode($selected_program); ?>&view=<?php echo $view; ?>" class="btn btn-success">
                                <i class="bi bi-file-earmark-excel me-1"></i> Export Excel
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold"><?php echo ($view === 'compliant' ? 'Compliant' : 'Incomplete'); ?> Facilities - <?php echo $selected_month_name . ' ' . $selected_year; ?></h5>
        <span class="badge <?php echo ($view === 'compliant' ? 'bg-success' : 'bg-warning text-dark'); ?> rounded-pill px-3"><?php echo count($results); ?> Facilities</span>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th width="50">S/N</th>
                        <th>Facility Name</th>
                        <th>Facility Code</th>
                        <th>LGA</th>
                        <th>Program</th>
                        <th class="text-center">Approved Docs</th>
                        <th class="d-print-none text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($results) > 0): ?>
                        <?php foreach($results as $index => $row): ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td class="fw-bold"><?php echo $row['facility_name']; ?></td>
                            <td><code><?php echo $row['facility_code']; ?></code></td>
                            <td><?php echo $row['lga']; ?></td>
                            <td><span class="badge bg-info text-dark"><?php echo $row['program']; ?></span></td>
                            <td class="text-center">
                                <span class="badge bg-success rounded-circle p-2">
                                    <i class="bi bi-check2"></i> <?php echo $row['approved_count']; ?>
                                </span>
                            </td>
                            <td class="d-print-none text-end">
                                <a href="supporting_documents.php?return_id=<?php echo $row['return_id']; ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye me-1"></i> View Documents
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-info-circle fs-2 d-block mb-2"></i>
                                No facilities found for the selected period with fully approved documents.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
@media print {
    .btn-group, .d-print-none { display: none !important; }
    .card { border: none !important; box-shadow: none !important; }
    .card-header { border-bottom: 2px solid #333 !important; }
    body { background: white !important; }
}
</style>

<?php getFooter(); ?>
