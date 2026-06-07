<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
?>
<?php
$allowed_roles = ['receptionist'];
include("../includes/auth_check.php");
require_once(__DIR__ . "/../includes/id_helper.php");

$success = '';
$error   = '';

/* ================= REGISTER WALK-IN ================= */
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register_walkin'])) {

    $full_name = mysqli_real_escape_string($conn, trim($_POST['full_name']));
    $email     = mysqli_real_escape_string($conn, trim($_POST['email']));
    $service   = mysqli_real_escape_string($conn, $_POST['service']);
    $date      = mysqli_real_escape_string($conn, $_POST['appointment_date']);
    $time      = mysqli_real_escape_string($conn, $_POST['appointment_time']);
    $notes     = mysqli_real_escape_string($conn, $_POST['notes'] ?? '');

    // Check if patient already exists
    $checkUser = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT patient_id FROM patients WHERE email='$email'"
    ));

    if ($checkUser) {
        $patient_id = $checkUser['patient_id'];   // VARCHAR e.g. PT001
    } else {
        // Create new patient — trigger assigns patient_id automatically
        $tempPass = password_hash('walkin' . rand(1000, 9999), PASSWORD_DEFAULT);
        mysqli_query($conn,
            "INSERT INTO patients (full_name, email, password, role)
             VALUES ('$full_name', '$email', '$tempPass', 'patient')"
        );
        $patient_id = get_last_inserted_id($conn, 'patients');
    }

    // Get next queue number for that date (excluding cancelled)
    $nextQueueResult = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COALESCE(MAX(queue_number),0)+1 AS next_q
         FROM appointments
         WHERE appointment_date='$date'
         AND status NOT IN ('Cancelled')"
    ));
    $queueNumber = $nextQueueResult['next_q'];

    // Insert appointment — trigger assigns appointment_id
    $pid_esc = mysqli_real_escape_string($conn, $patient_id);
    mysqli_query($conn,
        "INSERT INTO appointments
         (patient_id, service_type, service_desc, appointment_date, appointment_time, status, queue_number, notes)
         VALUES
         ('$pid_esc', '$service', '$service', '$date', '$time', 'Approved', '$queueNumber', '$notes')"
    );
    $new_appt_id = get_last_inserted_id($conn, 'appointments');

    notify_patient(
        $conn, $patient_id,
        'Walk-in Appointment Registered',
        notification_patient_walkin_recorded($full_name, $service, $date, $time),
        'Appointment', $new_appt_id
    );

    notify_receptionists(
        $conn,
        'Walk-in Appointment Recorded',
        notification_receptionist_walkin_recorded($full_name, $service, $date, $time),
        'Appointment', $new_appt_id
    );

    $success = "Walk-in registered successfully! Queue #$queueNumber assigned.";
}

// Today's walk-ins
$todayWalkins = mysqli_query($conn,
    "SELECT a.*, u.full_name AS patient_name
     FROM appointments a
     JOIN patients u ON a.patient_id = u.patient_id
     WHERE a.appointment_date = CURDATE()
     AND a.status IN ('Approved','In Progress','Completed')
     ORDER BY a.queue_number ASC"
);
?>

<?php include("../includes/receptionist_header.php"); ?>

<body>

<?php include("../includes/receptionist_sidebar.php"); ?>

<div class="main">

<?php include("../includes/receptionist_topbar.php"); ?>

<?php if ($success): ?>
<div class="alert-success">
<i class="fa-solid fa-circle-check"></i>
<?php echo $success; ?>
</div>
<?php endif; ?>

<div class="booking-layout">

<!-- REGISTRATION FORM -->
<div class="table-container hover-glow">

<div class="table-header">
<div>
<h2>Walk-in Registration</h2>
<p>Register walk-in patients and assign queue numbers.</p>
</div>
</div>

<form method="POST" class="booking-form">

<div class="form-group">
<label><i class="fa-solid fa-user"></i> Patient Full Name</label>
<input type="text" name="full_name" placeholder="Enter full name" required>
</div>

<div class="form-group">
<label><i class="fa-solid fa-envelope"></i> Patient Email</label>
<input type="email" name="email" placeholder="Enter email address" required>
<small style="color:#64748b;font-size:12px;">If not registered, a new patient account will be created.</small>
</div>

<div class="form-group">
<label><i class="fa-solid fa-tooth"></i> Dental Service</label>
<select name="service" required>
<option value="">Select Service</option>
<option>Teeth Cleaning</option>
<option>Tooth Extraction</option>
<option>Dental Filling</option>
<option>Braces Consultation</option>
<option>Dental Checkup</option>
<option>Emergency Dental</option>
</select>
</div>

<div class="form-row">
<div class="form-group">
<label><i class="fa-solid fa-calendar-days"></i> Date</label>
<input type="date" name="appointment_date"
value="<?php echo date('Y-m-d'); ?>"
min="<?php echo date('Y-m-d'); ?>" required>
</div>
<div class="form-group">
<label><i class="fa-solid fa-clock"></i> Time</label>
<input type="time" name="appointment_time"
value="<?php echo date('H:i'); ?>"
min="08:00" max="17:00" required>
</div>
</div>

<div class="form-group">
<label><i class="fa-solid fa-note-sticky"></i> Notes</label>
<textarea name="notes" placeholder="Dental concerns or special notes..."></textarea>
</div>

<button type="submit" name="register_walkin" class="primary-btn hover-glow">
<i class="fa-solid fa-person-walking-arrow-right"></i>
Register Walk-in
</button>

</form>

</div>

<!-- TODAY'S QUEUE CARD -->
<div class="table-container guide-card hover-glow">

<div class="table-header">
<div>
<h2>Today's Queue</h2>
<p><?php echo date("F d, Y"); ?></p>
</div>
</div>

<?php if (mysqli_num_rows($todayWalkins) > 0): ?>

<?php while ($row = mysqli_fetch_assoc($todayWalkins)): ?>

<div class="queue-card-item">

<div class="queue-number-badge">
#<?php echo $row['queue_number'] ?? '—'; ?>
</div>

<div class="queue-card-info">
<h4><?php echo htmlspecialchars($row['patient_name']); ?></h4>
<p><?php echo htmlspecialchars($row['service_type'] ?? '—'); ?></p>
</div>

<div class="status-pill <?php echo strtolower($row['status']); ?>">
<?php echo ucfirst($row['status']); ?>
</div>

</div>

<?php endwhile; ?>

<?php else: ?>
<div class="empty-state" style="padding:30px 0;">
<i class="fa-solid fa-users" style="font-size:32px;color:#334155;"></i>
<h3>No queue yet today</h3>
<p>Register walk-in patients to populate the queue.</p>
</div>
<?php endif; ?>

</div>

</div>

</div>

</body>
</html>
