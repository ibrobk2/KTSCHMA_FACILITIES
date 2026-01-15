<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'functions.php';

requireAdmin();

$db = Database::getInstance()->getConnection();
$stmt = $db->query("SELECT * FROM facilities ORDER BY facility_name ASC");
$facilities = $stmt->fetchAll(PDO::FETCH_ASSOC);

getHeader('Facilities');
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-building"></i> Facilities List</span>
        <a href="add_facility.php" class="btn btn-success btn-sm">
            <i class="bi bi-plus-circle"></i> Add New Facility
        </a>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <input type="text" id="liveSearchInput" class="form-control" placeholder="Search Facilities...">
        </div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Facility Name</th>
                        <th>Code</th>
                        <th>LGA</th>
                        <th>Address</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($facilities) > 0): ?>
                        <?php foreach ($facilities as $fac): ?>
                        <tr>
                            <td><?php echo cleanInput($fac['facility_name']); ?></td>
                            <td><?php echo cleanInput($fac['facility_code']); ?></td>
                            <td><?php echo cleanInput($fac['lga']); ?></td>
                            <td><?php echo cleanInput($fac['address']); ?></td>
                            <td>
                                <a href="edit_facility.php?id=<?php echo $fac['id']; ?>" class="btn btn-sm btn-warning">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="delete_facility.php?id=<?php echo $fac['id']; ?>" 
                                   class="btn btn-sm btn-danger"
                                   onclick="return confirm('Delete this facility? Users associated with it may lose their linkage.')">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center">No facilities found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php getFooter(); ?>
