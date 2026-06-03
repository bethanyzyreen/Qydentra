<?php
$allowed_roles = ['receptionist'];
include("../includes/auth_check.php");

/* ================= UPDATE QUEUE STATUS ================= */
if(isset($_POST['action']) && $_POST['action'] == 'update_status'){
    $id = (int)$_POST['appointment_id'];
    $status = mysqli_real_escape_string($conn, $_POST['queue_status']);

    // Map queue status to appointment status
    $apptStatus = $status;
    mysqli_query($conn,"UPDATE appointments SET status='$apptStatus' WHERE id='$id'");

    // Notify patient
    $getPatient = mysqli_fetch_assoc(mysqli_query($conn,"SELECT patient_id FROM appointments WHERE id='$id'"));
    $pid = $getPatient['patient_id'];
    $msg = "Your queue status has been updated to: $status.";
    mysqli_query($conn,"INSERT INTO notifications (user_id, message) VALUES ('$pid', '$msg')");

    header("Location: queue_management.php");
    exit();
}

$dateFilter = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$safeDate = mysqli_real_escape_string($conn, $dateFilter);

$queueList = mysqli_query($conn,"
SELECT a.*, u.full_name AS patient_name
FROM appointments a
JOIN users u ON a.patient_id = u.id
WHERE a.appointment_date = '$safeDate'
AND a.status IN ('Approved','In Progress','Completed','Waiting')
ORDER BY a.queue_number ASC
");

$waitingCount = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) AS c FROM appointments WHERE appointment_date='$safeDate' AND status='Approved'"))['c'];
$inProgressCount = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) AS c FROM appointments WHERE appointment_date='$safeDate' AND status='In Progress'"))['c'];
$completedCount = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) AS c FROM appointments WHERE appointment_date='$safeDate' AND status='Completed'"))['c'];
?>

<?php include("../includes/receptionist_header.php"); ?>

<body>

<?php include("../includes/receptionist_sidebar.php"); ?>

<div class="main">

<?php include("../includes/receptionist_topbar.php"); ?>

<!-- DATE FILTER -->
<div class="appointments-toolbar">
<form method="GET" style="display:flex;align-items:center;gap:12px;">
<label style="color:#94a3b8;font-size:14px;"><i class="fa-solid fa-calendar-days"></i> View Date:</label>
<input type="date" name="date" value="<?php echo $dateFilter; ?>"
style="background:rgba(255,255,255,0.05);border:1px solid rgba(96,165,250,0.2);color:white;padding:10px 14px;border-radius:12px;font-size:14px;">
<button type="submit" class="table-btn"><i class="fa-solid fa-magnifying-glass"></i> Filter</button>
</form>
</div>

<!-- STATUS SUMMARY CARDS -->
<div class="cards" style="margin-bottom:24px;">

<div class="card hover-glow" style="cursor:default;">
<div class="card-top">
<div class="card-icon"><i class="fa-solid fa-hourglass-half"></i></div>
<div class="card-badge">Waiting</div>
</div>
<h3>Waiting</h3>
<h1><?php echo $waitingCount; ?></h1>
<p>Patients waiting to be called.</p>
</div>

<div class="card hover-glow" style="cursor:default;">
<div class="card-top">
<div class="card-icon"><i class="fa-solid fa-user-doctor"></i></div>
<div class="card-badge">In Progress</div>
</div>
<h3>In Progress</h3>
<h1><?php echo $inProgressCount; ?></h1>
<p>Currently being attended to.</p>
</div>

<div class="card hover-glow" style="cursor:default;">
<div class="card-top">
<div class="card-icon"><i class="fa-solid fa-circle-check"></i></div>
<div class="card-badge">Done</div>
</div>
<h3>Completed</h3>
<h1><?php echo $completedCount; ?></h1>
<p>Visits completed today.</p>
</div>

</div>

<!-- QUEUE TABLE -->
<div class="table-container hover-glow">

<div class="table-header">
<div>
<h2>Queue Management</h2>
<p>Manage patient queue for <?php echo date("F d, Y", strtotime($dateFilter)); ?>.</p>
</div>
<button class="table-btn" onclick="window.location.href='walkin_registration.php'">
<i class="fa-solid fa-person-walking-arrow-right"></i>
Walk-in
</button>
</div>

<?php if(mysqli_num_rows($queueList) > 0): ?>

<table>
<thead>
<tr>
    <th>Queue #</th>
    <th>Patient</th>
    <th>Service</th>
    <th>Time</th>
    <th>Status</th>
    <th>Update Status</th>
</tr>
</thead>
<tbody>

<?php while($row = mysqli_fetch_assoc($queueList)): ?>

<tr>

<td>
<div class="queue-number-badge">
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
<p><?php echo !empty($row['notes']) ? htmlspecialchars(substr($row['notes'],0,30)) : 'No notes'; ?></p>
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
<form method="POST" style="display:flex;gap:8px;flex-wrap:wrap;">
<input type="hidden" name="action" value="update_status">
<input type="hidden" name="appointment_id" value="<?php echo $row['id']; ?>">

<button type="submit" name="queue_status" value="Approved"
class="queue-status-btn waiting"
<?php echo $row['status']=='Approved'?'disabled':''; ?>>
<i class="fa-solid fa-hourglass-half"></i> Waiting
</button>

<button type="submit" name="queue_status" value="In Progress"
class="queue-status-btn inprogress"
<?php echo $row['status']=='In Progress'?'disabled':''; ?>>
<i class="fa-solid fa-user-doctor"></i> In Progress
</button>

<button type="submit" name="queue_status" value="Completed"
class="queue-status-btn completed"
<?php echo $row['status']=='Completed'?'disabled':''; ?>>
<i class="fa-solid fa-check"></i> Completed
</button>

</form>
</td>

</tr>

<?php endwhile; ?>

</tbody>
</table>

<?php else: ?>

<div class="empty-state">
<i class="fa-solid fa-users"></i>
<h3>No Queue for <?php echo date("F d, Y", strtotime($dateFilter)); ?></h3>
<p>No approved appointments found for this date.</p>
</div>

<?php endif; ?>

</div>

</div>

</body>
</html>
