<?php
$allowed_roles = ['receptionist'];
include("../includes/auth_check.php");

/* ================= APPROVE ACTION ================= */
if(isset($_POST['action']) && $_POST['action'] == 'approve'){
    $id = (int)$_POST['appointment_id'];
    $queue = (int)$_POST['queue_number'];
    $date = mysqli_real_escape_string($conn, $_POST['appointment_date']);
    $time = mysqli_real_escape_string($conn, $_POST['appointment_time']);

    // Strictly enforce unique queue number per day — reject if already taken by another non-cancelled appointment
    $existing = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COUNT(*) AS cnt FROM appointments
         WHERE appointment_date='$date'
         AND queue_number='$queue'
         AND appointment_id != '$id'
         AND status NOT IN ('Cancelled')"
    ));
    if((int)$existing['cnt'] > 0){
        $nextAvail = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT COALESCE(MAX(queue_number),0)+1 AS next_q
             FROM appointments
             WHERE appointment_date='$date'
             AND status NOT IN ('Cancelled')"
        ));
        $suggested = (int)$nextAvail['next_q'];
        header("Location: pending_appointments.php?error=queue_taken&queue=$queue&suggested=$suggested&date=$date");
        exit();
    }

    mysqli_query($conn,"
    UPDATE appointments
    SET status='Approved', queue_number='$queue',
        appointment_date='$date', appointment_time='$time'
    WHERE appointment_id='$id'
    ");

    $getAppt = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT a.*, u.full_name AS patient_name FROM appointments a JOIN patients u ON a.patient_id = u.patient_id WHERE a.appointment_id='$id'"
    ));
    $pid = $getAppt['patient_id'];
    $patient_name_esc = mysqli_real_escape_string($conn, $getAppt['patient_name']);
    $service = mysqli_real_escape_string($conn, $getAppt['service_type']);

    // Notify patient
    $pat_msg = notification_patient_appointment_approved($patient_name_esc, $service, $date, $time);
    $pat_msg_esc = mysqli_real_escape_string($conn, $pat_msg);
    mysqli_query($conn,"INSERT INTO patient_notifications (patient_id, message) VALUES ('$pid', '$pat_msg_esc')");

    // Notify all receptionists
    $r_title = "Appointment Approved";
    $r_msg   = notification_receptionist_appointment_approved($patient_name_esc, $service, $date, $time);
    $rr = mysqli_query($conn,"SELECT staff_id AS user_id FROM staff WHERE role='receptionist'");
    while($rrow = mysqli_fetch_assoc($rr)){
        $rid = $rrow['user_id'];
        mysqli_query($conn,"INSERT INTO receptionist_notifications (receptionist_id,title,message,type,status) VALUES ('$rid','$r_title','$r_msg','Appointment','Unread')");
    }

    header("Location: pending_appointments.php?success=approved");
    exit();
}

/* ================= RESCHEDULE ACTION ================= */
if(isset($_POST['action']) && $_POST['action'] == 'reschedule'){
    $id = (int)$_POST['appointment_id'];
    $date = mysqli_real_escape_string($conn, $_POST['new_date']);
    $time = mysqli_real_escape_string($conn, $_POST['new_time']);

    mysqli_query($conn,"
    UPDATE appointments
    SET appointment_date='$date', appointment_time='$time', status='Pending'
    WHERE appointment_id='$id'
    ");

    $getAppt = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT a.*, u.full_name AS patient_name FROM appointments a JOIN patients u ON a.patient_id = u.patient_id WHERE a.appointment_id='$id'"
    ));
    $pid              = $getAppt['patient_id'];
    $patient_name_esc = mysqli_real_escape_string($conn, $getAppt['patient_name']);
    $service          = mysqli_real_escape_string($conn, $getAppt['service_type']);
    $fmt_date         = date("F d, Y", strtotime($date));
    $fmt_time         = date("g:i A",  strtotime($time));

    // Get receptionist's name
    $recep_self = mysqli_fetch_assoc(mysqli_query($conn,"SELECT full_name FROM patients WHERE patient_id='{$_SESSION['user_id']}'"));
    $recep_name = mysqli_real_escape_string($conn, $recep_self['full_name']);

    // Notify patient
    $pat_msg = notification_patient_appointment_rescheduled($patient_name_esc, $service, $date, $time);
    $pat_msg_esc = mysqli_real_escape_string($conn, $pat_msg);
    mysqli_query($conn,"INSERT INTO patient_notifications (patient_id, message) VALUES ('$pid', '$pat_msg_esc')");

    // Notify all receptionists
    $r_title = mysqli_real_escape_string($conn, "Appointment Rescheduled");
    $r_msg   = mysqli_real_escape_string($conn, notification_receptionist_appointment_rescheduled($patient_name_esc, $service, $date, $time));
    $rr = mysqli_query($conn,"SELECT staff_id AS user_id FROM staff WHERE role='receptionist'");
    while($rrow = mysqli_fetch_assoc($rr)){
        $rid = $rrow['user_id'];
        mysqli_query($conn,"INSERT INTO receptionist_notifications (receptionist_id,title,message,type,status) VALUES ('$rid','$r_title','$r_msg','Appointment','Unread')");
    }

    header("Location: pending_appointments.php?success=rescheduled");
    exit();
}

$pendingList = mysqli_query($conn,"
SELECT a.*, u.full_name AS patient_name, u.email AS patient_email
FROM appointments a
JOIN patients u ON a.patient_id = u.patient_id
WHERE a.status = 'Pending'
ORDER BY a.appointment_date ASC, a.appointment_time ASC
");

$nextQueue = mysqli_fetch_assoc(mysqli_query($conn,"
SELECT COALESCE(MAX(queue_number),0)+1 AS next_q FROM appointments
WHERE appointment_date = CURDATE()
"))['next_q'];
?>

<?php include("../includes/receptionist_header.php"); ?>

<body>

<?php include("../includes/receptionist_sidebar.php"); ?>

<div class="main">

<?php include("../includes/receptionist_topbar.php"); ?>

<?php if(isset($_GET['success'])): ?>
<div class="alert-success">
<i class="fa-solid fa-circle-check"></i>
<?php echo $_GET['success'] == 'approved' ? 'Appointment approved successfully.' : 'Appointment rescheduled successfully.'; ?>
</div>
<?php endif; ?>

<?php if(isset($_GET['error']) && $_GET['error'] === 'queue_taken'): ?>
<div class="alert-error" style="
    background:rgba(239,68,68,0.10);
    border:1px solid rgba(239,68,68,0.30);
    border-radius:14px;
    padding:14px 20px;
    margin-bottom:20px;
    color:#f87171;
    display:flex;
    align-items:center;
    gap:10px;
    font-size:14px;
    font-weight:500;
">
<i class="fa-solid fa-triangle-exclamation"></i>
Queue #<?php echo (int)$_GET['queue']; ?> is already taken on <?php echo date('F d, Y', strtotime($_GET['date'])); ?>.
Next available queue number is<strong style="color:#fca5a5;">#<?php echo (int)$_GET['suggested'];?></strong>.
Please re-approve with the correct queue number.
</div>
<?php endif; ?>

<div class="table-container hover-glow">

<div class="table-header">
<div>
<h2>Pending Appointments</h2>
<p>Review and approve patient appointment requests.</p>
</div>
<div class="badge-count"><?php echo mysqli_num_rows($pendingList); ?> Pending</div>
</div>

<?php if(mysqli_num_rows($pendingList) > 0): ?>

<table>
<thead>
<tr>
    <th>Patient</th>
    <th>Service</th>
    <th>Requested Date</th>
    <th>Time</th>
    <th>Notes</th>
    <th>Actions</th>
</tr>
</thead>
<tbody>

<?php while($row = mysqli_fetch_assoc($pendingList)): ?>

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

<td><?php echo !empty($row['notes']) ? htmlspecialchars(substr($row['notes'],0,40)).'...' : '—'; ?></td>

<td style="width:150px;white-space:nowrap;">
<div class="action-group">

<!-- APPROVE BUTTON (triggers modal) -->
<button class="approve-btn"
onclick="openApproveModal(
    <?php echo $row['appointment_id']; ?>,
    '<?php echo addslashes($row['patient_name']); ?>',
    '<?php echo $row['appointment_date']; ?>',
    '<?php echo $row['appointment_time']; ?>',
    <?php echo $nextQueue; ?>
)">
<i class="fa-solid fa-check"></i> Approve
</button>

<!-- RESCHEDULE BUTTON (triggers modal) -->
<button class="reschedule-btn"
onclick="openRescheduleModal(
    <?php echo $row['appointment_id']; ?>,
    '<?php echo addslashes($row['patient_name']); ?>'
)">
<i class="fa-solid fa-calendar-pen"></i> Reschedule
</button>

</div>
</td>

</tr>

<?php endwhile; ?>

</tbody>
</table>

<?php else: ?>

<div class="empty-state">
<i class="fa-solid fa-calendar-check"></i>
<h3>No Pending Requests</h3>
<p>All appointment requests have been reviewed.</p>
</div>

<?php endif; ?>

</div>

</div>

<!-- ================= APPROVE MODAL ================= -->

<div class="modal-overlay" id="approveModal">
<div class="modal-card">

<div class="modal-header">
<h3><i class="fa-solid fa-calendar-check"></i> Approve Appointment</h3>
<button class="modal-close" onclick="closeModal('approveModal')">
<i class="fa-solid fa-xmark"></i>
</button>
</div>

<p id="approvePatientName" style="color:#94a3b8;margin-bottom:20px;"></p>

<form method="POST">
<input type="hidden" name="action" value="approve">
<input type="hidden" name="appointment_id" id="approveAppointmentId">

<div class="form-group">
<label><i class="fa-solid fa-calendar-days"></i> Confirm Date</label>
<input type="date" name="appointment_date" id="approveDate" required>
</div>

<div class="form-row">
<div class="form-group">
<label><i class="fa-solid fa-clock"></i> Confirm Time</label>
<input type="time" name="appointment_time" id="approveTime" required>
</div>
<div class="form-group">
<label><i class="fa-solid fa-hashtag"></i> Queue Number</label>
<input type="number" name="queue_number" id="approveQueue" min="1" required>
</div>
</div>

<button type="submit" class="primary-btn hover-glow">
<i class="fa-solid fa-check"></i> Confirm Approval
</button>

</form>

</div>
</div>

<!-- ================= RESCHEDULE MODAL ================= -->

<div class="modal-overlay" id="rescheduleModal">
<div class="modal-card">

<div class="modal-header">
<h3><i class="fa-solid fa-calendar-pen"></i> Reschedule Appointment</h3>
<button class="modal-close" onclick="closeModal('rescheduleModal')">
<i class="fa-solid fa-xmark"></i>
</button>
</div>

<p id="reschedulePatientName" style="color:#94a3b8;margin-bottom:20px;"></p>

<form method="POST">
<input type="hidden" name="action" value="reschedule">
<input type="hidden" name="appointment_id" id="rescheduleAppointmentId">

<div class="form-row">
<div class="form-group">
<label><i class="fa-solid fa-calendar-days"></i> New Date</label>
<input type="date" name="new_date" min="<?php echo date('Y-m-d'); ?>" required>
</div>
<div class="form-group">
<label><i class="fa-solid fa-clock"></i> New Time</label>
<input type="time" name="new_time" min="08:00" max="17:00" required>
</div>
</div>

<button type="submit" class="primary-btn hover-glow" style="background:linear-gradient(135deg,#f59e0b,#d97706);">
<i class="fa-solid fa-calendar-pen"></i> Confirm Reschedule
</button>

</form>

</div>
</div>

<script>
function openApproveModal(id, name, date, time, queue){
    document.getElementById('approveAppointmentId').value = id;
    document.getElementById('approvePatientName').textContent = 'Patient: ' + name;
    document.getElementById('approveDate').value = date;
    document.getElementById('approveTime').value = time;
    document.getElementById('approveQueue').value = queue;
    document.getElementById('approveModal').classList.add('active');
}

function openRescheduleModal(id, name){
    document.getElementById('rescheduleAppointmentId').value = id;
    document.getElementById('reschedulePatientName').textContent = 'Patient: ' + name;
    document.getElementById('rescheduleModal').classList.add('active');
}

function closeModal(id){
    document.getElementById(id).classList.remove('active');
}

document.querySelectorAll('.modal-overlay').forEach(function(overlay){
    overlay.addEventListener('click', function(e){
        if(e.target === overlay) overlay.classList.remove('active');
    });
});

// When date changes in approve modal, fetch suggested queue number for that date
document.getElementById('approveDate').addEventListener('change', function(){
    var date = this.value;
    if(!date) return;
    fetch('get_queue_suggestion.php?date=' + encodeURIComponent(date))
        .then(function(r){ return r.json(); })
        .then(function(data){ document.getElementById('approveQueue').value = data.next_q; });
});
</script>

</body>
</html>
