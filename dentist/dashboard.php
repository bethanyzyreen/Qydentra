<?php
require_once("../config/database.php");
/** @var mysqli $conn */
$allowed_roles = ['dentist'];
include("../includes/auth_check.php");

// Today's Appointments (Approved or In Progress)
$todayQuery = mysqli_query($conn,"
SELECT COUNT(*) AS total
FROM appointments
WHERE appointment_date = CURDATE()
AND status IN ('Approved','In Progress')
");
$todayCount = mysqli_fetch_assoc($todayQuery)['total'];

// Patients in Queue (Waiting)
$queueQuery = mysqli_query($conn,"
SELECT COUNT(*) AS total
FROM appointments
WHERE appointment_date = CURDATE()
AND status = 'Approved'
AND queue_number IS NOT NULL
");
$queueCount = mysqli_fetch_assoc($queueQuery)['total'];

// Completed Today
$completedQuery = mysqli_query($conn,"
SELECT COUNT(*) AS total
FROM appointments
WHERE appointment_date = CURDATE()
AND status = 'Completed'
");
$completedCount = mysqli_fetch_assoc($completedQuery)['total'];

// In Progress
$inProgressQuery = mysqli_query($conn,"
SELECT COUNT(*) AS total
FROM appointments
WHERE appointment_date = CURDATE()
AND status = 'In Progress'
");
$inProgressCount = mysqli_fetch_assoc($inProgressQuery)['total'];

// Recent Today's Queue (Up to 7)
$recentQuery = mysqli_query($conn,"
SELECT a.*, p.full_name AS patient_name
FROM appointments a
JOIN patients p ON a.patient_id = p.patient_id
WHERE a.appointment_date = CURDATE()
AND a.status IN ('Approved', 'In Progress', 'Completed')
ORDER BY 
  CASE WHEN a.status = 'In Progress' THEN 1
       WHEN a.status = 'Approved' THEN 2
       ELSE 3 END,
  a.queue_number ASC
LIMIT 7
");
?>

<?php include("../includes/dentist_header.php"); ?>

<body>

<?php include("../includes/dentist_sidebar.php"); ?>

<div class="main">

<?php include("../includes/dentist_topbar.php"); ?>

<!-- ================= DASHBOARD CARDS ================= -->

<div class="cards">

<!-- TODAY'S APPOINTMENTS -->
<div class="card hover-glow" onclick="window.location.href='schedule.php'">
<div class="card-top">
<div class="card-icon">
<i class="fa-solid fa-calendar-day"></i>
</div>
<div class="card-badge">Today</div>
</div>
<h3>Total Scheduled</h3>
<h1><?php echo $todayCount; ?></h1>
<p><?php echo $todayCount; ?> patient<?php echo ($todayCount != 1 ? 's' : ''); ?> scheduled for today.</p>
</div>

<!-- IN QUEUE -->
<div class="card hover-glow" onclick="window.location.href='queue.php'">
<div class="card-top">
<div class="card-icon">
<i class="fa-solid fa-users"></i>
</div>
<div class="card-badge">Waiting</div>
</div>
<h3>Patients in Queue</h3>
<h1><?php echo $queueCount; ?></h1>
<p><?php echo $queueCount; ?> patient<?php echo ($queueCount != 1 ? 's' : ''); ?> waiting to be seen.</p>
</div>

<!-- IN PROGRESS -->
<div class="card hover-glow" onclick="window.location.href='queue.php'">
<div class="card-top">
<div class="card-icon" style="background: rgba(96, 165, 250, 0.1); color: #60a5fa;">
<i class="fa-solid fa-user-doctor"></i>
</div>
<div class="card-badge">Current</div>
</div>
<h3>In Consultation</h3>
<h1><?php echo $inProgressCount; ?></h1>
<p><?php echo $inProgressCount; ?> patient<?php echo ($inProgressCount != 1 ? 's' : ''); ?> currently in treatment.</p>
</div>

<!-- COMPLETED -->
<div class="card hover-glow" onclick="window.location.href='records.php'">
<div class="card-top">
<div class="card-icon">
<i class="fa-solid fa-circle-check"></i>
</div>
<div class="card-badge">Done</div>
</div>
<h3>Completed Today</h3>
<h1><?php echo $completedCount; ?></h1>
<p><?php echo $completedCount; ?> visit<?php echo ($completedCount != 1 ? 's' : ''); ?> successfully finished.</p>
</div>

</div>

<!-- ================= TODAY'S QUEUE OVERVIEW ================= -->

<div class="table-container hover-glow">

<div class="table-header">
<div>
<h2><i class="fa-solid fa-users-line" style="color:#ffffff; margin-right:8px;"></i>Today's Live Queue Overview</h2>
<p>Current patient queue status for today's appointments.</p>
</div>
<button class="table-btn" onclick="window.location.href='queue.php'">
<i class="fa-solid fa-users"></i>
Manage Queue
</button>
</div>

<table>
<thead>
<tr>
    <th>Queue #</th>
    <th>Patient</th>
    <th>Service</th>
    <th>Time</th>
    <th>Status</th>
    <th>Action</th>
</tr>
</thead>
<tbody>

<?php if(mysqli_num_rows($recentQuery) > 0): ?>
<?php while($row = mysqli_fetch_assoc($recentQuery)): ?>

<tr>

<td>
<div class="queue-pill" style="<?php echo ($row['status'] == 'In Progress') ? 'background:rgba(96,165,250,0.15);color:#60a5fa;border-color:rgba(96,165,250,0.3);' : ''; ?>">
<?php echo !empty($row['queue_number']) ? '#'.$row['queue_number'] : '—'; ?>
</div>
</td>

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
<?php if($row['status'] == 'Approved' || $row['status'] == 'In Progress'): ?>
<a href="queue.php" class="table-btn" style="background:rgba(59,130,246,0.12); color:#60A5FA;">
<i class="fa-solid fa-arrow-right"></i> Go to Queue
</a>
<?php else: ?>
<a href="records.php?patient_id=<?php echo urlencode($row['patient_id']); ?>" class="table-btn" style="background:rgba(59,130,246,0.12); color:#60A5FA;">
<i class="fa-solid fa-eye"></i> View Records
</a>
<?php endif; ?>
</td>

</tr>

<?php endwhile; ?>
<?php else: ?>
<tr>
<td colspan="6" style="text-align:center;padding:30px;">No appointments queued for today.</td>
</tr>
<?php endif; ?>

</tbody>
</table>

</div>

</div>

</body>
</html>
