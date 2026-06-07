<?php
$allowed_roles = ['patient'];
include("../includes/auth_check.php");

$user_id = $_SESSION['user_id'];                          // VARCHAR e.g. PT001
$id      = mysqli_real_escape_string($conn, $_GET['id'] ?? '');  // VARCHAR e.g. AP001
$uid_esc = mysqli_real_escape_string($conn, $user_id);

$apptRow = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT a.*, u.full_name AS patient_name
     FROM appointments a
     JOIN patients u ON a.patient_id = u.patient_id
     WHERE a.appointment_id='$id' AND a.patient_id='$uid_esc'
     AND a.status IN ('Pending','Approved')"
));

if ($apptRow) {
    mysqli_query($conn,
        "UPDATE appointments SET status='Cancelled'
         WHERE appointment_id='$id' AND patient_id='$uid_esc'
         AND status IN ('Pending','Approved')"
    );

    $service      = $apptRow['service_type'];
    $patient_name = $apptRow['patient_name'];

    notify_patient(
        $conn, $user_id,
        'Appointment Cancelled',
        notification_patient_appointment_cancelled(
            $patient_name, $service,
            $apptRow['appointment_date'],
            $apptRow['appointment_time']
        ),
        'Appointment', $id
    );

    $fmt_date = date("F d, Y", strtotime($apptRow['appointment_date']));
    $fmt_time = date("g:i A",  strtotime($apptRow['appointment_time']));

    notify_receptionists(
        $conn,
        'Appointment Cancelled by Patient',
        notification_receptionist_appointment_cancelled_by_patient(
            $patient_name, $service, $fmt_date, $fmt_time
        ),
        'Appointment', $id
    );
}

header("Location: appointments.php");
exit();
