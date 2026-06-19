<?php
require_once("../config/database.php");
/** @var mysqli $conn */
$allowed_roles = ['dentist'];
include("../includes/auth_check.php");

/* ================= CALL PATIENT ACTION ================= */
if (isset($_POST['action']) && $_POST['action'] == 'call_patient') {
    $id = mysqli_real_escape_string($conn, $_POST['appointment_id']); // VARCHAR
    
    // Update status to 'In Progress'
    mysqli_query($conn, "UPDATE appointments SET status='In Progress' WHERE appointment_id='$id'");

    // Notify Patient
    $getPatient = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT patient_id FROM appointments WHERE appointment_id='$id'"
    ));
    if ($getPatient) {
        $pid_esc = mysqli_real_escape_string($conn, $getPatient['patient_id']);
        $msg = "The Dentist is ready to see you now. Please proceed to the clinic room.";
        mysqli_query($conn,
            "INSERT INTO patient_notifications (patient_id, title, type, message, is_read)
             VALUES ('$pid_esc', 'Dentist Calling', 'Queue', '$msg', 0)"
        );
    }

    // Redirect to consultation page
    header("Location: consultation.php?id=" . urlencode($id));
    exit();
}

$dateFilter = date('Y-m-d');
$safeDate   = mysqli_real_escape_string($conn, $dateFilter);

$queueList = mysqli_query($conn,
    "SELECT a.*, u.full_name AS patient_name
     FROM appointments a
     JOIN patients u ON a.patient_id = u.patient_id
     WHERE a.appointment_date = '$safeDate'
     AND a.status IN ('Approved','In Progress')
     ORDER BY 
        CASE WHEN a.status = 'In Progress' THEN 1 ELSE 2 END,
        a.queue_number ASC"
);

$waitingCount   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM appointments WHERE appointment_date='$safeDate' AND status='Approved' AND queue_number IS NOT NULL"))['c'];
$inProgressCount= mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM appointments WHERE appointment_date='$safeDate' AND status='In Progress'"))['c'];
?>

<?php include("../includes/dentist_header.php"); ?>

<body>

<?php include("../includes/dentist_sidebar.php"); ?>

<div class="main">

<?php include("../includes/dentist_topbar.php"); ?>

<!-- STATUS SUMMARY CARDS -->
<div class="cards" style="margin-bottom:24px;">

<div class="card hover-glow" style="cursor:default;">
<div class="card-top">
<div class="card-icon"><i class="fa-solid fa-hourglass-half"></i></div>
<div class="card-badge">Waiting</div>
</div>
<h3>Patients Waiting</h3>
<h1><?php echo $waitingCount; ?></h1>
<p>Patients waiting to be called.</p>
</div>

<div class="card hover-glow card-consultation" style="cursor:default;">
<div class="card-top">
<div class="card-icon" style="background: rgba(167,139,250,0.15); color: #a78bfa;"><i class="fa-solid fa-user-doctor"></i></div>
<div class="card-badge">Current</div>
</div>
<h3>In Consultation</h3>
<h1><?php echo $inProgressCount; ?></h1>
<p>Currently being attended to.</p>
</div>

</div>

<!-- QUEUE TABLE -->
<div class="table-container hover-glow">

<div class="table-header">
<div>
<h2><i class="fa-solid fa-users" style="color:#ffffff; margin-right:8px;"></i> Patient Queue</h2>
<p>Manage today's live patient queue.</p>
</div>
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
    <th>Action</th>
</tr>
</thead>
<tbody>

<?php while ($row = mysqli_fetch_assoc($queueList)): ?>

<tr>

<td>
<div class="queue-number-badge" style="<?php echo ($row['status'] == 'In Progress') ? 'background:rgba(167,139,250,0.15);color:#a78bfa;border-color:rgba(167,139,250,0.3);' : ''; ?>">
<?php echo !empty($row['queue_number']) ? '#' . $row['queue_number'] : '—'; ?>
</div>
</td>

<td>
<div class="service-info">
<div class="service-icon consultation" style="background:rgba(167,139,250,0.15);border:1px solid rgba(167,139,250,0.25);color:#a78bfa;">
<i class="fa-solid fa-user"></i>
</div>
<div>
<h4><?php echo htmlspecialchars($row['patient_name']); ?></h4>
<p>Patient</p>
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
<div class="status-pill <?php echo strtolower($row['status']); ?>">
    <i class="fa-solid fa-circle-check"></i>
    <?php echo ucfirst($row['status']); ?>
</div>
</td>

<td>
<?php if($row['status'] == 'In Progress'): ?>
    <!-- Already in progress, just resume -->
    <a href="consultation.php?id=<?php echo urlencode($row['appointment_id']); ?>" class="primary-btn hover-glow" style="padding: 6px 12px; font-size: 13px; color:#ffffff;">
        <i class="fa-solid fa-play"></i> Resume
    </a>
<?php else: ?>
    <!-- Call Patient -->
    <form method="POST" style="display:inline;">
        <input type="hidden" name="action" value="call_patient">
        <input type="hidden" name="appointment_id" value="<?php echo htmlspecialchars($row['appointment_id']); ?>">
        <button type="submit" class="primary-btn hover-glow" style="padding: 6px 12px; font-size: 13px; background:linear-gradient(135deg,#3B82F6,#2563EB); color:#ffffff;">
            <i class="fa-solid fa-bullhorn"></i> Call
        </button>
    </form>
<?php endif; ?>
</td>

</tr>

<?php endwhile; ?>

</tbody>
</table>

<?php else: ?>

<div class="empty-state">
<i class="fa-solid fa-users"></i>
<h3>Queue Empty</h3>
<p>There are no patients waiting in the queue right now.</p>
</div>

<?php endif; ?>

</div>

</div>

</body>
</html>
