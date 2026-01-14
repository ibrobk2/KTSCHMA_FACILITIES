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
        if (empty($description) || empty($amount) || empty($date_spent)) {
            $error = "All fields required.";
        } else {
            $stmt = $db->prepare("INSERT INTO utilizations (return_id, description, amount, date_spent, receipt_file, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            if ($stmt->execute(array($return_id, $description, $amount, $date_spent, $receipt_file))) {
                header("Location: view_return_detail.php?id=" . $return_id);
                exit();
            } else {
                $error = "Database error.";
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
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">Date Spent</label>
                        <input type="date" name="date_spent" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description (Item/Service)</label>
                        <input type="text" name="description" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Amount</label>
                        <div class="input-group">
                            <span class="input-group-text">₦</span>
                            <input type="number" step="0.01" name="amount" class="form-control" required>
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
