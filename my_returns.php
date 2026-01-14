<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'functions.php';

requireLogin();
$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];

$stmt = $db->query("SELECT * FROM returns WHERE user_id = $user_id ORDER BY created_at DESC");
$returns = $stmt->fetchAll(PDO::FETCH_ASSOC);

getHeader('My Returns');
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-list-check"></i> My Monthly Returns</span>
        <a href="monthly_return.php" class="btn btn-success btn-sm">
            <i class="bi bi-plus-circle"></i> New Return
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Period</th>
                        <th>Amount Received</th>
                        <th>Status</th>
                        <th>Date Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($returns) > 0): ?>
                        <?php foreach ($returns as $row): ?>
                        <tr>
                            <td><?php echo $row['month'] . ' ' . $row['year']; ?></td>
                            <td><?php echo formatCurrency($row['amount_received']); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $row['status'] == 'Submitted' ? 'success' : 'warning'; ?>">
                                    <?php echo $row['status']; ?>
                                </span>
                            </td>
                            <td><?php echo formatDate($row['created_at']); ?></td>
                            <td>
                                <a href="view_return_detail.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary">
                                    <i class="bi bi-eye"></i> View
                                </a>
                                <a href="edit_return.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning">
                                    <i class="bi bi-pencil"></i> Edit
                                </a>
                                <?php if($row['status'] == 'Draft'): ?>
                                <a href="delete_return.php?id=<?php echo $row['id']; ?>" 
                                   class="btn btn-sm btn-danger"
                                   onclick="return confirm('Delete this return?')">
                                    <i class="bi bi-trash"></i>
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center">No returns found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php getFooter(); ?>
