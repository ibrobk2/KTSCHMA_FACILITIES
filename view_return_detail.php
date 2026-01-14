<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'functions.php';

requireLogin();
$db = Database::getInstance()->getConnection();

if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$return_id = $_GET['id'];
$user_id = $_SESSION['user_id'];
$is_admin = isAdmin();

// Fetch Return Details
$query = "SELECT r.*, f.facility_name, u.full_name 
          FROM returns r 
          JOIN facilities f ON r.facility_id = f.id 
          JOIN users u ON r.user_id = u.id 
          WHERE r.id = ?";
          
// If not admin, restrict to own return
if (!$is_admin) {
    $query .= " AND r.user_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute(array($return_id, $user_id));
} else {
    $stmt = $db->prepare($query);
    $stmt->execute(array($return_id));
}

$return = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$return) {
    die("Return not found or access denied.");
}

// Fetch Utilizations
$stmt_util = $db->prepare("SELECT * FROM utilizations WHERE return_id = ? ORDER BY date_spent ASC");
$stmt_util->execute(array($return_id));
$utilizations = $stmt_util->fetchAll(PDO::FETCH_ASSOC);

// Calculate Totals
$total_utilized = 0;
foreach ($utilizations as $u) {
    $total_utilized += $u['amount'];
}
$balance = $return['amount_received'] - $total_utilized;

// Handle Submit Final
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_return']) && $return['status'] == 'Draft') {
    $stmt = $db->prepare("UPDATE returns SET status = 'Submitted' WHERE id = ?");
    $stmt->execute(array($return_id));
    $_SESSION['success'] = "Return submitted successfully!";
    header("Location: view_return_detail.php?id=" . $return_id);
    exit();
}

getHeader('Return Details');
?>

<!-- Header Info -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-success text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        Monthly Return: <?php echo $return['month'] . ' ' . $return['year']; ?>
                        <span class="badge bg-light text-dark ms-2"><?php echo $return['status']; ?></span>
                    </h5>
                    <?php if($is_admin): ?>
                        <a href="view_returns.php" class="btn btn-sm btn-light">Back to List</a>
                    <?php else: ?>
                        <a href="my_returns.php" class="btn btn-sm btn-light">Back to List</a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <p><strong>Facility:</strong> <?php echo $return['facility_name']; ?></p>
                        <p><strong>Submitted By:</strong> <?php echo $return['full_name']; ?></p>
                    </div>
                    <div class="col-md-4">

                        <p><strong>Balance B/F:</strong> <?php echo formatCurrency($return['balance_before']); ?></p>
                        <p><strong>Capitation:</strong> <?php echo formatCurrency($return['capitation']); ?></p>
                        <p><strong>Fee for Service:</strong> <?php echo formatCurrency($return['fee_for_service']); ?></p>
                        <hr>
                        <p><strong>Total Available:</strong> <span class="text-success h5"><?php echo formatCurrency($return['amount_received']); ?></span></p>
                        <p><strong>Total Utilized:</strong> <span class="text-secondary h5"><?php echo formatCurrency($total_utilized); ?></span></p>
                    </div>
                    <div class="col-md-4">
                        <p><strong>Balance:</strong> <span class="h5 <?php echo $balance < 0 ? 'text-danger' : 'text-primary'; ?>"><?php echo formatCurrency($balance); ?></span></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Utilizations List -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-cart"></i> Utilizations (Expenditures)</span>
        <?php if($return['status'] == 'Draft' && !$is_admin): ?>
            <a href="add_utilization.php?return_id=<?php echo $return['id']; ?>" class="btn btn-primary btn-sm">
                <i class="bi bi-plus"></i> Add Expenditure
            </a>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Description</th>
                        <th>Amount</th>
                        <th>Evidence</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($utilizations) > 0): ?>
                        <?php foreach($utilizations as $util): ?>
                        <tr>
                            <td><?php echo formatDate($util['date_spent']); ?></td>
                            <td><?php echo cleanInput($util['description']); ?></td>
                            <td><?php echo formatCurrency($util['amount']); ?></td>
                            <td>
                                <?php if($util['receipt_file']): ?>
                                    <a href="uploads/receipts/<?php echo $util['receipt_file']; ?>" target="_blank" class="btn btn-sm btn-info">View File</a>
                                <?php else: ?>
                                    <span class="text-muted">None</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="edit_utilization.php?id=<?php echo $util['id']; ?>" class="btn btn-sm btn-warning">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="delete_utilization.php?id=<?php echo $util['id']; ?>&return_id=<?php echo $return['id']; ?>" 
                                   class="text-danger ms-2" onclick="return confirm('Remove this item?')">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center text-muted">No utilizations added yet.</td></tr>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr class="table-light fw-bold">
                        <td colspan="2" class="text-end">Total:</td>
                        <td><?php echo formatCurrency($total_utilized); ?></td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        <?php if($return['status'] == 'Draft' && !$is_admin): ?>
            <div class="d-grid gap-2 mt-4">
                <form method="POST" action="" onsubmit="return confirm('Are you sure you want to submit? You cannot edit after submission.');">
                    <button type="submit" name="submit_return" class="btn btn-success btn-lg w-100">
                        <i class="bi bi-check-circle"></i> Submit Final Return
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php getFooter(); ?>
