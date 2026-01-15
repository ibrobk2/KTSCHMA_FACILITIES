<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'functions.php';

requireLogin();
$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];

// Mark all as read
if (isset($_GET['action']) && $_GET['action'] == 'read_all') {
    $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $_SESSION['success'] = "All notifications marked as read.";
    header("Location: notifications.php");
    exit();
}

// Fetch Notifications
$stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

getHeader('Notifications');
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-bell"></i> Notifications</span>
                <a href="notifications.php?action=read_all" class="btn btn-sm btn-outline-secondary">Mark All as Read</a>
            </div>
            <div class="list-group list-group-flush">
                <?php if (count($notifications) > 0): ?>
                    <?php foreach ($notifications as $note): ?>
                        <div class="list-group-item <?php echo $note['is_read'] ? '' : 'bg-light'; ?>">
                            <div class="d-flex w-100 justify-content-between">
                                <div class="w-100">
                                    <p class="mb-1"><?php echo nl2br(cleanInput($note['message'])); ?></p>
                                    <?php if (!empty($note['link'])): ?>
                                        <a href="<?php echo $note['link']; ?>" class="btn btn-sm btn-primary mt-2">
                                            <i class="bi bi-eye"></i> View Request
                                        </a>
                                    <?php endif; ?>
                                    <small class="text-muted d-block mt-1"><?php echo formatDate($note['created_at']); ?></small>
                                </div>
                                <div class="ms-3 text-end" style="min-width: 40px;">
                                    <a href="delete_notification.php?id=<?php echo $note['id']; ?>" class="text-danger" onclick="return confirm('Delete this notification?');" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </div>
                            </div>
                            <?php if (!$note['is_read']): ?>
                                <span class="badge bg-primary rounded-pill">New</span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="list-group-item text-center text-muted py-4">No notifications found.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php getFooter(); ?>
