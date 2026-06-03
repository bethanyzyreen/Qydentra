<?php
$allowed_roles = ['patient'];
include("../includes/auth_check.php");

$user_id = $_SESSION['user_id'];

/* ================= MARK AS READ ================= */

mysqli_query($conn,"
UPDATE notifications
SET is_read = 1
WHERE user_id='$user_id'
AND is_read = 0
");
?>

<?php include("../includes/header.php"); ?>

<body>

<?php include("../includes/sidebar.php"); ?>

<div class="main">

<?php include("../includes/topbar.php"); ?>

<!-- PAGE CONTAINER -->

<div class="table-container hover-glow">

    <div class="table-header">

        <div>
            <h2>Notifications</h2>
            <p>Stay updated with your appointment alerts and updates</p>
        </div>

    </div>

    <div class="notification-wrapper">

        <?php

        $sql = "SELECT *
                FROM notifications
                WHERE user_id='$user_id'
                ORDER BY created_at DESC";

        $result = mysqli_query($conn,$sql);

        if(mysqli_num_rows($result) > 0){

            while($row = mysqli_fetch_assoc($result)){

                $notifClass = "";

                if($row['is_read'] == 0){
                    $notifClass = "unread";
                }

        ?>

        <div class="notification-card <?php echo $notifClass; ?>">

            <!-- ICON -->

            <div class="notification-icon">
                <i class="fa-solid fa-bell"></i>
            </div>

            <!-- MESSAGE -->

            <div class="notification-content">

                <p>
                    <?php echo htmlspecialchars($row['message']); ?>
                </p>

                <small>
                    <?php echo date("F d, Y • g:i A", strtotime($row['created_at'])); ?>
                </small>

            </div>

            <!-- UNREAD DOT -->

            <?php if($row['is_read'] == 0){ ?>

                <div class="notification-dot"></div>

            <?php } ?>

        </div>

        <?php
            }

        } else {
        ?>

        <!-- EMPTY STATE -->

        <div class="empty-state">

            <i class="fa-solid fa-bell-slash"></i>

            <h3>No Notifications</h3>

            <p>
                You currently have no updates or alerts.
            </p>

        </div>

        <?php } ?>

    </div>

</div>

</div>

</body>
</html>