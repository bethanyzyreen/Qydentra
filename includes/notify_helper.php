<?php
/**
 * notify_helper.php
 * Centralised INSERT wrappers for patient & receptionist notifications.
 * Every function returns true on success, false on failure and logs
 * the MySQL error to PHP's error log so failures are never silent.
 */

/**
 * Insert a notification row for a patient.
 *
 * @param mysqli $conn
 * @param int    $patient_id
 * @param string $title           Short heading, e.g. "Appointment Approved"
 * @param string $message         Full notification body
 * @param string $type            'Appointment' | 'Queue' | 'System'
 * @param int|null $appointment_id  FK to appointments table (nullable)
 * @return bool
 */
function notify_patient(
    mysqli $conn,
    int    $patient_id,
    string $title,
    string $message,
    string $type         = 'Appointment',
    ?int   $appointment_id = null
): bool {
    $pid   = (int)$patient_id;
    $t     = mysqli_real_escape_string($conn, $title);
    $m     = mysqli_real_escape_string($conn, $message);
    $tp    = mysqli_real_escape_string($conn, $type);
    $appt  = ($appointment_id !== null) ? (int)$appointment_id : 'NULL';

    $sql = "INSERT INTO patient_notifications
                (patient_id, title, type, message, appointment_id, is_read)
            VALUES
                ('$pid', '$t', '$tp', '$m', $appt, 0)";

    $ok = mysqli_query($conn, $sql);
    if (!$ok) {
        error_log('[Qydentra] notify_patient failed: ' . mysqli_error($conn) . ' | SQL: ' . $sql);
    }
    return (bool)$ok;
}

/**
 * Insert a notification row for every receptionist (or a specific one).
 *
 * @param mysqli   $conn
 * @param string   $title
 * @param string   $message
 * @param string   $type            'Appointment' | 'Queue' | 'System'
 * @param int|null $appointment_id  FK to appointments table (nullable)
 * @param int|null $only_receptionist_id  If set, only notify this staff_id
 * @return bool  true if all inserts succeeded
 */
function notify_receptionists(
    mysqli $conn,
    string $title,
    string $message,
    string $type              = 'Appointment',
    ?int   $appointment_id   = null,
    ?int   $only_receptionist_id = null
): bool {
    $t    = mysqli_real_escape_string($conn, $title);
    $m    = mysqli_real_escape_string($conn, $message);
    $tp   = mysqli_real_escape_string($conn, $type);
    $appt = ($appointment_id !== null) ? (int)$appointment_id : 'NULL';

    if ($only_receptionist_id !== null) {
        $whereClause = "WHERE staff_id = '" . (int)$only_receptionist_id . "'";
    } else {
        $whereClause = "WHERE role = 'receptionist'";
    }

    $rr  = mysqli_query($conn, "SELECT staff_id FROM staffs $whereClause");
    $all = true;

    while ($rrow = mysqli_fetch_assoc($rr)) {
        $rid = (int)$rrow['staff_id'];
        $sql = "INSERT INTO receptionist_notifications
                    (receptionist_id, title, message, type, status, appointment_id)
                VALUES
                    ('$rid', '$t', '$m', '$tp', 'Unread', $appt)";

        $ok = mysqli_query($conn, $sql);
        if (!$ok) {
            error_log('[Qydentra] notify_receptionists failed for staff_id=' . $rid
                . ': ' . mysqli_error($conn) . ' | SQL: ' . $sql);
            $all = false;
        }
    }
    return $all;
}
