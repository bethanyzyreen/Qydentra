<?php
$allowed_roles = ['patient'];
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

function redirect_reschedule($params = []) {
    $query = http_build_query($params);
    header("Location: appointments.php" . ($query ? "?$query" : ""));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_reschedule();
}

$user_id = $_SESSION['user_id'];
$uid_esc = mysqli_real_escape_string($conn, $user_id);
$id = mysqli_real_escape_string($conn, $_POST['appointment_id'] ?? '');
$date = mysqli_real_escape_string($conn, $_POST['new_date'] ?? '');

if ($id === '' || $date === '') {
    redirect_reschedule(['reschedule_error' => 'missing']);
}

try {
    $picked_date = new DateTime($date);
    $today = new DateTime(date('Y-m-d'));
} catch (Exception $e) {
    redirect_reschedule(['reschedule_error' => 'invalid_date']);
}

if ($picked_date < $today) {
    redirect_reschedule(['reschedule_error' => 'past']);
}

if ($picked_date->format('N') == 7) {
    redirect_reschedule(['reschedule_error' => 'sunday']);
}

$appt = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT a.*, p.full_name AS patient_name
     FROM appointments a
     JOIN patients p ON a.patient_id = p.patient_id
     WHERE a.appointment_id='$id'
       AND a.patient_id='$uid_esc'
       AND a.status IN ('Pending','Approved')"
));

if (!$appt) {
    redirect_reschedule(['reschedule_error' => 'not_found']);
}

$booked_result = mysqli_query($conn,
    "SELECT queue_number FROM appointments
     WHERE appointment_date = '$date'
       AND appointment_id <> '$id'
       AND status NOT IN ('Cancelled')"
);

$booked_queues = [];
while ($row = mysqli_fetch_assoc($booked_result)) {
    $booked_queues[(int)$row['queue_number']] = true;
}

$now = new DateTime('now');
$queue_number = null;
$time = null;
$time_label = null;

foreach ($queue_schedule as $qnum => $info) {
    [$time_val, $label] = $info;
    $slot_dt = new DateTime($date . ' ' . $time_val);
    if (!isset($booked_queues[$qnum]) && $slot_dt > $now) {
        $queue_number = $qnum;
        $time = $time_val;
        $time_label = $label;
        break;
    }
}

if ($queue_number === null) {
    redirect_reschedule(['reschedule_error' => 'full']);
}

$queue_esc = (int)$queue_number;
mysqli_query($conn,
    "UPDATE appointments
     SET appointment_date='$date',
         appointment_time='$time',
         queue_number='$queue_esc',
         status='Pending'
     WHERE appointment_id='$id'
       AND patient_id='$uid_esc'
       AND status IN ('Pending','Approved')"
);

notify_patient(
    $conn,
    $user_id,
    'Reschedule Request Submitted',
    'Your reschedule request was submitted for ' . date('F d, Y', strtotime($date)) . ' at ' . $time_label . ' with Queue #' . $queue_number . '. The clinic will review it shortly.',
    'Appointment',
    $id
);

notify_receptionists(
    $conn,
    'Appointment Reschedule Requested',
    'Patient ' . ($appt['patient_name'] ?? 'Patient') . ' requested to reschedule ' . ($appt['service_type'] ?? 'an appointment') . ' to ' . date('F d, Y', strtotime($date)) . ' at ' . $time_label . ' with Queue #' . $queue_number . '.',
    'Appointment',
    $id
);

redirect_reschedule([
    'rescheduled' => 1,
    'queue' => $queue_number,
    'time' => $time_label,
    'date' => $date,
]);
?>
