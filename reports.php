<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'functions.php';

requireAdmin();
$db = Database::getInstance()->getConnection();

// Default values
$year = isset($_GET['year']) ? $_GET['year'] : date('Y');
$facility_id = isset($_GET['facility_id']) ? $_GET['facility_id'] : '';

// Build Query
$where = "r.year = ?";
$params = array($year);

if ($facility_id) {
    $where .= " AND r.facility_id = ?";
    $params[] = $facility_id;
}

// 1. Summary Stats
$query_stats = "SELECT 
                    COUNT(*) as total_returns,
                    SUM(r.amount_received) as total_received
                FROM returns r 
                WHERE $where AND r.status = 'Submitted'";
$stmt = $db->prepare($query_stats);
$stmt->execute($params);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Calculate Utilized for these returns
// Complex join for sum
$query_util = "SELECT SUM(u.amount) as total_utilized 
               FROM utilizations u 
               JOIN returns r ON u.return_id = r.id 
               WHERE $where AND r.status = 'Submitted'";
$stmt = $db->prepare($query_util);
$stmt->execute($params);
$util_stats = $stmt->fetch(PDO::FETCH_ASSOC);

$total_received = $stats['total_received'] ?: 0;
$total_utilized = $util_stats['total_utilized'] ?: 0;
$balance = $total_received - $total_utilized;

// 2. Fetch Detailed Data for Table
$query_list = "SELECT r.*, f.facility_name 
               FROM returns r 
               JOIN facilities f ON r.facility_id = f.id 
               WHERE $where AND r.status = 'Submitted' 
               ORDER BY r.month DESC";
$stmt = $db->prepare($query_list);
$stmt->execute($params);
$report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Facilities for filter
$facilities = $db->query("SELECT * FROM facilities ORDER BY facility_name ASC")->fetchAll(PDO::FETCH_ASSOC);

getHeader('Reports');
?>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Year</label>
                        <select name="year" class="form-control">
                            <?php for($y=date('Y'); $y>=date('Y')-5; $y--): ?>
                                <option value="<?php echo $y; ?>" <?php echo $year == $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Facility</label>
                        <select name="facility_id" class="form-control">
                            <option value="">All Facilities</option>
                            <?php foreach($facilities as $fac): ?>
                                <option value="<?php echo $fac['id']; ?>" <?php echo $facility_id == $fac['id'] ? 'selected' : ''; ?>>
                                    <?php echo $fac['facility_name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <button type="submit" class="btn btn-primary">Generate Report</button>
                        <a href="reports.php" class="btn btn-secondary">Reset</a>
                        <a href="export_excel.php?year=<?php echo $year; ?>&facility_id=<?php echo $facility_id; ?>" target="_blank" class="btn btn-success float-end">
                            <i class="bi bi-file-earmark-spreadsheet"></i> Export to Excel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Stats Cards -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card border-primary text-center py-3">
            <h5 class="text-primary">Total Allocation</h5>
            <h3><?php echo formatCurrency($total_received); ?></h3>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-success text-center py-3">
            <h5 class="text-success">Total Utilized</h5>
            <h3><?php echo formatCurrency($total_utilized); ?></h3>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-warning text-center py-3">
            <h5 class="text-secondary">Balance / Unspent</h5>
            <h3 class="<?php echo $balance < 0 ? 'text-danger' : 'text-dark'; ?>"><?php echo formatCurrency($balance); ?></h3>
        </div>
    </div>
</div>

<!-- Detailed Table -->
<div class="card">
    <div class="card-header">
        Submitted Returns Details (<?php echo $year; ?>)
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Facility</th>
                        <th>Month</th>
                        <th>Allocation Received</th>
                        <!-- Note: Getting actual utilization per return requires subquery or separate fetching logic which is heavy. 
                             For simple reporting, we often just show what was allocated vs fully detailed drilldown. 
                             Adding a column 'Utilized' would require a subquery in $query_list. 
                             Let's do a quick sub-loookup or join. -->
                        <th>Utilized Amount</th>
                        <th>Balance</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($report_data) > 0): ?>
                        <?php foreach($report_data as $row): 
                            // Quick utilization fetch for each row (inefficient for large data but fine for PHP 5.2/small scale)
                            $s = $db->prepare("SELECT SUM(amount) FROM utilizations WHERE return_id = ?");
                            $s->execute(array($row['id']));
                            $u_amt = $s->fetchColumn() ?: 0;
                            $bal = $row['amount_received'] - $u_amt;
                        ?>
                        <tr>
                            <td><?php echo $row['facility_name']; ?></td>
                            <td><?php echo $row['month']; ?></td>
                            <td><?php echo formatCurrency($row['amount_received']); ?></td>
                            <td><?php echo formatCurrency($u_amt); ?></td>
                            <td class="<?php echo $bal < 0 ? 'text-danger fw-bold' : ''; ?>"><?php echo formatCurrency($bal); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center">No data available for selected criteria.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php getFooter(); ?>
