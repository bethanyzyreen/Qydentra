<?php
$allowed_roles = ['receptionist'];
include("../includes/auth_check.php");

/* ================= CANCEL ACTION ================= */
if(isset($_POST['action']) && $_POST['action'] == 'cancel'){
    $id = (int)$_POST['appointment_id'];
    mysqli_query($conn,"UPDATE appointments SET status='Cancelled' WHERE id='$id'");
    $getPatient = mysqli_fetch_assoc(mysqli_query($conn,"SELECT patient_id FROM appointments WHERE id='$id'"));
    $pid = $getPatient['patient_id'];
    mysqli_query($conn,"INSERT INTO notifications (user_id, message) VALUES ('$pid', 'Your appointment has been cancelled by the clinic.')");
    header("Location: appointment_management.php?success=cancelled");
    exit();
}

/* ================= REASSIGN QUEUE ACTION ================= */
if(isset($_POST['action']) && $_POST['action'] == 'reassign'){
    $id = (int)$_POST['appointment_id'];
    $queue = (int)$_POST['queue_number'];
    mysqli_query($conn,"UPDATE appointments SET queue_number='$queue' WHERE id='$id'");
    header("Location: appointment_management.php?success=reassigned");
    exit();
}

$statusFilter = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : 'all';
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';

$where = "WHERE 1=1";
if($statusFilter !== 'all'){
    $where .= " AND a.status='$statusFilter'";
}
if(!empty($search)){
    $where .= " AND (u.full_name LIKE '%$search%' OR u.email LIKE '%$search%' OR a.service_type LIKE '%$search%')";
}

$appointments = mysqli_query($conn,"
SELECT a.*, u.full_name AS patient_name, u.email AS patient_email
FROM appointments a
JOIN users u ON a.patient_id = u.id
$where
ORDER BY a.appointment_date DESC, a.appointment_time DESC
");
?>

<?php include("../includes/receptionist_header.php"); ?>

<body>

<?php include("../includes/receptionist_sidebar.php"); ?>

<div class="main">

<?php include("../includes/receptionist_topbar.php"); ?>

<?php if(isset($_GET['success'])): ?>
<div class="alert-success">
<i class="fa-solid fa-circle-check"></i>
<?php
$msgs = ['cancelled'=>'Appointment cancelled.','reassigned'=>'Queue number updated.'];
echo $msgs[$_GET['success']] ?? 'Action completed.';
?>
</div>
<?php endif; ?>

<!-- FILTER + SEARCH BAR -->
<div class="appt-toolbar-wrap">
  <div class="filter-bar">
    <a href="?status=all<?php echo !empty($search)?'&search='.urlencode($search):''; ?>" class="filter-btn <?php echo $statusFilter=='all'?'active':''; ?>">All</a>
    <a href="?status=Pending<?php echo !empty($search)?'&search='.urlencode($search):''; ?>" class="filter-btn <?php echo $statusFilter=='Pending'?'active':''; ?>">Pending</a>
    <a href="?status=Approved<?php echo !empty($search)?'&search='.urlencode($search):''; ?>" class="filter-btn <?php echo $statusFilter=='Approved'?'active':''; ?>">Approved</a>
    <a href="?status=Completed<?php echo !empty($search)?'&search='.urlencode($search):''; ?>" class="filter-btn <?php echo $statusFilter=='Completed'?'active':''; ?>">Completed</a>
    <a href="?status=Cancelled<?php echo !empty($search)?'&search='.urlencode($search):''; ?>" class="filter-btn <?php echo $statusFilter=='Cancelled'?'active':''; ?>">Cancelled</a>
  </div>
  <form method="GET" class="appt-search-form">
    <input type="hidden" name="status" value="<?php echo $statusFilter; ?>">
    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
      placeholder="Search patient, email or service..." class="search-box" style="width:280px;">
    <button type="submit" class="table-btn"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
    <?php if(!empty($search)): ?>
    <a href="?status=<?php echo $statusFilter; ?>" class="table-btn" style="background:rgba(255,255,255,0.05);text-decoration:none;">
      <i class="fa-solid fa-xmark"></i> Clear
    </a>
    <?php endif; ?>
  </form>
</div>

<div class="table-container hover-glow">

<div class="table-header">
<div>
<h2>Appointment Management</h2>
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

<?php if(mysqli_num_rows($appointments) > 0): ?>
<?php while($row = mysqli_fetch_assoc($appointments)): ?>

<tr>

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
<div class="status-pill <?php echo strtolower(str_replace(' ', '-', $row['status'])); ?>">
<?php echo ucfirst($row['status']); ?>
</div>
</td>

<td>
<div class="queue-pill">
<?php echo !empty($row['queue_number']) ? '#'.$row['queue_number'] : '—'; ?>
</div>
</td>

<td>
<div class="action-group">

<?php if($row['status'] == 'Approved'): ?>
<button class="action-btn-sm edit"
onclick="openQueueModal(<?php echo $row['id']; ?>, '<?php echo addslashes($row['patient_name']); ?>', <?php echo (int)$row['queue_number']; ?>)">
<i class="fa-solid fa-hashtag"></i>
</button>
<?php endif; ?>

<?php if(in_array($row['status'], ['Pending','Approved'])): ?>
<form method="POST" style="display:inline;"
onsubmit="return confirm('Cancel this appointment?')">
<input type="hidden" name="action" value="cancel">
<input type="hidden" name="appointment_id" value="<?php echo $row['id']; ?>">
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
<td colspan="7" style="text-align:center;padding:30px;">No appointments found.</td>
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
<h3><i class="fa-solid fa-hashtag"></i> Reassign Queue Number</h3>
<button class="modal-close" onclick="closeModal('queueModal')">
<i class="fa-solid fa-xmark"></i>
</button>
</div>

<p id="queuePatientName" style="color:#94a3b8;margin-bottom:20px;"></p>

<form method="POST">
<input type="hidden" name="action" value="reassign">
<input type="hidden" name="appointment_id" id="queueAppointmentId">

<div class="form-group">
<label><i class="fa-solid fa-hashtag"></i> New Queue Number</label>
<input type="number" name="queue_number" id="queueNumber" min="1" required>
</div>

<button type="submit" class="primary-btn hover-glow">
<i class="fa-solid fa-hashtag"></i> Update Queue
</button>

</form>

</div>
</div>

<script>
function openQueueModal(id, name, queue){
    document.getElementById('queueAppointmentId').value = id;
    document.getElementById('queuePatientName').textContent = 'Patient: ' + name;
    document.getElementById('queueNumber').value = queue || '';
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
