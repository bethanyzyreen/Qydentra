<?php
$allowed_roles = ['receptionist'];
include("../includes/auth_check.php");

$user_id = $_SESSION['user_id'];                              // VARCHAR e.g. RE001
$uid_esc = mysqli_real_escape_string($conn, $user_id);

/* ================= MARK SINGLE AS READ ================= */
if (isset($_POST['mark_read']) && !empty($_POST['notif_id'])) {
    $notif_id = mysqli_real_escape_string($conn, $_POST['notif_id']); // VARCHAR e.g. RN001

    $ok = mysqli_query($conn, "
        UPDATE receptionist_notifications
        SET status = 'Read'
        WHERE receptionist_notification_id = '$notif_id'
          AND receptionist_id = '$uid_esc'
    ");
    if (!$ok) {
        error_log('[Qydentra] recep mark_read failed: ' . mysqli_error($conn));
    }
    $redir_page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
    header("Location: receptionist_notifications.php?page=" . $redir_page);
    exit();
}

/* ================= MARK ALL AS READ ================= */
if (isset($_POST['mark_all_read'])) {
    $ok = mysqli_query($conn, "
        UPDATE receptionist_notifications
        SET status = 'Read'
        WHERE receptionist_id = '$uid_esc'
          AND LOWER(status) <> 'read'
    ");
    if (!$ok) {
        error_log('[Qydentra] recep mark_all_read failed: ' . mysqli_error($conn));
    }
    $redir_page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
    header("Location: receptionist_notifications.php?page=" . $redir_page);
    exit();
}
?>

<?php include("../includes/receptionist_header.php"); ?>

<body>

<?php include("../includes/receptionist_sidebar.php"); ?>

<div class="main">

<?php include("../includes/receptionist_topbar.php"); ?>

<div class="table-container hover-glow">

<div class="table-header">
    <div>
        <h2><i class="fa-solid fa-bell" style="color:#ffffff; margin-right:8px;"></i>Notifications</h2>
        <p>Stay updated with appointment alerts and clinic activity.</p>
    </div>
    <?php
    $unread_check = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COUNT(*) AS cnt FROM receptionist_notifications
         WHERE receptionist_id = '$uid_esc' AND LOWER(status) <> 'read'"
    ));
    if ((int)$unread_check['cnt'] > 0):
    ?>
    <form method="POST">
        <input type="hidden" name="page" value="<?php echo isset($page) ? (int)$page : 1; ?>">
        <button type="submit" name="mark_all_read" class="table-btn">
            <i class="fa-solid fa-check-double"></i> Mark All as Read
        </button>
    </form>
    <?php endif; ?>
</div>

<div class="notification-wrapper">

<?php
        $perPage = 5;
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $offset = ($page - 1) * $perPage;

        $countRes = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT COUNT(*) AS cnt FROM receptionist_notifications WHERE receptionist_id = '$uid_esc'"
        ));
        $totalNotifs = (int)$countRes['cnt'];
        $totalPages = max(1, (int)ceil($totalNotifs / $perPage));

        $result = mysqli_query($conn,
            "SELECT * FROM receptionist_notifications
             WHERE receptionist_id = '$uid_esc'
             ORDER BY created_at DESC
             LIMIT $perPage OFFSET $offset"
        );

        if (mysqli_num_rows($result) > 0) {

            while ($row = mysqli_fetch_assoc($result)) {

        $isUnread = (strtolower($row['status'] ?? 'Unread') !== 'read');
        $notif_id = $row['receptionist_notification_id'];  // VARCHAR e.g. RN001
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
<div class="notification-dot"></div>
                    <form method="POST" style="margin:0;">
                        <input type="hidden" name="notif_id" value="<?php echo htmlspecialchars($notif_id); ?>">
                        <input type="hidden" name="page" value="<?php echo isset($page) ? (int)$page : 1; ?>">
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

    }

} else {
?>

<div class="empty-state">
<i class="fa-solid fa-bell-slash"></i>
<h3>No Notifications</h3>
<p>No alerts at the moment.</p>
</div>

<?php } ?>

<?php if (!empty($totalNotifs) && $totalNotifs > 0): ?>
    <div style="display:flex; justify-content:center; margin-top:12px;">
        <div>
            <button type="button" class="qyd-page-btn" <?php echo $page<=1 ? 'disabled' : ''; ?> onclick="if(!this.disabled) location.href='?page=<?php echo $page-1; ?>'">
                <i class="fa-solid fa-chevron-left"></i>
            </button>
            <span class="qyd-page-label"><?php echo $page; ?> of <?php echo $totalPages; ?></span>
            <button type="button" class="qyd-page-btn" <?php echo $page>=$totalPages ? 'disabled' : ''; ?> onclick="if(!this.disabled) location.href='?page=<?php echo $page+1; ?>'">
                <i class="fa-solid fa-chevron-right"></i>
            </button>
        </div>
    </div>
<?php endif; ?>

</div>

</div>

</div>

</body>
</html>
