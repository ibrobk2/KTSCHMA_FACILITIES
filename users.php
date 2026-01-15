<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'functions.php';

requireAdmin();

$db = Database::getInstance()->getConnection();
// Updated query to handle cases where facility might be deleted or null
$stmt = $db->query("
    SELECT u.*, f.facility_name 
    FROM users u
    LEFT JOIN facilities f ON u.facility_id = f.id
    WHERE u.role = 'user'
    ORDER BY u.created_at DESC
");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

getHeader('Manage Users');
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-people"></i> Users List</span>
        <a href="add_user.php" class="btn btn-success btn-sm">
            <i class="bi bi-plus-circle"></i> Add New User
        </a>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <input type="text" id="liveSearchInput" class="form-control" placeholder="Search Users...">
        </div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Facility</th>
                        <th>LGA</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($users) > 0): ?>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo cleanInput($user['full_name']); ?></td>
                            <td><?php echo cleanInput($user['email']); ?></td>
                            <td><?php echo cleanInput($user['phone_number']); ?></td>
                            <td><?php echo $user['facility_name'] ? cleanInput($user['facility_name']) : '<span class="text-muted">N/A</span>'; ?></td>
                            <td><?php echo cleanInput($user['lga']); ?></td>
                            <td>
                                <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-warning">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="delete_user.php?id=<?php echo $user['id']; ?>" 
                                   class="btn btn-sm btn-danger"
                                   onclick="return confirm('Are you sure you want to delete this user? This will also delete their returns.')">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center">No users found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php getFooter(); ?>
