<?php
$allowed_roles = ['receptionist'];
include("../includes/auth_check.php");

$pendingQuery = mysqli_query($conn,"
SELECT COUNT(*) AS total
FROM appointments
WHERE status='Pending'
");
$pendingCount = mysqli_fetch_assoc($pendingQuery)['total'];

$approvedQuery = mysqli_query($conn,"
SELECT COUNT(*) AS total
FROM appointments
WHERE status='Approved'
");
$approvedCount = mysqli_fetch_assoc($approvedQuery)['total'];

$todayQueue = mysqli_query($conn,"
SELECT COUNT(*) AS total
FROM appointments
WHERE appointment_date = CURDATE()
AND status IN ('Approved','In Progress')
");
$todayQueueCount = mysqli_fetch_assoc($todayQueue)['total'];

$completedToday = mysqli_query($conn,"
SELECT COUNT(*) AS total
FROM appointments
WHERE appointment_date = CURDATE()
AND status='Completed'
");
$completedTodayCount = mysqli_fetch_assoc($completedToday)['total'];

$recentQuery = mysqli_query($conn,"
SELECT a.*, u.full_name AS patient_name
FROM appointments a
JOIN patients u ON a.patient_id = u.patient_id
ORDER BY a.created_at DESC
LIMIT 7
");
?>

<?php include("../includes/receptionist_header.php"); ?>

<body>

<?php include("../includes/receptionist_sidebar.php"); ?>

<div class="main">

<?php include("../includes/receptionist_topbar.php"); ?>

<!-- ================= DASHBOARD CARDS ================= -->

<div class="cards">

<!-- PENDING -->
<div class="card hover-glow"
onclick="window.location.href='pending_appointments.php'">

<div class="card-top">
<div class="card-icon">
<i class="fa-solid fa-clock"></i>
</div>
<div class="card-badge">Needs Review</div>
</div>

<h3>Pending Requests</h3>
<h1><?php echo $pendingCount; ?></h1>
<p><?php echo $pendingCount; ?> appointment<?php echo ($pendingCount != 1 ? 's' : ''); ?> awaiting review.</p>

</div>

<!-- APPROVED -->
<div class="card hover-glow"
onclick="window.location.href='appointment_management.php'">

<div class="card-top">
<div class="card-icon">
<i class="fa-solid fa-calendar-check"></i>
</div>
<div class="card-badge">Approved</div>
</div>

<h3>Approved Appointments</h3>
<h1><?php echo $approvedCount; ?></h1>
<p><?php echo $approvedCount; ?> appointment<?php echo ($approvedCount != 1 ? 's' : ''); ?> approved and scheduled.</p>

</div>

<!-- TODAY'S QUEUE -->
<div class="card hover-glow"
onclick="window.location.href='queue_management.php'">

<div class="card-top">
<div class="card-icon">
<i class="fa-solid fa-users"></i>
</div>
<div class="card-badge">Live Queue</div>
</div>

<h3>Today's Queue</h3>
<h1><?php echo $todayQueueCount; ?></h1>
<p><?php echo $todayQueueCount; ?> patient<?php echo ($todayQueueCount != 1 ? 's' : ''); ?> in queue for today.</p>

</div>

<!-- COMPLETED TODAY -->
<div class="card hover-glow"
onclick="window.location.href='queue_management.php'">

<div class="card-top">
<div class="card-icon">
<i class="fa-solid fa-circle-check"></i>
</div>
<div class="card-badge">Completed</div>
</div>

<h3>Completed Today</h3>
<h1><?php echo $completedTodayCount; ?></h1>
<p><?php echo $completedTodayCount; ?> visit<?php echo ($completedTodayCount != 1 ? 's' : ''); ?> completed today.</p>

</div>

</div>

<!-- ================= RECENT APPOINTMENTS TABLE ================= -->

<div class="table-container hover-glow">

<div class="table-header">
<div>
<h2>Recent Appointments</h2>
<p>Latest appointment requests and their current status.</p>
</div>
<button class="table-btn"
onclick="window.location.href='pending_appointments.php'">
<i class="fa-solid fa-list-check"></i>
Review Pending
</button>
</div>

<table>
<thead>
<tr>
    <th>Patient</th>
    <th>Service</th>
    <th>Date</th>
    <th>Time</th>
    <th>Status</th>
    <th>Queue</th>
    <th>Action</th>
</tr>
</thead>
<tbody>

<?php if(mysqli_num_rows($recentQuery) > 0): ?>
<?php while($row = mysqli_fetch_assoc($recentQuery)): ?>

<tr>

<td>
<div class="service-info">
<div class="service-icon consultation">
<i class="fa-solid fa-user"></i>
</div>
<div>
<h4><?php echo htmlspecialchars($row['patient_name']); ?></h4>
<p>Patient</p>
</div>
</div>
</td>

<td><?php echo htmlspecialchars($row['service_type'] ?? $row['service'] ?? '—'); ?></td>

<td>
<div class="table-date">
<i class="fa-solid fa-calendar-days"></i>
<?php echo date("M d, Y", strtotime($row['appointment_date'])); ?>
</div>
</td>

<td>
<div class="table-date">
<i class="fa-solid fa-clock"></i>
<?php echo date("g:i A", strtotime($row['appointment_time'])); ?>
</div>
</td>

<td>
<div class="status-pill <?php echo strtolower($row['status']); ?>">
<?php echo ucfirst($row['status']); ?>
</div>
</td>

<td>
<div class="queue-pill">
<?php echo !empty($row['queue_number']) ? '#'.$row['queue_number'] : '—'; ?>
</div>
</td>

<td>
<a href="appointment_management.php?id=<?php echo $row['appointment_id']; ?>" class="action-btn-sm edit">
<i class="fa-solid fa-eye"></i>
</a>
</td>

</tr>

<?php endwhile; ?>
<?php else: ?>
<tr>
<td colspan="7" style="text-align:center;padding:30px;">No appointments found.</td>
</tr>
<?php endif; ?>

</tbody>
</table>

</div>

</div>

</body>
</html>
