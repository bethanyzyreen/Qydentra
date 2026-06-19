<?php
$allowed_roles = ['receptionist'];
include("../includes/auth_check.php");

/* ================= CANCEL ACTION ================= */
if (isset($_POST['action']) && $_POST['action'] == 'cancel') {
    $id = mysqli_real_escape_string($conn, $_POST['appointment_id']); // VARCHAR

    mysqli_query($conn, "UPDATE appointments SET status='Cancelled' WHERE appointment_id='$id'");

    $getAppt = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT a.*, u.full_name AS patient_name
         FROM appointments a JOIN patients u ON a.patient_id = u.patient_id
         WHERE a.appointment_id='$id'"
    ));
    $pid          = $getAppt['patient_id'];   // VARCHAR e.g. PT001
    $patient_name = $getAppt['patient_name'];
    $service      = $getAppt['service_type'];
    $fmt_date     = date("F d, Y", strtotime($getAppt['appointment_date']));
    $fmt_time     = date("g:i A",  strtotime($getAppt['appointment_time']));

    notify_patient(
        $conn, $pid,
        'Appointment Cancelled',
        notification_patient_appointment_cancelled($patient_name, $service, $fmt_date, $fmt_time),
        'Appointment', $id
    );

    notify_receptionists(
        $conn,
        'Appointment Cancelled',
        notification_receptionist_appointment_cancelled($patient_name, $service, $fmt_date, $fmt_time),
        'Appointment', $id
    );

    header("Location: appointment_management.php?success=cancelled");
    exit();
}

/* ================= REASSIGN QUEUE ACTION ================= */
if (isset($_POST['action']) && $_POST['action'] == 'reassign') {
    $id = mysqli_real_escape_string($conn, $_POST['appointment_id']); // VARCHAR

    // Get the appointment's time to derive the correct queue slot
    $apptRow = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT appointment_time FROM appointments WHERE appointment_id='$id'"
    ));
    $apptTime = date('H:00', strtotime($apptRow['appointment_time']));

    $slot_map = ['08:00'=>1,'09:00'=>2,'10:00'=>3,'11:00'=>4,'12:00'=>5,
                 '13:00'=>6,'14:00'=>7,'15:00'=>8,'16:00'=>9,'17:00'=>10];
    $queue = $slot_map[$apptTime] ?? (int)($_POST['queue_number'] ?? 0);

    mysqli_query($conn, "UPDATE appointments SET queue_number='$queue' WHERE appointment_id='$id'");
    header("Location: appointment_management.php?success=reassigned");
    exit();
}

$statusFilter  = isset($_GET['status'])     ? mysqli_real_escape_string($conn, $_GET['status'])     : 'all';
$search        = isset($_GET['search'])     ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';
$patientFilter = isset($_GET['patient_id']) ? mysqli_real_escape_string($conn, $_GET['patient_id']) : '';

$where = "WHERE 1=1";
if ($statusFilter !== 'all') {
    $where .= " AND a.status='$statusFilter'";
}
if (!empty($search)) {
    $where .= " AND (u.full_name LIKE '%$search%' OR u.email LIKE '%$search%' OR a.service_type LIKE '%$search%')";
}
if (!empty($patientFilter)) {
    $where .= " AND a.patient_id='$patientFilter'";
}

$appointments = mysqli_query($conn,
    "SELECT a.*, u.full_name AS patient_name, u.email AS patient_email
     FROM appointments a
     JOIN patients u ON a.patient_id = u.patient_id
     $where
     ORDER BY a.appointment_date DESC, a.appointment_time DESC"
);
?>

<?php include("../includes/receptionist_header.php"); ?>

<body>

<?php include("../includes/receptionist_sidebar.php"); ?>

<div class="main">

<?php include("../includes/receptionist_topbar.php"); ?>

<?php if (isset($_GET['success'])): ?>
<?php
$msgs = ['cancelled' => 'Appointment cancelled. That time slot is now open for new bookings.', 'reassigned' => 'Queue number updated.'];
$msg = $msgs[$_GET['success']] ?? 'Action completed.';
?>
<div data-toast="<?php echo htmlspecialchars($msg); ?>" data-toast-type="success"></div>
<?php endif; ?>

<?php if (isset($_GET['error']) && $_GET['error'] === 'queue_taken'): ?>
<div data-toast="Queue #<?php echo (int)$_GET['queue']; ?> is already taken. Next available is #<?php echo (int)$_GET['suggested']; ?>." data-toast-type="error"></div>
<?php endif; ?>

<!-- FILTER + SEARCH BAR -->
<div class="appt-toolbar-wrap">
  <div class="filter-bar">
    <a href="?status=all<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="filter-btn <?php echo $statusFilter == 'all' ? 'active' : ''; ?>">All</a>
    <a href="?status=Pending<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="filter-btn <?php echo $statusFilter == 'Pending' ? 'active' : ''; ?>">Pending</a>
    <a href="?status=Approved<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="filter-btn <?php echo $statusFilter == 'Approved' ? 'active' : ''; ?>">Approved</a>
    <a href="?status=Completed<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="filter-btn <?php echo $statusFilter == 'Completed' ? 'active' : ''; ?>">Completed</a>
    <a href="?status=Cancelled<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="filter-btn <?php echo $statusFilter == 'Cancelled' ? 'active' : ''; ?>">Cancelled</a>
  </div>
  <form method="GET" class="appt-search-form">
    <input type="hidden" name="status" value="<?php echo $statusFilter; ?>">
    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
      placeholder="Search patient, email or service..." class="search-box" style="width:280px;">
    <button type="submit" class="table-btn"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
    <?php if (!empty($search)): ?>
    <a href="?status=<?php echo $statusFilter; ?>" class="table-btn" style="background:rgba(255,255,255,0.05);text-decoration:none;">
      <i class="fa-solid fa-xmark"></i> Clear
    </a>
    <?php endif; ?>
  </form>
</div>

<div class="table-container hover-glow">

<div class="table-header">
<div>
<h2><i class="fa-solid fa-calendar-days" style="color:#ffffff; margin-right:8px;"></i>Appointment Management</h2>
<p>View, manage, and update all patient appointments.</p>
</div>
<button class="table-btn" onclick="window.location.href='pending_appointments.php'">
<i class="fa-solid fa-list-check"></i>
Review Pending
</button>
</div>

<table>
<thead>
<tr>
    <th>Appt ID</th>
    <th>Patient</th>
    <th>Service</th>
    <th>Date</th>
    <th>Time</th>
    <th>Status</th>
    <th>Queue #</th>
    <th>Actions</th>
</tr>
</thead>
<tbody>

<?php if (mysqli_num_rows($appointments) > 0): ?>
<?php while ($row = mysqli_fetch_assoc($appointments)): ?>

<tr>

<td>
<span style="font-family:monospace;font-size:13px;color:#94a3b8;">
    <?php echo htmlspecialchars($row['appointment_id']); ?>
</span>
</td>

<td>
<div class="service-info">
<div class="service-icon consultation">
<i class="fa-solid fa-user"></i>
</div>
<div>
<h4><?php echo htmlspecialchars($row['patient_name']); ?></h4>
<p><?php echo htmlspecialchars($row['patient_email']); ?></p>
</div>
</div>
</td>

<td><?php echo htmlspecialchars($row['service_type'] ?? '—'); ?></td>

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
<div class="status-pill <?php echo strtolower(str_replace(' ', '-', $row['status'])); ?>">
    <i class="fa-solid fa-circle-check"></i>
    <?php echo ucfirst($row['status']); ?>
</div>
</td>


<td>
<div class="queue-pill">
<?php echo !empty($row['queue_number']) ? '#' . $row['queue_number'] : '—'; ?>
</div>
</td>

<td>
<div class="action-group">

<?php if ($row['status'] == 'Approved'): ?>
<button class="action-btn-sm edit"
onclick="openQueueModal('<?php echo addslashes($row['appointment_id']); ?>', '<?php echo addslashes($row['patient_name']); ?>', <?php echo (int)$row['queue_number']; ?>)">
<i class="fa-solid fa-hashtag"></i>
</button>
<?php endif; ?>

<?php if (in_array($row['status'], ['Pending', 'Approved'])): ?>
<form method="POST" style="display:inline;"
onsubmit="return confirm('Cancel this appointment?')">
<input type="hidden" name="action" value="cancel">
<input type="hidden" name="appointment_id" value="<?php echo htmlspecialchars($row['appointment_id']); ?>">
<button type="submit" class="action-btn-sm cancel-sm">
<i class="fa-solid fa-xmark"></i>
</button>
</form>
<?php endif; ?>

</div>
</td>

</tr>

<?php endwhile; ?>
<?php else: ?>
<tr>
<td colspan="8" style="text-align:center;padding:30px;">No appointments found.</td>
</tr>
<?php endif; ?>

</tbody>
</table>

</div>

</div>

<!-- ================= QUEUE REASSIGN MODAL ================= -->
<div class="modal-overlay" id="queueModal">
<div class="modal-card">

<div class="modal-header">
<h3><i class="fa-solid fa-hashtag"></i> Queue Number</h3>
<button class="modal-close" onclick="closeModal('queueModal')">
<i class="fa-solid fa-xmark"></i>
</button>
</div>

<p id="queuePatientName" style="color:#94a3b8;margin-bottom:20px;"></p>

<div style="padding:16px;background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);border-radius:12px;text-align:center;">
<div style="color:#9ca3af;font-size:12px;margin-bottom:6px;">Assigned Queue Number</div>
<div id="queueDisplay" style="color:#93c5fd;font-size:28px;font-weight:700;">—</div>
<div style="color:#6b7280;font-size:11px;margin-top:8px;">Queue numbers are fixed to their time slot.</div>
</div>

<button type="button" class="primary-btn hover-glow" style="margin-top:16px;" onclick="closeModal('queueModal')">
<i class="fa-solid fa-check"></i> Close
</button>

</div>
</div>

<script>
function openQueueModal(id, name, queue){
    document.getElementById('queuePatientName').textContent = 'Patient: ' + name;
    document.getElementById('queueDisplay').textContent = queue ? '#' + queue : '—';
    document.getElementById('queueModal').classList.add('active');
}
function closeModal(id){
    document.getElementById(id).classList.remove('active');
}
document.querySelectorAll('.modal-overlay').forEach(function(overlay){
    overlay.addEventListener('click', function(e){
        if(e.target === overlay) overlay.classList.remove('active');
    });
});
</script>

</body>
</html>
