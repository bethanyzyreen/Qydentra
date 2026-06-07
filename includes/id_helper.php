<?php
/**
 * id_helper.php
 * Helpers for working with VARCHAR prefixed PKs in Qydentra.
 *
 * Since MySQL's LAST_INSERT_ID() returns 0 for VARCHAR PKs,
 * use get_last_inserted_id() to retrieve the most recently
 * inserted prefixed ID from the relevant sequence table.
 *
 * Prefix reference:
 *   PT → patients              PT001, PT002, …
 *   RE → staffs (receptionist) RE001, RE002, …
 *   ST → staffs (other)        ST001, ST002, …
 *   AP → appointments          AP001, AP002, …
 *   PN → patient_notifications PN001, PN002, …
 *   RN → receptionist_notifications RN001, RN002, …
 */

/**
 * Returns the most recently auto-assigned prefixed ID for a table.
 *
 * @param mysqli $conn
 * @param string $table  One of: 'patients', 'staffs', 'appointments',
 *                                'patient_notifications', 'receptionist_notifications'
 * @return string|null   e.g. "PT003", or null on failure
 */
if (!function_exists('get_last_inserted_id')) {
function get_last_inserted_id(mysqli $conn, string $table): ?string
{
    $map = [
        'patients'                      => ['_seq_patients',     'PT'],
        'staffs'                        => ['_seq_staffs',       null],  // prefix varies; use query below
        'appointments'                  => ['_seq_appointments', 'AP'],
        'patient_notifications'         => ['_seq_pat_notif',    'PN'],
        'receptionist_notifications'    => ['_seq_rec_notif',    'RN'],
    ];

    if (!isset($map[$table])) {
        error_log("[Qydentra] get_last_inserted_id: unknown table '$table'");
        return null;
    }

    [$seq_table, $prefix] = $map[$table];

    $row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT last_id FROM $seq_table LIMIT 1"));
    if (!$row) return null;

    $n = (int)$row['last_id'];

    // For staffs the prefix is role-dependent; query the actual row instead
    if ($table === 'staffs') {
        $r2 = mysqli_fetch_assoc(mysqli_query(
            $conn,
            "SELECT staff_id FROM staffs ORDER BY created_at DESC, staff_id DESC LIMIT 1"
        ));
        return $r2 ? $r2['staff_id'] : null;
    }

    return $prefix . str_pad($n, 3, '0', STR_PAD_LEFT);
}

/**
 * Formats a stored VARCHAR prefixed ID for display (internal/DB view only).
 * NOT used in the application UI — only in DB-level debug views.
 */
}

if (!function_exists('fmt_id')) {
function fmt_id(string $prefix, $raw_id): string
{
    // If already prefixed (VARCHAR), return as-is
    if (is_string($raw_id) && !is_numeric($raw_id)) {
        return $raw_id;
    }
    return $prefix . str_pad((int)$raw_id, 3, '0', STR_PAD_LEFT);
}

}
?>
