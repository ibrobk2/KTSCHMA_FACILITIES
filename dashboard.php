<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'functions.php';

requireLogin();
$db = Database::getInstance()->getConnection();

// Statistics Logic
// Statistics Logic
if (isAdmin()) {
    // Admin Stats
    // Assuming facilities and users are GLOBAL (not program specific), we might keep them total or filter.
    // Requirement says "Each of the programme has exact features". Likely users/facilities are shared but returns are split.
    // Let's filter Returns related stats by program.
    $program = $_SESSION['program'];
    
    $total_facilities = $db->query("SELECT COUNT(*) FROM facilities")->fetchColumn(); // Facilities are likely shared
    $total_users = $db->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn(); // Users are likely shared
    
    $pending_returns = $db->prepare("SELECT COUNT(*) FROM returns WHERE status = 'Submitted' AND program = ?");
    $pending_returns->execute([$program]);
    $pending_returns = $pending_returns->fetchColumn();
    
    // Recent returns for admin
    $recent_stmt = $db->prepare("SELECT r.*, f.facility_name, u.full_name 
                              FROM returns r 
                              JOIN facilities f ON r.facility_id = f.id 
                              JOIN users u ON r.user_id = u.id 
                              WHERE r.program = ?
                              ORDER BY r.created_at DESC LIMIT 5");
    $recent_stmt->execute([$program]);
    $recent_returns = $recent_stmt->fetchAll(PDO::FETCH_ASSOC);

} else {
    // User Stats
    $user_id = $_SESSION['user_id'];
    $program = $_SESSION['program'];
    
    $my_returns_count = $db->prepare("SELECT COUNT(*) FROM returns WHERE user_id = ? AND program = ?");
    $my_returns_count->execute([$user_id, $program]);
    $my_returns_count = $my_returns_count->fetchColumn();
    
    // Calculate total utilization for this user & program
    $total_utilization = $db->prepare("
        SELECT SUM(u.amount) 
        FROM utilizations u 
        JOIN returns r ON u.return_id = r.id 
        WHERE r.user_id = ? AND r.program = ? AND u.status = 'Approved'
    ");
    $total_utilization->execute([$user_id, $program]);
    $total_utilization = $total_utilization->fetchColumn();
    
    // Recent returns for user
    $recent_stmt = $db->prepare("SELECT * FROM returns WHERE user_id = ? AND program = ? ORDER BY created_at DESC LIMIT 5");
    $recent_stmt->execute([$user_id, $program]);
    $recent_returns = $recent_stmt->fetchAll(PDO::FETCH_ASSOC);
}

getHeader('Dashboard');
?>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="card bg-success text-white">
            <div class="card-body p-4">
                <h3>Welcome back, <?php echo $_SESSION['full_name']; ?>!</h3>
                <p class="mb-0">
                    <?php echo isAdmin() ? 'Administrator Panel' : 'Facility Manager Dashboard'; ?>
                </p>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <?php if (isAdmin()): ?>
        <div class="col-md-4">
            <div class="card border-primary h-100">
                <div class="card-body text-center">
                    <h5 class="card-title text-muted">Total Facilities</h5>
                    <h2 class="display-4 text-primary"><?php echo $total_facilities; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-info h-100">
                <div class="card-body text-center">
                    <h5 class="card-title text-muted">Registered Users</h5>
                    <h2 class="display-4 text-info"><?php echo $total_users; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-warning h-100">
                <div class="card-body text-center">
                    <h5 class="card-title text-muted">Returns to Review</h5>
                    <h2 class="display-4 text-warning"><?php echo $pending_returns; ?></h2>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="col-md-6">
            <div class="card border-primary h-100">
                <div class="card-body text-center">
                    <h5 class="card-title text-muted">My Returns Submitted</h5>
                    <h2 class="display-4 text-primary"><?php echo $my_returns_count; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-success h-100">
                <div class="card-body text-center">
                    <h5 class="card-title text-muted">Total Utilized Funds</h5>
                    <h2 class="display-4 text-success"><?php echo formatCurrency($total_utilization ?: 0); ?></h2>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-header">
        <i class="bi bi-clock-history"></i> Recent Activity
    </div>
    <div class="card-body">
        <div class="mb-3">
             <input type="text" id="liveSearchInput" class="form-control" placeholder="Search Recent Activity...">
        </div>
        <?php if (count($recent_returns) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <?php if(isAdmin()): ?><th>Facility</th><?php endif; ?>
                            <th>Period</th>
                            <th>Amount Received</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($recent_returns as $return): ?>
                        <tr>
                            <td><?php echo formatDate($return['created_at']); ?></td>
                            <?php if(isAdmin()): ?>
                                <td><?php echo isset($return['facility_name']) ? $return['facility_name'] : 'N/A'; ?></td>
                            <?php endif; ?>
                            <td><?php echo $return['month'] . ' ' . $return['year']; ?></td>
                            <td><?php echo formatCurrency($return['amount_received']); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $return['status'] == 'Submitted' ? 'success' : 'secondary'; ?>">
                                    <?php echo $return['status']; ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-center text-muted my-3">No recent returns found.</p>
        <?php endif; ?>
    </div>
</div>

<?php getFooter(); ?>
