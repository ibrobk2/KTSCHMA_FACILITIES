<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'functions.php';

requireLogin();
$db = Database::getInstance()->getConnection();

$return_id = isset($_GET['return_id']) ? $_GET['return_id'] : null;
$is_admin = isAdmin();
$user_id = $_SESSION['user_id'];

$return_data = null;

if ($return_id) {
    $stmt_r = $db->prepare("SELECT * FROM returns WHERE id = ?");
    $stmt_r->execute([$return_id]);
    $return_data = $stmt_r->fetch(PDO::FETCH_ASSOC);
    
    if (!$return_data) {
        die("Return not found.");
    }
    
    if (!$is_admin && $return_data['user_id'] != $user_id) {
        die("Access denied.");
    }
}

// Define Document Types
$doc_types = [
    'Complete one month Bank Statement',
    'Original Receipt and invoice for all the purchase',
    'Approval for all the payment',
    'Payment voucher duly sign',
    'Justification for the payment of human Resource',
    'Minute of the meeting to justify meeting refreshment',
    'Authentic Receipt'
];

$error = '';
$success = '';

// Handle Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_doc'])) {
    if (!$return_id) die("Return ID required for upload.");
    $reason = cleanInput($_POST['reason']);
    $document_type = cleanInput($_POST['document_type']);
    
    if (isset($_FILES['doc']) && $_FILES['doc']['error'] == 0 && !empty($reason) && !empty($document_type)) {
        $allowed = array('jpg', 'jpeg', 'png', 'pdf');
        $filename = $_FILES['doc']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $new_name = 'doc_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($_FILES['doc']['tmp_name'], 'uploads/receipts/' . $new_name)) {
                $stmt = $db->prepare("INSERT INTO supporting_documents (return_id, user_id, document_type, file_path, reason, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$return_id, $user_id, $document_type, $new_name, $reason]);
                $success = "Document uploaded successfully.";
            } else {
                $error = "Failed to upload file.";
            }
        } else {
            $error = "Invalid file type. Only JPG, PNG, PDF allowed.";
        }
    } else {
        $error = "Document type, reason, and file are required.";
    }
}

// Handle Admin Action (Approve/Reject)
if ($is_admin && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_action'])) {
    $doc_id = $_POST['doc_id'];
    $action = $_POST['action'];
    $rejection_reason = cleanInput($_POST['rejection_reason']);
    
    $status = ($action === 'approve') ? 'Approved' : 'Rejected';
    
    if ($status === 'Rejected' && empty($rejection_reason)) {
        $error = "Rejection reason is required.";
    } else {
        $stmt = $db->prepare("UPDATE supporting_documents SET status = ?, rejection_reason = ? WHERE id = ?");
        $stmt->execute([$status, $rejection_reason, $doc_id]);
        
        // Notify User
        $stmt_doc = $db->prepare("SELECT user_id, return_id FROM supporting_documents WHERE id = ?");
        $stmt_doc->execute([$doc_id]);
        $doc_info = $stmt_doc->fetch(PDO::FETCH_ASSOC);
        
        $msg = "A Supporting Document for Retirement (" . $doc_info['return_id'] . ") has been $status.";
        if ($status === 'Rejected') {
            $msg .= " Reason: $rejection_reason";
        }
        
        $link = "supporting_documents.php?return_id=" . $doc_info['return_id'];
        
        $stmt_n = $db->prepare("INSERT INTO notifications (user_id, message, link, created_at) VALUES (?, ?, ?, NOW())");
        $stmt_n->execute([$doc_info['user_id'], $msg, $link]);
        
        $success = "Action performed successfully.";
    }
}

// Handle Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_doc'])) {
    $doc_id = $_POST['doc_id'];
    // Verify ownership or admin
    $stmt = $db->prepare("SELECT * FROM supporting_documents WHERE id = ?");
    $stmt->execute([$doc_id]);
    $doc = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($doc && ($is_admin || $doc['user_id'] == $user_id)) {
        if (unlink('uploads/receipts/' . $doc['file_path'])) {
            $stmt = $db->prepare("DELETE FROM supporting_documents WHERE id = ?");
            $stmt->execute([$doc_id]);
            $success = "Document deleted.";
        } else {
            $error = "Failed to delete file.";
        }
    }
}

// Fetch Facilities for Filter
$all_facilities = $db->query("SELECT id, facility_name FROM facilities ORDER BY facility_name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Filters
$filter_facility = isset($_GET['f_id']) ? $_GET['f_id'] : '';
$filter_month = isset($_GET['month']) ? $_GET['month'] : '';
$filter_year = isset($_GET['year']) ? $_GET['year'] : '';

// Fetch Documents
if ($return_id) {
    if ($is_admin) {
        $stmt_docs = $db->prepare("SELECT sd.*, us.full_name as user_name, f.facility_name, r.month, r.year 
                                  FROM supporting_documents sd 
                                  JOIN users us ON sd.user_id = us.id 
                                  LEFT JOIN facilities f ON us.facility_id = f.id
                                  LEFT JOIN returns r ON sd.return_id = r.id
                                  WHERE sd.return_id = ? 
                                  ORDER BY sd.created_at DESC");
    } else {
        $stmt_docs = $db->prepare("SELECT sd.* FROM supporting_documents sd WHERE sd.return_id = ? ORDER BY sd.created_at DESC");
    }
    $stmt_docs->execute([$return_id]);
}
 else {
    if ($is_admin) {
        $sql = "SELECT sd.*, us.full_name as user_name, f.facility_name, r.month, r.year 
                FROM supporting_documents sd 
                JOIN users us ON sd.user_id = us.id 
                LEFT JOIN facilities f ON us.facility_id = f.id
                LEFT JOIN returns r ON sd.return_id = r.id
                WHERE 1=1";
        $params = [];
        
        if ($filter_facility) {
            $sql .= " AND us.facility_id = ?";
            $params[] = $filter_facility;
        }
        if ($filter_month) {
            $sql .= " AND r.month = ?";
            $params[] = $filter_month;
        }
        if ($filter_year) {
            $sql .= " AND r.year = ?";
            $params[] = $filter_year;
        }
        
        $sql .= " ORDER BY sd.created_at DESC";
        $stmt_docs = $db->prepare($sql);
        $stmt_docs->execute($params);
    } else {
        $stmt_docs = $db->prepare("SELECT sd.* FROM supporting_documents sd WHERE sd.user_id = ? ORDER BY sd.created_at DESC");
        $stmt_docs->execute([$user_id]);
    }
}
$docs = $stmt_docs->fetchAll(PDO::FETCH_ASSOC);

getHeader('Supporting Documents');
?>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="card bg-light">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <?php if($return_data): ?>
                        <h5 class="mb-1">Retirement Validity: <?php echo $return_data['month'] . ' ' . $return_data['year']; ?></h5>
                        <p class="text-muted mb-0">Program: <?php echo $return_data['program']; ?></p>
                    <?php else: ?>
                        <h5 class="mb-1">All Supporting Documents</h5>
                        <p class="text-muted mb-0"><?php echo $is_admin ? 'Manage all facility document uploads' : 'View all your uploaded documents'; ?></p>
                    <?php endif; ?>
                </div>
                <?php if($return_id): ?>
                    <a href="view_return_detail.php?id=<?php echo $return_id; ?>" class="btn btn-secondary">Back to Details</a>
                <?php else: ?>
                    <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if(!$return_id && $is_admin): ?>
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card bg-white shadow-sm border-0">
            <div class="card-body">
                <form class="row g-3 align-items-end" method="GET">
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Facility</label>
                        <select name="f_id" class="form-select">
                            <option value="">-- All Facilities --</option>
                            <?php foreach($all_facilities as $f): ?>
                                <option value="<?php echo $f['id']; ?>" <?php echo $filter_facility == $f['id'] ? 'selected' : ''; ?>><?php echo $f['facility_name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Month</label>
                        <select name="month" class="form-select">
                            <option value="">-- All Months --</option>
                            <?php 
                            $months = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
                            foreach($months as $m): 
                            ?>
                                <option value="<?php echo $m; ?>" <?php echo $filter_month == $m ? 'selected' : ''; ?>><?php echo $m; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold">Year</label>
                        <select name="year" class="form-select">
                            <option value="">-- All Years --</option>
                            <?php for($y=2024; $y<=2030; $y++): ?>
                                <option value="<?php echo $y; ?>" <?php echo $filter_year == $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search me-1"></i> Filter Results
                        </button>
                    </div>
                </form>
                <hr>
                <div class="d-flex gap-2">
                    <a href="approved_facilities_report.php?view=compliant" class="btn btn-outline-success">
                        <i class="bi bi-check-all me-1"></i> View Compliant Facilities (7/7 Approved)
                    </a>
                    <a href="approved_facilities_report.php?view=incomplete" class="btn btn-outline-warning text-dark">
                        <i class="bi bi-exclamation-triangle me-1"></i> View Incomplete Facilities
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if($return_id): ?>
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card border-primary shadow-sm">
            <div class="card-header bg-primary text-white py-3">
                <i class="bi bi-shield-check me-2"></i> Retirement Validity Checklist
            </div>
            <div class="card-body">
                <p class="small text-muted mb-3">These documents are <strong>Compulsory</strong> for this retirement to be Valid and Reliable:</p>
                <div class="row">
                    <?php 
                    $stmt_check = $db->prepare("SELECT DISTINCT document_type FROM supporting_documents WHERE return_id = ?");
                    $stmt_check->execute([$return_id]);
                    $uploaded_types = $stmt_check->fetchAll(PDO::FETCH_COLUMN);
                    
                    foreach($doc_types as $type): 
                        $is_uploaded = in_array($type, $uploaded_types);
                    ?>
                    <div class="col-md-6 mb-2">
                        <div class="p-2 border rounded-3 d-flex align-items-center <?php echo $is_uploaded ? 'bg-success-light border-success text-success' : 'bg-light'; ?>">
                            <i class="bi <?php echo $is_uploaded ? 'bi-patch-check-fill' : 'bi-dash-circle text-muted'; ?> me-2 fs-5"></i>
                            <span class="<?php echo $is_uploaded ? 'fw-bold' : ''; ?>"><?php echo $type; ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if($error): ?><div class="alert alert-danger shadow-sm border-0"><?php echo $error; ?></div><?php endif; ?>
<?php if($success): ?><div class="alert alert-success shadow-sm border-0"><?php echo $success; ?></div><?php endif; ?>

<div class="row">
    <!-- Upload Section -->
    <?php if(!$is_admin && $return_id): ?>
    <div class="col-md-4">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-primary text-white">Upload New Document</div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase">Document Type <span class="text-danger">*</span></label>
                        <select name="document_type" class="form-select border-2" required>
                            <option value="">-- Select Type --</option>
                            <?php foreach($doc_types as $type): ?>
                                <option value="<?php echo $type; ?>"><?php echo $type; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase">File (JPG, PNG, PDF) <span class="text-danger">*</span></label>
                        <input type="file" name="doc" class="form-control border-2" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase">Reason/Description</label>
                        <textarea name="reason" class="form-control border-2" rows="3" required placeholder="Describe what this document represents (e.g. Bank statement for January 2024)"></textarea>
                    </div>
                    <button type="submit" name="upload_doc" class="btn btn-primary w-100 py-2">
                        <i class="bi bi-cloud-arrow-up me-2"></i> Upload Document
                    </button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-8">
    <?php elseif(!$is_admin && !$return_id): ?>
    <div class="col-md-12">
        <div class="alert alert-info border-0 shadow-sm mb-4">
            <i class="bi bi-info-circle me-2"></i> To upload new documents, please go to a specific <strong>Monthly Return</strong> and click "Manage Retirement Documents".
        </div>
    <?php else: ?>
    <div class="col-md-12">
    <?php endif; ?>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold">Existing Documents</h6>
                <span class="badge bg-primary rounded-pill"><?php echo count($docs); ?> Total</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>File</th>
                                <th>Category</th>
                                <?php if($is_admin): ?><th>Facility Name</th><?php endif; ?>
                                <th>Description</th>
                                <th>Status</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(count($docs) > 0): ?>
                                <?php foreach($docs as $d): ?>
                                <tr>
                                    <td>
                                        <a href="uploads/receipts/<?php echo $d['file_path']; ?>" target="_blank" class="btn btn-sm btn-outline-info">
                                            <i class="bi bi-eye me-1"></i> View
                                        </a>
                                        <br><small class="text-muted"><?php echo formatDate($d['created_at']); ?></small>
                                    </td>
                                    <td><span class="badge bg-secondary rounded-pill"><?php echo $d['document_type']; ?></span></td>
                                    <?php if($is_admin): ?><td><span class="small fw-bold"><?php echo (isset($d['facility_name']) && $d['facility_name']) ? $d['facility_name'] : 'N/A'; ?></span><br><span class="text-muted" style="font-size: 0.75rem;"><?php echo isset($d['user_name']) ? $d['user_name'] : 'N/A'; ?></span></td><?php endif; ?>
                                    <td><span class="small"><?php echo nl2br(cleanInput($d['reason'])); ?></span></td>
                                    <td>
                                        <?php if($d['status'] == 'Approved'): ?>
                                            <span class="badge bg-success"><i class="bi bi-check me-1"></i>Approved</span>
                                        <?php elseif($d['status'] == 'Rejected'): ?>
                                            <span class="badge bg-danger"><i class="bi bi-x me-1"></i>Rejected</span>
                                            <?php if($d['rejection_reason']): ?>
                                                <br><small class="text-danger italic"><?php echo $d['rejection_reason']; ?></small>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark"><i class="bi bi-clock me-1"></i>Pending</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <?php if($is_admin && $d['status'] == 'Pending'): ?>
                                            <div class="btn-group btn-group-sm me-2">
                                                <button type="button" class="btn btn-success" onclick="adminAction('approve', <?php echo $d['id']; ?>)">Approve</button>
                                                <button type="button" class="btn btn-danger" onclick="adminAction('reject', <?php echo $d['id']; ?>)">Reject</button>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if(!$is_admin || $d['user_id'] == $user_id): ?>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Delete this document?');">
                                                <input type="hidden" name="doc_id" value="<?php echo $d['id']; ?>">
                                                <button type="submit" name="delete_doc" class="btn btn-sm btn-link text-danger"><i class="bi bi-trash"></i></button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="6" class="text-center py-5 text-muted small">No documents uploaded yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.bg-success-light { background-color: rgba(25, 135, 84, 0.1); }
.italic { font-style: italic; }
</style>

<!-- Admin Action Modal -->
<div class="modal fade" id="adminModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="adminModalTitle">Review Document</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="doc_id" id="modal_doc_id">
                    <input type="hidden" name="action" id="modal_action">
                    <div id="rejection_reason_div" style="display:none;">
                        <label class="form-label">Reason for Rejection <span class="text-danger">*</span></label>
                        <textarea name="rejection_reason" id="rejection_reason" class="form-control" rows="3"></textarea>
                    </div>
                    <p id="modal_confirm_text" class="mb-0"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="admin_action" class="btn btn-primary">Confirm Action</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function adminAction(action, docId) {
    const modal = new bootstrap.Modal(document.getElementById('adminModal'));
    document.getElementById('modal_doc_id').value = docId;
    document.getElementById('modal_action').value = action;
    const reasonDiv = document.getElementById('rejection_reason_div');
    const reasonInput = document.getElementById('rejection_reason');
    const confirmText = document.getElementById('modal_confirm_text');
    const title = document.getElementById('adminModalTitle');
    
    if (action === 'reject') {
        title.innerText = 'Reject Document';
        reasonDiv.style.display = 'block';
        reasonInput.required = true;
        confirmText.innerText = 'Please provide a reason for rejecting this document.';
    } else {
        title.innerText = 'Approve Document';
        reasonDiv.style.display = 'none';
        reasonInput.required = false;
        confirmText.innerText = 'Are you sure you want to approve this document?';
    }
    
    modal.show();
}
</script>
</script>

<?php getFooter(); ?>
