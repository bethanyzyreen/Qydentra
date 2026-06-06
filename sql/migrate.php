<?php
/**
 * migrate.php  —  Run once to update the live qydentra database.
 * Place this file in the /sql/ folder, open it in your browser ONCE,
 * then DELETE it immediately after it reports success.
 *
 * Safe to run multiple times: every ALTER uses IF NOT EXISTS / tries
 * gracefully so it won't break an already-updated database.
 */

require_once(__DIR__ . '/../config/database.php');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$steps = [];

/* ── Helper ─────────────────────────────────────────────── */
function run(mysqli $conn, string $label, string $sql, array &$steps): void {
    try {
        mysqli_query($conn, $sql);
        $steps[] = ['ok', $label];
    } catch (mysqli_sql_exception $e) {
        // 1060 = Duplicate column, 1061 = Duplicate key  → already applied
        if (in_array($e->getCode(), [1060, 1061, 1091])) {
            $steps[] = ['skip', "$label (already applied)"];
        } else {
            $steps[] = ['err', "$label — " . $e->getMessage()];
        }
    }
}

/* ══════════════════════════════════════════════════════════
   patient_notifications  — add title, type, appointment_id
   ══════════════════════════════════════════════════════════ */
run($conn, 'patient_notifications: add title',
    "ALTER TABLE patient_notifications
     ADD COLUMN title VARCHAR(100) DEFAULT NULL AFTER patient_id",
    $steps);

run($conn, 'patient_notifications: add type',
    "ALTER TABLE patient_notifications
     ADD COLUMN type ENUM('Appointment','Queue','System') DEFAULT 'Appointment' AFTER title",
    $steps);

run($conn, 'patient_notifications: add appointment_id',
    "ALTER TABLE patient_notifications
     ADD COLUMN appointment_id INT DEFAULT NULL AFTER message",
    $steps);

run($conn, 'patient_notifications: add FK appointment_id',
    "ALTER TABLE patient_notifications
     ADD CONSTRAINT fk_pn_appointment
     FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id) ON DELETE SET NULL",
    $steps);

/* ══════════════════════════════════════════════════════════
   receptionist_notifications  — add appointment_id
   ══════════════════════════════════════════════════════════ */
run($conn, 'receptionist_notifications: add appointment_id',
    "ALTER TABLE receptionist_notifications
     ADD COLUMN appointment_id INT DEFAULT NULL AFTER status",
    $steps);

run($conn, 'receptionist_notifications: add FK appointment_id',
    "ALTER TABLE receptionist_notifications
     ADD CONSTRAINT fk_rn_appointment
     FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id) ON DELETE SET NULL",
    $steps);

/* ── Output ─────────────────────────────────────────────── */
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Qydentra Migration</title>
<style>
  body { font-family: monospace; background:#0f172a; color:#e2e8f0; padding:40px; }
  h2   { color:#38bdf8; }
  .ok   { color:#4ade80; }
  .skip { color:#facc15; }
  .err  { color:#f87171; font-weight:bold; }
  li   { margin:6px 0; font-size:15px; }
  .warn { margin-top:30px; padding:16px; background:#7f1d1d;
          border:1px solid #ef4444; border-radius:8px; color:#fca5a5; }
</style>
</head>
<body>
<h2>🗄 Qydentra Database Migration</h2>
<ul>
<?php foreach($steps as [$status, $msg]): ?>
  <li class="<?= $status ?>">
    <?= $status === 'ok' ? '✅' : ($status === 'skip' ? '⏭' : '❌') ?>
    <?= htmlspecialchars($msg) ?>
  </li>
<?php endforeach; ?>
</ul>
<?php
$errors = array_filter($steps, fn($s) => $s[0] === 'err');
if(empty($errors)):
?>
<p class="ok" style="font-size:18px;margin-top:20px;">
  ✅ Migration complete. <strong>Delete this file now.</strong>
</p>
<?php else: ?>
<p class="err" style="font-size:16px;margin-top:20px;">
  ❌ Some steps failed — check the errors above.
</p>
<?php endif; ?>
<div class="warn">
  ⚠️ <strong>Security notice:</strong> Delete <code>sql/migrate.php</code>
  immediately after this page confirms success. Never leave migration
  scripts accessible on a live server.
</div>
</body>
</html>
