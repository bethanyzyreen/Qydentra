<?php
$allowed_roles = ['admin'];
include("../includes/auth_check.php");
require_once("../includes/admin_helpers.php");
ensure_admin_tables_exist($conn);

$search     = safe_input($conn, $_GET['search']    ?? '');
$filter_action = safe_input($conn, $_GET['action_filter'] ?? '');
$filter_date   = safe_input($conn, $_GET['log_date']      ?? '');
$limit      = (int)($_GET['limit'] ?? 100);
$limit      = in_array($limit, [50, 100, 200, 500]) ? $limit : 100;

$where = "WHERE 1=1";
if ($search !== '')        $where .= " AND (action LIKE '%$search%' OR details LIKE '%$search%' OR admin_id LIKE '%$search%')";
if ($filter_action !== '') $where .= " AND action='$filter_action'";
if ($filter_date !== '')   $where .= " AND DATE(created_at)='$filter_date'";

$logs = mysqli_query($conn, "SELECT * FROM admin_audit_logs $where ORDER BY created_at DESC LIMIT $limit");
$totalLogs = mysqli_num_rows($logs);
mysqli_data_seek($logs, 0);

// Get distinct actions for filter
$actionTypes = mysqli_query($conn, "SELECT DISTINCT action FROM admin_audit_logs ORDER BY action ASC");

$totalAllLogs = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM admin_audit_logs"))['total'];
?>
<?php include("../includes/admin_header.php"); ?>
<body>
<?php include("../includes/admin_sidebar.php"); ?>

<div class="main">
<?php include("../includes/admin_topbar.php"); ?>



<div class="grid-4" style="margin-bottom:28px;">
    <div class="stat-card hover-glow"><h3>Total Entries</h3><p><?php echo $totalAllLogs; ?></p></div>
    <div class="stat-card hover-glow"><h3>Showing</h3><p style="color:#fbbf24;"><?php echo $totalLogs; ?></p></div>
    <div class="stat-card hover-glow"><h3>Today's Logs</h3>
        <p style="color:#ffffff;"><?php
            $todayLogs = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM admin_audit_logs WHERE DATE(created_at)=CURDATE()"))['total'];
            echo $todayLogs;
        ?></p>
    </div>
    <div class="stat-card hover-glow"><h3>Action Types</h3>
        <p style="color:#34d399;"><?php
            $actionCount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(DISTINCT action) AS total FROM admin_audit_logs"))['total'];
            echo $actionCount;
        ?></p>
    </div>
</div>

<!-- FILTERS -->
<div class="filter-card hover-glow">
    <form method="GET">
        <div class="filter-row">
            <div>
                <label>Search</label>
                <input type="text" name="search" placeholder="Action, admin ID, details…" value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div>
                <label>Action Type</label>
                <select name="action_filter">
                    <option value="">All Actions</option>
                    <?php while ($at = mysqli_fetch_assoc($actionTypes)): ?>
                        <option value="<?php echo htmlspecialchars($at['action']); ?>" <?php echo $filter_action === $at['action'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($at['action']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div>
                <label>Date</label>
                <input type="date" name="log_date" value="<?php echo htmlspecialchars($filter_date); ?>">
            </div>
            <div>
                <label>Show</label>
                <select name="limit">
                    <?php foreach ([50,100,200,500] as $lopt): ?>
                        <option value="<?php echo $lopt; ?>" <?php echo $limit === $lopt ? 'selected' : ''; ?>><?php echo $lopt; ?> entries</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display:flex; gap:8px; align-self:end;">
                <button type="submit" class="btn-primary" style="height:40px;">
                    <i class="fa-solid fa-filter"></i> Apply
                </button>
                <a href="audit_logs.php" class="btn-secondary" style="height:40px;">Clear</a>
            </div>
        </div>
    </form>
</div>

<!-- LOGS TABLE -->
<div class="table-container hover-glow">
    <div class="table-header">
        <div>
            <h2><i class="fa-solid fa-shield-halved" style="color:#ffffff; margin-right:8px;"></i>Audit History</h2>
            <p>Showing <?php echo $totalLogs; ?> of <?php echo $totalAllLogs; ?> total entries.</p>
        </div>
        <button class="export-btn csv" onclick="exportLogsCSV()">
            <i class="fa-solid fa-file-csv"></i> Export CSV
        </button>
    </div>

    <table id="logsTable">
        <thead>
            <tr>
                <th>ID</th>
                <th>Admin ID</th>
                <th>Action</th>
                <th>Details</th>
                <th>IP Address</th>
                <th>Timestamp</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($totalLogs > 0): ?>
                <?php while ($log = mysqli_fetch_assoc($logs)): ?>
                <?php
                    $actionColorMap = [
                        'Add'    => ['rgba(34,197,94,0.10)',  'rgba(34,197,94,0.22)',  '#4ade80'],
                        'Edit'   => ['rgba(96,165,250,0.10)', 'rgba(96,165,250,0.22)', '#60a5fa'],
                        'Delete' => ['rgba(239,68,68,0.10)',  'rgba(239,68,68,0.22)',  '#f87171'],
                        'Send'   => ['rgba(251,191,36,0.10)', 'rgba(251,191,36,0.22)', '#fbbf24'],
                    ];
                    $actionKey = 'Other';
                    foreach ($actionColorMap as $k => $_) {
                        if (stripos($log['action'], $k) !== false) { $actionKey = $k; break; }
                    }
                    $colors = $actionColorMap[$actionKey] ?? ['rgba(107,114,128,0.10)','rgba(107,114,128,0.22)','#9ca3af'];
                ?>
                <tr>
                    <td style="color:#ffffff; font-size:12px;"><?php echo htmlspecialchars($log['audit_id']); ?></td>
                    <td>
                        <div style="display:flex; align-items:center; gap:8px;">
                            <div style="width:28px; height:28px; border-radius:8px; background:rgba(96,165,250,0.10); border:1px solid rgba(96,165,250,0.18); display:flex; align-items:center; justify-content:center; color:#ffffff; font-size:11px; font-weight:700; flex-shrink:0;">
                                <i class="fa-solid fa-user-shield"></i>
                            </div>
                            <span style="font-size:13px;"><?php echo htmlspecialchars($log['admin_id']); ?></span>
                        </div>
                    </td>
                    <td>
                        <span style="display:inline-flex; align-items:center; padding:4px 12px; border-radius:20px; font-size:12px; font-weight:600; background:<?php echo $colors[0]; ?>; border:1px solid <?php echo $colors[1]; ?>; color:<?php echo $colors[2]; ?>;">
                            <?php echo htmlspecialchars($log['action']); ?>
                        </span>
                    </td>
                    <td style="max-width:220px; font-size:13px; color:#94a3b8;"><?php echo htmlspecialchars($log['details'] ?: '—'); ?></td>
                    <td style="font-size:12px; color:#ffffff;"><?php echo htmlspecialchars($log['ip_address'] ?: '—'); ?></td>
                    <td><div class="table-date"><i class="fa-solid fa-clock"></i><?php echo date('M d, Y g:i A', strtotime($log['created_at'])); ?></div></td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="6" style="text-align:center; padding:30px; color:#9ca3af;">No audit entries found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</div>
</div>

<script>
function exportLogsCSV() {
    const rows = document.querySelectorAll('#logsTable tr');
    let csv = [];
    rows.forEach(row => {
        const cells = [...row.querySelectorAll('th, td')].map(c => '"' + c.innerText.replace(/"/g,'""').trim() + '"');
        if (cells.length > 0) csv.push(cells.join(','));
    });
    const blob = new Blob([csv.join('\n')], { type: 'text/csv' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'audit_logs_<?php echo date("Y-m-d"); ?>.csv';
    a.click();
}
</script>

</body>
</html>
