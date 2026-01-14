<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'functions.php';

requireAdmin();
$db = Database::getInstance()->getConnection();

if (!isset($_GET['id'])) {
    header("Location: facilities.php");
    exit();
}

$id = $_GET['id'];
$stmt = $db->prepare("SELECT * FROM facilities WHERE id = ?");
$stmt->execute(array($id));
$facility = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$facility) {
    die("Facility not found.");
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = cleanInput($_POST['facility_name']);
    $code = cleanInput($_POST['facility_code']);
    $lga = cleanInput($_POST['lga']);
    $address = cleanInput($_POST['address']);

    if (empty($name) || empty($code) || empty($lga)) {
        $error = 'Facility Name, Code and LGA are required.';
    } else {
        $stmt = $db->prepare("UPDATE facilities SET facility_name=?, facility_code=?, lga=?, address=? WHERE id=?");
        if ($stmt->execute(array($name, $code, $lga, $address, $id))) {
            $_SESSION['success'] = "Facility updated successfully!";
            header("Location: facilities.php");
            exit();
        } else {
            $error = "Failed to update facility.";
        }
    }
}

getHeader('Edit Facility');
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-pencil-square"></i> Edit Facility
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label">Facility Name <span class="text-danger">*</span></label>
                        <input type="text" name="facility_name" class="form-control" value="<?php echo cleanInput($facility['facility_name']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Facility Code <span class="text-danger">*</span></label>
                        <input type="text" name="facility_code" class="form-control" value="<?php echo cleanInput($facility['facility_code']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">LGA <span class="text-danger">*</span></label>
                        <select name="lga" class="form-control" required>
                            <option value="">Select LGA</option>
                            <?php foreach (getKatsinaLGAs() as $lga_opt): ?>
                                <option value="<?php echo $lga_opt; ?>" <?php echo $facility['lga'] == $lga_opt ? 'selected' : ''; ?>>
                                    <?php echo $lga_opt; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control" rows="3"><?php echo cleanInput($facility['address']); ?></textarea>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="facilities.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-warning">Update Facility</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php getFooter(); ?>
