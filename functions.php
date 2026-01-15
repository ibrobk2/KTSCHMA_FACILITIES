<?php
// functions.php - Helper Functions

// Sanitize Input
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Get Katsina LGAs
function getKatsinaLGAs() {
    return array(
        "Bakori", "Batagarawa", "Batsari", "Baure", "Bindawa", "Charanchi", "Dandume", 
        "Danja", "Dan Musa", "Daura", "Dutsi", "Dutsin Ma", "Faskari", "Funtua", 
        "Ingawa", "Jibia", "Kafur", "Kaita", "Kankara", "Kankia", "Katsina", 
        "Kurfi", "Kusada", "Mai'Adua", "Malumfashi", "Mani", "Mashi", "Matazu", 
        "Musawa", "Rimi", "Sabuwa", "Safana", "Sandamu", "Zango"
    );
}

// Format Currency
function formatCurrency($amount) {
    return '₦' . number_format($amount, 2);
}

// Format Date
function formatDate($date) {
    return date('d M, Y', strtotime($date));
}

// Get Header
function getHeader($title = "") {
    $pageTitle = $title ? $title . " - " . SITE_NAME : SITE_NAME;
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo $pageTitle; ?></title>
        <!-- Bootstrap 5 CSS -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <!-- Bootstrap Icons -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
        <!-- Custom CSS -->
        <style>
            :root {
                --primary-green: #2E7D32; /* Katsina Green */
                --light-green: #E8F5E9;
            }
            body {
                background-color: #f8f9fa;
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            }
            .navbar {
                background-color: var(--primary-green);
            }
            .sidebar {
                min-height: 100vh;
                background-color: #fff;
                box-shadow: 2px 0 5px rgba(0,0,0,0.05);
            }
            .nav-link {
                color: #333;
                padding: 12px 20px;
            }
            .nav-link:hover, .nav-link.active {
                background-color: var(--light-green);
                color: var(--primary-green);
                border-right: 3px solid var(--primary-green);
            }
            .card {
                border: none;
                box-shadow: 0 2px 10px rgba(0,0,0,0.05);
                margin-bottom: 20px;
            }
            .card-header {
                background-color: #fff;
                border-bottom: 1px solid #eee;
                font-weight: bold;
                color: var(--primary-green);
            }
            .btn-success {
                background-color: var(--primary-green);
                border-color: var(--primary-green);
            }
            .text-primary-green {
                color: var(--primary-green);
            }
            .login-container {
                max-width: 400px;
                margin: 100px auto;
            }
        </style>
    </head>
    <body>
        <?php if(isLoggedIn()): ?>
        <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
            <div class="container-fluid">
                <a class="navbar-brand d-flex align-items-center" href="dashboard.php">
                    <img src="images/KTSCHMA_logo.webp" alt="Logo" height="50" class="me-2">
                    <span class="d-none d-md-block" style="font-size: 0.9rem; white-space: normal; max-width: 300px; line-height: 1.2;">Katsina State Contributory Healthcare Management Agency</span>
                    <span class="d-block d-md-none">KTSCHMA</span>
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <!-- Mobile Menu Links -->
                    <ul class="navbar-nav me-auto d-md-none border-top mt-3 pt-3">
                        <li class="nav-item">
                            <a class="nav-link text-white" href="dashboard.php">
                                <i class="bi bi-speedometer2 me-2"></i> Dashboard
                            </a>
                        </li>
                        
                        <?php if(isAdmin()): ?>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="users.php">
                                <i class="bi bi-people me-2"></i> Manage Users
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="facilities.php">
                                <i class="bi bi-building me-2"></i> Facilities
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="view_returns.php">
                                <i class="bi bi-file-earmark-text me-2"></i> View Returns
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="reports.php">
                                <i class="bi bi-bar-chart me-2"></i> Reports
                            </a>
                        </li>
                        <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="monthly_return.php">
                                <i class="bi bi-plus-circle me-2"></i> Submit Return
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="my_returns.php">
                                <i class="bi bi-list-check me-2"></i> My Returns
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                    
                    <ul class="navbar-nav ms-auto border-top border-md-0 mt-2 mt-md-0 pt-2 pt-md-0">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle text-white" href="#" role="button" data-bs-toggle="dropdown">
                                <?php echo $_SESSION['full_name']; ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
        <div class="container-fluid">
            <div class="row">
                <!-- Sidebar -->
                <div class="col-md-2 d-none d-md-block sidebar py-4">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                                <i class="bi bi-speedometer2 me-2"></i> Dashboard
                            </a>
                        </li>
                        
                        <?php if(isAdmin()): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>" href="users.php">
                                <i class="bi bi-people me-2"></i> Manage Users
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'facilities.php' ? 'active' : ''; ?>" href="facilities.php">
                                <i class="bi bi-building me-2"></i> Facilities
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'view_returns.php' ? 'active' : ''; ?>" href="view_returns.php">
                                <i class="bi bi-file-earmark-text me-2"></i> View Returns
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>" href="reports.php">
                                <i class="bi bi-bar-chart me-2"></i> Reports
                            </a>
                        </li>
                        <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'monthly_return.php' ? 'active' : ''; ?>" href="monthly_return.php">
                                <i class="bi bi-plus-circle me-2"></i> Submit Return
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'my_returns.php' ? 'active' : ''; ?>" href="my_returns.php">
                                <i class="bi bi-list-check me-2"></i> My Returns
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <li class="nav-item mt-5">
                            <a class="nav-link text-danger" href="logout.php">
                                <i class="bi bi-box-arrow-right me-2"></i> Logout
                            </a>
                        </li>
                    </ul>
                </div>
                <!-- Main Content -->
                <div class="col-md-10 py-4 px-md-4">
                    <h2 class="mb-4 text-secondary"><?php echo $title; ?></h2>
                    <!-- Flash Messages -->
                    <?php if(isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    <?php if(isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
        <?php else: ?>
            <!-- Login Page Style Body -->
            <div class="container">
        <?php endif; ?>
    <?php
}

function getFooter() {
    ?>
            <?php if(isLoggedIn()): ?>
                </div><!-- End Main Content -->
            </div><!-- End Row -->
        </div><!-- End Container -->
        <?php else: ?>
            </div><!-- End Container -->
        <?php endif; ?>
        
        <div class="container text-center py-3 mt-4">
            <small class="text-muted d-block mb-1">Powered By:</small>
            <img src="images/fws_logo.webp" alt="FWS Logo" height="40">
        </div>
        
        <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script>
            $(document).ready(function(){
                $("#liveSearchInput").on("keyup", function() {
                    var value = $(this).val().toLowerCase();
                    $(".table tbody tr").filter(function() {
                        $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
                    });
                });
            });
        </script>
    </body>
    </html>
    <?php
}
?>
