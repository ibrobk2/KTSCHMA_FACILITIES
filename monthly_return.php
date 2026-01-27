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
    
    // New Fields (Strip commas before sanitizing)
    $balance_before = str_replace(',', '', $_POST['balance_before']);
    $capitation = str_replace(',', '', $_POST['capitation']);
    $fee_for_service = str_replace(',', '', $_POST['fee_for_service']);
    
    $balance_before = filter_var($balance_before, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $capitation = filter_var($capitation, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $fee_for_service = filter_var($fee_for_service, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    
    // Calculate Total Automatically (Move 15% Capitation and Balance Before to Reserved)
    $reserve_from_capitation = $capitation * 0.15;
    $dmsa_amount = $capitation * 0.50;
    $reserved_this_month = $balance_before + $reserve_from_capitation;
    
    // Spendable Amount: 35% Capitation + Fee for Service (85% - 50% DMSA = 35%)
    $amount = ($capitation - $reserve_from_capitation - $dmsa_amount) + $fee_for_service;
    
    $user_id = $_SESSION['user_id'];
    $facility_id = $_SESSION['facility_id'];
    
    // Validate
    if (!$facility_id) {
        $error = "You are not assigned to a facility. Contact Admin.";
    } else {
        // Validate Facility Exists
        $facStmt = $db->prepare("SELECT id FROM facilities WHERE id = ?");
        $facStmt->execute([$facility_id]);
        if ($facStmt->rowCount() === 0) {
            $error = "Your assigned facility appears to be invalid or deleted. Please contact the administrator.";
        }
    }

    if ($error) {
        // Fall through to display error
    } elseif (empty($month) || empty($year)) {
        $error = "Month and Year are required.";
    } elseif ($balance_before === '' || $capitation === '' || $fee_for_service === '') {
        $error = "All financial fields are required (enter 0 if none).";
    } else {
        // Check for duplicate
        $program = $_SESSION['program'];
        $stmt = $db->prepare("SELECT id FROM returns WHERE user_id = ? AND month = ? AND year = ? AND program = ?");
        $stmt->execute(array($user_id, $month, $year, $program));
        if ($stmt->rowCount() > 0) {
            $error = "A return for this period already exists in " . $program . ".";
        } else {
            // Extension handling logic for bank_statement upload would go here if single step, 
            // but requirements often separate uploads. I'll add simple upload here if needed or separate.
            // Let's keep it simple: create header first.
            
            $program = $_SESSION['program'];
            
            $stmt = $db->prepare("INSERT INTO returns (user_id, facility_id, month, year, amount_received, program, balance_before, capitation, fee_for_service, reserved_amount, dmsa_amount, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Draft', NOW())");
            if ($stmt->execute(array($user_id, $facility_id, $month, $year, $amount, $program, $balance_before, $capitation, $fee_for_service, $reserved_this_month, $dmsa_amount))) {
                $return_id = $db->lastInsertId();
                
                // Update Facility Reserved Funds with only the 15% portion
                $updFac = $db->prepare("UPDATE facilities SET reserved_funds = reserved_funds + ? WHERE id = ?");
                $updFac->execute([$reserve_from_capitation, $facility_id]);
                
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
                                <input type="text" name="balance_before" id="balance_before" class="form-control calc-input" required value="0">
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Capitation</label>
                            <div class="input-group">
                                <span class="input-group-text">₦</span>
                                <input type="text" name="capitation" id="capitation" class="form-control calc-input" required value="0">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Fee for Service</label>
                            <div class="input-group">
                                <span class="input-group-text">₦</span>
                                <input type="text" name="fee_for_service" id="fee_for_service" class="form-control calc-input" required value="0">
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Gross Allocation (85%)</label>
                            <div class="input-group">
                                <span class="input-group-text">₦</span>
                                <input type="text" id="gross_display" class="form-control" readonly style="background-color: #f8f9fa;">
                            </div>
                            <small class="text-muted">85% Capitation + Fee for Service</small>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">DMSA Drug Fund (50%)</label>
                            <div class="input-group">
                                <span class="input-group-text">₦</span>
                                <input type="text" id="dmsa_display" class="form-control" readonly style="background-color: #fff3f3; color: #a94442;">
                            </div>
                            <small class="text-muted">Deducted from Gross Allocation</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Net Spendable Allocation (35%)</label>
                            <div class="input-group">
                                <span class="input-group-text">₦</span>
                                <input type="text" name="amount_display" id="total_amount" class="form-control" readonly style="background-color: #e9ecef; font-weight: bold; color: green; font-size: 1.1rem;">
                            </div>
                            <small class="text-muted">Net available for facility use</small>
                        </div>
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Reserved Fund (B/F + 15%)</label>
                            <div class="input-group">
                                <span class="input-group-text">₦</span>
                                <input type="text" id="reserved_display" class="form-control" readonly style="background-color: #f8f9fa;">
                            </div>
                            <small class="text-muted">Balance B/F + 15% Of Current Capitation</small>
                        </div>
                    </div>

                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            const inputs = document.querySelectorAll('.calc-input');
                            const totalDisplay = document.getElementById('total_amount');
                            const reservedDisplay = document.getElementById('reserved_display');

                            function formatNumber(num) {
                                return new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(num);
                            }

                            function parseNumber(str) {
                                if (!str) return 0;
                                return parseFloat(str.toString().replace(/,/g, '')) || 0;
                            }

                             function calculateTotal() {
                                let balance_before = parseNumber(document.getElementById('balance_before').value);
                                let capitation = parseNumber(document.getElementById('capitation').value);
                                let fee = parseNumber(document.getElementById('fee_for_service').value);

                                let reserve_from_capitation = capitation * 0.15;
                                let dmsa_amount = capitation * 0.50;
                                let gross_allocation = (capitation - reserve_from_capitation) + fee;
                                let reserved_this_month = balance_before + reserve_from_capitation;
                                let net_spendable = gross_allocation - dmsa_amount;

                                totalDisplay.value = formatNumber(net_spendable);
                                reservedDisplay.value = formatNumber(reserved_this_month);
                                if(document.getElementById('gross_display')) {
                                    document.getElementById('gross_display').value = formatNumber(gross_allocation);
                                }
                                if(document.getElementById('dmsa_display')) {
                                    document.getElementById('dmsa_display').value = formatNumber(dmsa_amount);
                                }
                            }

                            inputs.forEach(input => {
                                input.addEventListener('input', function() {
                                    // Strip non-numeric and non-dot
                                    let val = this.value.replace(/[^\d.]/g, '');
                                    // Avoid multiple dots
                                    let parts = val.split('.');
                                    if(parts.length > 2) val = parts[0] + '.' + parts.slice(1).join('');
                                    this.value = val;
                                    calculateTotal();
                                });

                                input.addEventListener('blur', function() {
                                    let val = parseNumber(this.value);
                                    if(!isNaN(val)) {
                                        this.value = formatNumber(val);
                                    }
                                });

                                // Format on focus to remove commas for easier editing (optional)
                                input.addEventListener('focus', function() {
                                    let val = parseNumber(this.value);
                                    if(val === 0) {
                                        this.value = '';
                                    } else {
                                        this.value = val.toString().replace(/,/g, '');
                                    }
                                });
                            });
                            
                            // Initial calculation
                            calculateTotal();
                            
                            // Auto-fetch previous balance
                            fetch('ajax_get_previous_balance.php')
                                .then(response => response.json())
                                .then(data => {
                                    if (data.balance !== undefined) {
                                        const balanceInput = document.getElementById('balance_before');
                                        const currentVal = parseNumber(balanceInput.value);
                                        if (currentVal == 0) {
                                            balanceInput.value = formatNumber(data.balance);
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
