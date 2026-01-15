<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'functions.php';

requireAdmin();
$db = Database::getInstance()->getConnection();

if (!isset($_GET['id'])) {
    header("Location: users.php");
    exit();
}

$id = $_GET['id'];
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute(array($id));
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("User not found.");
}

// Fetch facilities for dropdown
$facilities = $db->query("SELECT * FROM facilities ORDER BY facility_name ASC")->fetchAll(PDO::FETCH_ASSOC);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = cleanInput($_POST['full_name']);
    $email = cleanInput($_POST['email']);
    $phone = cleanInput($_POST['phone']);
    $facility_id = $_POST['facility_id'];
    $lga = cleanInput($_POST['lga']);
    
    // Optional password update
    $password = $_POST['password'];

    if (empty($full_name) || empty($email)) {
        $error = 'Name and email are required.';
    } else {
        // Check email uniqueness if changed
        if ($email != $user['email']) {
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute(array($email, $id));
            if ($stmt->rowCount() > 0) {
                $error = 'Email already exists.';
            }
        }
        
        if (empty($error)) {
            if (!empty($password)) {
                // Update with password
                $hashed_password = md5($password);
                $stmt = $db->prepare("UPDATE users SET full_name=?, email=?, phone_number=?, password=?, facility_id=?, lga=? WHERE id=?");
                $result = $stmt->execute(array($full_name, $email, $phone, $hashed_password, $facility_id, $lga, $id));
            } else {
                // Update without password
                $stmt = $db->prepare("UPDATE users SET full_name=?, email=?, phone_number=?, facility_id=?, lga=? WHERE id=?");
                $result = $stmt->execute(array($full_name, $email, $phone, $facility_id, $lga, $id));
            }
            
            if ($result) {
                $_SESSION['success'] = "User updated successfully!";
                header("Location: users.php");
                exit();
            } else {
                $error = "Failed to update user.";
            }
        }
    }
}

getHeader('Edit User');
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-pencil-square"></i> Edit User
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" class="form-control" value="<?php echo cleanInput($user['full_name']); ?>" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" value="<?php echo cleanInput($user['email']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone Number</label>
                            <input type="text" name="phone" class="form-control" value="<?php echo cleanInput($user['phone_number']); ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password <small class="text-muted">(Leave blank to keep current)</small></label>
                        <input type="password" name="password" class="form-control">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Facility</label>
                            <select name="facility_id" class="form-control">
                                <option value="">Select Facility</option>
                                <?php foreach ($facilities as $fac): ?>
                                    <option value="<?php echo $fac['id']; ?>" <?php echo $user['facility_id'] == $fac['id'] ? 'selected' : ''; ?>>
                                        <?php echo $fac['facility_name']; ?> (<?php echo $fac['facility_code']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">LGA</label>
                            <select name="lga" class="form-control">
                                <option value="">Select LGA</option>
                                <?php foreach (getKatsinaLGAs() as $lga_opt): ?>
                                    <option value="<?php echo $lga_opt; ?>" <?php echo $user['lga'] == $lga_opt ? 'selected' : ''; ?>>
                                        <?php echo $lga_opt; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="users.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-warning">Update User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php getFooter(); ?>
