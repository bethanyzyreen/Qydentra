<?php
$allowed_roles = ['admin'];
include("../includes/auth_check.php");
require_once("../includes/admin_helpers.php");
ensure_admin_tables_exist($conn);

$message = '';
$messageType = 'success';
$editSchedule = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action        = $_POST['action'] ?? '';
    $dentist_id    = safe_input($conn, $_POST['dentist_id']    ?? '');
    $schedule_date = safe_input($conn, $_POST['schedule_date'] ?? '');
    $start_time    = safe_input($conn, $_POST['start_time']    ?? '');
    $end_time      = safe_input($conn, $_POST['end_time']      ?? '');
    $notes         = safe_input($conn, $_POST['notes']         ?? '');

    if ($action === 'add_schedule') {
        if ($dentist_id === '' || $schedule_date === '' || $start_time === '' || $end_time === '') {
            $message = 'Please fill in all required fields.'; $messageType = 'error';
        } else {
            if (mysqli_query($conn, "INSERT INTO dentist_schedules (dentist_id,schedule_date,start_time,end_time,notes) VALUES ('$dentist_id','$schedule_date','$start_time','$end_time','$notes')")) {
                log_admin_action($conn, $_SESSION['user_id'], 'Add schedule', "Added schedule for dentist $dentist_id on $schedule_date");
                $message = 'Schedule added successfully.';
            } else {
                $message = 'Error: ' . mysqli_error($conn); $messageType = 'error';
            }
        }
    }

    if ($action === 'edit_schedule') {
        $schedule_id = safe_input($conn, $_POST['schedule_id'] ?? '');
        if ($schedule_id === '' || $dentist_id === '' || $schedule_date === '' || $start_time === '' || $end_time === '') {
            $message = 'Please fill in all required fields.'; $messageType = 'error';
        } else {
            if (mysqli_query($conn, "UPDATE dentist_schedules SET dentist_id='$dentist_id',schedule_date='$schedule_date',start_time='$start_time',end_time='$end_time',notes='$notes' WHERE schedule_id='$schedule_id'")) {
                log_admin_action($conn, $_SESSION['user_id'], 'Edit schedule', "Updated schedule $schedule_id");
                $message = 'Schedule updated successfully.';
            } else {
                $message = 'Error: ' . mysqli_error($conn); $messageType = 'error';
            }
        }
    }

    if ($action === 'delete_schedule') {
        $schedule_id = safe_input($conn, $_POST['schedule_id'] ?? '');
        if ($schedule_id !== '') {
            if (mysqli_query($conn, "DELETE FROM dentist_schedules WHERE schedule_id='$schedule_id'")) {
                log_admin_action($conn, $_SESSION['user_id'], 'Delete schedule', "Deleted schedule $schedule_id");
                $message = 'Schedule deleted successfully.';
            } else {
                $message = 'Error: ' . mysqli_error($conn); $messageType = 'error';
            }
        }
    }
}

if (isset($_GET['edit'])) {
    $sid = safe_input($conn, $_GET['edit']);
    $editSchedule = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM dentist_schedules WHERE schedule_id='$sid' LIMIT 1"));
}

$dentists  = mysqli_query($conn, "SELECT * FROM dentists ORDER BY full_name ASC");

// Filter by dentist
$filter_dentist = safe_input($conn, $_GET['dentist_id'] ?? '');
$filter_date    = safe_input($conn, $_GET['filter_date'] ?? '');
$swhere = "WHERE 1=1";
if ($filter_dentist !== '') $swhere .= " AND ds.dentist_id='$filter_dentist'";
if ($filter_date !== '')    $swhere .= " AND ds.schedule_date='$filter_date'";

$schedules = mysqli_query($conn, "SELECT ds.*, d.full_name AS dentist_name
    FROM dentist_schedules ds LEFT JOIN dentists d ON ds.dentist_id=d.dentist_id
    $swhere ORDER BY ds.schedule_date DESC, ds.start_time ASC");
$totalSchedules = mysqli_num_rows($schedules);
mysqli_data_seek($schedules, 0);
?>
<?php include("../includes/admin_header.php"); ?>
<body>
<?php include("../includes/admin_sidebar.php"); ?>

<div class="main">
<?php include("../includes/admin_topbar.php"); ?>


<?php if ($message !== ''): ?>
<div class="alert-msg <?php echo $messageType; ?>">
    <i class="fa-solid fa-<?php echo $messageType === 'success' ? 'circle-check' : 'circle-exclamation'; ?>"></i>
    <?php echo htmlspecialchars($message); ?>
</div>
<?php endif; ?>

<!-- FORM -->
<div class="form-card hover-glow">
    <h2>
        <i class="fa-solid fa-<?php echo $editSchedule ? 'pen-to-square' : 'calendar-plus'; ?>" style="color:#ffffff; margin-right:8px;"></i>
        <?php echo $editSchedule ? 'Edit Schedule' : 'Add Schedule'; ?>
    </h2>
    <form method="POST">
        <input type="hidden" name="action" value="<?php echo $editSchedule ? 'edit_schedule' : 'add_schedule'; ?>">
        <?php if ($editSchedule): ?>
            <input type="hidden" name="schedule_id" value="<?php echo htmlspecialchars($editSchedule['schedule_id']); ?>">
        <?php endif; ?>

        <div class="form-grid-2">
            <div class="form-group">
                <label>Dentist</label>
                <select name="dentist_id" required>
                    <option value="">— Select Dentist —</option>
                    <?php
                    mysqli_data_seek($dentists, 0);
                    while ($d = mysqli_fetch_assoc($dentists)):
                    ?>
                        <option value="<?php echo htmlspecialchars($d['dentist_id']); ?>"
                            <?php echo ($editSchedule['dentist_id'] ?? '') === $d['dentist_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($d['full_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Schedule Date</label>
                <input type="date" name="schedule_date" value="<?php echo htmlspecialchars($editSchedule['schedule_date'] ?? ''); ?>" required>
            </div>
        </div>
        <div class="form-grid-3">
            <div class="form-group">
                <label>Start Time</label>
                <input type="time" name="start_time" value="<?php echo htmlspecialchars($editSchedule['start_time'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label>End Time</label>
                <input type="time" name="end_time" value="<?php echo htmlspecialchars($editSchedule['end_time'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label>Notes (optional)</label>
                <input type="text" name="notes" placeholder="e.g. Half day, clinic only" value="<?php echo htmlspecialchars($editSchedule['notes'] ?? ''); ?>">
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn-primary">
                <i class="fa-solid fa-<?php echo $editSchedule ? 'floppy-disk' : 'plus'; ?>"></i>
                <?php echo $editSchedule ? 'Save Changes' : 'Add Schedule'; ?>
            </button>
            <?php if ($editSchedule): ?>
                <a href="dentist_schedule_management.php" class="btn-secondary"><i class="fa-solid fa-xmark"></i> Cancel</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- FILTER -->
<div class="filter-card hover-glow">
    <form method="GET">
        <div class="filter-row">
            <div>
                <label>Filter by Dentist</label>
                <select name="dentist_id">
                    <option value="">All Dentists</option>
                    <?php
                    mysqli_data_seek($dentists, 0);
                    while ($d = mysqli_fetch_assoc($dentists)):
                    ?>
                        <option value="<?php echo htmlspecialchars($d['dentist_id']); ?>"
                            <?php echo $filter_dentist === $d['dentist_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($d['full_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div>
                <label>Filter by Date</label>
                <input type="date" name="filter_date" value="<?php echo htmlspecialchars($filter_date); ?>">
            </div>
            <div style="display:flex; gap:8px; align-self:end;">
                <button type="submit" class="btn-primary" style="height:40px;">
                    <i class="fa-solid fa-filter"></i> Apply
                </button>
                <a href="dentist_schedule_management.php" class="btn-secondary" style="height:40px;">Clear</a>
            </div>
        </div>
    </form>
</div>

<!-- TABLE -->
<div class="table-container hover-glow">
    <div class="table-header">
        <div>
            <h2><i class="fa-solid fa-calendar-week" style="color:#ffffff; margin-right:8px;"></i>Dentist Schedules</h2>
            <p><?php echo $totalSchedules; ?> schedule<?php echo $totalSchedules != 1 ? 's' : ''; ?> found.</p>
        </div>
    </div>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Dentist</th>
                <th>Date</th>
                <th>Start</th>
                <th>End</th>
                <th>Notes</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($totalSchedules > 0): ?>
                <?php while ($sch = mysqli_fetch_assoc($schedules)): ?>
                <tr>
                    <td style="color:#ffffff; font-size:12px;"><?php echo htmlspecialchars($sch['schedule_id']); ?></td>
                    <td>
                        <div class="service-info">
                            <div class="service-icon" style="background:rgba(59,130,246,0.10); color:#ffffff; border:1px solid rgba(59,130,246,0.20);">
                                <i class="fa-solid fa-user-doctor"></i>
                            </div>
                            <div><h4><?php echo htmlspecialchars($sch['dentist_name'] ?: $sch['dentist_id']); ?></h4></div>
                        </div>
                    </td>
                    <td><div class="table-date"><i class="fa-solid fa-calendar-days"></i><?php echo date('M d, Y', strtotime($sch['schedule_date'])); ?></div></td>
                    <td><div class="table-date"><i class="fa-solid fa-play"></i><?php echo date('g:i A', strtotime($sch['start_time'])); ?></div></td>
                    <td><div class="table-date"><i class="fa-solid fa-stop"></i><?php echo date('g:i A', strtotime($sch['end_time'])); ?></div></td>
                    <td style="font-size:13px; color:#94a3b8;"><?php echo htmlspecialchars($sch['notes'] ?: '—'); ?></td>
                    <td>
                        <div class="action-group">
                            <a href="dentist_schedule_management.php?edit=<?php echo urlencode($sch['schedule_id']); ?><?php echo $filter_dentist ? '&dentist_id='.$filter_dentist : ''; ?>" class="action-btn-sm edit" title="Edit">
                                <i class="fa-solid fa-pen"></i>
                            </a>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this schedule entry?');">
                                <input type="hidden" name="action" value="delete_schedule">
                                <input type="hidden" name="schedule_id" value="<?php echo htmlspecialchars($sch['schedule_id']); ?>">
                                <button type="submit" class="action-btn-sm cancel-sm" title="Delete"><i class="fa-solid fa-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="7" style="text-align:center; padding:30px; color:#9ca3af;">No schedules found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</div>
</div>
</body>
</html>
