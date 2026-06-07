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
 *   DE → staffs (dentist)      DE001, DE002, …
 *   AD → staffs (admin)        AD001, AD002, …
 *   AP → appointments          AP001, AP002, …
 *   PN → patient_notifications PN001, PN002, …
 *   RN → receptionist_notifications RN001, RN002, …
 */

/**
 * Returns the most recently auto-assigned prefixed ID for a table.
 *
 * For the staffs table, pass the staff's role as the second argument
 * so the correct role-specific sequence table is read:
 *   get_last_inserted_id($conn, 'staffs', 'receptionist') → RE001
 *   get_last_inserted_id($conn, 'staffs', 'dentist')      → DE001
 *   get_last_inserted_id($conn, 'staffs', 'admin')        → AD001
 *
 * @param mysqli      $conn
 * @param string      $table  One of: 'patients', 'staffs', 'appointments',
 *                                    'patient_notifications', 'receptionist_notifications'
 * @param string|null $role   Required when $table === 'staffs'.
 *                            One of: 'receptionist', 'dentist', 'admin'
 * @return string|null        e.g. "RE001", "DE002", or null on failure
 */
if (!function_exists('get_last_inserted_id')) {
function get_last_inserted_id(mysqli $conn, string $table, ?string $role = null): ?string
{
    // Non-staff tables: static prefix + single sequence table
    $static_map = [
        'patients'                   => ['_seq_patients',     'PT'],
        'appointments'               => ['_seq_appointments', 'AP'],
        'patient_notifications'      => ['_seq_pat_notif',    'PN'],
        'receptionist_notifications' => ['_seq_rec_notif',    'RN'],
    ];

    if (isset($static_map[$table])) {
        [$seq_table, $prefix] = $static_map[$table];
        $row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT last_id FROM `$seq_table` LIMIT 1"));
        if (!$row) return null;
        return $prefix . str_pad((int)$row['last_id'], 3, '0', STR_PAD_LEFT);
    }

    // staffs table: role-specific sequence + prefix
    if ($table === 'staffs') {
        $role_map = [
            'receptionist' => ['_seq_staff_re', 'RE'],
            'dentist'      => ['_seq_staff_de', 'DE'],
            'admin'        => ['_seq_staff_ad', 'AD'],
        ];

        $role_key = strtolower((string)$role);

        if (!isset($role_map[$role_key])) {
            // Fallback: query the actual last-inserted row
            $r = mysqli_fetch_assoc(mysqli_query(
                $conn,
                "SELECT staff_id FROM staffs ORDER BY created_at DESC, staff_id DESC LIMIT 1"
            ));
            return $r ? $r['staff_id'] : null;
        }

        [$seq_table, $prefix] = $role_map[$role_key];
        $row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT last_id FROM `$seq_table` LIMIT 1"));
        if (!$row) return null;
        return $prefix . str_pad((int)$row['last_id'], 3, '0', STR_PAD_LEFT);
    }

    error_log("[Qydentra] get_last_inserted_id: unknown table '$table'");
    return null;
}
}

/**
 * Formats a stored VARCHAR prefixed ID for display (internal/DB view only).
 * NOT used in the application UI — only in DB-level debug views.
 */
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

/**
 * Returns the correct staff_id prefix for a given role.
 *
 * @param  string $role  'receptionist' | 'dentist' | 'admin'
 * @return string        'RE' | 'DE' | 'AD'
 */
if (!function_exists('get_staff_prefix')) {
function get_staff_prefix(string $role): string
{
    return match(strtolower($role)) {
        'receptionist' => 'RE',
        'dentist'      => 'DE',
        'admin'        => 'AD',
        default        => 'ST',   // safety fallback
    };
}
}
?>
