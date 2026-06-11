<?php
$allowed_roles = ['admin'];
include("../includes/auth_check.php");
require_once("../includes/admin_helpers.php");
ensure_admin_tables_exist($conn);

$report_date   = safe_input($conn, $_GET['report_date'] ?? date('Y-m-d'));
$status_filter = safe_input($conn, $_GET['status'] ?? '');

$where = "WHERE a.appointment_date = '$report_date'";
if ($status_filter !== '') $where .= " AND a.status='$status_filter'";

$totals = mysqli_fetch_assoc(mysqli_query($conn, "SELECT
    COUNT(*) AS total,
    SUM(a.status='Approved')    AS approved,
    SUM(a.status='In Progress') AS in_progress,
    SUM(a.status='Completed')   AS completed,
    SUM(a.status='Pending')     AS pending,
    SUM(a.status='Cancelled')   AS cancelled
    FROM appointments a $where"));

$queueResult = mysqli_query($conn, "SELECT a.*, p.full_name AS patient_name
    FROM appointments a LEFT JOIN patients p ON a.patient_id=p.patient_id
    $where ORDER BY a.queue_number ASC, a.appointment_time ASC");

$queueRows = [];
while ($row = mysqli_fetch_assoc($queueResult)) {
    $queueRows[] = $row;
}

$exportType = $_GET['export'] ?? '';
if ($exportType === 'csv' || $exportType === 'pdf') {
    $exportRows = [];
    foreach ($queueRows as $row) {
        $exportRows[] = [
            $row['queue_number'] ?? '',
            $row['patient_name'] ?: ($row['patient_id'] ?? ''),
            $row['service_type'] ?? '',
            $row['appointment_time'] ?? '',
            $row['status'] ?? '',
            $row['notes'] ?? '',
        ];
    }

    $headers = ['Queue #', 'Patient', 'Service', 'Time', 'Status', 'Notes'];
    if ($exportType === 'csv') {
        stream_csv_download('queue_report_' . $report_date . '.csv', $headers, $exportRows);
    }

    $pdfLines = [];
    $pdfLines[] = 'Queue date: ' . $report_date;
    $pdfLines[] = 'Status filter: ' . ($status_filter ?: 'All');
    $pdfLines[] = 'Total entries: ' . (int)$totals['total'];
    $pdfLines[] = '---';
    foreach ($exportRows as $row) {
        $pdfLines[] = implode(' | ', $row);
    }
    stream_pdf_download('queue_report_' . $report_date . '.pdf', 'Queue Report', $pdfLines);
}

$exportParamsCsv = http_build_query(['report_date'=>$report_date,'status'=>$status_filter,'export'=>'csv']);
$exportParamsPdf = http_build_query(['report_date'=>$report_date,'status'=>$status_filter,'export'=>'pdf']);
?>
<?php include("../includes/admin_header.php"); ?>
<body>
<?php include("../includes/admin_sidebar.php"); ?>

<div class="main">
<?php include("../includes/admin_topbar.php"); ?>


<!-- FILTERS -->
<div class="filter-card hover-glow">
    <form method="GET">
        <div class="filter-row">
            <div>
                <label>Queue Date</label>
                <input type="date" name="report_date" value="<?php echo htmlspecialchars($report_date); ?>" required>
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
            <div style="display:flex; gap:8px; align-self:end;">
                <button type="submit" class="btn-primary" style="height:40px;">
                    <i class="fa-solid fa-users-viewfinder"></i> View Queue
                </button>
                <a href="queue_reports.php" class="btn-secondary" style="height:40px;">Today</a>
            </div>
        </div>
    </form>
</div>

<!-- STAT CARDS -->
<div class="grid-6" style="margin-bottom:28px;">
    <div class="stat-card hover-glow"><h3>Total</h3><p><?php echo (int)$totals['total']; ?></p></div>
    <div class="stat-card hover-glow"><h3>Pending</h3><p><?php echo (int)$totals['pending']; ?></p></div>
    <div class="stat-card hover-glow"><h3>Approved</h3><p><?php echo (int)$totals['approved']; ?></p></div>
    <div class="stat-card hover-glow"><h3>In Progress</h3><p><?php echo (int)$totals['in_progress']; ?></p></div>
    <div class="stat-card hover-glow"><h3>Completed</h3><p><?php echo (int)$totals['completed']; ?></p></div>
    <div class="stat-card hover-glow"><h3>Cancelled</h3><p><?php echo (int)$totals['cancelled']; ?></p></div>
</div>

<!-- QUEUE TABLE -->
<div class="table-container hover-glow">
    <div class="table-header">
        <div>
            <h2><i class="fa-solid fa-list-ol" style="color:#ffffff; margin-right:8px;"></i>Queue List</h2>
            <p><?php echo date('l, F d, Y', strtotime($report_date)); ?> — <?php echo (int)$totals['total']; ?> entrie<?php echo $totals['total'] != 1 ? 's' : ''; ?>.</p>
        </div>
        <div style="display:flex; gap:10px;">
            <a href="queue_reports.php?<?php echo $exportParamsCsv; ?>" class="export-btn csv">
                <i class="fa-solid fa-file-csv"></i> Export CSV
            </a>
            <a href="queue_reports.php?<?php echo $exportParamsPdf; ?>" class="export-btn" style="background:#f59e0b; color:#fff;">
                <i class="fa-solid fa-file-pdf"></i> Export PDF
            </a>
        </div>
    </div>

    <table id="queueTable">
        <thead>
            <tr>
                <th>Queue #</th>
                <th>Patient</th>
                <th>Service</th>
                <th>Time</th>
                <th>Status</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($queueRows) > 0): ?>
                <?php foreach ($queueRows as $row): ?>
                <tr>
                    <td>
                        <?php if (!empty($row['queue_number'])): ?>
                            <div class="queue-pill">#<?php echo htmlspecialchars($row['queue_number']); ?></div>
                        <?php else: echo '—'; endif; ?>
                    </td>
                    <td>
                        <div class="service-info">
                            <div class="service-icon consultation"><i class="fa-solid fa-user"></i></div>
                            <div>
                                <h4><?php echo htmlspecialchars($row['patient_name'] ?: $row['patient_id']); ?></h4>
                                <p style="font-size:12px; color:#64748b;"><?php echo htmlspecialchars($row['appointment_id']); ?></p>
                            </div>
                        </div>
                    </td>
                    <td><?php echo htmlspecialchars($row['service_type'] ?? '—'); ?></td>
                    <td><div class="table-date"><i class="fa-solid fa-clock"></i><?php echo date('g:i A', strtotime($row['appointment_time'])); ?></div></td>
                    <td>
                        <div class="status-pill <?php echo strtolower(str_replace(' ','-',$row['status'])); ?>">
                            <?php echo htmlspecialchars($row['status']); ?>
                        </div>
                    </td>
                    <td style="max-width:160px; font-size:13px; color:#94a3b8;"><?php echo htmlspecialchars($row['notes'] ?? '—'); ?></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="6" style="text-align:center; padding:30px; color:#9ca3af;">No queue entries for this date.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</div>
</div>

</body>
</html>
