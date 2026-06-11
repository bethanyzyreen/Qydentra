<?php
$allowed_roles = ['admin'];
include("../includes/auth_check.php");
require_once("../includes/admin_helpers.php");
ensure_admin_tables_exist($conn);

$start_date    = safe_input($conn, $_GET['start_date'] ?? date('Y-m-01'));
$end_date      = safe_input($conn, $_GET['end_date']   ?? date('Y-m-t'));
$status_filter = safe_input($conn, $_GET['status']     ?? '');
$search        = safe_input($conn, $_GET['search']     ?? '');

$where = "WHERE a.appointment_date BETWEEN '$start_date' AND '$end_date'";
if ($status_filter !== '') $where .= " AND a.status='$status_filter'";
if ($search !== '')        $where .= " AND (p.full_name LIKE '%$search%' OR a.service_type LIKE '%$search%')";

$totals = mysqli_fetch_assoc(mysqli_query($conn, "SELECT
    COUNT(*) AS total,
    SUM(a.status='Pending')     AS pending,
    SUM(a.status='Approved')    AS approved,
    SUM(a.status='In Progress') AS in_progress,
    SUM(a.status='Completed')   AS completed,
    SUM(a.status='Cancelled')   AS cancelled
    FROM appointments a LEFT JOIN patients p ON a.patient_id=p.patient_id $where"));

$appointmentResult = mysqli_query($conn, "SELECT a.*, p.full_name AS patient_name
    FROM appointments a LEFT JOIN patients p ON a.patient_id=p.patient_id
    $where ORDER BY a.appointment_date DESC, a.appointment_time DESC");

$appointments = [];
while ($row = mysqli_fetch_assoc($appointmentResult)) {
    $appointments[] = $row;
}

$exportType = $_GET['export'] ?? '';
if ($exportType === 'csv' || $exportType === 'pdf') {
    $exportRows = [];
    foreach ($appointments as $row) {
        $exportRows[] = [
            $row['appointment_id'] ?? '',
            $row['patient_name'] ?: ($row['patient_id'] ?? ''),
            $row['service_type'] ?? '',
            $row['appointment_date'] ?? '',
            $row['appointment_time'] ?? '',
            $row['status'] ?? '',
            $row['queue_number'] ?? '',
            $row['notes'] ?? '',
        ];
    }

    $headers = ['ID', 'Patient', 'Service', 'Date', 'Time', 'Status', 'Queue', 'Notes'];
    if ($exportType === 'csv') {
        stream_csv_download('appointment_report_' . date('Y-m-d') . '.csv', $headers, $exportRows);
    }

    $pdfLines = [];
    $pdfLines[] = 'Date range: ' . $start_date . ' to ' . $end_date;
    $pdfLines[] = 'Status filter: ' . ($status_filter ?: 'All');
    $pdfLines[] = 'Search: ' . ($search ?: 'All');
    $pdfLines[] = 'Total appointments: ' . (int)$totals['total'];
    $pdfLines[] = '---';
    foreach ($exportRows as $row) {
        $pdfLines[] = implode(' | ', $row);
    }
    stream_pdf_download('appointment_report_' . date('Y-m-d') . '.pdf', 'Appointment Report', $pdfLines);
}

$exportParamsCsv = http_build_query(['start_date'=>$start_date,'end_date'=>$end_date,'status'=>$status_filter,'search'=>$search,'export'=>'csv']);
$exportParamsPdf = http_build_query(['start_date'=>$start_date,'end_date'=>$end_date,'status'=>$status_filter,'search'=>$search,'export'=>'pdf']);
?>
<?php include("../includes/admin_header.php"); ?>
<body>
<?php include("../includes/admin_sidebar.php"); ?>

<div class="main">
<?php include("../includes/admin_topbar.php"); ?>


<!-- FILTERS -->
<div class="filter-card hover-glow">
    <form method="GET">
        <div class="filter-row" style="align-items:end; gap:16px;">
            <div>
                <label>From Date</label>
                <input type="date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>" required>
            </div>
            <div>
                <label>To Date</label>
                <input type="date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>" required>
            </div>
            <div>
                <label>Status</label>
                <select name="status">
                    <option value="">All Statuses</option>
                    <?php foreach (['Pending','Approved','In Progress','Completed','Cancelled'] as $s): ?>
                        <option value="<?php echo $s; ?>" <?php echo $status_filter === $s ? 'selected' : ''; ?>><?php echo $s; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Search</label>
                <input type="text" name="search" placeholder="Patient or service…" value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div style="display:flex; gap:8px; padding-top:0;">
                <button type="submit" class="btn-primary" style="height:40px;">
                    <i class="fa-solid fa-magnifying-glass"></i> Filter
                </button>
                <a href="appointment_reports.php" class="btn-secondary" style="height:40px;">Reset</a>
            </div>
        </div>
    </form>
</div>

<!-- STAT CARDS -->
<div class="grid-6" style="margin-bottom:28px;">
    <div class="stat-card hover-glow">
        <h3>Total</h3>
        <p><?php echo (int)$totals['total']; ?></p>
    </div>
    <div class="stat-card hover-glow">
        <h3>Pending</h3>
        <p><?php echo (int)$totals['pending']; ?></p>
    </div>
    <div class="stat-card hover-glow">
        <h3>Approved</h3>
        <p><?php echo (int)$totals['approved']; ?></p>
    </div>
    <div class="stat-card hover-glow">
        <h3>In Progress</h3>
        <p><?php echo (int)$totals['in_progress']; ?></p>
    </div>
    <div class="stat-card hover-glow">
        <h3>Completed</h3>
        <p><?php echo (int)$totals['completed']; ?></p>
    </div>
    <div class="stat-card hover-glow">
        <h3>Cancelled</h3>
        <p><?php echo (int)$totals['cancelled']; ?></p>
    </div>
</div>

<!-- TABLE -->
<div class="table-container hover-glow">
    <div class="table-header">
        <div>
            <h2><i class="fa-solid fa-calendar-days" style="color:#ffffff; margin-right:8px;"></i>Appointments</h2>
            <p><?php echo (int)$totals['total']; ?> result<?php echo $totals['total'] != 1 ? 's' : ''; ?> for <?php echo date('M d', strtotime($start_date)); ?> – <?php echo date('M d, Y', strtotime($end_date)); ?>.</p>
        </div>
        <div style="display:flex; gap:10px;">
            <a href="appointment_reports.php?<?php echo $exportParamsCsv; ?>" class="export-btn csv">
                <i class="fa-solid fa-file-csv"></i> Export CSV
            </a>
            <a href="appointment_reports.php?<?php echo $exportParamsPdf; ?>" class="export-btn" style="background:#f59e0b; color:#fff;">
                <i class="fa-solid fa-file-pdf"></i> Export PDF
            </a>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Patient</th>
                <th>Service</th>
                <th>Date</th>
                <th>Time</th>
                <th>Status</th>
                <th>Queue</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($appointments) > 0): ?>
                <?php foreach ($appointments as $row): ?>
                <tr>
                    <td style="color:#ffffff; font-size:12px;"><?php echo htmlspecialchars($row['appointment_id']); ?></td>
                    <td>
                        <div class="service-info">
                            <div class="service-icon consultation"><i class="fa-solid fa-user"></i></div>
                            <div><h4><?php echo htmlspecialchars($row['patient_name'] ?: $row['patient_id']); ?></h4></div>
                        </div>
                    </td>
                    <td><?php echo htmlspecialchars($row['service_type'] ?? '—'); ?></td>
                    <td><div class="table-date"><i class="fa-solid fa-calendar-days"></i><?php echo date('M d, Y', strtotime($row['appointment_date'])); ?></div></td>
                    <td><div class="table-date"><i class="fa-solid fa-clock"></i><?php echo date('g:i A', strtotime($row['appointment_time'])); ?></div></td>
                    <td>
                        <div class="status-pill <?php echo strtolower(str_replace(' ','-',$row['status'])); ?>">
                            <?php echo htmlspecialchars($row['status']); ?>
                        </div>
                    </td>
                    <td>
                        <?php if (!empty($row['queue_number'])): ?>
                            <div class="queue-pill">#<?php echo htmlspecialchars($row['queue_number']); ?></div>
                        <?php else: echo '—'; endif; ?>
                    </td>
                    <td style="max-width:180px; white-space:normal; font-size:13px; color:#94a3b8;">
                        <?php echo htmlspecialchars($row['notes'] ?? '—'); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="8" style="text-align:center; padding:30px; color:#9ca3af;">No appointments match the selected filters.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</div>
</div>

</body>
</html>
