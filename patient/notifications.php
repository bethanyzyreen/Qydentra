<?php
$allowed_roles = ['patient'];
include("../includes/auth_check.php");

$user_id     = $_SESSION['user_id'];                           // VARCHAR e.g. PT001
$uid_esc     = mysqli_real_escape_string($conn, $user_id);

/* ================= MARK SINGLE AS READ ================= */
if (isset($_POST['mark_read']) && !empty($_POST['notif_id'])) {
    $notif_id = mysqli_real_escape_string($conn, $_POST['notif_id']);  // VARCHAR e.g. PN001

    $ok = mysqli_query($conn, "
        UPDATE patient_notifications
        SET is_read = 1
        WHERE notification_id = '$notif_id'
          AND patient_id = '$uid_esc'
    ");
    if (!$ok) {
        error_log('[Qydentra] mark_read failed: ' . mysqli_error($conn));
    }
    header("Location: notifications.php");
    exit();
}

/* ================= MARK ALL AS READ ================= */
if (isset($_POST['mark_all_read'])) {
    $ok = mysqli_query($conn, "
        UPDATE patient_notifications
        SET is_read = 1
        WHERE patient_id = '$uid_esc'
          AND is_read = 0
    ");
    if (!$ok) {
        error_log('[Qydentra] mark_all_read failed: ' . mysqli_error($conn));
    }
    header("Location: notifications.php");
    exit();
}
?>

<?php include("../includes/header.php"); ?>

<body>

<?php include("../includes/sidebar.php"); ?>

<div class="main">

<?php include("../includes/topbar.php"); ?>

<div class="table-container hover-glow">

    <div class="table-header">
        <div>
            <h2><i class="fa-solid fa-bell" style="color:#ffffff; margin-right:8px;"></i>Notifications</h2>
            <p>Stay updated with your appointment alerts and updates</p>
        </div>
        <?php
        $unread_check = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT COUNT(*) AS cnt FROM patient_notifications
             WHERE patient_id = '$uid_esc' AND is_read = 0"
        ));
        if ((int)$unread_check['cnt'] > 0):
        ?>
        <form method="POST">
            <button type="submit" name="mark_all_read" class="table-btn">
                <i class="fa-solid fa-check-double"></i> Mark All as Read
            </button>
        </form>
        <?php endif; ?>
    </div>

    <div class="notification-wrapper">

        <?php
        $result = mysqli_query($conn,
            "SELECT * FROM patient_notifications
             WHERE patient_id = '$uid_esc'
             ORDER BY created_at DESC"
        );

        if (mysqli_num_rows($result) > 0):
            while ($row = mysqli_fetch_assoc($result)):
                $isUnread = ((int)$row['is_read'] === 0);
                $notif_id = $row['notification_id'];   // VARCHAR e.g. PN001
                $type     = $row['type'] ?? 'Appointment';
                $typeIcon = match($type) {
                    'Queue'  => 'fa-list-ol',
                    'System' => 'fa-gear',
                    default  => 'fa-calendar-check',
                };
        ?>

        <div class="notification-card <?php echo $isUnread ? 'unread' : ''; ?>">

            <div class="notification-icon">
                <i class="fa-solid <?php echo $isUnread ? 'fa-bell' : 'fa-circle-check'; ?>"></i>
            </div>

            <div class="notification-content">
                <?php if (!empty($row['title'])): ?>
                <strong><?php echo htmlspecialchars($row['title']); ?></strong>
                <?php endif; ?>
                <p><?php echo htmlspecialchars($row['message']); ?></p>
                <small>
                    <i class="fa-regular fa-clock"></i>
                    <?php echo date("F d, Y • g:i A", strtotime($row['created_at'])); ?>
                    &nbsp;·&nbsp;
                    <span class="notif-type-badge">
                        <i class="fa-solid <?php echo $typeIcon; ?>"></i>
                        <?php echo htmlspecialchars($type); ?>
                    </span>
                </small>
            </div>

            <div class="notification-actions">
                <?php if ($isUnread): ?>
                    <form method="POST" style="margin:0;">
                        <input type="hidden" name="notif_id" value="<?php echo htmlspecialchars($notif_id); ?>">
                        <button type="submit" name="mark_read" class="mark-read-btn">
                            <i class="fa-solid fa-check"></i> Mark as Read
                        </button>
                    </form>
                <?php else: ?>
                    <span class="read-label">
                        <i class="fa-solid fa-circle-check"></i> Read
                    </span>
                <?php endif; ?>
            </div>

        </div>

        <?php
            endwhile;
        else:
        ?>

        <div class="empty-state">
            <i class="fa-solid fa-bell-slash"></i>
            <h3>No Notifications</h3>
            <p>You currently have no updates or alerts.</p>
        </div>

        <?php endif; ?>

    </div>

</div>

</div>

</body>
</html>
