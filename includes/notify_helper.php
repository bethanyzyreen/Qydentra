<?php
/**
 * notify_helper.php
 * Centralised INSERT wrappers for patient & receptionist notifications.
 *
 * VARCHAR PK note: MySQL's LAST_INSERT_ID() is 0 for VARCHAR PKs.
 * IDs are auto-assigned by BEFORE INSERT triggers; use get_last_inserted_id()
 * from id_helper.php if you need the newly created notification ID.
 *
 * ID Prefixes (DB only — never shown in UI):
 *   PN → patient_notifications
 *   RN → receptionist_notifications
 *   AP → appointments
 *   PT → patients
 *   RE/ST → staffs
 */

require_once __DIR__ . '/id_helper.php';

/**
 * Insert a notification for a patient.
 *
 * @param mysqli      $conn
 * @param string      $patient_id       FK → patients.patient_id  (e.g. "PT001")
 * @param string      $title
 * @param string      $message
 * @param string      $type             'Appointment' | 'Queue' | 'System'
 * @param string|null $appointment_id   FK → appointments.appointment_id (e.g. "AP001"), nullable
 * @return bool
 */
function notify_patient(
    mysqli  $conn,
    string  $patient_id,
    string  $title,
    string  $message,
    string  $type           = 'Appointment',
    ?string $appointment_id = null
): bool {
    // Accept legacy int calls (cast to string "1" → look up as PT001 prefix)
    if (is_numeric($patient_id)) {
        // Treat bare int as the numeric portion; reconstruct prefixed form
        $patient_id = 'PT' . str_pad((int)$patient_id, 3, '0', STR_PAD_LEFT);
    }

    // Validate patient exists
    $pid_esc   = mysqli_real_escape_string($conn, $patient_id);
    $pid_check = mysqli_query($conn,
        "SELECT patient_id FROM patients WHERE patient_id = '$pid_esc' LIMIT 1"
    );
    if (!$pid_check || mysqli_num_rows($pid_check) === 0) {
        error_log('[Qydentra] notify_patient: patient_id=' . $patient_id . ' does not exist.');
        return false;
    }

    $t  = mysqli_real_escape_string($conn, $title);
    $m  = mysqli_real_escape_string($conn, $message);
    $tp = mysqli_real_escape_string($conn, $type);

    $appt_sql = ($appointment_id !== null)
        ? "'" . mysqli_real_escape_string($conn, $appointment_id) . "'"
        : 'NULL';

    $sql = "INSERT INTO patient_notifications
                (patient_id, title, type, message, appointment_id, is_read)
            VALUES
                ('$pid_esc', '$t', '$tp', '$m', $appt_sql, 0)";

    $ok = mysqli_query($conn, $sql);
    if (!$ok) {
        error_log('[Qydentra] notify_patient failed: ' . mysqli_error($conn) . ' | SQL: ' . $sql);
    }
    return (bool)$ok;
}

/**
 * Insert a notification for every patient.
 *
 * @param mysqli      $conn
 * @param string      $title
 * @param string      $message
 * @param string      $type
 * @param string|null $appointment_id
 * @return bool
 */
function notify_all_patients(
    mysqli  $conn,
    string  $title,
    string  $message,
    string  $type           = 'System',
    ?string $appointment_id = null
): bool {
    $t  = mysqli_real_escape_string($conn, $title);
    $m  = mysqli_real_escape_string($conn, $message);
    $tp = mysqli_real_escape_string($conn, $type);

    $appt_sql = ($appointment_id !== null)
        ? "'" . mysqli_real_escape_string($conn, $appointment_id) . "'"
        : 'NULL';

    $result = mysqli_query($conn, "SELECT patient_id FROM patients");
    if (!$result) {
        error_log('[Qydentra] notify_all_patients failed: ' . mysqli_error($conn));
        return false;
    }
    if (mysqli_num_rows($result) === 0) {
        error_log('[Qydentra] notify_all_patients: no patient accounts found.');
        return false;
    }

    $all = true;
    while ($row = mysqli_fetch_assoc($result)) {
        $pid = mysqli_real_escape_string($conn, $row['patient_id']);
        $sql = "INSERT INTO patient_notifications
                    (patient_id, title, type, message, appointment_id, is_read)
                VALUES
                    ('$pid', '$t', '$tp', '$m', $appt_sql, 0)";

        if (!mysqli_query($conn, $sql)) {
            error_log('[Qydentra] notify_all_patients failed for patient_id=' . $pid
                . ': ' . mysqli_error($conn));
            $all = false;
        }
    }

    return $all;
}

/**
 * Insert a notification for every receptionist (or one specific one).
 *
 * @param mysqli      $conn
 * @param string      $title
 * @param string      $message
 * @param string      $type
 * @param string|null $appointment_id        FK → appointments, nullable
 * @param string|null $only_receptionist_id  If set, notify only this staff_id (e.g. "RE001")
 * @return bool  true if all inserts succeeded
 */
function notify_receptionists(
    mysqli  $conn,
    string  $title,
    string  $message,
    string  $type                 = 'Appointment',
    ?string $appointment_id       = null,
    ?string $only_receptionist_id = null
): bool {
    $t    = mysqli_real_escape_string($conn, $title);
    $m    = mysqli_real_escape_string($conn, $message);
    $tp   = mysqli_real_escape_string($conn, $type);

    $appt_sql = ($appointment_id !== null)
        ? "'" . mysqli_real_escape_string($conn, $appointment_id) . "'"
        : 'NULL';

    if ($only_receptionist_id !== null) {
        $rid_esc     = mysqli_real_escape_string($conn, $only_receptionist_id);
        $whereClause = "WHERE staff_id = '$rid_esc' AND role = 'receptionist'";
    } else {
        $whereClause = "WHERE role = 'receptionist'";
    }

    $rr = mysqli_query($conn, "SELECT staff_id FROM staffs $whereClause");

    if (!$rr) {
        error_log('[Qydentra] notify_receptionists: staffs query failed: ' . mysqli_error($conn));
        return false;
    }
    if (mysqli_num_rows($rr) === 0) {
        error_log('[Qydentra] notify_receptionists: no receptionist accounts found.');
        return false;
    }

    $all = true;
    while ($rrow = mysqli_fetch_assoc($rr)) {
        $rid = mysqli_real_escape_string($conn, $rrow['staff_id']);

        $sql = "INSERT INTO receptionist_notifications
                    (receptionist_id, title, message, type, status, appointment_id)
                VALUES
                    ('$rid', '$t', '$m', '$tp', 'Unread', $appt_sql)";

        $ok = mysqli_query($conn, $sql);
        if (!$ok) {
            error_log('[Qydentra] notify_receptionists failed for staff_id=' . $rid
                . ': ' . mysqli_error($conn));
            $all = false;
        }
    }
    return $all;
}
