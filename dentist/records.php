<?php
require_once("../config/database.php");
/** @var mysqli $conn */
$allowed_roles = ['dentist'];
include("../includes/auth_check.php");

$patient_id = isset($_GET['patient_id']) ? mysqli_real_escape_string($conn, trim($_GET['patient_id'])) : '';

if(!empty($patient_id)) {
    // SINGLE PATIENT RECORD VIEW
    $patientQuery = mysqli_query($conn, "SELECT * FROM patients WHERE patient_id='$patient_id' AND role='patient'");
    if(mysqli_num_rows($patientQuery) == 0) {
        header("Location: records.php");
        exit();
    }
    $patient = mysqli_fetch_assoc($patientQuery);

    $historyQuery = mysqli_query($conn, "
        SELECT a.*
        FROM appointments a
        WHERE a.patient_id='$patient_id' 
        ORDER BY a.appointment_date DESC, a.appointment_time DESC
    ");
?>
<?php include("../includes/dentist_header.php"); ?>
<body>
<?php include("../includes/dentist_sidebar.php"); ?>
<div class="main">
<?php include("../includes/dentist_topbar.php"); ?>

<div class="appointments-toolbar">
    <a href="records.php" class="table-btn" style="text-decoration:none;"><i class="fa-solid fa-arrow-left"></i> Back to Patients</a>
</div>

<div class="table-container hover-glow" style="margin-bottom:28px; padding:32px 36px;">
    <div style="display:flex; align-items:center; gap:24px; flex-wrap:wrap;">
        <div style="width:72px; height:72px; border-radius:50%; background:linear-gradient(135deg,#60a5fa,#3b82f6); display:flex; align-items:center; justify-content:center; font-size:28px; font-weight:700; color:#0f172a; flex-shrink:0; box-shadow:0 0 24px rgba(96,165,250,0.25);">
            <?php echo strtoupper(substr($patient['full_name'], 0, 1)); ?>
        </div>
        <div style="flex:1; min-width:200px;">
            <h2 style="font-size:22px; font-weight:700; color:#f8fafc; margin:0 0 6px 0;">
                <?php echo htmlspecialchars($patient['full_name']); ?>
            </h2>
            <div style="display:flex; flex-wrap:wrap; gap:16px; margin-top:8px;">
                <span style="display:flex; align-items:center; gap:6px; color:#94a3b8; font-size:14px;">
                    <i class="fa-solid fa-envelope" style="color:#60a5fa;"></i>
                    <?php echo htmlspecialchars($patient['email']); ?>
                </span>
                <?php if(!empty($patient['phone_number'])): ?>
                <span style="display:flex; align-items:center; gap:6px; color:#94a3b8; font-size:14px;">
                    <i class="fa-solid fa-phone" style="color:#60a5fa;"></i>
                    <?php echo htmlspecialchars($patient['phone_number']); ?>
                </span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>



<div class="table-container hover-glow">
    <div class="table-header" style="padding-bottom:20px;">
        <div>
            <h2 style="font-size:18px; font-weight:600; color:#f8fafc;"><i class="fa-solid fa-clock-rotate-left" style="color:#60a5fa; margin-right:8px;"></i>Treatment History</h2>
            <p style="color:#64748b; margin-top:4px; font-size:14px;">Past appointments and consultation notes.</p>
        </div>
    </div>
    
    <?php if(mysqli_num_rows($historyQuery) > 0): ?>
    <table>
        <thead>
            <tr>
                <th>Date & Time</th>
                <th>Service</th>
                <th>Notes & Findings</th>
                <th>Prescription</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php while($appt = mysqli_fetch_assoc($historyQuery)): ?>
            <tr>
                <td>
                    <div class="table-date" style="margin-bottom:4px;">
                        <i class="fa-solid fa-calendar-days"></i> <?php echo date("M d, Y", strtotime($appt['appointment_date'])); ?>
                    </div>
                    <div class="table-date">
                        <i class="fa-solid fa-clock"></i> <?php echo date("g:i A", strtotime($appt['appointment_time'])); ?>
                    </div>
                </td>
                <td>
                    <strong style="color:#f8fafc;"><?php echo htmlspecialchars($appt['service_type'] ?? '—'); ?></strong>
                    <?php if(!empty($appt['service_desc'])): ?>
                    <div style="font-size:12px; color:#94a3b8; margin-top:4px; max-width:200px; white-space:normal;">
                        <?php echo htmlspecialchars($appt['service_desc']); ?>
                    </div>
                    <?php endif; ?>
                </td>
                <td style="white-space:normal; vertical-align:top; min-width:200px;">
                    <?php 
                    $p_notes = trim($appt['notes'] ?? '');
                    $d_notes = trim($appt['dentist_notes'] ?? '');
                    
                    if(!empty($d_notes)) {
                        echo "<div style='margin-bottom:8px; color:#cbd5e1; font-size:13px; line-height:1.6;'>" . nl2br(htmlspecialchars($d_notes)) . "</div>";
                    }
                    if(!empty($p_notes)) {
                        echo "<div style='font-size:12px; color:#64748b; border-left:2px solid #334155; padding-left:8px; margin-top:4px;'><i>Patient:</i> " . htmlspecialchars($p_notes) . "</div>";
                    }
                    if(empty($d_notes) && empty($p_notes)) {
                        echo "<span style='color:#64748b;font-style:italic;font-size:13px;'>—</span>";
                    }
                    ?>

                    <button class="table-btn" style="margin-top:14px; font-size:12px; padding:6px 14px; background:rgba(96,165,250,0.1); color:#60a5fa;"
                        onclick="openHistoryModal(this)"
                        data-med="<?php echo htmlspecialchars($appt['medical_history'] ?? ''); ?>"
                        data-odo="<?php echo htmlspecialchars($appt['odontogram_data'] ?? '{}'); ?>"
                    >
                        <i class="fa-solid fa-tooth"></i> View Chart
                    </button>
                </td>
                <td style="white-space:normal; vertical-align:top; min-width:160px;">
                    <?php 
                    $prescription = trim($appt['prescription'] ?? '');
                    if(!empty($prescription)) {
                        echo "<div style='color:#93c5fd; font-size:13px; line-height:1.6;'>" . nl2br(htmlspecialchars($prescription)) . "</div>";
                    } else {
                        echo "<span style='color:#64748b;font-style:italic;font-size:13px;'>—</span>";
                    }
                    ?>
                </td>
                <td style="vertical-align:top;">
                    <div class="status-pill <?php echo strtolower($appt['status']); ?>">
                        <?php echo ucfirst($appt['status']); ?>
                    </div>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div class="empty-state">
        <i class="fa-solid fa-folder-open"></i>
        <h3>No History</h3>
        <p>This patient has no past appointments.</p>
    </div>
    <?php endif; ?>
</div>
</div>
</div>

<!-- HISTORY MODAL -->
<div class="modal-overlay" id="historyModal">
    <div class="modal-card" style="max-width:600px;">
        <div class="modal-header">
            <h3><i class="fa-solid fa-file-medical"></i> Appointment Chart</h3>
            <button class="modal-close" onclick="closeModal('historyModal')">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div style="padding: 20px;">
            
            <div id="histEmptyMessage" style="display:none; text-align:center; padding:30px; color:#94a3b8; font-style:italic;">
                No chart or medical information recorded for this appointment.
            </div>

            <div id="histMedicalContainer" style="display:none; margin-bottom:20px;">
                <h3 style="color:#f8fafc; font-size:16px; margin-bottom:12px;"><i class="fa-solid fa-notes-medical"></i> Medical Information</h3>
                <div style="background:rgba(168,85,247,0.1); border:1px solid rgba(168,85,247,0.3); border-radius:8px; padding:12px;">
                    <p style="margin:0; color:#f8fafc; font-size:14px;"><strong style="color:#d8b4fe;">Conditions:</strong> <span id="histMedicalText"></span></p>
                </div>
            </div>

            <div id="histOdontogramContainer">
                <h3 style="color:#f8fafc; font-size:16px; margin-bottom:12px;"><i class="fa-solid fa-tooth"></i> Odontogram</h3>
                <div style="background:rgba(15,23,42,0.6); padding:16px; border-radius:8px; border:1px solid rgba(255,255,255,0.05);">
                    <div id="odontogram-hist-ui" style="display:flex; flex-direction:column; gap:8px; align-items:center;">
                        <!-- Top Teeth -->
                        <div style="display:flex; gap:2px; flex-wrap:wrap; justify-content:center;" id="teeth-hist-top"></div>
                        <div style="width:100%; height:1px; background:rgba(255,255,255,0.1); margin:2px 0;"></div>
                        <!-- Bottom Teeth -->
                        <div style="display:flex; gap:2px; flex-wrap:wrap; justify-content:center;" id="teeth-hist-bottom"></div>
                    </div>
                </div>
                <div style="margin-top:12px; display:flex; gap:12px; justify-content:center; font-size:12px;">
                    <span style="display:flex; align-items:center; gap:4px; color:#94a3b8;"><div style="width:12px; height:12px; background:#e2e8f0; border-radius:2px;"></div> Healthy</span>
                    <span style="display:flex; align-items:center; gap:4px; color:#94a3b8;"><div style="width:12px; height:12px; background:#ef4444; border-radius:2px;"></div> Decayed</span>
                    <span style="display:flex; align-items:center; gap:4px; color:#94a3b8;"><div style="width:12px; height:12px; background:#3b82f6; border-radius:2px;"></div> Filled</span>
                    <span style="display:flex; align-items:center; gap:4px; color:#94a3b8;"><div style="width:12px; height:12px; background:#334155; border-radius:2px;"></div> Missing</span>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
const topGroups = [
    { label: "Molars", ids: [1, 2, 3] },
    { label: "Premolars", ids: [4, 5] },
    { label: "Canine", ids: [6] },
    { label: "Incisors", ids: [7, 8, 9, 10] },
    { label: "Canine", ids: [11] },
    { label: "Premolars", ids: [12, 13] },
    { label: "Molars", ids: [14, 15, 16] }
];

const bottomGroups = [
    { label: "Molars", ids: [32, 31, 30] },
    { label: "Premolars", ids: [29, 28] },
    { label: "Canine", ids: [27] },
    { label: "Incisors", ids: [26, 25, 24, 23] },
    { label: "Canine", ids: [22] },
    { label: "Premolars", ids: [21, 20] },
    { label: "Molars", ids: [19, 18, 17] }
];

const colors = {
    'Healthy': '#e2e8f0',
    'Normal': '#e2e8f0',
    'Decayed': '#ef4444',
    'Filled': '#3b82f6',
    'Missing': '#334155'
};

function renderTeethGroupsView(containerId, groups, data) {
    const container = document.getElementById(containerId);
    if(!container) return;
    container.innerHTML = "";
    
    groups.forEach(group => {
        const groupWrap = document.createElement("div");
        groupWrap.style.display = "flex";
        groupWrap.style.flexDirection = "column";
        groupWrap.style.alignItems = "center";
        groupWrap.style.gap = "4px";

        const label = document.createElement("span");
        label.innerText = group.label;
        label.style.fontSize = "10px";
        label.style.color = "#64748b";
        label.style.textTransform = "uppercase";
        label.style.letterSpacing = "0.5px";
        
        if (containerId === 'teeth-hist-top') groupWrap.appendChild(label);

        const teethRow = document.createElement("div");
        teethRow.style.display = "flex";
        teethRow.style.gap = "4px";

        group.ids.forEach(id => {
            const status = data[id] || 'Healthy';
            const bg = colors[status] || colors['Healthy'];

            const tooth = document.createElement("div");
            tooth.style.width = "24px";
            tooth.style.height = "32px";
            tooth.style.background = bg;
            tooth.style.borderRadius = "4px 4px 12px 12px";
            if(status === 'Missing') tooth.style.border = "1px solid rgba(255,255,255,0.2)";
            tooth.style.display = "flex";
            tooth.style.alignItems = "center";
            tooth.style.justifyContent = "center";
            tooth.style.fontSize = "10px";
            tooth.style.color = (status === 'Healthy' || status === 'Normal' || status === 'Filled') ? '#0f172a' : '#f8fafc';
            tooth.style.fontWeight = "bold";
            tooth.innerText = id;
            tooth.title = `Tooth ${id} - ${status}`;
            teethRow.appendChild(tooth);
        });

        groupWrap.appendChild(teethRow);

        if (containerId === 'teeth-hist-bottom') groupWrap.appendChild(label);

        container.appendChild(groupWrap);
    });
}

function openHistoryModal(btn) {
    const med = btn.getAttribute('data-med');
    const odo = btn.getAttribute('data-odo');
    
    let parsedOdo = {};
    try { parsedOdo = JSON.parse(odo); } catch(e) {}
    
    const isEmptyOdo = Object.keys(parsedOdo).length === 0;
    const isEmptyMed = (!med || med.trim() === '');
    
    const medCont = document.getElementById('histMedicalContainer');
    const odoCont = document.getElementById('histOdontogramContainer');
    const emptyMsg = document.getElementById('histEmptyMessage');
    
    if (isEmptyOdo && isEmptyMed) {
        medCont.style.display = 'none';
        odoCont.style.display = 'none';
        emptyMsg.style.display = 'block';
    } else {
        emptyMsg.style.display = 'none';
        
        if(!isEmptyMed) {
            medCont.style.display = 'block';
            document.getElementById('histMedicalText').innerText = med;
        } else {
            medCont.style.display = 'none';
        }
        
        if(!isEmptyOdo) {
            odoCont.style.display = 'block';
            renderTeethGroupsView('teeth-hist-top', topGroups, parsedOdo);
            renderTeethGroupsView('teeth-hist-bottom', bottomGroups, parsedOdo);
        } else {
            odoCont.style.display = 'none';
        }
    }
    
    document.getElementById('historyModal').classList.add('active');
}

function closeModal(id) {
    document.getElementById(id).classList.remove('active');
}
</script>
</body>
</html>
<?php 
    exit(); 
} 
?>

<?php
// FULL PATIENTS LIST VIEW
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

<?php include("../includes/dentist_header.php"); ?>

<body>

<?php include("../includes/dentist_sidebar.php"); ?>

<div class="main">

<?php include("../includes/dentist_topbar.php"); ?>

<div class="table-container hover-glow">

<div class="table-header">
<div>
<h2><i class="fa-solid fa-folder-open"></i> Patient Records</h2>
<p>Browse and search all registered patients to view treatment history.</p>
</div>
</div>

<!-- SEARCH BAR -->
<div class="appointments-toolbar" style="margin-bottom:0;padding:0 0 20px 0;">
<form method="GET" action="records.php" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
<input type="text" name="search"
value="<?php echo htmlspecialchars($search); ?>"
placeholder="Search patient name or email..."
class="search-box" style="width:320px;">
<button type="submit" class="table-btn">
<i class="fa-solid fa-magnifying-glass"></i> Search
</button>
<?php if(!empty($search)): ?>
<a href="records.php" class="table-btn" style="background:rgba(255,255,255,0.05);">
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
<button class="table-btn" style="background:rgba(96,165,250,0.1); color:#60a5fa;"
onclick="openPatientModal_js(
    '<?php echo addslashes($row['full_name']); ?>',
    '<?php echo addslashes($row['email']); ?>',
    '<?php echo addslashes($row['patient_id']); ?>',
    <?php echo (int)$row['total_appointments']; ?>,
    <?php echo (int)$row['completed']; ?>,
    <?php echo (int)$row['pending']; ?>
)">
<i class="fa-solid fa-eye"></i> View Profile
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
<h3><i class="fa-solid fa-user"></i> Patient Overview</h3>
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
<i class="fa-solid fa-file-medical"></i> View Full Treatment History
</a>

</div>

</div>
</div>

<script>
function openPatientModal_js(name, email, id, total, completed, pending){
    document.getElementById('modalInitial').textContent = name.charAt(0).toUpperCase();
    document.getElementById('modalName').textContent = name;
    document.getElementById('modalEmail').textContent = email;
    document.getElementById('modalTotal').textContent = total;
    document.getElementById('modalCompleted').textContent = completed;
    document.getElementById('modalPending').textContent = pending;
    // Link to the detailed history view (same page, passing patient_id)
    document.getElementById('modalApptLink').href = 'records.php?patient_id='+id;
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
