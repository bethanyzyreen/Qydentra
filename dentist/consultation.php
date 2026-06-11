<?php
require_once("../config/database.php");
/** @var mysqli $conn */
$allowed_roles = ['dentist'];
include("../includes/auth_check.php");

$id = isset($_GET['id']) ? mysqli_real_escape_string($conn, trim($_GET['id'])) : '';

if (empty($id)) {
    header("Location: queue.php");
    exit();
}

$apptQuery = mysqli_query($conn, "
    SELECT a.*, p.full_name AS patient_name, p.email AS patient_email, p.patient_id AS pid,
           IF(a.medical_history IS NOT NULL, a.medical_history, p.medical_history) AS medical_history, 
           IF(a.odontogram_data IS NOT NULL, a.odontogram_data, p.odontogram_data) AS odontogram_data
    FROM appointments a
    JOIN patients p ON a.patient_id = p.patient_id
    WHERE a.appointment_id = '$id'
");

if (mysqli_num_rows($apptQuery) == 0) {
    header("Location: queue.php");
    exit();
}

$appt = mysqli_fetch_assoc($apptQuery);

/* ================= SAVE TREATMENT NOTES ACTION ================= */
if (isset($_POST['action']) && $_POST['action'] == 'save_consultation') {
    $service_desc   = mysqli_real_escape_string($conn, $_POST['service_desc']);
    $dentist_notes  = mysqli_real_escape_string($conn, $_POST['dentist_notes']);
    $prescription   = mysqli_real_escape_string($conn, $_POST['prescription']);
    $status         = mysqli_real_escape_string($conn, $_POST['status']); // In Progress or Completed

    $medical_history= mysqli_real_escape_string($conn, $_POST['medical_history'] ?? '');
    $odontogram_data= mysqli_real_escape_string($conn, $_POST['odontogram_data'] ?? '{}');
    
    $pid_esc = mysqli_real_escape_string($conn, $appt['pid']);

    // Update appointment specifics, including the snapshot of the odontogram!
    mysqli_query($conn, "
        UPDATE appointments 
        SET service_desc='$service_desc', dentist_notes='$dentist_notes', prescription='$prescription', status='$status',
            medical_history='$medical_history', odontogram_data='$odontogram_data',
            dentist_id='" . mysqli_real_escape_string($conn, $_SESSION['user_id']) . "'
        WHERE appointment_id='$id'
    ");

    // Update patient general medical info (so it's the latest state globally)
    mysqli_query($conn, "
        UPDATE patients 
        SET medical_history='$medical_history', odontogram_data='$odontogram_data'
        WHERE patient_id='$pid_esc'
    ");

    if ($status == 'Completed') {
        // Send a final notification to the patient
        $pid_esc = mysqli_real_escape_string($conn, $appt['pid']);
        $msg = "Your dental appointment has been completed. Thank you for visiting!";
        mysqli_query($conn,
            "INSERT INTO patient_notifications (patient_id, title, type, message, is_read)
             VALUES ('$pid_esc', 'Consultation Finished', 'System', '$msg', 0)"
        );

        header("Location: dashboard.php?success=completed");
        exit();
    } else {
        header("Location: consultation.php?id=" . urlencode($id) . "&success=saved");
        exit();
    }
}
?>

<?php include("../includes/dentist_header.php"); ?>

<body>

<?php include("../includes/dentist_sidebar.php"); ?>

<div class="main">

<?php include("../includes/dentist_topbar.php"); ?>

<div class="appointments-toolbar">
    <a href="queue.php" class="table-btn" style="text-decoration:none;"><i class="fa-solid fa-arrow-left"></i> Back to Queue</a>
</div>

<?php if (isset($_GET['success']) && $_GET['success'] == 'saved'): ?>
<div class="alert-success" style="margin-bottom:20px;">
<i class="fa-solid fa-circle-check"></i> Treatment notes saved successfully.
</div>
<?php endif; ?>

<div style="display:grid; grid-template-columns: 1fr 2fr; gap: 24px; margin-bottom: 24px;">

<!-- PATIENT INFO CARD -->
<div class="table-container hover-glow" style="margin-bottom: 0;">
    <div class="table-header">
        <div>
            <h2><i class="fa-solid fa-user-injured" style="color:#ffffff; margin-right:8px;"></i> Patient Info</h2>
        </div>
    </div>
    <div style="padding: 20px;">
        <div style="display:flex; align-items:center; gap:16px; margin-bottom:24px;">
            <div class="profile-circle" style="width:60px; height:60px; font-size:22px; background:rgba(96,165,250,0.1); color:#60a5fa; border:1px solid rgba(96,165,250,0.3); border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:600;">
                <?php echo strtoupper(substr($appt['patient_name'], 0, 1)); ?>
            </div>
            <div>
                <h3 style="font-size:18px; margin:0 0 4px 0; color:#f8fafc;"><?php echo htmlspecialchars($appt['patient_name']); ?></h3>
                <p style="margin:0; color:#94a3b8; font-size:14px;"><?php echo htmlspecialchars($appt['patient_email']); ?></p>
            </div>
        </div>

        <?php if(!empty($appt['medical_history'])): ?>
        <div style="background:rgba(239,68,68,0.1); border:1px solid rgba(239,68,68,0.3); border-radius:8px; padding:12px; margin-bottom:16px;">
            <p style="margin:0 0 4px 0; color:#fca5a5; font-size:13px; font-weight:600;"><i class="fa-solid fa-triangle-exclamation"></i> MEDICAL ALERT</p>
            <p style="margin:0; color:#f8fafc; font-size:14px;"><strong>Conditions:</strong> <?php echo htmlspecialchars($appt['medical_history']); ?></p>
        </div>
        <?php endif; ?>

        <div style="border-top:1px solid rgba(255,255,255,0.05); padding-top:16px; margin-bottom:16px;">
            <p style="margin:0 0 8px 0; color:#64748b; font-size:12px; text-transform:uppercase; letter-spacing:0.5px;">Service Requested</p>
            <p style="margin:0; color:#e2e8f0; font-size:15px; font-weight:500;"><i class="fa-solid fa-tooth" style="color:#60a5fa; margin-right:6px;"></i> <?php echo htmlspecialchars($appt['service_type'] ?? '—'); ?></p>
        </div>

        <div style="border-top:1px solid rgba(255,255,255,0.05); padding-top:16px; margin-bottom:16px;">
            <p style="margin:0 0 8px 0; color:#64748b; font-size:12px; text-transform:uppercase; letter-spacing:0.5px;">Patient's Reason / Note</p>
            <p style="margin:0; color:#e2e8f0; font-size:14px; font-style:italic; background:rgba(255,255,255,0.02); padding:10px; border-radius:6px; border:1px solid rgba(255,255,255,0.05);">
                "<?php echo htmlspecialchars($appt['notes'] ?? 'No notes provided by patient.'); ?>"
            </p>
        </div>

        <div style="border-top:1px solid rgba(255,255,255,0.05); padding-top:16px;">
            <a href="records.php?patient_id=<?php echo urlencode($appt['pid']); ?>" target="_blank" class="table-btn" style="width:100%; text-align:center; display:inline-block; text-decoration:none;">
                <i class="fa-solid fa-folder-open"></i> View Past Records
            </a>
        </div>
    </div>
</div>

<!-- CONSULTATION FORM CARD -->
<div class="table-container hover-glow" style="margin-bottom: 0;">
    <div class="table-header">
        <div>
            <h2><i class="fa-solid fa-stethoscope" style="color:#ffffff; margin-right:8px;"></i> Treatment & Consultation Notes</h2>
        </div>
        <div class="status-pill <?php echo strtolower($appt['status']); ?>">
            <?php echo ucfirst($appt['status']); ?>
        </div>
    </div>

    <div style="padding: 20px;">
        <form method="POST">
            <input type="hidden" name="action" value="save_consultation">

            <div class="form-group" style="margin-bottom: 20px;">
                <label style="display:block; color:#94a3b8; font-size:14px; margin-bottom:8px; font-weight:500;">Exact Procedure / Service Description</label>
                <input type="text" name="service_desc" value="<?php echo htmlspecialchars($appt['service_desc'] ?? ''); ?>" placeholder="e.g. Tooth Extraction, Deep Cleaning..." class="search-box" style="width:100%; border-radius:8px; padding:12px; background:rgba(15,23,42,0.6);" <?php echo ($appt['status'] == 'Completed') ? 'readonly' : ''; ?>>
            </div>

            <div style="margin-bottom: 24px;">
                <div class="form-group">
                    <label style="display:block; color:#94a3b8; font-size:14px; margin-bottom:8px; font-weight:500;">Medical Conditions</label>
                    <textarea name="medical_history" rows="2" placeholder="e.g. Diabetic, High Blood..." class="search-box" style="width:100%; border-radius:8px; padding:12px; background:rgba(15,23,42,0.6); resize:vertical;" <?php echo ($appt['status'] == 'Completed') ? 'readonly' : ''; ?>><?php echo htmlspecialchars($appt['medical_history'] ?? ''); ?></textarea>
                </div>
            </div>

            <!-- ODONTOGRAM / TEETH CHART -->
            <div class="form-group" style="margin-bottom: 24px;">
                <label style="display:block; color:#94a3b8; font-size:14px; margin-bottom:8px; font-weight:500;">Interactive Teeth Chart (Odontogram)</label>
                <div style="background:rgba(15,23,42,0.6); padding:16px; border-radius:8px; border:1px solid rgba(255,255,255,0.05);">
                    <input type="hidden" name="odontogram_data" id="odontogram_data" value="<?php echo htmlspecialchars($appt['odontogram_data'] ?? '{}'); ?>">
                    <div id="odontogram-ui" style="display:flex; flex-direction:column; gap:12px; align-items:center;">
                        <!-- Top Teeth -->
                        <div style="display:flex; gap:4px; flex-wrap:wrap; justify-content:center;" id="teeth-top"></div>
                        <div style="width:100%; height:1px; background:rgba(255,255,255,0.1); margin:4px 0;"></div>
                        <!-- Bottom Teeth -->
                        <div style="display:flex; gap:4px; flex-wrap:wrap; justify-content:center;" id="teeth-bottom"></div>
                    </div>
                </div>
                <div id="odontogram-legend" style="margin-top:12px; display:flex; gap:12px; justify-content:center; font-size:12px;"></div>
                <p style="margin:8px 0 0 0; color:#64748b; font-size:12px; text-align:center;">Select a status from the legend, then click the teeth to apply it.</p>
            </div>
            <div class="form-group" style="margin-bottom: 24px;">
                <label style="display:block; color:#94a3b8; font-size:14px; margin-bottom:8px; font-weight:500;">Dentist's Notes & Findings</label>
                <textarea name="dentist_notes" rows="6" placeholder="Record diagnosis or specific observations here..." class="search-box" style="width:100%; border-radius:8px; padding:12px; background:rgba(15,23,42,0.6); resize:vertical;" <?php echo ($appt['status'] == 'Completed') ? 'readonly' : ''; ?>><?php echo htmlspecialchars($appt['dentist_notes'] ?? ''); ?></textarea>
            </div>

            <div class="form-group" style="margin-bottom: 24px;">
                <label style="display:block; color:#94a3b8; font-size:14px; margin-bottom:8px; font-weight:500;"><i class="fa-solid fa-prescription-bottle-medical"></i> Prescription</label>
                <textarea name="prescription" rows="4" placeholder="Write prescription here (e.g. Amoxicillin 500mg - 3x a day for 7 days)..." class="search-box" style="width:100%; border-radius:8px; padding:12px; background:rgba(15,23,42,0.6); resize:vertical;" <?php echo ($appt['status'] == 'Completed') ? 'readonly' : ''; ?>><?php echo htmlspecialchars($appt['prescription'] ?? ''); ?></textarea>
            </div>

            <?php if($appt['status'] != 'Completed'): ?>
            <div style="display:flex; gap:16px; align-items:center; border-top:1px solid rgba(255,255,255,0.05); padding-top:20px;">
                <div style="flex:1;">
                    <select name="status" class="search-box" style="width:100%; border-radius:8px; padding:12px; background:rgba(15,23,42,0.6); cursor:pointer;">
                        <option value="In Progress" <?php echo ($appt['status'] == 'In Progress') ? 'selected' : ''; ?>>Keep In Progress</option>
                        <option value="Completed">Mark as Completed</option>
                    </select>
                </div>
                <div>
                    <button type="submit" class="primary-btn hover-glow" style="padding: 12px 24px; font-size: 15px;">
                        <i class="fa-solid fa-floppy-disk"></i> Save & Update
                    </button>
                </div>
            </div>
            <?php else: ?>
            <div style="border-top:1px solid rgba(255,255,255,0.05); padding-top:20px; display:flex; justify-content:space-between; align-items:center;">
                <p style="color:#22c55e; margin:0; font-weight:500;"><i class="fa-solid fa-circle-check"></i> This consultation has been finalized and locked.</p>
                <div style="display:flex; gap:12px;">
                    <a href="print_prescription.php?id=<?php echo urlencode($id); ?>" target="_blank" class="table-btn" style="text-decoration:none;"><i class="fa-solid fa-print"></i> Print Prescription</a>
                    <a href="print_certificate.php?id=<?php echo urlencode($id); ?>" target="_blank" class="table-btn" style="text-decoration:none;"><i class="fa-solid fa-certificate"></i> Print Certificate</a>
                </div>
            </div>
            <?php endif; ?>

        </form>
    </div>
</div>

</div>

</div>

</body>
</html>

<script>
// Odontogram Logic
document.addEventListener("DOMContentLoaded", function() {
    const isCompleted = <?php echo ($appt['status'] == 'Completed') ? 'true' : 'false'; ?>;
    
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

    const statuses = ['Healthy', 'Decayed', 'Filled', 'Missing'];
    const colors = {
        'Healthy': '#e2e8f0',
        'Decayed': '#ef4444',
        'Filled': '#3b82f6',
        'Missing': '#334155'
    };

    const hiddenInput = document.getElementById('odontogram_data');
    let chartData = {};
    try { chartData = JSON.parse(hiddenInput.value) || {}; } catch(e){}

    let activeTool = 'Healthy';

    function renderLegend() {
        const legendContainer = document.getElementById("odontogram-legend");
        if(!legendContainer) return;
        legendContainer.innerHTML = "";

        statuses.forEach(status => {
            const span = document.createElement("span");
            span.style.display = "flex";
            span.style.alignItems = "center";
            span.style.gap = "6px";
            span.style.cursor = isCompleted ? "default" : "pointer";
            span.style.padding = "4px 8px";
            span.style.borderRadius = "6px";
            
            if (status === activeTool && !isCompleted) {
                span.style.background = "rgba(255,255,255,0.1)";
                span.style.color = "#f8fafc";
                span.style.border = "1px solid rgba(255,255,255,0.2)";
            } else {
                span.style.background = "transparent";
                span.style.color = "#94a3b8";
                span.style.border = "1px solid transparent";
            }

            const box = document.createElement("div");
            box.style.width = "14px";
            box.style.height = "14px";
            box.style.background = colors[status];
            box.style.borderRadius = "3px";
            
            span.appendChild(box);
            span.appendChild(document.createTextNode(status));

            if (!isCompleted) {
                span.addEventListener("click", () => {
                    activeTool = status;
                    renderLegend();
                });
            }

            legendContainer.appendChild(span);
        });
    }

    function renderTeethGroups(containerId, groups) {
        const container = document.getElementById(containerId);
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
            
            if (containerId === 'teeth-top') {
                groupWrap.appendChild(label);
            }

            const teethRow = document.createElement("div");
            teethRow.style.display = "flex";
            teethRow.style.gap = "4px";

            group.ids.forEach(id => {
                const status = chartData[id] || 'Healthy';
                const bg = colors[status];

                const tooth = document.createElement("div");
                tooth.style.width = "32px";
                tooth.style.height = "40px";
                tooth.style.background = bg;
                tooth.style.borderRadius = "4px 4px 12px 12px";
                if(status === 'Missing') tooth.style.border = "1px solid rgba(255,255,255,0.2)";
                tooth.style.display = "flex";
                tooth.style.alignItems = "center";
                tooth.style.justifyContent = "center";
                tooth.style.fontSize = "12px";
                tooth.style.color = (status === 'Healthy' || status === 'Filled') ? '#0f172a' : '#f8fafc';
                tooth.style.fontWeight = "bold";
                tooth.style.cursor = isCompleted ? "default" : "pointer";
                tooth.style.userSelect = "none";
                tooth.innerText = id;
                tooth.title = `Tooth ${id} - ${status}`;
                
                if(!isCompleted) {
                    tooth.addEventListener("click", function() {
                        chartData[id] = activeTool;
                        
                        tooth.style.background = colors[activeTool];
                        tooth.style.color = (activeTool === 'Healthy' || activeTool === 'Filled') ? '#0f172a' : '#f8fafc';
                        if(activeTool === 'Missing') tooth.style.border = "1px solid rgba(255,255,255,0.2)";
                        else tooth.style.border = "none";
                        tooth.title = `Tooth ${id} - ${activeTool}`;
                        
                        hiddenInput.value = JSON.stringify(chartData);
                    });
                }
                teethRow.appendChild(tooth);
            });

            groupWrap.appendChild(teethRow);

            if (containerId === 'teeth-bottom') {
                groupWrap.appendChild(label);
            }

            container.appendChild(groupWrap);
        });
    }

    renderLegend();
    renderTeethGroups('teeth-top', topGroups);
    renderTeethGroups('teeth-bottom', bottomGroups);
});
</script>
