<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'functions.php';

requireAdmin();
$db = Database::getInstance()->getConnection();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = cleanInput($_POST['facility_name']);
    $code = cleanInput($_POST['facility_code']);
    $lga = cleanInput($_POST['lga']);
    $address = cleanInput($_POST['address']);

    if (empty($name) || empty($lga) || empty($code)) {
        $error = 'Facility Name, Code and LGA are required.';
    } else {
        $stmt = $db->prepare("INSERT INTO facilities (facility_name, facility_code, lga, address, created_at) VALUES (?, ?, ?, ?, NOW())");
        if ($stmt->execute(array($name, $code, $lga, $address))) {
            $_SESSION['success'] = "Facility added successfully!";
            header("Location: facilities.php");
            exit();
        } else {
            $error = "Failed to add facility.";
        }
    }
}

getHeader('Add Facility');
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-building-add"></i> Add New Facility
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label">Facility Name <span class="text-danger">*</span></label>
                        <input type="text" name="facility_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Facility Code <span class="text-danger">*</span></label>
                        <input type="text" name="facility_code" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">LGA <span class="text-danger">*</span></label>
                        <select name="lga" class="form-control" required>
                            <option value="">Select LGA</option>
                            <?php foreach (getKatsinaLGAs() as $lga_opt): ?>
                                <option value="<?php echo $lga_opt; ?>"><?php echo $lga_opt; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control" rows="3"></textarea>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="facilities.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-success">Save Facility</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php getFooter(); ?>
