<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit();
}

// Handle Login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = cleanInput($_POST['email']);
    $password = cleanInput($_POST['password']);
    
    if (loginUser($email, $password)) {
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Invalid email or password";
    }
}

getHeader('Login');
?>

<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card shadow-lg mt-5">
            <div class="card-header text-center py-4">
                <h3 class="mb-0 text-success">
                    <img src="images/ktschma_logo.webp" alt="" height="50" class="me-2">
                    <i class="bi bi-hospital-fill"></i> KTSCHMA
                </h3>
                <small class="text-muted">Katsina State Contributory Healthcare Management Agency</small>
            </div>
            <div class="card-body p-4">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email" required autofocus>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-success btn-lg">
                            Login <i class="bi bi-box-arrow-in-right ms-2"></i>
                        </button>
                    </div>
                </form>
            </div>
            <div class="card-footer text-center py-3 bg-light">
                <small class="text-muted">&copy; <?php echo date('Y'); ?> Katsina State Government</small>
            </div>
        </div>
    </div>
</div>

<?php getFooter(); ?>
