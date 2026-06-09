<?php
$allowed_roles = ['admin'];
include("../includes/auth_check.php");
require_once("../includes/admin_helpers.php");
ensure_admin_tables_exist($conn);

$message = '';
$messageType = 'success';
$editPatient = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_patient') {
        $full_name      = safe_input($conn, $_POST['full_name'] ?? '');
        $email          = safe_input($conn, $_POST['email'] ?? '');
        $password       = $_POST['password'] ?? '';
        $phone_number   = safe_input($conn, $_POST['phone_number'] ?? '');
        $medical_history= safe_input($conn, $_POST['medical_history'] ?? '');
        if ($full_name === '' || $email === '' || $password === '') {
            $message = 'Please provide name, email, and password.'; $messageType = 'error';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO patients (full_name,email,password,role,phone_number,medical_history) VALUES ('$full_name','$email','$hash','patient','$phone_number','$medical_history')";
            if (mysqli_query($conn, $sql)) {
                log_admin_action($conn, $_SESSION['user_id'], 'Add patient', "Added patient $email");
                $message = 'Patient account created successfully.';
            } else {
                $message = 'Error: ' . mysqli_error($conn); $messageType = 'error';
            }
        }
    }

    if ($action === 'edit_patient') {
        $patient_id     = safe_input($conn, $_POST['patient_id'] ?? '');
        $full_name      = safe_input($conn, $_POST['full_name'] ?? '');
        $email          = safe_input($conn, $_POST['email'] ?? '');
        $phone_number   = safe_input($conn, $_POST['phone_number'] ?? '');
        $medical_history= safe_input($conn, $_POST['medical_history'] ?? '');
        if ($patient_id === '' || $full_name === '' || $email === '') {
            $message = 'Please provide name and email.'; $messageType = 'error';
        } else {
            $sql = "UPDATE patients SET full_name='$full_name',email='$email',phone_number='$phone_number',medical_history='$medical_history' WHERE patient_id='$patient_id'";
            if (mysqli_query($conn, $sql)) {
                log_admin_action($conn, $_SESSION['user_id'], 'Edit patient', "Updated patient $patient_id");
                $message = 'Patient updated successfully.';
            } else {
                $message = 'Error: ' . mysqli_error($conn); $messageType = 'error';
            }
        }
    }

    if ($action === 'delete_patient') {
        $patient_id = safe_input($conn, $_POST['patient_id'] ?? '');
        if ($patient_id !== '') {
            mysqli_query($conn, "DELETE FROM patient_notifications WHERE patient_id='$patient_id'");
            mysqli_query($conn, "DELETE FROM appointments WHERE patient_id='$patient_id'");
            if (mysqli_query($conn, "DELETE FROM patients WHERE patient_id='$patient_id'")) {
                log_admin_action($conn, $_SESSION['user_id'], 'Delete patient', "Deleted patient $patient_id");
                $message = 'Patient deleted successfully.';
            } else {
                $message = 'Error: ' . mysqli_error($conn); $messageType = 'error';
            }
        }
    }
}

if (isset($_GET['edit'])) {
    $pid = safe_input($conn, $_GET['edit']);
    $r = mysqli_query($conn, "SELECT * FROM patients WHERE patient_id='$pid' LIMIT 1");
    $editPatient = mysqli_fetch_assoc($r);
}

$search = safe_input($conn, $_GET['search'] ?? '');
$where  = $search !== '' ? "WHERE full_name LIKE '%$search%' OR email LIKE '%$search%'" : '';
$patients = mysqli_query($conn, "SELECT * FROM patients $where ORDER BY created_at DESC");
$totalCount = mysqli_num_rows($patients);
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

<!-- ADD / EDIT FORM -->
<div class="form-card hover-glow">
    <h2>
        <i class="fa-solid fa-<?php echo $editPatient ? 'pen-to-square' : 'user-plus'; ?>" style="color:#60a5fa; margin-right:8px;"></i>
        <?php echo $editPatient ? 'Edit Patient' : 'Add Patient'; ?>
    </h2>
    <form method="POST">
        <?php if ($editPatient): ?>
            <input type="hidden" name="patient_id" value="<?php echo htmlspecialchars($editPatient['patient_id']); ?>">
            <input type="hidden" name="action" value="edit_patient">
        <?php else: ?>
            <input type="hidden" name="action" value="add_patient">
        <?php endif; ?>

        <div class="form-grid-2">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="full_name" placeholder="e.g. Maria Santos" value="<?php echo htmlspecialchars($editPatient['full_name'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" placeholder="patient@email.com" value="<?php echo htmlspecialchars($editPatient['email'] ?? ''); ?>" required>
            </div>
        </div>
        <div class="form-grid-2">
            <div class="form-group">
                <label>Phone Number</label>
                <input type="text" name="phone_number" placeholder="09XX XXX XXXX" value="<?php echo htmlspecialchars($editPatient['phone_number'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label><?php echo $editPatient ? 'New Password (leave blank to keep)' : 'Password'; ?></label>
                <input type="password" name="password" placeholder="••••••••" <?php echo $editPatient ? '' : 'required'; ?>>
            </div>
        </div>
        <div class="form-group">
            <label>Medical History / Notes</label>
            <textarea name="medical_history" placeholder="Allergies, conditions, medications..."><?php echo htmlspecialchars($editPatient['medical_history'] ?? ''); ?></textarea>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn-primary">
                <i class="fa-solid fa-<?php echo $editPatient ? 'floppy-disk' : 'plus'; ?>"></i>
                <?php echo $editPatient ? 'Save Changes' : 'Create Patient'; ?>
            </button>
            <?php if ($editPatient): ?>
                <a href="user_management.php" class="btn-secondary">
                    <i class="fa-solid fa-xmark"></i> Cancel
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- PATIENT TABLE -->
<div class="table-container hover-glow">
    <div class="table-header">
        <div>
            <h2>Patient Accounts</h2>
            <p><?php echo $totalCount; ?> patient<?php echo $totalCount != 1 ? 's' : ''; ?> registered.</p>
        </div>
        <form method="GET" style="display:flex; gap:10px; align-items:center;">
            <input type="text" name="search" placeholder="Search name or email…" value="<?php echo htmlspecialchars($search); ?>"
                style="padding:9px 14px; border-radius:12px; border:1px solid rgba(255,255,255,0.08); background:rgba(255,255,255,0.05); color:white; font-size:13px; outline:none; width:220px;">
            <button type="submit" class="table-btn"><i class="fa-solid fa-magnifying-glass"></i></button>
            <?php if ($search): ?>
                <a href="user_management.php" class="table-btn"><i class="fa-solid fa-xmark"></i></a>
            <?php endif; ?>
        </form>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Patient</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Registered</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (mysqli_num_rows($patients) > 0): ?>
                <?php while ($p = mysqli_fetch_assoc($patients)): ?>
                <tr>
                    <td style="color:#64748b; font-size:12px;"><?php echo htmlspecialchars($p['patient_id']); ?></td>
                    <td>
                        <div class="service-info">
                            <div class="service-icon consultation" style="background:rgba(96,165,250,0.10); color:#60a5fa; border:1px solid rgba(96,165,250,0.18);">
                                <i class="fa-solid fa-user"></i>
                            </div>
                            <div>
                                <h4><?php echo htmlspecialchars($p['full_name']); ?></h4>
                                <p class="role-badge patient" style="margin-top:3px;">Patient</p>
                            </div>
                        </div>
                    </td>
                    <td><?php echo htmlspecialchars($p['email']); ?></td>
                    <td><?php echo htmlspecialchars($p['phone_number'] ?: '—'); ?></td>
                    <td>
                        <div class="table-date">
                            <i class="fa-solid fa-calendar-days"></i>
                            <?php echo date('M d, Y', strtotime($p['created_at'])); ?>
                        </div>
                    </td>
                    <td>
                        <div class="action-group">
                            <a href="user_management.php?edit=<?php echo urlencode($p['patient_id']); ?>" class="action-btn-sm edit" title="Edit">
                                <i class="fa-solid fa-pen"></i>
                            </a>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this patient and all related records?');">
                                <input type="hidden" name="action" value="delete_patient">
                                <input type="hidden" name="patient_id" value="<?php echo htmlspecialchars($p['patient_id']); ?>">
                                <button type="submit" class="action-btn-sm cancel-sm" title="Delete">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="6" style="text-align:center; padding:30px; color:#9ca3af;">
                    <?php echo $search ? 'No patients match your search.' : 'No patients registered yet.'; ?>
                </td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</div>
</div>
</body>
</html>
