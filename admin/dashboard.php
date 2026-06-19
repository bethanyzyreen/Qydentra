<?php
$allowed_roles = ['admin'];
include_once(__DIR__ . "/../includes/auth_check.php");
require_once(__DIR__ . "/../config/database.php");
require_once(__DIR__ . "/../includes/admin_helpers.php");
ensure_admin_tables_exist($conn);

$totalPatients       = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM patients"))['total'];
$totalStaff          = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM staffs"))['total'];
$totalDentists       = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM dentists"))['total'];
$totalServices       = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM services"))['total'] ?? 0;
$pendingAppts        = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM appointments WHERE status='Pending'"))['total'];
$approvedAppts       = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM appointments WHERE status='Approved'"))['total'];
$completedToday      = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM appointments WHERE appointment_date=CURDATE() AND status='Completed'"))['total'];
$todayQueue          = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM appointments WHERE appointment_date=CURDATE() AND status IN ('Approved','In Progress')"))['total'];

$recentAppts = mysqli_query($conn, "SELECT a.*, p.full_name AS patient_name FROM appointments a LEFT JOIN patients p ON a.patient_id=p.patient_id ORDER BY a.created_at DESC");
?>
<?php include(__DIR__ . "/../includes/admin_header.php"); ?>
<style>
/* Inline override to bypass aggressive browser caching */
.card:nth-child(3)::before {
    background: #f97316 !important;
}
.card:nth-child(3) .card-icon {
    background: rgba(249, 115, 22, 0.15) !important;
    color: #fb923c !important;
    border: 1px solid rgba(251, 146, 60, 0.3) !important;
}
.card:nth-child(3) h1 {
    color: #fb923c !important;
}
</style>
<body>
<?php include(__DIR__ . "/../includes/admin_sidebar.php"); ?>

<div class="main">
<?php include(__DIR__ . "/../includes/admin_topbar.php"); ?>

<!-- STAT CARDS -->
<div class="cards" style="margin-bottom:28px;">
    <div class="card hover-glow" onclick="window.location.href='user_management.php'">
        <div class="card-top">
            <div class="card-icon"><i class="fa-solid fa-users"></i></div>
            <div class="card-badge">Patients</div>
        </div>
        <h3>Total Patients</h3>
        <h1><?php echo $totalPatients; ?></h1>
        <p>Registered patient accounts.</p>
    </div>

    <div class="card hover-glow" onclick="window.location.href='staff_dentist_management.php'">
        <div class="card-top">
            <div class="card-icon"><i class="fa-solid fa-user-doctor"></i></div>
            <div class="card-badge">Staff</div>
        </div>
        <h3>Staff & Dentists</h3>
        <h1><?php echo $totalStaff + $totalDentists; ?></h1>
        <p><?php echo $totalDentists; ?> dentist<?php echo $totalDentists != 1 ? 's' : ''; ?> + <?php echo $totalStaff; ?> staff.</p>
    </div>

    <div class="card hover-glow" onclick="window.location.href='appointment_reports.php'">
        <div class="card-top">
            <div class="card-icon"><i class="fa-solid fa-calendar-check"></i></div>
            <div class="card-badge">Appointments</div>
        </div>
        <h3>Active Appointments</h3>
        <h1><?php echo $pendingAppts + $approvedAppts; ?></h1>
        <p><?php echo $pendingAppts; ?> pending, <?php echo $approvedAppts; ?> approved.</p>
    </div>

    <div class="card hover-glow" onclick="window.location.href='queue_reports.php'">
        <div class="card-top">
            <div class="card-icon"><i class="fa-solid fa-users-viewfinder"></i></div>
            <div class="card-badge">Live Queue</div>
        </div>
        <h3>Today's Queue</h3>
        <h1><?php echo $todayQueue; ?></h1>
        <p><?php echo $completedToday; ?> completed today.</p>
    </div>
</div>

<!-- QUICK ACTIONS -->
<div class="page-header" style="margin-bottom:16px;">
    <h1 style="font-size:24px;">Quick Actions</h1>
</div>

<div class="grid-4" style="margin-bottom:32px;">
    <a href="service_management.php" class="stat-card hover-glow" style="text-decoration:none; display:block;">
        <h3><i class="fa-solid fa-briefcase-medical" style="margin-right:6px; color:#ffffff;"></i> Services</h3>
        <p><?php echo $totalServices; ?></p>
    </a>
    <a href="dentist_schedule_management.php" class="stat-card hover-glow" style="text-decoration:none; display:block;">
        <h3><i class="fa-solid fa-calendar-days" style="margin-right:6px; color:#fbbf24;"></i> Schedules</h3>
        <p style="color:#fbbf24; font-size:14px; font-weight:600; margin-top:4px;">Manage</p>
    </a>
    <a href="notifications.php" class="stat-card hover-glow" style="text-decoration:none; display:block;">
        <h3><i class="fa-solid fa-bell" style="margin-right:6px; color:#ffffff;"></i> Notifications</h3>
        <p style="color:#ffffff; font-size:14px; font-weight:600; margin-top:4px;">Broadcast</p>
    </a>
    <a href="audit_logs.php" class="stat-card hover-glow" style="text-decoration:none; display:block;">
        <h3><i class="fa-solid fa-file-lines" style="margin-right:6px; color:#34d399;"></i> Audit Logs</h3>
        <p style="color:#34d399; font-size:14px; font-weight:600; margin-top:4px;">Review</p>
    </a>
</div>

<!-- RECENT APPOINTMENTS -->
<div class="table-container hover-glow">
    <div class="table-header">
        <div>
            <h2><i class="fa-solid fa-clock-rotate-left" style="color:#ffffff; margin-right:8px;"></i>Recent Appointments</h2>
            <p>Latest activity across all appointment records.</p>
        </div>
        <a href="appointment_reports.php" class="table-btn">
            <i class="fa-solid fa-chart-line"></i>
            Full Report
        </a>
    </div>

    <table>
        <thead>
            <tr>
                <th>Patient</th>
                <th>Service</th>
                <th>Date</th>
                <th>Time</th>
                <th>Status</th>
                <th>Queue</th>
            </tr>
        </thead>
        <tbody>
            <?php if (mysqli_num_rows($recentAppts) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($recentAppts)): ?>
                <tr>
                    <td>
                        <div class="service-info">
                            <div class="service-icon consultation">
                                <i class="fa-solid fa-user"></i>
                            </div>
                            <div>
                                <h4><?php echo htmlspecialchars($row['patient_name'] ?: $row['patient_id']); ?></h4>
                                <p>Patient</p>
                            </div>
                        </div>
                    </td>
                    <td><?php echo htmlspecialchars($row['service_type'] ?? '—'); ?></td>
                    <td>
                        <div class="table-date">
                            <i class="fa-solid fa-calendar-days"></i>
                            <?php echo date('M d, Y', strtotime($row['appointment_date'])); ?>
                        </div>
                    </td>
                    <td>
                        <div class="table-date">
                            <i class="fa-solid fa-clock"></i>
                            <?php echo date('g:i A', strtotime($row['appointment_time'])); ?>
                        </div>
                    </td>
                    <td>
                        <div class="status-pill <?php echo strtolower(str_replace(' ','-',$row['status'])); ?>">
                            <i class="fa-solid fa-circle-check"></i>
                            <?php echo htmlspecialchars($row['status']); ?>
                        </div>
                    </td>
                    <td>
                        <div class="queue-pill">
                            <?php echo !empty($row['queue_number']) ? '#'.$row['queue_number'] : '—'; ?>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="6" style="text-align:center; padding:30px; color:#9ca3af;">No appointments found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</div><!-- end page-content -->
</div><!-- end main -->

</body>
</html>
