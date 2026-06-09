<?php
$allowed_roles = ['admin'];
include("../includes/auth_check.php");
require_once("../includes/admin_helpers.php");
ensure_admin_tables_exist($conn);

$message = '';
$messageType = 'success';
$editService = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action      = $_POST['action'] ?? '';
    $name        = safe_input($conn, $_POST['service_name'] ?? '');
    $description = safe_input($conn, $_POST['service_description'] ?? '');
    $duration    = safe_input($conn, $_POST['duration'] ?? '');

    if ($action === 'add_service') {
        if ($name === '') {
            $message = 'Please provide a service name.'; $messageType = 'error';
        } else {
            if (mysqli_query($conn, "INSERT INTO services (service_name,service_description,duration) VALUES ('$name','$description','$duration')")) {
                log_admin_action($conn, $_SESSION['user_id'], 'Add service', "Added service $name");
                $message = 'Service added successfully.';
            } else {
                $message = 'Error: ' . mysqli_error($conn); $messageType = 'error';
            }
        }
    }

    if ($action === 'edit_service') {
        $service_id = safe_input($conn, $_POST['service_id'] ?? '');
        if ($service_id === '' || $name === '') {
            $message = 'Please provide a service name.'; $messageType = 'error';
        } else {
            if (mysqli_query($conn, "UPDATE services SET service_name='$name',service_description='$description',duration='$duration' WHERE service_id='$service_id'")) {
                log_admin_action($conn, $_SESSION['user_id'], 'Edit service', "Updated service $service_id");
                $message = 'Service updated successfully.';
            } else {
                $message = 'Error: ' . mysqli_error($conn); $messageType = 'error';
            }
        }
    }

    if ($action === 'delete_service') {
        $service_id = safe_input($conn, $_POST['service_id'] ?? '');
        if ($service_id !== '') {
            if (mysqli_query($conn, "DELETE FROM services WHERE service_id='$service_id'")) {
                log_admin_action($conn, $_SESSION['user_id'], 'Delete service', "Deleted service $service_id");
                $message = 'Service deleted successfully.';
            } else {
                $message = 'Error: ' . mysqli_error($conn); $messageType = 'error';
            }
        }
    }
}

if (isset($_GET['edit'])) {
    $sid = safe_input($conn, $_GET['edit']);
    $editService = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM services WHERE service_id='$sid' LIMIT 1"));
}

$services = mysqli_query($conn, "SELECT * FROM services ORDER BY service_name ASC");
$totalServices = mysqli_num_rows($services);
mysqli_data_seek($services, 0);
?>
<?php include("../includes/admin_header.php"); ?>
<body>
<?php include("../includes/admin_sidebar.php"); ?>

<div class="main">
<?php include("../includes/admin_topbar.php"); ?>

<?php if ($message !== ''): ?>
<div class="alert-msg <?php echo $messageType; ?>">
    <i class="fa-solid fa-<?php echo $messageType === 'success' ? 'circle-check' : 'circle-exclamation'; ?>"></i>
    <?php echo htmlspecialchars($message); ?>
</div>
<?php endif; ?>

<!-- FORM -->
<div class="form-card hover-glow">
    <h2>
        <i class="fa-solid fa-<?php echo $editService ? 'pen-to-square' : 'plus-circle'; ?>" style="color:#60a5fa; margin-right:8px;"></i>
        <?php echo $editService ? 'Edit Service' : 'Add Service'; ?>
    </h2>
    <form method="POST">
        <input type="hidden" name="action" value="<?php echo $editService ? 'edit_service' : 'add_service'; ?>">
        <?php if ($editService): ?>
            <input type="hidden" name="service_id" value="<?php echo htmlspecialchars($editService['service_id']); ?>">
        <?php endif; ?>

        <div class="form-grid-2" style="max-width:640px;">
            <div class="form-group">
                <label>Service Name</label>
                <input type="text" name="service_name" placeholder="e.g. Teeth Cleaning" value="<?php echo htmlspecialchars($editService['service_name'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label>Duration</label>
                <input type="text" name="duration" placeholder="e.g. 30 mins" value="<?php echo htmlspecialchars($editService['duration'] ?? ''); ?>">
            </div>
        </div>
        <div class="form-group">
            <label>Description</label>
            <textarea name="service_description" placeholder="Brief description of the service…"><?php echo htmlspecialchars($editService['service_description'] ?? ''); ?></textarea>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn-primary">
                <i class="fa-solid fa-<?php echo $editService ? 'floppy-disk' : 'plus'; ?>"></i>
                <?php echo $editService ? 'Save Changes' : 'Add Service'; ?>
            </button>
            <?php if ($editService): ?>
                <a href="service_management.php" class="btn-secondary"><i class="fa-solid fa-xmark"></i> Cancel</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- SERVICE TABLE -->
<div class="table-container hover-glow">
    <div class="table-header">
        <div>
            <h2>Dental Services</h2>
            <p><?php echo $totalServices; ?> service<?php echo $totalServices != 1 ? 's' : ''; ?> available.</p>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Service Name</th>
                <th>Description</th>
                <th>Duration</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($totalServices > 0): ?>
                <?php while ($svc = mysqli_fetch_assoc($services)): ?>
                <tr>
                    <td style="color:#64748b; font-size:12px;"><?php echo htmlspecialchars($svc['service_id']); ?></td>
                    <td>
                        <div style="display:flex; align-items:center; gap:10px;">
                            <div style="width:34px; height:34px; border-radius:10px; background:rgba(96,165,250,0.10); border:1px solid rgba(96,165,250,0.18); display:flex; align-items:center; justify-content:center; color:#60a5fa; flex-shrink:0;">
                                <i class="fa-solid fa-tooth" style="font-size:13px;"></i>
                            </div>
                            <strong><?php echo htmlspecialchars($svc['service_name']); ?></strong>
                        </div>
                    </td>
                    <td style="max-width:240px; font-size:13px; color:#94a3b8;"><?php echo htmlspecialchars($svc['service_description'] ?: '—'); ?></td>
                    <td style="font-size:13px; color:#94a3b8;"><?php echo htmlspecialchars($svc['duration'] ?: '—'); ?></td>
                    <td><div class="table-date"><i class="fa-solid fa-calendar-days"></i><?php echo date('M d, Y', strtotime($svc['created_at'])); ?></div></td>
                    <td>
                        <div class="action-group">
                            <a href="service_management.php?edit=<?php echo urlencode($svc['service_id']); ?>" class="action-btn-sm edit" title="Edit">
                                <i class="fa-solid fa-pen"></i>
                            </a>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this service?');">
                                <input type="hidden" name="action" value="delete_service">
                                <input type="hidden" name="service_id" value="<?php echo htmlspecialchars($svc['service_id']); ?>">
                                <button type="submit" class="action-btn-sm cancel-sm" title="Delete"><i class="fa-solid fa-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="6" style="text-align:center; padding:30px; color:#9ca3af;">No services added yet.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</div>
</div>
</body>
</html>
