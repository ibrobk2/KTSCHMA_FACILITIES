<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'functions.php';

requireAdmin();
$db = Database::getInstance()->getConnection();

// Fetch facilities for dropdown
$facilities = $db->query("SELECT * FROM facilities ORDER BY facility_name ASC")->fetchAll(PDO::FETCH_ASSOC);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = cleanInput($_POST['full_name']);
    $email = cleanInput($_POST['email']);
    $phone = cleanInput($_POST['phone']);
    $password = $_POST['password'];
    $facility_id = $_POST['facility_id'];
    $lga = cleanInput($_POST['lga']);

    if (empty($full_name) || empty($email) || empty($password)) {
        $error = 'Please fill in all required fields.';
    } else {
        // Check if email exists
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute(array($email));
        if ($stmt->rowCount() > 0) {
            $error = 'Email already exists.';
        } else {
            // Insert User
            $hashed_password = md5($password);
            $stmt = $db->prepare("INSERT INTO users (full_name, email, phone_number, password, role, facility_id, lga, created_at) VALUES (?, ?, ?, ?, 'user', ?, ?, NOW())");
            
            if ($stmt->execute(array($full_name, $email, $phone, $hashed_password, $facility_id, $lga))) {
                $_SESSION['success'] = "User added successfully!";
                header("Location: users.php");
                exit();
            } else {
                $error = "Failed to add user.";
            }
        }
    }
}

getHeader('Add User');
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-person-plus"></i> Add New User
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone Number</label>
                            <input type="text" name="phone" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Facility</label>
                            <select name="facility_id" class="form-control">
                                <option value="">Select Facility</option>
                                <?php foreach ($facilities as $fac): ?>
                                    <option value="<?php echo $fac['id']; ?>"><?php echo $fac['facility_name']; ?> (<?php echo $fac['facility_code']; ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">LGA</label>
                            <select name="lga" class="form-control">
                                <option value="">Select LGA</option>
                                <?php foreach (getKatsinaLGAs() as $lga_opt): ?>
                                    <option value="<?php echo $lga_opt; ?>"><?php echo $lga_opt; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="users.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-success">Create User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php getFooter(); ?>
