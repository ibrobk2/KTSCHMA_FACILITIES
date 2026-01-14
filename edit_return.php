<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'functions.php';

requireLogin(); // User or Admin can edit? Usually Admin can edit anything. User only Draft.
$db = Database::getInstance()->getConnection();

if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$id = $_GET['id'];
$user_id = $_SESSION['user_id'];
$is_admin = isAdmin();

$query = "SELECT * FROM returns WHERE id = ?";
$params = array($id);

if (!$is_admin) {
    // User can edit their own returns (Any status now per user request)
    $query .= " AND user_id = ?";
    $params[] = $user_id;
}

$stmt = $db->prepare($query);
$stmt->execute($params);
$return = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$return) {
    die("Return not found or cannot be edited (already submitted).");
}

$months = array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');
$years = range(date('Y'), date('Y') - 5);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $month = $_POST['month'];
    $year = $_POST['year'];
    
    // New Fields
    $balance_before = filter_var($_POST['balance_before'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $capitation = filter_var($_POST['capitation'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $fee_for_service = filter_var($_POST['fee_for_service'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    
    // Auto Calculate Total
    $amount = $balance_before + $capitation + $fee_for_service;
    
    $status = isset($_POST['status']) ? $_POST['status'] : $return['status'];

    if (empty($month) || empty($year) || empty($amount)) {
        $error = "All fields required.";
    } else {
        // Update
        // Update
        $sql = "UPDATE returns SET month=?, year=?, amount_received=?, balance_before=?, capitation=?, fee_for_service=?, updated_at=NOW()";
        $update_params = array($month, $year, $amount, $balance_before, $capitation, $fee_for_service);
        
        if ($is_admin) {
            $sql .= ", status=?";
            $update_params[] = $status;
        }
        
        $sql .= " WHERE id=?";
        $update_params[] = $id;
        
        $stmt = $db->prepare($sql);
        if ($stmt->execute($update_params)) {
             $_SESSION['success'] = "Return updated successfully.";
             header("Location: view_return_detail.php?id=" . $id);
             exit();
        } else {
            $error = "Update failed.";
        }
    }
}

getHeader('Edit Return');
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                Edit Return Details
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Month</label>
                            <select name="month" class="form-control" required>
                                <?php foreach ($months as $m): ?>
                                    <option value="<?php echo $m; ?>" <?php echo $return['month'] == $m ? 'selected' : ''; ?>>
                                        <?php echo $m; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Year</label>
                            <select name="year" class="form-control" required>
                                <?php foreach ($years as $y): ?>
                                    <option value="<?php echo $y; ?>" <?php echo $return['year'] == $y ? 'selected' : ''; ?>>
                                        <?php echo $y; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Balance Before (B/F)</label>
                            <div class="input-group">
                                <span class="input-group-text">₦</span>
                                <input type="number" step="0.01" name="balance_before" id="balance_before" class="form-control calc-input" value="<?php echo $return['balance_before']; ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Capitation</label>
                            <div class="input-group">
                                <span class="input-group-text">₦</span>
                                <input type="number" step="0.01" name="capitation" id="capitation" class="form-control calc-input" value="<?php echo $return['capitation']; ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Fee for Service</label>
                            <div class="input-group">
                                <span class="input-group-text">₦</span>
                                <input type="number" step="0.01" name="fee_for_service" id="fee_for_service" class="form-control calc-input" value="<?php echo $return['fee_for_service']; ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Total Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">₦</span>
                                <input type="number" step="0.01" name="amount_display" id="total_amount" class="form-control" value="<?php echo $return['amount_received']; ?>" readonly style="background-color: #e9ecef;">
                            </div>
                            <small class="text-muted">Auto-calculated</small>
                        </div>
                    </div>

                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            const inputs = document.querySelectorAll('.calc-input');
                            const totalDisplay = document.getElementById('total_amount');

                            function calculateTotal() {
                                let total = 0;
                                inputs.forEach(input => {
                                    total += parseFloat(input.value) || 0;
                                });
                                totalDisplay.value = total.toFixed(2);
                            }

                            inputs.forEach(input => {
                                input.addEventListener('input', calculateTotal);
                            });
                        });
                    </script>
                    
                    <?php if($is_admin): ?>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-control">
                            <option value="Draft" <?php echo $return['status'] == 'Draft' ? 'selected' : ''; ?>>Draft</option>
                            <option value="Submitted" <?php echo $return['status'] == 'Submitted' ? 'selected' : ''; ?>>Submitted</option>
                        </select>
                    </div>
                    <?php endif; ?>
                    
                    <div class="d-flex justify-content-between">
                        <a href="view_return_detail.php?id=<?php echo $id; ?>" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-warning">Update Return</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php getFooter(); ?>
