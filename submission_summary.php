<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'functions.php';

requireAdmin();
$db = Database::getInstance()->getConnection();

// Default to current month/year
// Default to current month/year
$selected_month_num = isset($_GET['month']) ? $_GET['month'] : date('m');
$selected_year = isset($_GET['year']) ? $_GET['year'] : date('Y');
$selected_program = isset($_SESSION['program']) ? $_SESSION['program'] : 'Formal Sector';

// Convert month number to name for query
$selected_month = date('F', mktime(0, 0, 0, $selected_month_num, 10));

// --- DATA FETCHING ---
// 1. All Facilities
$facilities = $db->query("SELECT * FROM facilities ORDER BY facility_name ASC")->fetchAll(PDO::FETCH_ASSOC);

// 2. Submitted Returns
$stmt = $db->prepare("SELECT facility_id, status FROM returns WHERE month = ? AND year = ? AND program = ?");
$stmt->execute([$selected_month, $selected_year, $selected_program]);
$submitted_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

$submitted_ids = [];
foreach ($submitted_data as $row) {
    $submitted_ids[] = $row['facility_id'];
}

// 3. Lists
$list_submitted = [];
$list_pending = [];

foreach ($facilities as $fac) {
    if (in_array($fac['id'], $submitted_ids)) {
        $list_submitted[] = $fac;
    } else {
        $list_pending[] = $fac;
    }
}

// --- ACTIONS ---

// Export to Excel
if (isset($_GET['export']) && $_GET['export'] == 'defaulters') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="defaulters_' . $selected_month . '_' . $selected_year . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Facility Name', 'Facility Code', 'Program', 'Month', 'Year']);
    
    foreach ($list_pending as $fac) {
        fputcsv($output, [
            $fac['facility_name'], 
            $fac['facility_code'], 
            $selected_program,
            $selected_month,
            $selected_year
        ]);
    }
    
    fclose($output);
    exit();
}

// Send Single Reminder
if (isset($_POST['send_reminder'])) {
    $fac_id = $_POST['facility_id'];
    $prog = $_POST['program'];
    
    // Get Users for Facility
    $stmt = $db->prepare("SELECT id FROM users WHERE facility_id = ?");
    $stmt->execute([$fac_id]);
    $users = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $message = "REMINDER: You have not submitted your Monthly Return for $selected_month/$selected_year ($prog). Please submit immediately.";
    
    $count = 0;
    foreach ($users as $uid) {
        $stmt_ins = $db->prepare("INSERT INTO notifications (user_id, message, created_at) VALUES (?, ?, NOW())");
        $stmt_ins->execute([$uid, $message]);
        $count++;
    }
    
    $_SESSION['success'] = "Reminder sent to $count users.";
    header("Location: submission_summary.php?month=$selected_month&year=$selected_year");
    exit();
}

// Send Bulk Reminder
if (isset($_POST['send_reminder_all'])) {
    $message = "REMINDER: You have not submitted your Monthly Return for $selected_month/$selected_year ($selected_program). Please submit immediately.";
    $total_notified = 0;
    
    foreach ($list_pending as $fac) {
        $stmt_u = $db->prepare("SELECT id FROM users WHERE facility_id = ?");
        $stmt_u->execute([$fac['id']]);
        $users = $stmt_u->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($users as $uid) {
            $stmt_ins = $db->prepare("INSERT INTO notifications (user_id, message, created_at) VALUES (?, ?, NOW())");
            $stmt_ins->execute([$uid, $message]);
            $total_notified++;
        }
    }
    
    $_SESSION['success'] = "Bulk reminder sent to $total_notified users across " . count($list_pending) . " facilities.";
    header("Location: submission_summary.php?month=$selected_month_num&year=$selected_year");
    exit();
}

getHeader('Submission Summary');
?>

<div class="row">
    <div class="col-md-12 mb-4">
        <div class="card bg-light">
            <div class="card-body">
                <form class="row g-3 align-items-center" method="GET">
                    <div class="col-auto">
                        <label class="col-form-label">Filter:</label>
                    </div>
                    <div class="col-auto">
                        <select name="month" class="form-select">
                            <?php for($m=1; $m<=12; $m++): 
                                $m_str = str_pad($m, 2, '0', STR_PAD_LEFT);
                                $m_name = date('F', mktime(0, 0, 0, $m, 10));
                            ?>
                                <option value="<?php echo $m_str; ?>" <?php echo $selected_month_num == $m_str ? 'selected' : ''; ?>><?php echo $m_name; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-auto">
                        <select name="year" class="form-select">
                            <?php for($y=2024; $y<=2030; $y++): ?>
                                <option value="<?php echo $y; ?>" <?php echo $selected_year == $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary">Filter</button>
                    </div>
                    <div class="col-auto">
                        <span class="badge bg-secondary p-2 mt-1"><?php echo $selected_program; ?></span>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Pending List -->
    <div class="col-md-6">
        <div class="card border-warning mb-3">
            <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
                <strong><i class="bi bi-x-circle"></i> Not Submitted (<?php echo count($list_pending); ?>)</strong>
                <div>
                   <?php if(count($list_pending) > 0): ?>
                        <form method="POST" class="d-inline" onsubmit="return confirm('Send notification to ALL defaulters?');">
                            <button type="submit" name="send_reminder_all" class="btn btn-sm btn-dark">
                                <i class="bi bi-bell-fill"></i> Remind All
                            </button>
                        </form>
                        <a href="submission_summary.php?month=<?php echo $selected_month; ?>&year=<?php echo $selected_year; ?>&export=defaulters" class="btn btn-sm btn-outline-dark" target="_blank">
                            <i class="bi bi-file-earmark-excel"></i> Export
                        </a>
                   <?php endif; ?>
                </div>
            </div>
            <div class="list-group list-group-flush">
                <?php if(count($list_pending) > 0): ?>
                    <?php foreach($list_pending as $fac): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong><?php echo $fac['facility_name']; ?></strong><br>
                                <small class="text-muted"><?php echo $fac['facility_code']; ?></small>
                            </div>
                            <form method="POST" onsubmit="return confirm('Send notification to all users of this facility?');">
                                <input type="hidden" name="facility_id" value="<?php echo $fac['id']; ?>">
                                <input type="hidden" name="program" value="<?php echo $selected_program; ?>">
                                <button type="submit" name="send_reminder" class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-bell"></i> Remind
                                </button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="list-group-item text-center text-success"><i class="bi bi-check-all"></i> Everyone has submitted!</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Submitted List -->
    <div class="col-md-6">
        <div class="card border-success mb-3">
            <div class="card-header bg-success text-white">
                <strong><i class="bi bi-check-circle"></i> Submitted (<?php echo count($list_submitted); ?>)</strong>
            </div>
            <div class="list-group list-group-flush">
                <?php if(count($list_submitted) > 0): ?>
                    <?php foreach($list_submitted as $fac): ?>
                        <div class="list-group-item">
                            <i class="bi bi-check text-success"></i> 
                            <?php echo $fac['facility_name']; ?> 
                            <small class="text-muted">(<?php echo $fac['facility_code']; ?>)</small>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="list-group-item text-center text-muted">No submissions yet.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php getFooter(); ?>
