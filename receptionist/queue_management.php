<?php
$allowed_roles = ['receptionist'];
include("../includes/auth_check.php");

/* ================= UPDATE QUEUE STATUS ================= */
if (isset($_POST['action']) && $_POST['action'] == 'update_status') {
    $id     = mysqli_real_escape_string($conn, $_POST['appointment_id']); // VARCHAR
    $status = mysqli_real_escape_string($conn, $_POST['queue_status']);

    mysqli_query($conn, "UPDATE appointments SET status='$status' WHERE appointment_id='$id'");

    $getPatient = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT patient_id FROM appointments WHERE appointment_id='$id'"
    ));
    $pid = $getPatient['patient_id'];  // VARCHAR e.g. PT001
    $pid_esc = mysqli_real_escape_string($conn, $pid);

    $msg = "Your queue status has been updated to: $status.";
    mysqli_query($conn,
        "INSERT INTO patient_notifications (patient_id, title, type, message, is_read)
         VALUES ('$pid_esc', 'Queue Update', 'Queue', '$msg', 0)"
    );

    header("Location: queue_management.php");
    exit();
}

$dateFilter = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$safeDate   = mysqli_real_escape_string($conn, $dateFilter);
$search     = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';

$searchWhere = '';
if (!empty($search)) {
    $searchWhere = " AND (u.full_name LIKE '%$search%' OR a.service_type LIKE '%$search%')";
}

$queueList = mysqli_query($conn,
    "SELECT a.*, u.full_name AS patient_name
     FROM appointments a
     JOIN patients u ON a.patient_id = u.patient_id
     WHERE a.appointment_date = '$safeDate'
     AND a.status IN ('Approved','In Progress','Completed','Waiting')
     $searchWhere
     ORDER BY a.queue_number ASC"
);

$waitingCount   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM appointments WHERE appointment_date='$safeDate' AND status='Approved'"))['c'];
$inProgressCount= mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM appointments WHERE appointment_date='$safeDate' AND status='In Progress'"))['c'];
$completedCount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM appointments WHERE appointment_date='$safeDate' AND status='Completed'"))['c'];
?>

<?php include("../includes/receptionist_header.php"); ?>

<body>

<?php include("../includes/receptionist_sidebar.php"); ?>

<div class="main">

<?php include("../includes/receptionist_topbar.php"); ?>

<!-- DATE FILTER + SEARCH -->
<div class="appointments-toolbar queue-toolbar">
<form method="GET" class="queue-filter-form">
  <div class="queue-filter-left">
    <label class="filter-label"><i class="fa-solid fa-calendar-days"></i> Date:</label>
    <input type="date" name="date" value="<?php echo $dateFilter; ?>" class="queue-date-input">
    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search patient or service..." class="search-box" style="width:240px;">
    <button type="submit" class="table-btn"><i class="fa-solid fa-magnifying-glass"></i> Filter</button>
    <?php if (!empty($search)): ?>
    <a href="?date=<?php echo $dateFilter; ?>" class="table-btn" style="background:rgba(255,255,255,0.05);text-decoration:none;">
      <i class="fa-solid fa-xmark"></i> Clear
    </a>
    <?php endif; ?>
  </div>
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
<h2><i class="fa-solid fa-users-line" style="color:#ffffff; margin-right:8px;"></i>Queue Management</h2>
<p>Manage patient queue for <?php echo date("F d, Y", strtotime($dateFilter)); ?>.</p>
</div>
<button class="table-btn" onclick="window.location.href='walkin_registration.php'">
<i class="fa-solid fa-person-walking-arrow-right"></i>
Walk-in
</button>
</div>

<?php if (mysqli_num_rows($queueList) > 0): ?>

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

<?php while ($row = mysqli_fetch_assoc($queueList)): ?>

<tr>

<td>
<div class="queue-number-badge">
<?php echo !empty($row['queue_number']) ? '#' . $row['queue_number'] : '—'; ?>
</div>
</td>

<td>
<div class="service-info">
<div class="service-icon consultation">
<i class="fa-solid fa-user"></i>
</div>
<div>
<h4><?php echo htmlspecialchars($row['patient_name']); ?></h4>
<p><?php echo !empty($row['notes']) ? htmlspecialchars(substr($row['notes'], 0, 30)) : 'No notes'; ?></p>
</div>
</div>
</td>

<td><?php echo htmlspecialchars($row['service_type'] ?? '—'); ?></td>

<td>
<div class="table-date">
<i class="fa-solid fa-clock"></i>
<?php echo date("g:i A", strtotime($row['appointment_time'])); ?>
</div>
</td>

<td>
<div class="status-pill <?php echo strtolower(str_replace(' ', '-', $row['status'])); ?>">
<?php echo ucfirst($row['status']); ?>
</div>
</td>

<td>
<form method="POST" class="queue-action-form">
<input type="hidden" name="action" value="update_status">
<input type="hidden" name="appointment_id" value="<?php echo htmlspecialchars($row['appointment_id']); ?>">
<input type="hidden" name="queue_status" id="qs_<?php echo htmlspecialchars($row['appointment_id']); ?>" value="">

<button type="button"
class="queue-status-btn waiting <?php echo $row['status']=='Approved'?'active-status':''; ?>"
<?php echo $row['status']=='Approved'?'disabled':''; ?>
onclick="submitQueueStatus('<?php echo addslashes($row['appointment_id']); ?>', 'Approved', this)">
<i class="fa-solid fa-hourglass-half"></i><span>Waiting</span>
</button>

<button type="button"
class="queue-status-btn inprogress <?php echo $row['status']=='In Progress'?'active-status':''; ?>"
<?php echo $row['status']=='In Progress'?'disabled':''; ?>
onclick="submitQueueStatus('<?php echo addslashes($row['appointment_id']); ?>', 'In Progress', this)">
<i class="fa-solid fa-user-doctor"></i><span>In Progress</span>
</button>

<button type="button"
class="queue-status-btn completed <?php echo $row['status']=='Completed'?'active-status':''; ?>"
<?php echo $row['status']=='Completed'?'disabled':''; ?>
onclick="submitQueueStatus('<?php echo addslashes($row['appointment_id']); ?>', 'Completed', this)">
<i class="fa-solid fa-check"></i><span>Completed</span>
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

<script>
function submitQueueStatus(appointmentId, statusValue, btn) {
    var hiddenInput = document.getElementById('qs_' + appointmentId);
    hiddenInput.value = statusValue;
    btn.closest('form').submit();
}
</script>

</body>
</html>
