<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'functions.php';

// Ensure user is logged in
if (!isLoggedIn()) {
    header("Location: index.php");
    exit();
}

// Handle Selection
if (isset($_GET['program'])) {
    $program = $_GET['program'];
    $valid_programs = ['Formal Sector', 'BHCPF', 'Others'];
    
    if (in_array($program, $valid_programs)) {
        $_SESSION['program'] = $program;
        header("Location: dashboard.php");
        exit();
    }
}

getHeader('Select Program');
?>

<div class="row justify-content-center mt-5">
    <div class="col-md-10">
        <div class="text-center mb-5">
            <h2 class="text-success fw-bold">Select Programme</h2>
            <p class="lead">Please choose the programme you wish to manage.</p>
        </div>
        
        <div class="row justify-content-center">
            <!-- Formal Sector -->
            <div class="col-md-4 mb-4">
                <div class="card h-100 shadow-sm hover-effect text-center p-4">
                    <div class="card-body">
                        <div class="mb-3 text-success">
                            <i class="bi bi-building-fill-gear display-4"></i>
                        </div>
                        <h4 class="card-title mb-3">Formal Sector</h4>
                        <p class="card-text text-muted mb-4">Manage returns and data for the Formal Sector programme.</p>
                        <a href="?program=Formal Sector" class="btn btn-outline-success stretched-link">Select Programme</a>
                    </div>
                </div>
            </div>

            <!-- BHCPF -->
            <div class="col-md-4 mb-4">
                <div class="card h-100 shadow-sm hover-effect text-center p-4">
                    <div class="card-body">
                        <div class="mb-3 text-primary">
                            <i class="bi bi-hospital display-4"></i>
                        </div>
                        <h4 class="card-title mb-3">BHCPF</h4>
                        <p class="card-text text-muted mb-4">Manage Basic Health Care Provision Fund activities.</p>
                        <a href="?program=BHCPF" class="btn btn-outline-primary stretched-link">Select Programme</a>
                    </div>
                </div>
            </div>

            <!-- Others -->
            <div class="col-md-4 mb-4">
                <div class="card h-100 shadow-sm hover-effect text-center p-4">
                    <div class="card-body">
                        <div class="mb-3 text-warning">
                            <i class="bi bi-grid-3x3-gap display-4"></i>
                        </div>
                        <h4 class="card-title mb-3">Others</h4>
                        <p class="card-text text-muted mb-4">Access other miscellaneous programmes and funds.</p>
                        <a href="?program=Others" class="btn btn-outline-warning stretched-link">Select Programme</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .hover-effect {
        transition: transform 0.2s, box-shadow 0.2s;
        border-top: 5px solid transparent;
    }
    .hover-effect:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1);
    }
    .card:hover {
        border-color: #eee;
    }
    /* Specific Top Borders for visual cue */
    .col-md-4:nth-child(1) .card { border-top-color: var(--primary-green); }
    .col-md-4:nth-child(2) .card { border-top-color: #0d6efd; }
    .col-md-4:nth-child(3) .card { border-top-color: #ffc107; }
</style>

<?php getFooter(); ?>
