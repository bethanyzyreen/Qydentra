<?php
$allowed_roles = ['receptionist'];
include("../includes/auth_check.php");

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';

$whereSearch = "";
if(!empty($search)){
    $whereSearch = "AND (u.full_name LIKE '%$search%' OR u.email LIKE '%$search%')";
}

$patients = mysqli_query($conn,"
SELECT u.*,
    COUNT(a.appointment_id) AS total_appointments,
    SUM(CASE WHEN a.status='Completed' THEN 1 ELSE 0 END) AS completed,
    SUM(CASE WHEN a.status='Pending' THEN 1 ELSE 0 END) AS pending,
    MAX(a.appointment_date) AS last_visit
FROM patients u
LEFT JOIN appointments a ON a.patient_id = u.patient_id
WHERE u.role='patient'
$whereSearch
GROUP BY u.patient_id
ORDER BY u.full_name ASC
");
?>

<?php include("../includes/receptionist_header.php"); ?>

<body>

<?php include("../includes/receptionist_sidebar.php"); ?>

<div class="main">

<?php include("../includes/receptionist_topbar.php"); ?>

<div class="table-container hover-glow">

<div class="table-header">
<div>
<h2>Patient Records</h2>
<p>Browse and search all registered patients.</p>
</div>
</div>

<!-- SEARCH BAR -->
<div class="appointments-toolbar" style="margin-bottom:0;padding:0 0 20px 0;">
<form method="GET" action="patient_records.php" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
<input type="text" name="search"
value="<?php echo htmlspecialchars($search); ?>"
placeholder="Search patient name or email..."
class="search-box" style="width:320px;">
<button type="submit" class="table-btn">
<i class="fa-solid fa-magnifying-glass"></i> Search
</button>
<?php if(!empty($search)): ?>
<a href="patient_records.php" class="table-btn" style="background:rgba(255,255,255,0.05);">
<i class="fa-solid fa-xmark"></i> Clear
</a>
<?php endif; ?>
</form>
</div>

<table>
<thead>
<tr>
    <th>Patient</th>
    <th>Email</th>
    <th>Total Visits</th>
    <th>Completed</th>
    <th>Pending</th>
    <th>Last Visit</th>
    <th>Action</th>
</tr>
</thead>
<tbody>

<?php if(mysqli_num_rows($patients) > 0): ?>
<?php while($row = mysqli_fetch_assoc($patients)): ?>

<tr>

<td>
<div class="patient-cell">
<div class="patient-avatar">
<?php echo strtoupper(substr($row['full_name'],0,1)); ?>
</div>
<div class="patient-info">
<h4><?php echo htmlspecialchars($row['full_name']); ?></h4>
</div>
</div>
</td>

<td class="email-cell"><?php echo htmlspecialchars($row['email']); ?></td>

<td>
<div class="visits-count"><?php echo $row['total_appointments']; ?> visits</div>
</td>

<td>
<div class="status-pill completed"><?php echo $row['completed']; ?></div>
</td>

<td>
<div class="status-pill pending"><?php echo $row['pending']; ?></div>
</td>

<td>
<div class="table-date">
<i class="fa-solid fa-calendar-days"></i>
<?php echo $row['last_visit'] ? date("M d, Y", strtotime($row['last_visit'])) : 'No visits'; ?>
</div>
</td>

<td>
<button class="action-btn-sm edit"
onclick="openPatientModal_js(
    '<?php echo addslashes($row['full_name']); ?>',
    '<?php echo addslashes($row['email']); ?>',
    '<?php echo addslashes($row['patient_id']); ?>',
    <?php echo (int)$row['total_appointments']; ?>,
    <?php echo (int)$row['completed']; ?>,
    <?php echo (int)$row['pending']; ?>
)">
<i class="fa-solid fa-eye"></i>
</button>
</td>

</tr>

<?php endwhile; ?>
<?php else: ?>
<tr>
<td colspan="7" style="text-align:center;padding:30px;">No patients found.</td>
</tr>
<?php endif; ?>

</tbody>
</table>

</div>

</div>

<!-- PATIENT DETAIL MODAL -->
<div class="modal-overlay" id="patientModal">
<div class="modal-card">

<div class="modal-header">
<h3><i class="fa-solid fa-user"></i> Patient Details</h3>
<button class="modal-close" onclick="closeModal('patientModal')">
<i class="fa-solid fa-xmark"></i>
</button>
</div>

<div id="patientModalBody">

<div style="display:flex;align-items:center;gap:16px;margin-bottom:24px;">
<div class="profile-circle" style="width:60px;height:60px;font-size:22px;" id="modalInitial"></div>
<div>
<h2 id="modalName" style="font-size:18px;font-weight:600;color:white;"></h2>
<p id="modalEmail" style="color:#94a3b8;font-size:14px;"></p>
</div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:20px;">

<div style="background:rgba(96,165,250,0.08);border:1px solid rgba(96,165,250,0.15);border-radius:14px;padding:16px;text-align:center;">
<h4 style="font-size:24px;color:#60a5fa;" id="modalTotal"></h4>
<p style="color:#64748b;font-size:12px;">Total Visits</p>
</div>

<div style="background:rgba(34,197,94,0.08);border:1px solid rgba(34,197,94,0.15);border-radius:14px;padding:16px;text-align:center;">
<h4 style="font-size:24px;color:#22c55e;" id="modalCompleted"></h4>
<p style="color:#64748b;font-size:12px;">Completed</p>
</div>

<div style="background:rgba(245,158,11,0.08);border:1px solid rgba(245,158,11,0.15);border-radius:14px;padding:16px;text-align:center;">
<h4 style="font-size:24px;color:#f59e0b;" id="modalPending"></h4>
<p style="color:#64748b;font-size:12px;">Pending</p>
</div>

</div>

<a id="modalApptLink" href="#" class="primary-btn hover-glow" style="display:block;text-align:center;text-decoration:none;margin-top:4px;">
<i class="fa-solid fa-calendar-days"></i> View Appointments
</a>

</div>

</div>
</div>

<script>
function openPatientModal(name, email, id, total, completed, pending){
    document.getElementById('modalInitial').textContent = name.charAt(0).toUpperCase();
    document.getElementById('modalName').textContent = name;
    document.getElementById('modalEmail').textContent = email;
    document.getElementById('modalTotal').textContent = total;
    document.getElementById('modalCompleted').textContent = completed;
    document.getElementById('modalPending').textContent = pending;
    document.getElementById('modalApptLink').href = 'appointment_management.php?patient_id='+id;
    document.getElementById('patientModal').classList.add('active');
}
function closeModal(id){
    document.getElementById(id).classList.remove('active');
}
document.querySelectorAll('.modal-overlay').forEach(function(overlay){
    overlay.addEventListener('click', function(e){
        if(e.target === overlay) overlay.classList.remove('active');
    });
});
</script>

</body>
</html>
