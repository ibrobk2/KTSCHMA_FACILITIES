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

$id = $_GET['id'];
$stmt = $db->prepare("SELECT u.*, r.user_id, r.status as return_status 
                     FROM utilizations u 
                     JOIN returns r ON u.return_id = r.id 
                     WHERE u.id = ?");
$stmt->execute(array($id));
$util = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$util) {
    die("Item not found.");
}

// Check access
// Check access
if (!isAdmin()) {
    if ($util['user_id'] != $_SESSION['user_id']) {
        die("Access denied.");
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $description = cleanInput($_POST['description']);
    $amount = filter_var($_POST['amount'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $date_spent = $_POST['date_spent'];
    
    // File handling left out for brevity in edit, preserving old if new not uploaded
    // (Assuming user might re-upload receipt but simplest is to just finish core logic)
    
    if (empty($description) || empty($amount) || empty($date_spent)) {
        $error = "All fields required.";
    } else {
        // Validation Logic (Expenditure Limits)
        $expenditure_type = $util['expenditure_type']; // Type cannot be changed in edit, or add dropdown if allowed.
        // If type editing is allowed, we need to fetch POST. Let's assume type is fixed at creation to simplify, or allow edit?
        // User didn't specify if type can be edited. Let's assume yes and add dropdown.
        $expenditure_type = $_POST['expenditure_type']; // Get from POST or fallback to existing
        
        $limits = [
            'Admin' => getSetting('limit_admin', 10) / 100,
            'HR' => getSetting('limit_hr', 10) / 100,
            'Lab' => getSetting('limit_lab', 15) / 100,
            'Reserve' => getSetting('limit_reserve', 15) / 100
        ];
        
        // Fetch Return Amount
        $stmt_r = $db->prepare("SELECT amount_received FROM returns WHERE id = ?");
        $stmt_r->execute([$util['return_id']]);
        $amount_received = $stmt_r->fetchColumn();
        
        if (isset($limits[$expenditure_type])) {
            $percentage = $limits[$expenditure_type];
            $max_allowed = $amount_received * $percentage;
            
            // Get current spent for this type EXCLUDING this item
            $stmt = $db->prepare("SELECT SUM(amount) FROM utilizations WHERE return_id = ? AND expenditure_type = ? AND id != ? AND status = 'Approved'");
            $stmt->execute([$util['return_id'], $expenditure_type, $id]);
            $current_spent = $stmt->fetchColumn() ?: 0;
            
            if (($current_spent + $amount) > $max_allowed) {
                $error = "Limit Exceeded! You have spent " . formatCurrency($current_spent) . 
                         " of " . formatCurrency($max_allowed) . " allowed for " . $expenditure_type . 
                         " (" . ($percentage * 100) . "% of Total Allocation). Cannot update to " . formatCurrency($amount);
            }
        }
        
        if (empty($error)) {
            $stmt = $db->prepare("UPDATE utilizations SET description=?, amount=?, expenditure_type=?, date_spent=? WHERE id=?");
            if ($stmt->execute(array($description, $amount, $expenditure_type, $date_spent, $id))) {
                 header("Location: view_return_detail.php?id=" . $util['return_id']);
                 exit();
            } else {
                $error = "Update failed.";
            }
        }
    }
}

getHeader('Edit Expenditure');
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                Edit Expenditure
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label">Date Spent</label>
                        <input type="date" name="date_spent" class="form-control" value="<?php echo $util['date_spent']; ?>" required>
                    </div>
                    <div class="mb-3">
                        <input type="text" name="description" class="form-control" value="<?php echo cleanInput($util['description']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Expenditure Type</label>
                        <select name="expenditure_type" class="form-control" required>
                            <option value="Admin" <?php echo $util['expenditure_type'] == 'Admin' ? 'selected' : ''; ?>>Admin (10%)</option>
                            <option value="HR" <?php echo $util['expenditure_type'] == 'HR' ? 'selected' : ''; ?>>HR (10%)</option>
                            <option value="Lab" <?php echo $util['expenditure_type'] == 'Lab' ? 'selected' : ''; ?>>Lab (15%)</option>
                            <option value="Reserve" <?php echo $util['expenditure_type'] == 'Reserve' ? 'selected' : ''; ?>>Reserve (15%)</option>
                            <option value="General" <?php echo $util['expenditure_type'] == 'General' ? 'selected' : ''; ?>>General / Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Amount</label>
                        <div class="input-group">
                            <span class="input-group-text">₦</span>
                            <input type="number" step="0.01" name="amount" class="form-control" value="<?php echo $util['amount']; ?>" required>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="view_return_detail.php?id=<?php echo $util['return_id']; ?>" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-warning">Update Item</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php getFooter(); ?>
