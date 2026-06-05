<?php
$allowed_roles = ['patient'];
include("../includes/auth_check.php");

$user_id = $_SESSION['user_id'];

$thisWeekQuery = mysqli_query($conn,"
SELECT COUNT(*) AS total
FROM appointments
WHERE patient_id='$user_id'
AND status IN ('Pending','Approved')
AND YEARWEEK(appointment_date, 1) = YEARWEEK(CURDATE(), 1)
");

$thisWeekCount = mysqli_fetch_assoc($thisWeekQuery)['total'];

$upcomingQuery = mysqli_query($conn,"
SELECT COUNT(*) AS total
FROM appointments
WHERE patient_id='$user_id'
AND status IN ('Pending','Approved')
");

$upcomingCount = mysqli_fetch_assoc($upcomingQuery)['total'];

$completedQuery = mysqli_query($conn,"
SELECT COUNT(*) AS total
FROM appointments
WHERE patient_id='$user_id'
AND status='Completed'
");

$completedCount = mysqli_fetch_assoc($completedQuery)['total'];

$notifQuery = mysqli_query($conn,"
SELECT COUNT(*) AS total
FROM notifications
WHERE user_id='$user_id'
AND is_read = 0
");

$notifCount = mysqli_fetch_assoc($notifQuery)['total'];

$queueQuery = mysqli_query($conn,"
SELECT queue_number
FROM appointments
WHERE patient_id='$user_id'
AND status='Approved'
ORDER BY appointment_date ASC, appointment_time ASC
LIMIT 1
");

$queueData = mysqli_fetch_assoc($queueQuery);

$currentQueue = $queueData['queue_number'] ?? '—';

$appointmentQuery = mysqli_query($conn,"
SELECT *
FROM appointments
WHERE patient_id='$user_id'
AND status IN ('Pending','Approved')
ORDER BY appointment_date ASC, appointment_time ASC
LIMIT 1
");

$nextAppointment = mysqli_fetch_assoc($appointmentQuery);
?>

<?php include("../includes/header.php"); ?>

<body>

<?php include("../includes/sidebar.php"); ?>

<div class="main">

<?php include("../includes/topbar.php"); ?>

<!-- ================= DASHBOARD CARDS ================= -->

<div class="cards">

<!-- UPCOMING -->

<div class="card hover-glow"
onclick="window.location.href='appointments.php'">

<div class="card-top">

<div class="card-icon">
<i class="fa-solid fa-calendar-days"></i>
</div>

<div class="card-badge">

<?php
echo ($thisWeekCount > 0)
    ? "+" . $thisWeekCount . " this week"
    : "0 this week";
?>

</div>

</div>

<h3>
Upcoming Appointments
</h3>

<h1>
<?php echo $upcomingCount; ?>
</h1>

<p>
You have <?php echo $upcomingCount; ?> pending or approved appointment<?php echo ($upcomingCount != 1 ? 's' : ''); ?>.
</p>

</div>

<!-- QUEUE -->

<div class="card hover-glow"
onclick="window.location.href='queue.php'">

<div class="card-top">

<div class="card-icon">
<i class="fa-solid fa-users"></i>
</div>

<div class="card-badge">
Live Queue
</div>

</div>

<h3>
Queue Number
</h3>

<h1>
<?php
echo ($currentQueue !== '—')
? '#'.$currentQueue
: '—';
?>
</h1>

<p>
Current active queue position for today's appointment.
</p>

</div>

<!-- NOTIFICATIONS -->

<div class="card hover-glow"
onclick="window.location.href='notifications.php'">

<div class="card-top">

<div class="card-icon">
<i class="fa-solid fa-bell"></i>
</div>

<div class="card-badge">
New Alerts
</div>

</div>

<h3>
Notifications
</h3>

<h1>
<?php echo $notifCount; ?>
</h1>

<p>
You currently have <?php echo $notifCount; ?> notification<?php echo ($notifCount != 1 ? 's' : ''); ?>.
</p>

</div>

<!-- COMPLETED -->

<div class="card hover-glow"
onclick="window.location.href='appointments.php'">

<div class="card-top">

<div class="card-icon">
<i class="fa-solid fa-circle-check"></i>
</div>

<div class="card-badge">
Completed
</div>

</div>

<h3>
Completed Visits
</h3>

<h1>
<?php echo $completedCount; ?>
</h1>

<p>
You have completed <?php echo $completedCount; ?> dental visit<?php echo ($completedCount != 1 ? 's' : ''); ?>.
</p>

</div>

</div>

<!-- ================= UPCOMING APPOINTMENT ================= -->

<?php if($nextAppointment){ ?>

<div class="appointment-card">

<div class="appointment-left">

<div class="appointment-image">

<img src="https://images.unsplash.com/photo-1629909613654-28e377c37b09?q=80&w=1932&auto=format&fit=crop">

</div>

<div class="appointment-info">

<p class="appointment-label">
Upcoming Appointment
</p>

<h2>
<?php echo $nextAppointment['service_type']; ?>
</h2>

<p class="doctor-name">
Status:
<?php echo ucfirst($nextAppointment['status']); ?>
</p>

<div class="appointment-details">

<div class="detail-item">

<i class="fa-solid fa-calendar-days"></i>

<span>
<?php echo date("F d, Y", strtotime($nextAppointment['appointment_date'])); ?>
</span>

</div>

<div class="detail-item">

<i class="fa-solid fa-clock"></i>

<span>
<?php echo date("g:i A", strtotime($nextAppointment['appointment_time'])); ?>
</span>

</div>

<div class="detail-item">

<i class="fa-solid fa-hashtag"></i>

<span>

<?php
if(!empty($nextAppointment['queue_number'])){
    echo "Queue #".$nextAppointment['queue_number'];
}
else{
    echo "Queue Pending";
}
?>

</span>

</div>

</div>

</div>

</div>

<button
class="primary-btn"
onclick="window.location.href='appointments.php'">

<i class="fa-solid fa-arrow-up-right-from-square"></i>

View Details

</button>

</div>

<?php } else { ?>

<div class="appointment-card">

<div class="appointment-left">

<div class="appointment-info">

<p class="appointment-label">
No Upcoming Appointment
</p>

<h2>
Book Your Next Visit
</h2>

<p class="doctor-name">
You currently have no pending or approved appointments.
</p>

</div>

</div>

<button
class="primary-btn"
onclick="window.location.href='book_appointment.php'">

<i class="fa-solid fa-calendar-plus"></i>

Book Appointment

</button>

</div>

<?php } ?>

<!-- ================= APPOINTMENT HISTORY ================= -->

<div class="table-container">

<div class="table-header">

<div>

<h2>
Recent Appointments
</h2>

<p>
Track your latest dental appointments and appointment statuses.
</p>

</div>

<button class="table-btn"
onclick="window.location.href='book_appointment.php'">

<i class="fa-solid fa-calendar-plus"></i>

Book New

</button>

</div>

<table>

<thead>
<tr>
    <th>Service</th>
    <th>Date</th>
    <th>Time</th>
    <th>Status</th>
    <th>Queue</th>
</tr>
</thead>

<tbody>

<?php

$historyQuery = mysqli_query($conn,"
SELECT *
FROM appointments
WHERE patient_id='$user_id'
ORDER BY appointment_date DESC, appointment_time DESC
LIMIT 5
");

if(mysqli_num_rows($historyQuery) > 0){

while($row = mysqli_fetch_assoc($historyQuery)){

$service = strtolower($row['service_type']);

$icon = "fa-tooth";
$serviceClass = "cleaning";
$serviceDesc = "Routine Dental Care";

if(str_contains($service,"consultation")){

    $icon = "fa-user-doctor";
    $serviceClass = "consultation";
    $serviceDesc = "Initial Checkup";

}
elseif(str_contains($service,"cleaning")){

    $icon = "fa-tooth";
    $serviceClass = "cleaning";
    $serviceDesc = "Routine Dental Care";

}
elseif(str_contains($service,"checkup")){

    $icon = "fa-stethoscope";
    $serviceClass = "checkup";
    $serviceDesc = "General Oral Exam";

}
elseif(str_contains($service,"filling")){

    $icon = "fa-syringe";
    $serviceClass = "filling";
    $serviceDesc = "Tooth Restoration";

}
elseif(str_contains($service,"braces")){

    $icon = "fa-teeth";
    $serviceClass = "braces";
    $serviceDesc = "Orthodontic Assessment";

}
elseif(str_contains($service,"extraction")){

    $icon = "fa-teeth-open";
    $serviceClass = "extraction";
    $serviceDesc = "Tooth Removal";

}

?>

<tr>

<td>

<div class="service-info">

<div class="service-icon <?php echo $serviceClass; ?>">

<i class="fa-solid <?php echo $icon; ?>"></i>

</div>

<div>

<h4>
<?php echo $row['service_type']; ?>
</h4>

<p>
<?php echo $serviceDesc; ?>
</p>

</div>

</div>

</td>

<td>

<div class="table-date">

<i class="fa-solid fa-calendar-days"></i>

<?php echo date("F d, Y", strtotime($row['appointment_date'])); ?>

</div>

</td>

<td>

<div class="table-date">

<i class="fa-solid fa-clock"></i>

<?php echo date("g:i A", strtotime($row['appointment_time'])); ?>

</div>

</td>

<td>

<div class="status-pill <?php echo strtolower(str_replace(" ", "-", $row["status"])); ?>">

<?php echo ucfirst($row['status']); ?>

</div>

</td>

<td>

<div class="queue-pill">

<?php

if(!empty($row['queue_number'])){
    echo "#".$row['queue_number'];
}
else{
    echo "—";
}

?>

</div>

</td>

</tr>

<?php

}

}else{

?>

<tr>

<td colspan="5" style="text-align:center;padding:30px;">

No appointments found.

</td>

</tr>

<?php } ?>

</tbody>

</table>

</div>

</div>

</body>
</html>
