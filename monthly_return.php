<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'functions.php';

requireLogin();
$db = Database::getInstance()->getConnection();

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
    
    // Calculate Total Automatically
    $amount = $balance_before + $capitation + $fee_for_service;
    
    $user_id = $_SESSION['user_id'];
    $facility_id = $_SESSION['facility_id'];
    
    // Validate
    if (!$facility_id) {
        $error = "You are not assigned to a facility. Contact Admin.";
    } elseif (empty($month) || empty($year)) {
        $error = "Month and Year are required.";
    } elseif ($balance_before === '' || $capitation === '' || $fee_for_service === '') {
        $error = "All financial fields are required (enter 0 if none).";
    } else {
        // Check for duplicate
        $stmt = $db->prepare("SELECT id FROM returns WHERE user_id = ? AND month = ? AND year = ?");
        $stmt->execute(array($user_id, $month, $year));
        if ($stmt->rowCount() > 0) {
            $error = "A return for this period already exists.";
        } else {
            // Extension handling logic for bank_statement upload would go here if single step, 
            // but requirements often separate uploads. I'll add simple upload here if needed or separate.
            // Let's keep it simple: create header first.
            
            $stmt = $db->prepare("INSERT INTO returns (user_id, facility_id, month, year, amount_received, balance_before, capitation, fee_for_service, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Draft', NOW())");
            if ($stmt->execute(array($user_id, $facility_id, $month, $year, $amount, $balance_before, $capitation, $fee_for_service))) {
                $return_id = $db->lastInsertId();
                header("Location: view_return_detail.php?id=" . $return_id);
                exit();
            } else {
                $error = "Failed to create return.";
            }
        }
    }
}

getHeader('New Monthly Return');
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-file-earmark-plus"></i> Start New Monthly Return
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if (!$_SESSION['facility_id']): ?>
                    <div class="alert alert-warning">
                        Warning: You are not currently assigned to any facility. You cannot submit returns.
                    </div>
                <?php else: ?>
                
                <form method="POST" action="">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Month <span class="text-danger">*</span></label>
                            <select name="month" class="form-control" required>
                                <option value="">Select Month</option>
                                <?php foreach ($months as $m): ?>
                                    <option value="<?php echo $m; ?>"><?php echo $m; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Year <span class="text-danger">*</span></label>
                            <select name="year" class="form-control" required>
                                <?php foreach ($years as $y): ?>
                                    <option value="<?php echo $y; ?>"><?php echo $y; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Balance Before (B/F)</label>
                            <div class="input-group">
                                <span class="input-group-text">₦</span>
                                <input type="number" step="0.01" name="balance_before" id="balance_before" class="form-control calc-input" required value="0">
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Capitation</label>
                            <div class="input-group">
                                <span class="input-group-text">₦</span>
                                <input type="number" step="0.01" name="capitation" id="capitation" class="form-control calc-input" required value="0">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Fee for Service</label>
                            <div class="input-group">
                                <span class="input-group-text">₦</span>
                                <input type="number" step="0.01" name="fee_for_service" id="fee_for_service" class="form-control calc-input" required value="0">
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Total Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">₦</span>
                                <input type="number" step="0.01" name="amount_display" id="total_amount" class="form-control" readonly style="background-color: #e9ecef;">
                            </div>
                            <small class="text-muted">Auto-calculated (B/F + Capitation + Fee)</small>
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
                            
                            // Initial calculation
                            calculateTotal();
                            
                            // Auto-fetch previous balance
                            fetch('ajax_get_previous_balance.php')
                                .then(response => response.json())
                                .then(data => {
                                    if (data.balance !== undefined) {
                                        const balanceInput = document.getElementById('balance_before');
                                        // Only set if currently 0 (to avoid overwriting if user already typed, though this is on load)
                                        if (balanceInput.value == 0 || balanceInput.value == '') {
                                            balanceInput.value = parseFloat(data.balance).toFixed(2);
                                            calculateTotal();
                                        }
                                    }
                                })
                                .catch(err => console.error('Error fetching balance:', err));
                        });
                    </script>
                    
                    <div class="d-flex justify-content-between">
                        <a href="my_returns.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-success">Next: Add Utilizations <i class="bi bi-arrow-right"></i></button>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php getFooter(); ?>
