<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'functions.php';

requireAdmin();
$db = Database::getInstance()->getConnection();

// Filters
$where = "1=1";
$params = array();

if (isset($_GET['status']) && $_GET['status'] != '') {
    $where .= " AND r.status = ?";
    $params[] = $_GET['status'];
}

if (isset($_GET['facility_id']) && $_GET['facility_id'] != '') {
    $where .= " AND r.facility_id = ?";
    $params[] = $_GET['facility_id'];
}

$query = "SELECT r.*, f.facility_name, f.facility_code, u.full_name 
          FROM returns r 
          JOIN facilities f ON r.facility_id = f.id 
          JOIN users u ON r.user_id = u.id 
          WHERE $where 
          ORDER BY r.created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$returns = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch facilities for filter
$facilities = $db->query("SELECT * FROM facilities ORDER BY facility_name ASC")->fetchAll(PDO::FETCH_ASSOC);

getHeader('All Returns');
?>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <select name="facility_id" class="form-control">
                    <option value="">All Facilities</option>
                    <?php foreach($facilities as $fac): ?>
                        <option value="<?php echo $fac['id']; ?>" <?php echo (isset($_GET['facility_id']) && $_GET['facility_id'] == $fac['id']) ? 'selected' : ''; ?>>
                            [<?php echo $fac['facility_code']; ?>] <?php echo $fac['facility_name']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <select name="status" class="form-control">
                    <option value="">All Statuses</option>
                    <option value="Draft" <?php echo (isset($_GET['status']) && $_GET['status'] == 'Draft') ? 'selected' : ''; ?>>Draft</option>
                    <option value="Submitted" <?php echo (isset($_GET['status']) && $_GET['status'] == 'Submitted') ? 'selected' : ''; ?>>Submitted</option>
                </select>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="view_returns.php" class="btn btn-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <i class="bi bi-list-check"></i> All Monthly Returns
    </div>
    <div class="card-body">
        <div class="mb-3">
            <input type="text" id="liveSearchInput" class="form-control" placeholder="Search Returns...">
        </div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Facility</th>
                        <th>User</th>
                        <th>Period</th>
                        <th>Amount Received</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($returns) > 0): ?>
                        <?php foreach ($returns as $row): ?>
                        <tr>
                            <td>
                                <strong><?php echo $row['facility_name']; ?></strong><br>
                                <small class="text-muted"><?php echo $row['facility_code']; ?></small>
                            </td>
                            <td><?php echo $row['full_name']; ?></td>
                            <td><?php echo $row['month'] . ' ' . $row['year']; ?></td>
                            <td><?php echo formatCurrency($row['amount_received']); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $row['status'] == 'Submitted' ? 'success' : 'warning'; ?>">
                                    <?php echo $row['status']; ?>
                                </span>
                            </td>
                            <td><?php echo formatDate($row['created_at']); ?></td>
                            <td>
                                <a href="view_return_detail.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="edit_return.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="delete_return.php?id=<?php echo $row['id']; ?>" 
                                   class="btn btn-sm btn-danger"
                                   onclick="return confirm('Delete this return? This cannot be undone.')">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center">No returns found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php getFooter(); ?>
