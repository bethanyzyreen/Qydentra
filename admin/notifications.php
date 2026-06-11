<?php
$allowed_roles = ['admin'];
include("../includes/auth_check.php");
require_once("../includes/admin_helpers.php");
ensure_admin_tables_exist($conn);

$message = '';
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'send_notification') {
        $title       = safe_input($conn, $_POST['title']       ?? '');
        $msg_text    = safe_input($conn, $_POST['message']     ?? '');
        $target_type = safe_input($conn, $_POST['target_type'] ?? 'all');
        $target_id   = safe_input($conn, $_POST['target_id']   ?? '');

        if ($title === '' || $msg_text === '') {
            $message = 'Please provide both title and message.'; $messageType = 'error';
        } else {
            $tid_sql = $target_id !== '' ? "'$target_id'" : "NULL";
            $sql = "INSERT INTO admin_notifications (author_id,target_type,target_id,title,message) VALUES ('{$_SESSION['user_id']}','$target_type',$tid_sql,'$title','$msg_text')";
            if (mysqli_query($conn, $sql)) {
                $delivery_ok = true;
                if ($target_type === 'patients') {
                    if ($target_id !== '') {
                        $delivery_ok = notify_patient($conn, $target_id, $title, $msg_text, 'System');
                    } else {
                        $delivery_ok = notify_all_patients($conn, $title, $msg_text, 'System');
                    }
                } elseif ($target_type === 'receptionists') {
                    $delivery_ok = $target_id !== '' ?
                        notify_receptionists($conn, $title, $msg_text, 'System', null, $target_id) :
                        notify_receptionists($conn, $title, $msg_text, 'System');
                } else {
                    if ($target_id !== '') {
                        $target_id_prefix = strtoupper(substr($target_id, 0, 2));
                        if ($target_id_prefix === 'PT') {
                            $delivery_ok = notify_patient($conn, $target_id, $title, $msg_text, 'System');
                        } else {
                            $delivery_ok = notify_receptionists($conn, $title, $msg_text, 'System', null, $target_id);
                        }
                    } else {
                        $delivery_ok = notify_all_patients($conn, $title, $msg_text, 'System')
                            && notify_receptionists($conn, $title, $msg_text, 'System');
                    }
                }

                if (!$delivery_ok) {
                    error_log('[Qydentra] admin notification delivery failed: target_type=' . $target_type . ', target_id=' . $target_id);
                    $message = 'Notification was recorded, but delivery to recipients failed.';
                    $messageType = 'error';
                } else {
                    $message = 'Notification sent successfully.';
                }

                log_admin_action($conn, $_SESSION['user_id'], 'Send notification', "Sent '$title' to $target_type");
            } else {
                $message = 'Error: ' . mysqli_error($conn); $messageType = 'error';
            }
        }
    }

    if ($action === 'delete_notification') {
        $nid = safe_input($conn, $_POST['notification_id'] ?? '');
        if ($nid !== '' && mysqli_query($conn, "DELETE FROM admin_notifications WHERE admin_notification_id='$nid'")) {
            log_admin_action($conn, $_SESSION['user_id'], 'Delete notification', "Deleted notification $nid");
            $message = 'Notification deleted.';
        } else {
            $message = 'Unable to delete notification.'; $messageType = 'error';
        }
    }
}

$notifications = mysqli_query($conn, "SELECT * FROM admin_notifications ORDER BY created_at DESC");
$totalNotifs = mysqli_num_rows($notifications);
mysqli_data_seek($notifications, 0);
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

<div class="grid-2" style="align-items:start;">

<!-- COMPOSE FORM -->
<div class="form-card hover-glow" style="margin-bottom:0;">
    <h2><i class="fa-solid fa-paper-plane" style="color:#ffffff; margin-right:8px;"></i>Compose Notification</h2>
    <form method="POST">
        <input type="hidden" name="action" value="send_notification">
        <div class="form-group">
            <label>Title</label>
            <input type="text" name="title" placeholder="Notification title" required>
        </div>
        <div class="form-group">
            <label>Message</label>
            <textarea name="message" rows="5" placeholder="Write your announcement here…" required></textarea>
        </div>
        <div class="form-grid-2">
            <div class="form-group">
                <label>Target Audience</label>
                <select name="target_type" required>
                    <option value="all">All Users</option>
                    <option value="patients">Patients Only</option>
                    <option value="receptionists">Receptionists Only</option>
                </select>
            </div>
            <div class="form-group">
                <label>Specific Target ID (optional)</label>
                <input type="text" name="target_id" placeholder="e.g. PT001">
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn-primary">
                <i class="fa-solid fa-paper-plane"></i> Send Notification
            </button>
        </div>
    </form>
</div>

<!-- QUICK STATS -->
<div style="display:flex; flex-direction:column; gap:16px;">
    <div class="stat-card hover-glow">
        <h3><i class="fa-solid fa-bell" style="margin-right:6px;"></i> Total Sent</h3>
        <p><?php echo $totalNotifs; ?></p>
    </div>
    <div style="padding:20px 24px; border-radius:18px; background:rgba(96,165,250,0.06); border:1px solid rgba(96,165,250,0.12);">
        <h3 style="font-size:13px; font-weight:600; color:#94a3b8; margin-bottom:12px; text-transform:uppercase; letter-spacing:0.5px;">Audience Guide</h3>
        <div style="display:flex; flex-direction:column; gap:8px; font-size:13px; color:#d1d5db; line-height:1.6;">
            <div><i class="fa-solid fa-users" style="color:#60a5fa; width:18px;"></i> <strong>All Users</strong> — patients + receptionists</div>
            <div><i class="fa-solid fa-user" style="color:#4ade80; width:18px;"></i> <strong>Patients Only</strong> — visible in patient portal</div>
            <div><i class="fa-solid fa-id-badge" style="color:#fbbf24; width:18px;"></i> <strong>Receptionists Only</strong> — staff-facing</div>
            <div><i class="fa-solid fa-bullseye" style="color:#ffffff; width:18px;"></i> <strong>Specific ID</strong> — target one account</div>
        </div>
    </div>
</div>

</div><!-- end grid-2 -->

<!-- NOTIFICATIONS LIST -->
<div class="table-container hover-glow" style="margin-top:24px;">
    <div class="table-header">
        <div>
            <h2><i class="fa-solid fa-paper-plane" style="color:#ffffff; margin-right:8px;"></i>Sent Notifications</h2>
            <p><?php echo $totalNotifs; ?> notification<?php echo $totalNotifs != 1 ? 's' : ''; ?> on record.</p>
        </div>
    </div>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Message</th>
                <th>Audience</th>
                <th>Sent</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($totalNotifs > 0): ?>
                <?php while ($n = mysqli_fetch_assoc($notifications)): ?>
                <tr>
                    <td style="color:#ffffff; font-size:12px;"><?php echo htmlspecialchars($n['admin_notification_id']); ?></td>
                    <td><strong style="color:#e5e7eb;"><?php echo htmlspecialchars($n['title']); ?></strong></td>
                    <td style="max-width:240px; font-size:13px; color:#94a3b8;"><?php echo htmlspecialchars($n['message']); ?></td>
                    <td>
                        <span class="status-pill <?php echo $n['target_type'] === 'all' ? 'approved' : ($n['target_type'] === 'patients' ? 'completed' : 'pending'); ?>">
                            <?php echo ucfirst($n['target_type']); ?>
                            <?php echo $n['target_id'] ? ' / ' . htmlspecialchars($n['target_id']) : ''; ?>
                        </span>
                    </td>
                    <td><div class="table-date"><i class="fa-solid fa-clock"></i><?php echo date('M d, Y g:i A', strtotime($n['created_at'])); ?></div></td>
                    <td>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this notification?');">
                            <input type="hidden" name="action" value="delete_notification">
                            <input type="hidden" name="notification_id" value="<?php echo htmlspecialchars($n['admin_notification_id']); ?>">
                            <button type="submit" class="action-btn-sm cancel-sm" title="Delete"><i class="fa-solid fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="6" style="text-align:center; padding:30px; color:#9ca3af;">No notifications sent yet.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</div>
</div>
</body>
</html>
