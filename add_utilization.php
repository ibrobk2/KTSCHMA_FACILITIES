<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'functions.php';

requireLogin();
$db = Database::getInstance()->getConnection();

if (!isset($_GET['return_id'])) {
    header("Location: my_returns.php");
    exit();
}

$return_id = $_GET['return_id'];
// Verify ownership and status
$stmt = $db->prepare("SELECT * FROM returns WHERE id = ? AND user_id = ? AND status = 'Draft'");
$stmt->execute(array($return_id, $_SESSION['user_id']));
$return = $stmt->fetch();

if (!$return) {
    die("Invalid return or return is already submitted.");
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $description = cleanInput($_POST['description']);
    $amount = filter_var($_POST['amount'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $expenditure_type = $_POST['expenditure_type'];
    $date_spent = $_POST['date_spent'];
    
    // File Upload Header
    $receipt_file = null;
    if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] == 0) {
        $allowed = array('jpg', 'jpeg', 'png', 'pdf');
        $filename = $_FILES['receipt']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $new_name = uniqid() . '.' . $ext;
            if (move_uploaded_file($_FILES['receipt']['tmp_name'], 'uploads/receipts/' . $new_name)) {
                $receipt_file = $new_name;
            } else {
                $error = "Failed to upload file.";
            }
        } else {
            $error = "Invalid file type. Only JPG, PNG, PDF allowed.";
        }
    }
    
    if (empty($error)) {
        if (empty($description) || empty($amount) || empty($date_spent) || empty($expenditure_type)) {
            $error = "All fields required.";
        } else {
            // Validate Expenditure Limits
            // Admin, HR, Lab, Reserve (Dynamic Settings)
            $limits = [
                'Admin' => getSetting('limit_admin', 10) / 100,
                'HR' => getSetting('limit_hr', 10) / 100,
                'Lab' => getSetting('limit_lab', 15) / 100,
                'Reserve' => getSetting('limit_reserve', 15) / 100
            ];
            
            if (isset($limits[$expenditure_type])) {
                $percentage = $limits[$expenditure_type];
                $max_allowed = $return['amount_received'] * $percentage;
                
                // Get current spent for this type
                $stmt = $db->prepare("SELECT SUM(amount) FROM utilizations WHERE return_id = ? AND expenditure_type = ? AND status = 'Approved'");
                $stmt->execute([$return_id, $expenditure_type]);
                $current_spent = $stmt->fetchColumn() ?: 0;
                
                if (($current_spent + $amount) > $max_allowed) {
                    $limit_msg = "Limit Exceeded! You have spent " . formatCurrency($current_spent) . 
                                 " of " . formatCurrency($max_allowed) . " allowed for " . $expenditure_type . 
                                 " (" . ($percentage * 100) . "% of Total Allocation). Cannot add " . formatCurrency($amount);
                    
                    if (isset($_POST['request_approval']) && $_POST['request_approval'] == '1') {
                         // Proceed with Pending Status
                         $status = 'Pending';
                         $request_note = $limit_msg;
                    } else {
                        $error = $limit_msg;
                        $show_approval_btn = true;
                    }
                } else {
                    $status = 'Approved';
                    $request_note = null;
                }
            }
            
            if (empty($error)) {
                $status = isset($status) ? $status : 'Approved';
                $request_note = isset($request_note) ? $request_note : null;

                $stmt = $db->prepare("INSERT INTO utilizations (return_id, description, amount, expenditure_type, status, request_note, date_spent, receipt_file, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                if ($stmt->execute(array($return_id, $description, $amount, $expenditure_type, $status, $request_note, $date_spent, $receipt_file))) {
                    if ($status == 'Pending') {
                        $_SESSION['success'] = "Expenditure submitted for approval. Admins have been notified.";
                        
                        // Notify Admins
                        $admins = $db->query("SELECT id FROM users WHERE role = 'admin'")->fetchAll(PDO::FETCH_COLUMN);
                        $msg = "Approval Request: A user has requested approval for " . formatCurrency($amount) . " (" . $expenditure_type . ").";
                        $link = "view_return_detail.php?id=" . $return_id;
                        
                        foreach ($admins as $admin_id) {
                            $stmt_n = $db->prepare("INSERT INTO notifications (user_id, message, link, created_at) VALUES (?, ?, ?, NOW())");
                            $stmt_n->execute([$admin_id, $msg, $link]);
                        }
                    }
                    header("Location: view_return_detail.php?id=" . $return_id);
                    exit();
                } else {
                    $error = "Database error.";
                }
            }
        }
    }
}

getHeader('Add Utilization');
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                Add Expenditure
            </div>
            <div class="card-body">
                <?php if ($error && isset($show_approval_btn)): ?>
                    <div class="alert alert-warning">
                        <?php echo $error; ?>
                        <div class="mt-2">

                            <form method="POST" action="">
                                <?php foreach ($_POST as $key => $value): ?>
                                    <?php if ($key !== 'request_approval'): ?>
                                        <input type="hidden" name="<?php echo htmlspecialchars($key); ?>" value="<?php echo htmlspecialchars($value); ?>">
                                    <?php endif; ?>
                                <?php endforeach; ?>
                                <button type="submit" name="request_approval" value="1" class="btn btn-warning btn-sm">
                                    <i class="bi bi-send"></i> Send Request to Admin for Approval
                                </button>
                            </form>
                        </div>
                    </div>
                <?php elseif ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">Expenditure Type <span class="text-danger">*</span></label>
                        <select name="expenditure_type" class="form-select" required>
                            <option value="">Select Type</option>
                            <option value="Admin" <?php echo (isset($_POST['expenditure_type']) && $_POST['expenditure_type'] == 'Admin') ? 'selected' : ''; ?>>Admin (10%)</option>
                            <option value="HR" <?php echo (isset($_POST['expenditure_type']) && $_POST['expenditure_type'] == 'HR') ? 'selected' : ''; ?>>HR (10%)</option>
                            <option value="Lab" <?php echo (isset($_POST['expenditure_type']) && $_POST['expenditure_type'] == 'Lab') ? 'selected' : ''; ?>>Lab (15%)</option>
                            <option value="Reserve" <?php echo (isset($_POST['expenditure_type']) && $_POST['expenditure_type'] == 'Reserve') ? 'selected' : ''; ?>>Reserve (15%)</option>
                            <option value="General" <?php echo (isset($_POST['expenditure_type']) && $_POST['expenditure_type'] == 'General') ? 'selected' : ''; ?>>General / Other (Remaining)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Date Spent</label>
                        <input type="date" name="date_spent" class="form-control" required value="<?php echo isset($_POST['date_spent']) ? $_POST['date_spent'] : ''; ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description (Item/Service)</label>
                        <input type="text" name="description" class="form-control" required value="<?php echo isset($_POST['description']) ? $_POST['description'] : ''; ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Amount</label>
                        <div class="input-group">
                            <span class="input-group-text">₦</span>
                            <input type="number" step="0.01" name="amount" class="form-control" required value="<?php echo isset($_POST['amount']) ? $_POST['amount'] : ''; ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Receipt/Evidence (PDF/Image)</label>
                        <input type="file" name="receipt" class="form-control">
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="view_return_detail.php?id=<?php echo $return_id; ?>" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Add Item</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php getFooter(); ?>
