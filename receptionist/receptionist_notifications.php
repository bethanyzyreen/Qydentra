<?php
$allowed_roles = ['receptionist'];
include("../includes/auth_check.php");

$user_id = (int)$_SESSION['user_id'];

/* ================= MARK SINGLE AS READ ================= */
if(isset($_POST['mark_read']) && !empty($_POST['notif_id'])){
    $notif_id = (int)$_POST['notif_id'];
    $ok = mysqli_query($conn,"
        UPDATE receptionist_notifications
        SET status = 'Read'
        WHERE receptionist_notification_id = $notif_id
          AND receptionist_id = $user_id
          AND status = 'Unread'
    ");
    if(!$ok) error_log('[Qydentra] recep mark_read failed: ' . mysqli_error($conn));
    header("Location: receptionist_notifications.php");
    exit();
}

/* ================= MARK ALL AS READ ================= */
if(isset($_POST['mark_all_read'])){
    $ok = mysqli_query($conn,"
        UPDATE receptionist_notifications
        SET status = 'Read'
        WHERE receptionist_id = $user_id
          AND status = 'Unread'
    ");
    if(!$ok) error_log('[Qydentra] recep mark_all_read failed: ' . mysqli_error($conn));
    header("Location: receptionist_notifications.php");
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
<h2>Notifications</h2>
<p>Stay updated with appointment alerts and clinic activity.</p>
</div>

<?php
$unread_check = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS cnt FROM receptionist_notifications
     WHERE receptionist_id = $user_id AND status = 'Unread'"
));
if((int)$unread_check['cnt'] > 0):
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
    "SELECT * FROM receptionist_notifications
     WHERE receptionist_id = $user_id
     ORDER BY created_at DESC"
);

if(mysqli_num_rows($result) > 0){

    while($row = mysqli_fetch_assoc($result)){

        $isUnread = ($row['status'] === 'Unread');
        $notif_id = (int)$row['receptionist_notification_id'];
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
<strong><?php echo htmlspecialchars($row['title']); ?></strong>
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
<?php if($isUnread): ?>
<div class="notification-dot"></div>
<form method="POST" style="margin:0;">
<input type="hidden" name="notif_id" value="<?php echo $notif_id; ?>">
<button type="submit" name="mark_read" class="mark-read-btn">
<i class="fa-solid fa-check"></i> Mark as Read
</button>
</form>
<?php else: ?>
<span class="read-label"><i class="fa-solid fa-circle-check"></i> Read</span>
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
<p>You currently have no updates or alerts.</p>
</div>

<?php } ?>

</div>

</div>

</div>

</body>
</html>
