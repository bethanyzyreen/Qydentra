<?php
$allowed_roles = ['patient'];
include("../includes/auth_check.php");

$user_id = $_SESSION['user_id'];

/* ================= MARK SINGLE AS READ ================= */
if(isset($_POST['mark_read']) && !empty($_POST['notif_id'])){
    $notif_id = (int)$_POST['notif_id'];
    mysqli_query($conn,"
        UPDATE patient_notifications
        SET is_read=1
        WHERE notification_id='$notif_id' AND patient_id='$user_id'
    ");
    header("Location: patient_notifications.php");
    exit();
}

/* ================= MARK ALL AS READ ================= */
if(isset($_POST['mark_all_read'])){
    mysqli_query($conn,"UPDATE patient_notifications SET is_read=1 WHERE patient_id='$user_id' AND is_read=0");
    header("Location: patient_notifications.php");
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
            <h2>Notifications</h2>
            <p>Stay updated with your appointment alerts and updates</p>
        </div>
        <?php
        $unread_check = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT COUNT(*) AS cnt FROM patient_notifications WHERE patient_id='$user_id' AND is_read=0"
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
        $sql = "SELECT * FROM patient_notifications
                WHERE patient_id='$user_id'
                ORDER BY created_at DESC";
        $result = mysqli_query($conn, $sql);

        if(mysqli_num_rows($result) > 0):
            while($row = mysqli_fetch_assoc($result)):
                $isUnread = ($row['is_read'] == 0);
                $notif_id = (int)$row['notification_id'];
        ?>

        <div class="notification-card <?php echo $isUnread ? 'unread' : ''; ?>">

            <!-- LEFT: icon -->
            <div class="notification-icon">
                <i class="fa-solid <?php echo $isUnread ? 'fa-bell' : 'fa-circle-check'; ?>"></i>
            </div>

            <!-- CENTER: message -->
            <div class="notification-content">
                <p><?php echo htmlspecialchars($row['message']); ?></p>
                <small>
                    <i class="fa-regular fa-clock"></i>
                    <?php echo date("F d, Y • g:i A", strtotime($row['created_at'])); ?>
                </small>
            </div>

            <!-- RIGHT: badge + button -->
            <div class="notification-actions">
                <?php if($isUnread): ?>
                    <form method="POST" style="margin:0;">
                        <input type="hidden" name="notif_id" value="<?php echo $notif_id; ?>">
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
