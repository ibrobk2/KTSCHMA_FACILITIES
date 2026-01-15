<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'functions.php';

requireAdmin();
$db = Database::getInstance()->getConnection();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $limit_admin = cleanInput($_POST['limit_admin']);
    $limit_hr = cleanInput($_POST['limit_hr']);
    $limit_lab = cleanInput($_POST['limit_lab']);
    $limit_reserve = cleanInput($_POST['limit_reserve']);

    // Upsert Settings
    $settings = [
        'limit_admin' => $limit_admin,
        'limit_hr' => $limit_hr,
        'limit_lab' => $limit_lab,
        'limit_reserve' => $limit_reserve
    ];

    try {
        $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value, created_at) VALUES (?, ?, NOW()) 
                             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        
        foreach ($settings as $key => $val) {
            $stmt->execute([$key, $val]);
        }
        $message = "Settings updated successfully!";
    } catch (PDOException $e) {
        $error = "Error updating settings: " . $e->getMessage();
    }
}

// Fetch current values
$limit_admin = getSetting('limit_admin', 10);
$limit_hr = getSetting('limit_hr', 10);
$limit_lab = getSetting('limit_lab', 15);
$limit_reserve = getSetting('limit_reserve', 15);

getHeader('System Settings');
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-gear"></i> System Configurations
            </div>
            <div class="card-body">
                <?php if ($message): ?>
                    <div class="alert alert-success"><?php echo $message; ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <h5 class="mb-3">Expenditure Limits (%)</h5>
                    <p class="text-muted small">Set the maximum percentage of the monthly allocation allowed for each category.</p>
                    
                    <div class="mb-3">
                        <label class="form-label">Admin Limit (%)</label>
                        <input type="number" step="0.01" name="limit_admin" class="form-control" value="<?php echo $limit_admin; ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">HR Limit (%)</label>
                        <input type="number" step="0.01" name="limit_hr" class="form-control" value="<?php echo $limit_hr; ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Lab Limit (%)</label>
                        <input type="number" step="0.01" name="limit_lab" class="form-control" value="<?php echo $limit_lab; ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Reserve Limit (%)</label>
                        <input type="number" step="0.01" name="limit_reserve" class="form-control" value="<?php echo $limit_reserve; ?>" required>
                    </div>

                    <button type="submit" class="btn btn-primary">Save Changes</button>
                    <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php getFooter(); ?>
