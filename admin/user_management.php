<?php
$allowed_roles = ['admin'];
include_once(__DIR__ . "/../includes/auth_check.php");
require_once(__DIR__ . "/../config/database.php");
require_once(__DIR__ . "/../includes/admin_helpers.php");
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
            // Check if email already exists
            $checkEmail = mysqli_query($conn, "SELECT patient_id FROM patients WHERE email='$email' LIMIT 1");
            if (mysqli_num_rows($checkEmail) > 0) {
                $message = 'Error: That email is already registered.';
                $messageType = 'error';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $sql = "INSERT INTO patients (full_name,email,password,role,phone_number,medical_history) VALUES ('$full_name','$email','$hash','patient','$phone_number','$medical_history')";
                try {
                    if (mysqli_query($conn, $sql)) {
                        log_admin_action($conn, $_SESSION['user_id'], 'Add patient', "Added patient $email");
                        $message = 'Patient account created successfully.';
                    } else {
                        $message = 'Error: ' . mysqli_error($conn); $messageType = 'error';
                    }
                } catch (mysqli_sql_exception $e) {
                    if ($e->getCode() == 1062) {
                        $message = 'Error: That email is already registered.';
                    } else {
                        $message = 'Error: ' . $e->getMessage();
                    }
                    $messageType = 'error';
                }
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
            // Check if email already exists for another patient
            $checkEmail = mysqli_query($conn, "SELECT patient_id FROM patients WHERE email='$email' AND patient_id != '$patient_id' LIMIT 1");
            if (mysqli_num_rows($checkEmail) > 0) {
                $message = 'Error: That email is already registered to another patient.';
                $messageType = 'error';
            } else {
                $sql = "UPDATE patients SET full_name='$full_name',email='$email',phone_number='$phone_number',medical_history='$medical_history' WHERE patient_id='$patient_id'";
                try {
                    if (mysqli_query($conn, $sql)) {
                        log_admin_action($conn, $_SESSION['user_id'], 'Edit patient', "Updated patient $patient_id");
                        $message = 'Patient updated successfully.';
                    } else {
                        $message = 'Error: ' . mysqli_error($conn); $messageType = 'error';
                    }
                } catch (mysqli_sql_exception $e) {
                    if ($e->getCode() == 1062) {
                        $message = 'Error: That email is already registered to another patient.';
                    } else {
                        $message = 'Error: ' . $e->getMessage();
                    }
                    $messageType = 'error';
                }
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
<?php include(__DIR__ . "/../includes/admin_header.php"); ?>
<style>
/* Inline override to force left alignment of the Patient column */
table th:nth-child(2),
table td:nth-child(2) {
    text-align: left !important;
}
</style>
<body>
<?php include(__DIR__ . "/../includes/admin_sidebar.php"); ?>

<div class="main">
<?php include(__DIR__ . "/../includes/admin_topbar.php"); ?>

<?php if ($message !== ''): ?>
<div data-toast="<?php echo htmlspecialchars($message); ?>" data-toast-type="<?php echo $messageType; ?>"></div>
<?php endif; ?>

<!-- ADD / EDIT FORM -->
<div class="form-card hover-glow">
    <h2>
        <i class="fa-solid fa-<?php echo $editPatient ? 'pen-to-square' : 'user-plus'; ?>" style="color:#ffffff; margin-right:8px;"></i>
        <?php echo $editPatient ? 'Edit Patient' : 'Add Patient'; ?>
    </h2>
    <form method="POST" id="patient-form">
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
                <input type="text" name="phone_number" placeholder="09XX XXX XXXX" value="<?php echo htmlspecialchars($editPatient['phone_number'] ?? ''); ?>" maxlength="11">
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
            <h2><i class="fa-solid fa-users" style="color:#ffffff; margin-right:8px;"></i>Patient Accounts</h2>
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
                <th style="text-align: left !important;">Patient</th>
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
                    <td style="color:#ffffff; font-size:12px;"><?php echo htmlspecialchars($p['patient_id']); ?></td>
                    <td style="text-align: left !important;">
                        <div class="service-info">
                            <div class="service-icon consultation" style="background:rgba(96,165,250,0.10); color:#ffffff; border:1px solid rgba(96,165,250,0.18);">
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('patient-form');
    if (!form) return;

    const fullNameInput = form.querySelector('input[name="full_name"]');
    const emailInput = form.querySelector('input[name="email"]');
    const phoneInput = form.querySelector('input[name="phone_number"]');
    const passwordInput = form.querySelector('input[name="password"]');
    const medicalHistoryInput = form.querySelector('textarea[name="medical_history"]');

    function showError(input, message) {
        const group = input.closest('.form-group');
        if (!group) return;
        let errorSpan = group.querySelector('.validation-error');
        if (!errorSpan) {
            errorSpan = document.createElement('span');
            errorSpan.className = 'validation-error';
            errorSpan.style.color = '#fca5a5';
            errorSpan.style.fontSize = '12px';
            errorSpan.style.marginTop = '4px';
            errorSpan.style.display = 'block';
            group.appendChild(errorSpan);
        }
        errorSpan.textContent = message;
        errorSpan.style.display = 'block';
        input.style.borderColor = '#ef4444';
    }

    function clearError(input) {
        const group = input.closest('.form-group');
        if (!group) return;
        const errorSpan = group.querySelector('.validation-error');
        if (errorSpan) {
            errorSpan.style.display = 'none';
            errorSpan.textContent = '';
        }
        input.style.borderColor = '';
    }

    form.addEventListener('submit', function(event) {
        const actionInput = form.querySelector('input[name="action"]');
        if (!actionInput || actionInput.value !== 'add_patient') {
            return; // Validate only for Add Patient
        }

        let isValid = true;

        // 1. Full Name Validation
        const fullName = fullNameInput.value.trim();
        const nameRegex = /^[a-zA-ZñÑ\s]+$/;
        if (fullName === '') {
            showError(fullNameInput, 'Full name is required.');
            isValid = false;
        } else if (fullName.length < 2) {
            showError(fullNameInput, 'Full name must be at least 2 characters.');
            isValid = false;
        } else if (!nameRegex.test(fullName)) {
            showError(fullNameInput, "Full name must contain only letters, spaces, and 'ñ'.");
            isValid = false;
        } else {
            clearError(fullNameInput);
        }

        // 2. Email Validation
        const email = emailInput.value.trim();
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (email === '') {
            showError(emailInput, 'Email address is required.');
            isValid = false;
        } else if (!emailRegex.test(email)) {
            showError(emailInput, 'Please enter a valid email address.');
            isValid = false;
        } else {
            clearError(emailInput);
        }

        // 3. Phone Number Validation
        const phone = phoneInput.value.trim();
        if (phone !== '') {
            const phoneRegex = /^\d+$/;
            if (!phoneRegex.test(phone)) {
                showError(phoneInput, 'Phone number must contain numbers only.');
                isValid = false;
            } else if (phone.length !== 11) {
                showError(phoneInput, 'Phone number must be exactly 11 digits.');
                isValid = false;
            } else {
                clearError(phoneInput);
            }
        } else {
            clearError(phoneInput);
        }

        // 4. Password Validation
        const password = passwordInput.value;
        if (password === '') {
            showError(passwordInput, 'Password is required.');
            isValid = false;
        } else if (password.length < 8) {
            showError(passwordInput, 'Password must be at least 8 characters long.');
            isValid = false;
        } else if (!/[A-Z]/.test(password)) {
            showError(passwordInput, 'Password must contain at least one uppercase letter.');
            isValid = false;
        } else if (!/[a-z]/.test(password)) {
            showError(passwordInput, 'Password must contain at least one lowercase letter.');
            isValid = false;
        } else if (!/[0-9]/.test(password)) {
            showError(passwordInput, 'Password must contain at least one number.');
            isValid = false;
        } else {
            clearError(passwordInput);
        }

        // 5. Medical History Validation
        const medicalHistory = medicalHistoryInput.value.trim();
        if (medicalHistoryInput.hasAttribute('required') && medicalHistory === '') {
            showError(medicalHistoryInput, 'Medical History / Notes is required.');
            isValid = false;
        } else {
            clearError(medicalHistoryInput);
        }

        if (!isValid) {
            event.preventDefault();
        }
    });

    // Real-time error clearing on typing/input
    if (fullNameInput) {
        fullNameInput.addEventListener('input', function() {
            this.value = this.value.replace(/[^a-zA-ZñÑ\s]/g, '');
            clearError(fullNameInput);
        });
    }
    if (emailInput) emailInput.addEventListener('input', function() { clearError(emailInput); });
    if (phoneInput) {
        phoneInput.addEventListener('input', function() {
            this.value = this.value.replace(/\D/g, '');
            clearError(phoneInput);
        });
    }
    if (passwordInput) passwordInput.addEventListener('input', function() { clearError(passwordInput); });
    if (medicalHistoryInput) medicalHistoryInput.addEventListener('input', function() { clearError(medicalHistoryInput); });
});
</script>
</body>
</html>
