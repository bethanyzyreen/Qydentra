<?php
$allowed_roles = ['patient'];
include("../includes/auth_check.php");
?>

<?php include("../includes/header.php"); ?>

<body>

<?php include("../includes/sidebar.php"); ?>

<div class="main">

<?php include("../includes/topbar.php"); ?>

<div class="table-container hover-glow">

    <div class="table-header">
        <div>
            <h2>Queue Tracking</h2>
            <p>View your current queue status and position</p>
        </div>
    </div>

    <?php

    $user_id = $_SESSION['user_id'];

    $sql = "SELECT * FROM appointments
    WHERE patient_id='$user_id'
    AND status='Approved'
    ORDER BY appointment_date ASC
    LIMIT 1";

    $result = mysqli_query($conn,$sql);

    if(mysqli_num_rows($result)>0){

    $row = mysqli_fetch_assoc($result);

    ?>

    <div class="queue-number">
        #<?php echo $row['queue_number']; ?>
    </div>

    <p class="queue-label">
        Current Queue Position
    </p>

    <div class="queue-details">

        <div class="queue-info">
            <i class="fa-solid fa-tooth"></i>
            <span><?php echo htmlspecialchars($row['service_type'] ?? '—'); ?></span>
        </div>

        <div class="queue-info">
            <i class="fa-solid fa-calendar-days"></i>
            <span><?php echo date("F d, Y", strtotime($row['appointment_date'])); ?></span>
        </div>

        <div class="queue-info">
            <i class="fa-solid fa-clock"></i>
            <span><?php echo date("g:i A", strtotime($row['appointment_time'])); ?></span>
        </div>

    </div>

    <div class="status-pill approved">
        Approved
    </div>

    <div class="waiting-box">
        <i class="fa-solid fa-hourglass-half"></i>
        Estimated Waiting Time:
        <strong>30-40 mins</strong>
    </div>

    <?php } else { ?>

    <div class="empty-state">
        <i class="fa-solid fa-list-check"></i>
        <h3>No Active Queue</h3>
        <p>You currently have no approved appointment in queue.</p>
    </div>

    <?php } ?>

</div>

</div>

</body>
</html>