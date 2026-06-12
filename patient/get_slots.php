<?php
header('Content-Type: application/json');
$allowed_roles = ['patient'];
include(__DIR__ . '/../includes/auth_check.php');

$date = mysqli_real_escape_string($conn, $_GET['date'] ?? '');
$patient_id = $_SESSION['user_id'] ?? '';

if (empty($date)) {
    echo json_encode(['error' => 'No date provided']);
    exit;
}

// Queue Number => [time value (HH:MM 24h), display label]
$queue_schedule = [
    1 => ['08:00', '8:00 AM'],
    2 => ['09:00', '9:00 AM'],
    3 => ['10:00', '10:00 AM'],
    4 => ['11:00', '11:00 AM'],
    5 => ['12:00', '12:00 PM'],
    6 => ['13:00', '1:00 PM'],
    7 => ['14:00', '2:00 PM'],
    8 => ['15:00', '3:00 PM'],
    9 => ['16:00', '4:00 PM'],
    10 => ['17:00', '5:00 PM'],
];

// ── Sunday check ──────────────────────────────────────────────────────────
$picked_date = null;
try {
    $picked_date = new DateTime($date);
} catch (Exception $e) {
    echo json_encode(['error' => 'Invalid date']);
    exit;
}

if ($picked_date->format('N') == 7) { // 7 = Sunday
    echo json_encode([
        'date'      => $date,
        'is_sunday' => true,
        'error_msg' => 'Appointments are available Monday to Saturday only.',
    ]);
    exit;
}

// Get all booked (non-cancelled) queue numbers for this date
$booked_result = mysqli_query($conn,
    "SELECT queue_number FROM appointments
     WHERE appointment_date = '$date'
     AND status NOT IN ('Cancelled')"
);
$booked_queues = [];
while ($r = mysqli_fetch_assoc($booked_result)) {
    $booked_queues[(int)$r['queue_number']] = true;
}

$now = new DateTime('now');

// Determine next available queue number (lowest, must also be in the future)
$next_queue = null;
$next_time_label = null;
foreach ($queue_schedule as $qnum => $info) {
    list($time_val, $label) = $info;
    $slot_dt = new DateTime($date . ' ' . $time_val);
    if (!isset($booked_queues[$qnum]) && $slot_dt > $now) {
        $next_queue = $qnum;
        $next_time_label = $label;
        break;
    }
}

// Remaining = how many of the 10 queue slots are still bookable
// (not booked AND not already in the past for "today")
$remaining = 0;
foreach ($queue_schedule as $qnum => $info) {
    list($time_val, $label) = $info;
    $slot_dt = new DateTime($date . ' ' . $time_val);
    if (!isset($booked_queues[$qnum]) && $slot_dt > $now) {
        $remaining++;
    }
}

$fully_booked = ($next_queue === null);

// Availability level for UI styling: available / limited / full
if ($fully_booked) {
    $availability_level = 'full';
} elseif ($remaining <= 3) {
    $availability_level = 'limited';
} else {
    $availability_level = 'available';
}

// Check if this patient already has an active booking on this date
$already_booked = false;
if (!empty($patient_id)) {
    $pid_esc = mysqli_real_escape_string($conn, $patient_id);
    $dup_check = mysqli_query($conn,
        "SELECT COUNT(*) AS cnt FROM appointments
         WHERE appointment_date = '$date'
         AND patient_id = '$pid_esc'
         AND status NOT IN ('Cancelled')"
    );
    $dup_row = mysqli_fetch_assoc($dup_check);
    $already_booked = $dup_row && $dup_row['cnt'] > 0;
}

echo json_encode([
    'date'                => $date,
    'is_sunday'           => false,
    'remaining'           => $remaining,
    'total_slots'         => 10,
    'fully_booked'        => $fully_booked,
    'availability_level'  => $availability_level,
    'already_booked'      => $already_booked,
    'next_queue'          => $next_queue,
    'next_time_label'     => $next_time_label,
]);
