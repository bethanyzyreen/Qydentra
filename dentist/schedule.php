<?php
require_once("../config/database.php");
/** @var mysqli $conn */
$allowed_roles = ['dentist'];
include("../includes/auth_check.php");

$scheduleQuery = mysqli_query($conn,"
SELECT a.*, p.full_name AS patient_name, p.email AS patient_email
FROM appointments a
JOIN patients p ON a.patient_id = p.patient_id
WHERE a.appointment_date = CURDATE()
AND a.status IN ('Approved', 'In Progress')
ORDER BY 
  CASE WHEN a.status = 'In Progress' THEN 1
       WHEN a.status = 'Approved' THEN 2
       ELSE 3 END,
  a.appointment_time ASC
");
?>

<?php include("../includes/dentist_header.php"); ?>

<body>

<?php include("../includes/dentist_sidebar.php"); ?>

<div class="main">

<?php include("../includes/dentist_topbar.php"); ?>

<div class="table-container hover-glow">

<div class="table-header">
<div>
<h2><i class="fa-solid fa-calendar-day" style="color:#ffffff; margin-right:8px;"></i> Today's Schedule</h2>
<p>View all patients scheduled for today.</p>
</div>
<div class="badge-count"><?php echo mysqli_num_rows($scheduleQuery); ?> Scheduled</div>
</div>

<?php if(mysqli_num_rows($scheduleQuery) > 0): ?>

<table>
<thead>
<tr>
    <th>Queue #</th>
    <th>Time</th>
    <th>Patient</th>
    <th>Service</th>
    <th>Notes</th>
    <th>Status</th>
    <th>Action</th>
</tr>
</thead>
<tbody>

<?php while($row = mysqli_fetch_assoc($scheduleQuery)): ?>

<tr>

<td>
<div class="queue-pill" style="<?php echo ($row['status'] == 'In Progress') ? 'background:rgba(167,139,250,0.15);color:#a78bfa;border-color:rgba(167,139,250,0.3);' : ''; ?>">
<?php echo !empty($row['queue_number']) ? '#'.$row['queue_number'] : '—'; ?>
</div>
</td>

<td>
<div class="table-date">
<i class="fa-solid fa-clock"></i>
<?php echo date("g:i A", strtotime($row['appointment_time'])); ?>
</div>
</td>

<td>
<div class="service-info">
<div class="service-icon consultation">
<i class="fa-solid fa-user"></i>
</div>
<div>
<h4><?php echo htmlspecialchars($row['patient_name']); ?></h4>
<p><?php echo htmlspecialchars($row['patient_email']); ?></p>
</div>
</div>
</td>

<td><?php echo htmlspecialchars($row['service_type'] ?? '—'); ?></td>

<td><?php echo !empty($row['notes']) ? htmlspecialchars(substr($row['notes'], 0, 40)) . '...' : '—'; ?></td>

<td>
<div class="status-pill <?php echo strtolower($row['status']); ?>">
  <i class="fa-solid fa-circle-check"></i>
  <?php echo ucfirst($row['status']); ?>
</div>
</td>

<td>
<a href="queue.php" class="table-btn" style="background:rgba(59,130,246,0.12); color:#60A5FA;">
<i class="fa-solid fa-arrow-right"></i> Go to Queue
</a>
</td>

</tr>

<?php endwhile; ?>

</tbody>
</table>

<?php else: ?>

<div class="empty-state">
<i class="fa-solid fa-calendar-check"></i>
<h3>No Appointments Today</h3>
<p>You have no approved appointments scheduled for today.</p>
</div>

<?php endif; ?>

</div>

</div>

</body>
</html>
