<?php
$allowed_roles = ['receptionist'];
include("../includes/auth_check.php");

$queue_schedule = [
    1  => ['08:00', '8:00 AM'],
    2  => ['09:00', '9:00 AM'],
    3  => ['10:00', '10:00 AM'],
    4  => ['11:00', '11:00 AM'],
    5  => ['12:00', '12:00 PM'],
    6  => ['13:00', '1:00 PM'],
    7  => ['14:00', '2:00 PM'],
    8  => ['15:00', '3:00 PM'],
    9  => ['16:00', '4:00 PM'],
    10 => ['17:00', '5:00 PM'],
];

function get_queue_from_time($time) {
    $slot_map = ['08:00'=>1,'09:00'=>2,'10:00'=>3,'11:00'=>4,'12:00'=>5,
                 '13:00'=>6,'14:00'=>7,'15:00'=>8,'16:00'=>9,'17:00'=>10];
    $normalized = date('H:00', strtotime($time));
    return $slot_map[$normalized] ?? null;
}

function find_next_open_queue_slot($conn, $date, $exclude_id, $queue_schedule) {
    $date_esc = mysqli_real_escape_string($conn, $date);
    $exclude_esc = mysqli_real_escape_string($conn, $exclude_id);
    $result = mysqli_query($conn,
        "SELECT queue_number FROM appointments
         WHERE appointment_date='$date_esc'
           AND appointment_id <> '$exclude_esc'
           AND status NOT IN ('Cancelled')"
    );

    $booked = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $booked[(int)$row['queue_number']] = true;
    }

    $now = new DateTime('now');
    foreach ($queue_schedule as $queue => $info) {
        [$time, $label] = $info;
        $slot_dt = new DateTime($date . ' ' . $time);
        if (!isset($booked[$queue]) && $slot_dt > $now) {
            return ['queue' => $queue, 'time' => $time, 'label' => $label];
        }
    }

    return null;
}

/* ================= APPROVE ACTION ================= */
if (isset($_POST['action']) && $_POST['action'] == 'approve') {
    $id    = mysqli_real_escape_string($conn, $_POST['appointment_id']); // VARCHAR
    $date  = mysqli_real_escape_string($conn, $_POST['appointment_date']);
    $time  = mysqli_real_escape_string($conn, $_POST['appointment_time']);

    $queue = get_queue_from_time($time);

    $conflict = null;
    if ($queue !== null) {
        $conflict = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT appointment_id FROM appointments
             WHERE appointment_date='$date'
               AND queue_number='$queue'
               AND appointment_id <> '$id'
               AND status NOT IN ('Cancelled')
             LIMIT 1"
        ));
    }

    if ($queue === null || $conflict) {
        $slot = find_next_open_queue_slot($conn, $date, $id, $queue_schedule);
        if (!$slot) {
            header("Location: pending_appointments.php?error=fully_booked");
            exit();
        }
        $queue = $slot['queue'];
        $time = $slot['time'];
    }

    mysqli_query($conn,
        "UPDATE appointments
         SET status='Approved', queue_number='$queue',
             appointment_date='$date', appointment_time='$time'
         WHERE appointment_id='$id'"
    );

    $getAppt = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT a.*, u.full_name AS patient_name
         FROM appointments a JOIN patients u ON a.patient_id = u.patient_id
         WHERE a.appointment_id='$id'"
    ));
    $pid          = $getAppt['patient_id'];  // VARCHAR
    $patient_name = $getAppt['patient_name'];
    $service      = $getAppt['service_type'];

    notify_patient(
        $conn, $pid,
        'Appointment Approved',
        notification_patient_appointment_approved($patient_name, $service, $date, $time),
        'Appointment', $id
    );

    notify_receptionists(
        $conn,
        'Appointment Approved',
        notification_receptionist_appointment_approved($patient_name, $service, $date, $time),
        'Appointment', $id
    );

    header("Location: pending_appointments.php?success=approved");
    exit();
}

/* ================= RESCHEDULE ACTION ================= */
if (isset($_POST['action']) && $_POST['action'] == 'reschedule') {
    $id   = mysqli_real_escape_string($conn, $_POST['appointment_id']); // VARCHAR
    $date = mysqli_real_escape_string($conn, $_POST['new_date']);
    $slot = find_next_open_queue_slot($conn, $date, $id, $queue_schedule);

    if (!$slot) {
        header("Location: pending_appointments.php?error=fully_booked");
        exit();
    }

    $time = mysqli_real_escape_string($conn, $slot['time']);
    $queue = (int)$slot['queue'];

    mysqli_query($conn,
        "UPDATE appointments
         SET appointment_date='$date', appointment_time='$time', queue_number='$queue', status='Pending'
         WHERE appointment_id='$id'"
    );

    $getAppt = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT a.*, u.full_name AS patient_name
         FROM appointments a JOIN patients u ON a.patient_id = u.patient_id
         WHERE a.appointment_id='$id'"
    ));
    $pid          = $getAppt['patient_id'];  // VARCHAR
    $patient_name = $getAppt['patient_name'];
    $service      = $getAppt['service_type'];

    notify_patient(
        $conn, $pid,
        'Appointment Rescheduled',
        notification_patient_appointment_rescheduled($patient_name, $service, $date, $time),
        'Appointment', $id
    );

    notify_receptionists(
        $conn,
        'Appointment Rescheduled',
        notification_receptionist_appointment_rescheduled($patient_name, $service, $date, $time),
        'Appointment', $id
    );

    header("Location: pending_appointments.php?success=rescheduled");
    exit();
}

$pendingList = mysqli_query($conn,
    "SELECT a.*, u.full_name AS patient_name, u.email AS patient_email
     FROM appointments a
     JOIN patients u ON a.patient_id = u.patient_id
     WHERE a.status = 'Pending'
     ORDER BY a.appointment_date ASC, a.appointment_time ASC"
);

$nextQueue = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COALESCE(MAX(queue_number),0)+1 AS next_q FROM appointments
     WHERE appointment_date = CURDATE()"
))['next_q'];
?>

<?php include("../includes/receptionist_header.php"); ?>

<body>

<?php include("../includes/receptionist_sidebar.php"); ?>

<div class="main">

<?php include("../includes/receptionist_topbar.php"); ?>

<?php if (isset($_GET['success'])): ?>
<div data-toast="<?php echo $_GET['success'] == 'approved' ? 'Appointment approved successfully.' : 'Appointment rescheduled successfully.'; ?>" data-toast-type="success"></div>
<?php endif; ?>

<?php if (isset($_GET['error']) && $_GET['error'] === 'fully_booked'): ?>
<div data-toast="That date has no open queue slots. Please choose another date." data-toast-type="error"></div>
<?php endif; ?>

<?php if (isset($_GET['error']) && $_GET['error'] === 'queue_taken'): ?>
<div data-toast="Queue #<?php echo (int)$_GET['queue']; ?> is already taken. Next available is #<?php echo (int)$_GET['suggested']; ?>. Please re-approve with the correct queue number." data-toast-type="error"></div>
<?php endif; ?>

<div class="table-container hover-glow">

<div class="table-header">
<div>
<h2><i class="fa-solid fa-hourglass-half" style="color:#ffffff; margin-right:8px;"></i>Pending Appointments</h2>
<p>Review and approve patient appointment requests.</p>
</div>
<div class="badge-count"><?php echo mysqli_num_rows($pendingList); ?> Pending</div>
</div>

<?php if (mysqli_num_rows($pendingList) > 0): ?>

<table>
<thead>
<tr>
    <th>Appt ID</th>
    <th>Patient</th>
    <th>Service</th>
    <th>Requested Date</th>
    <th>Time</th>
    <th>Notes</th>
    <th>Actions</th>
</tr>
</thead>
<tbody>

<?php while ($row = mysqli_fetch_assoc($pendingList)): ?>

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

<td><?php echo !empty($row['notes']) ? htmlspecialchars(substr($row['notes'], 0, 40)) . '...' : '—'; ?></td>

<td style="width:150px;white-space:nowrap;">
<div class="action-group">

<button class="approve-btn"
onclick="openApproveModal(
    '<?php echo addslashes($row['appointment_id']); ?>',
    '<?php echo addslashes($row['patient_name']); ?>',
    '<?php echo $row['appointment_date']; ?>',
    '<?php echo $row['appointment_time']; ?>',
    <?php echo (int)$row['queue_number']; ?>
)">
<i class="fa-solid fa-check"></i> Approve
</button>

<button class="reschedule-btn"
onclick="openRescheduleModal(
    '<?php echo addslashes($row['appointment_id']); ?>',
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
<input type="hidden" name="queue_number" id="approveQueue">
<div id="approveQueueDisplay" style="padding:10px 14px;background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);border-radius:10px;color:#93c5fd;font-weight:600;font-size:15px;">—</div>
<small style="color:#9ca3af;font-size:11px;margin-top:4px;display:block;">Auto-set from the chosen time slot.</small>
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

<div class="form-group">
<label><i class="fa-solid fa-calendar-days"></i> New Date</label>
<input type="date" name="new_date" min="<?php echo date('Y-m-d'); ?>" required>
</div>
<p class="modal-note"><i class="fa-solid fa-circle-info"></i> The next open queue slot and appointment time will be assigned automatically.</p>

<button type="submit" class="primary-btn hover-glow" style="background:linear-gradient(135deg,#f59e0b,#d97706);">
<i class="fa-solid fa-calendar-pen"></i> Confirm Reschedule
</button>

</form>

</div>
</div>

<script>
const slotMap = {'08:00':1,'09:00':2,'10:00':3,'11:00':4,'12:00':5,
                 '13:00':6,'14:00':7,'15:00':8,'16:00':9,'17:00':10};

function getQueueFromTime(time) {
    // Normalize to HH:00
    var hh = time ? time.substring(0,5).replace(/:\d\d$/, ':00') : '';
    // For <input type="time"> values like "08:00"
    var normalized = time ? time.substring(0,2) + ':00' : '';
    return slotMap[normalized] || slotMap[hh] || '—';
}

function updateQueueDisplay(time) {
    var q = getQueueFromTime(time);
    document.getElementById('approveQueue').value = q !== '—' ? q : '';
    document.getElementById('approveQueueDisplay').textContent = q !== '—' ? '#' + q : '—';
}

function openApproveModal(id, name, date, time, queue){
    document.getElementById('approveAppointmentId').value = id;
    document.getElementById('approvePatientName').textContent = 'Patient: ' + name;
    document.getElementById('approveDate').value = date;
    document.getElementById('approveTime').value = time;
    updateQueueDisplay(time);
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

// When time changes in the modal, recalculate queue
document.getElementById('approveTime').addEventListener('change', function(){
    updateQueueDisplay(this.value);
});
</script>

</body>
</html>
