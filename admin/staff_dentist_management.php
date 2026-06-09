<?php
$allowed_roles = ['admin'];
include("../includes/auth_check.php");
require_once("../includes/admin_helpers.php");
ensure_admin_tables_exist($conn);

$message = '';
$messageType = 'success';
$activeTab = $_GET['tab'] ?? 'staff';
$allowedStaffRoles = ['receptionist', 'admin', 'dentist'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    /* ── STAFF ── */
    if ($action === 'add_staff') {
        $full_name = safe_input($conn, $_POST['full_name'] ?? '');
        $email     = safe_input($conn, $_POST['email'] ?? '');
        $password  = $_POST['password'] ?? '';
        $roleInput = safe_input($conn, $_POST['role'] ?? 'receptionist');
        $role      = in_array($roleInput, $allowedStaffRoles, true) ? $roleInput : 'receptionist';
        if ($full_name === '' || $email === '' || $password === '') {
            $message = 'Please fill in all required fields.'; $messageType = 'error';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            if (mysqli_query($conn, "INSERT INTO staffs (full_name,email,password,role) VALUES ('$full_name','$email','$hash','$role')")) {
                log_admin_action($conn, $_SESSION['user_id'], 'Add staff', "Added staff $email ($role)");
                $message = 'Staff account created successfully.';
            } else {
                $message = 'Error: ' . mysqli_error($conn); $messageType = 'error';
            }
        }
        $activeTab = 'staff';
    }

    if ($action === 'edit_staff') {
        $staff_id  = safe_input($conn, $_POST['staff_id'] ?? '');
        $full_name = safe_input($conn, $_POST['full_name'] ?? '');
        $email     = safe_input($conn, $_POST['email'] ?? '');
        $roleInput = safe_input($conn, $_POST['role'] ?? 'receptionist');
        $role      = in_array($roleInput, $allowedStaffRoles, true) ? $roleInput : 'receptionist';
        $password  = $_POST['password'] ?? '';
        $updates   = ["full_name='$full_name'", "email='$email'", "role='$role'"];
        if ($password !== '') $updates[] = "password='" . password_hash($password, PASSWORD_DEFAULT) . "'";
        if (mysqli_query($conn, "UPDATE staffs SET " . implode(',', $updates) . " WHERE staff_id='$staff_id'")) {
            log_admin_action($conn, $_SESSION['user_id'], 'Edit staff', "Updated staff $staff_id");
            $message = 'Staff updated successfully.';
        } else {
            $message = 'Error: ' . mysqli_error($conn); $messageType = 'error';
        }
        $activeTab = 'staff';
    }

    if ($action === 'delete_staff') {
        $staff_id = safe_input($conn, $_POST['staff_id'] ?? '');
        mysqli_query($conn, "DELETE FROM receptionist_notifications WHERE receptionist_id='$staff_id'");
        if (mysqli_query($conn, "DELETE FROM staffs WHERE staff_id='$staff_id'")) {
            log_admin_action($conn, $_SESSION['user_id'], 'Delete staff', "Deleted staff $staff_id");
            $message = 'Staff deleted successfully.';
        } else {
            $message = 'Error: ' . mysqli_error($conn); $messageType = 'error';
        }
        $activeTab = 'staff';
    }

    /* ── DENTISTS ── */
    if ($action === 'add_dentist') {
        $full_name     = safe_input($conn, $_POST['full_name'] ?? '');
        $email         = safe_input($conn, $_POST['email'] ?? '');
        $password      = $_POST['password'] ?? '';
        $transfer_from = safe_input($conn, $_POST['transfer_from'] ?? '');
        if ($full_name === '' || $email === '' || $password === '') {
            $message = 'Please fill in all required fields.'; $messageType = 'error';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            // dentist_id is left out — the trg_dentists_bi trigger assigns DE### automatically.
            $sql  = "INSERT INTO dentists (dentist_id,full_name,email,password,role,status) VALUES ('','$full_name','$email','$hash','dentist','active')";
            if (mysqli_query($conn, $sql)) {
                // Fetch the newly created dentist_id (VARCHAR PK set by trigger).
                $lastRow = mysqli_fetch_assoc(mysqli_query($conn, "SELECT dentist_id FROM dentists WHERE email='$email' ORDER BY created_at DESC LIMIT 1"));
                $new_did = $lastRow['dentist_id'] ?? '';

                // Transfer records from resigned dentist if specified.
                if ($transfer_from !== '' && $new_did !== '') {
                    $new_did_safe = mysqli_real_escape_string($conn, $new_did);
                    mysqli_query($conn, "UPDATE dental_records SET dentist_id='$new_did_safe' WHERE dentist_id='$transfer_from'");
                    mysqli_query($conn, "UPDATE appointments SET dentist_id='$new_did_safe' WHERE dentist_id='$transfer_from'");
                    mysqli_query($conn, "UPDATE dentist_schedules SET dentist_id='$new_did_safe' WHERE dentist_id='$transfer_from'");
                    log_admin_action($conn, $_SESSION['user_id'], 'Transfer records', "Transferred records from dentist $transfer_from to $new_did_safe");
                    $message = 'Dentist account created and records transferred successfully.';
                } else {
                    $message = 'Dentist account created successfully.';
                }
                log_admin_action($conn, $_SESSION['user_id'], 'Add dentist', "Added dentist $email ($new_did)");
            } else {
                $message = 'Error: ' . mysqli_error($conn); $messageType = 'error';
            }
        }
        $activeTab = 'dentists';
    }

    if ($action === 'edit_dentist') {
        $dentist_id = safe_input($conn, $_POST['dentist_id'] ?? '');
        $full_name  = safe_input($conn, $_POST['full_name'] ?? '');
        $email      = safe_input($conn, $_POST['email'] ?? '');
        $password   = $_POST['password'] ?? '';
        $updates    = ["full_name='$full_name'", "email='$email'"];
        if ($password !== '') $updates[] = "password='" . password_hash($password, PASSWORD_DEFAULT) . "'";
        if (mysqli_query($conn, "UPDATE dentists SET " . implode(',', $updates) . " WHERE dentist_id='$dentist_id'")) {
            log_admin_action($conn, $_SESSION['user_id'], 'Edit dentist', "Updated dentist $dentist_id");
            $message = 'Dentist updated successfully.';
        } else {
            $message = 'Error: ' . mysqli_error($conn); $messageType = 'error';
        }
        $activeTab = 'dentists';
    }

    if ($action === 'mark_resigned') {
        $dentist_id = safe_input($conn, $_POST['dentist_id'] ?? '');
        $note = safe_input($conn, $_POST['resignation_note'] ?? '');
        if (mysqli_query($conn, "UPDATE dentists SET status='inactive', resigned_at=NOW(), resignation_note='$note' WHERE dentist_id='$dentist_id'")) {
            log_admin_action($conn, $_SESSION['user_id'], 'Mark dentist resigned', "Dentist $dentist_id marked as resigned/inactive. Note: $note");
            $message = 'Dentist has been marked as resigned/inactive. Their records are preserved.';
        } else {
            $message = 'Error: ' . mysqli_error($conn); $messageType = 'error';
        }
        $activeTab = 'dentists';
    }

    if ($action === 'reactivate_dentist') {
        $dentist_id = safe_input($conn, $_POST['dentist_id'] ?? '');
        if (mysqli_query($conn, "UPDATE dentists SET status='active', resigned_at=NULL, resignation_note=NULL WHERE dentist_id='$dentist_id'")) {
            log_admin_action($conn, $_SESSION['user_id'], 'Reactivate dentist', "Dentist $dentist_id reactivated");
            $message = 'Dentist has been reactivated.';
        } else {
            $message = 'Error: ' . mysqli_error($conn); $messageType = 'error';
        }
        $activeTab = 'dentists';
    }

    if ($action === 'delete_dentist') {
        $dentist_id = safe_input($conn, $_POST['dentist_id'] ?? '');
        mysqli_query($conn, "DELETE FROM dentist_schedules WHERE dentist_id='$dentist_id'");
        if (mysqli_query($conn, "DELETE FROM dentists WHERE dentist_id='$dentist_id'")) {
            log_admin_action($conn, $_SESSION['user_id'], 'Delete dentist', "Deleted dentist $dentist_id");
            $message = 'Dentist deleted successfully.';
        } else {
            $message = 'Error: ' . mysqli_error($conn); $messageType = 'error';
        }
        $activeTab = 'dentists';
    }
}

$editStaff   = null;
$editDentist = null;
if (isset($_GET['edit_staff'])) {
    $sid = safe_input($conn, $_GET['edit_staff']);
    $editStaff = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM staffs WHERE staff_id='$sid' LIMIT 1"));
    $activeTab = 'staff';
}
if (isset($_GET['edit_dentist'])) {
    $did = safe_input($conn, $_GET['edit_dentist']);
    $editDentist = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM dentists WHERE dentist_id='$did' LIMIT 1"));
    $activeTab = 'dentists';
}

$staffList     = mysqli_query($conn, "SELECT * FROM staffs ORDER BY created_at DESC");
$dentistList   = mysqli_query($conn, "SELECT * FROM dentists ORDER BY status ASC, created_at DESC");
$inactiveDents = mysqli_query($conn, "SELECT * FROM dentists WHERE status='inactive' ORDER BY resigned_at DESC");
$totalStaff    = mysqli_num_rows($staffList);
$totalDentists = mysqli_num_rows($dentistList);
$totalInactive = mysqli_num_rows($inactiveDents);
mysqli_data_seek($staffList, 0);
mysqli_data_seek($dentistList, 0);
mysqli_data_seek($inactiveDents, 0);
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

<!-- TABS -->
<div style="display:flex; gap:10px; margin-bottom:24px; flex-wrap:wrap;">
    <a href="?tab=staff" class="table-btn <?php echo $activeTab === 'staff' ? 'active' : ''; ?>" style="<?php echo $activeTab === 'staff' ? 'background:rgba(96,165,250,0.14); border-color:rgba(96,165,250,0.30); color:#60a5fa;' : ''; ?>">
        <i class="fa-solid fa-id-badge"></i> Staff (<?php echo $totalStaff; ?>)
    </a>
    <a href="?tab=dentists" class="table-btn <?php echo $activeTab === 'dentists' ? 'active' : ''; ?>" style="<?php echo $activeTab === 'dentists' ? 'background:rgba(167,139,250,0.14); border-color:rgba(167,139,250,0.30); color:#a78bfa;' : ''; ?>">
        <i class="fa-solid fa-user-doctor"></i> Dentists (<?php echo $totalDentists; ?>)
    </a>
    <?php if ($totalInactive > 0): ?>
    <a href="?tab=resigned" class="table-btn <?php echo $activeTab === 'resigned' ? 'active' : ''; ?>" style="<?php echo $activeTab === 'resigned' ? 'background:rgba(248,113,113,0.14); border-color:rgba(248,113,113,0.30); color:#f87171;' : ''; ?>">
        <i class="fa-solid fa-user-slash"></i> Resigned (<?php echo $totalInactive; ?>)
    </a>
    <?php endif; ?>
</div>

<?php if ($activeTab === 'staff'): ?>
<!-- ── STAFF SECTION ── -->
<?php if ($editStaff): ?>
<div class="form-card hover-glow">
    <h2>
        <i class="fa-solid fa-pen-to-square" style="color:#60a5fa; margin-right:8px;"></i>
        Edit Staff Account
    </h2>
    <form method="POST">
        <input type="hidden" name="action" value="edit_staff">
        <input type="hidden" name="staff_id" value="<?php echo htmlspecialchars($editStaff['staff_id']); ?>">
        <div class="form-grid-3">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="full_name" placeholder="Staff full name" value="<?php echo htmlspecialchars($editStaff['full_name'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" placeholder="staff@qydentra.com" value="<?php echo htmlspecialchars($editStaff['email'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label>Role</label>
                <select name="role" required>
                    <option value="receptionist" <?php echo ($editStaff['role'] ?? '') === 'receptionist' ? 'selected' : ''; ?>>Receptionist</option>
                    <option value="admin" <?php echo ($editStaff['role'] ?? '') === 'admin' ? 'selected' : ''; ?>>Admin</option>
                    <option value="dentist" <?php echo ($editStaff['role'] ?? '') === 'dentist' ? 'selected' : ''; ?>>Dentist</option>
                </select>
            </div>
        </div>
        <div class="form-grid-2" style="max-width:520px;">
            <div class="form-group">
                <label>New Password <span style="color:#64748b; font-weight:400;">(leave blank to keep current)</span></label>
                <input type="password" name="password" placeholder="••••••••">
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn-primary">
                <i class="fa-solid fa-floppy-disk"></i> Save Changes
            </button>
            <a href="?tab=staff" class="btn-secondary"><i class="fa-solid fa-xmark"></i> Cancel</a>
        </div>
    </form>
</div>
<?php endif; ?>

<div class="table-container hover-glow">
    <div class="table-header">
        <div><h2>Staff Accounts</h2><p><?php echo $totalStaff; ?> staff account<?php echo $totalStaff != 1 ? 's' : ''; ?>.</p></div>
    </div>
    <table>
        <thead>
            <tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Created</th><th>Actions</th></tr>
        </thead>
        <tbody>
            <?php if ($totalStaff > 0): ?>
                <?php while ($s = mysqli_fetch_assoc($staffList)): ?>
                <tr>
                    <td style="color:#64748b; font-size:12px;"><?php echo htmlspecialchars($s['staff_id']); ?></td>
                    <td>
                        <div class="service-info">
                            <div class="service-icon" style="background:rgba(96,165,250,0.10); color:#60a5fa; border:1px solid rgba(96,165,250,0.18);">
                                <i class="fa-solid fa-id-badge"></i>
                            </div>
                            <div><h4><?php echo htmlspecialchars($s['full_name']); ?></h4></div>
                        </div>
                    </td>
                    <td><?php echo htmlspecialchars($s['email']); ?></td>
                    <td>
                        <span class="role-badge <?php echo $s['role']; ?>">
                            <?php echo ucfirst($s['role']); ?>
                        </span>
                    </td>
                    <td><div class="table-date"><i class="fa-solid fa-calendar-days"></i><?php echo date('M d, Y', strtotime($s['created_at'])); ?></div></td>
                    <td>
                        <div class="action-group">
                            <a href="?tab=staff&edit_staff=<?php echo urlencode($s['staff_id']); ?>" class="action-btn-sm edit" title="Edit"><i class="fa-solid fa-pen"></i></a>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this staff account?');">
                                <input type="hidden" name="action" value="delete_staff">
                                <input type="hidden" name="staff_id" value="<?php echo htmlspecialchars($s['staff_id']); ?>">
                                <button type="submit" class="action-btn-sm cancel-sm" title="Delete"><i class="fa-solid fa-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="6" style="text-align:center; padding:30px; color:#9ca3af;">No staff accounts found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php elseif ($activeTab === 'dentists'): ?>
<!-- ── DENTIST SECTION ── -->
<div class="form-card hover-glow">
    <h2>
        <i class="fa-solid fa-<?php echo $editDentist ? 'pen-to-square' : 'user-plus'; ?>" style="color:#a78bfa; margin-right:8px;"></i>
        <?php echo $editDentist ? 'Edit Dentist Account' : 'Add Dentist Account'; ?>
    </h2>
    <form method="POST">
        <input type="hidden" name="action" value="<?php echo $editDentist ? 'edit_dentist' : 'add_dentist'; ?>">
        <?php if ($editDentist): ?>
            <input type="hidden" name="dentist_id" value="<?php echo htmlspecialchars($editDentist['dentist_id']); ?>">
        <?php endif; ?>
        <div class="form-grid-2">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="full_name" placeholder="Dr. Full Name" value="<?php echo htmlspecialchars($editDentist['full_name'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" placeholder="dentist@qydentra.com" value="<?php echo htmlspecialchars($editDentist['email'] ?? ''); ?>" required>
            </div>
        </div>
        <div class="form-grid-2" style="max-width:520px;">
            <div class="form-group">
                <label><?php echo $editDentist ? 'New Password (blank to keep)' : 'Password'; ?></label>
                <input type="password" name="password" placeholder="••••••••" <?php echo $editDentist ? '' : 'required'; ?>>
            </div>
        </div>
        <?php if (!$editDentist): ?>
        <!-- Transfer Records: only show when adding a new dentist and there are resigned dentists -->
        <?php
            $resignedOpts = mysqli_query($conn, "SELECT dentist_id, full_name, resigned_at FROM dentists WHERE status='inactive' ORDER BY resigned_at DESC");
            $resignedCount = mysqli_num_rows($resignedOpts);
            mysqli_data_seek($resignedOpts, 0);
        ?>
        <?php if ($resignedCount > 0): ?>
        <div class="form-group" style="margin-top:4px;">
            <label style="display:flex; align-items:center; gap:6px; color:#fbbf24; margin-bottom:8px;">
                <i class="fa-solid fa-arrow-right-arrow-left" style="font-size:13px;"></i>
                Transfer Records from Resigned Dentist <span style="font-weight:400; color:#9ca3af; font-size:12px;">(optional)</span>
            </label>
            <select name="transfer_from" style="max-width:360px;">
                <option value="">— No transfer, start fresh —</option>
                <?php while ($ro = mysqli_fetch_assoc($resignedOpts)): ?>
                    <option value="<?php echo htmlspecialchars($ro['dentist_id']); ?>">
                        <?php echo htmlspecialchars($ro['full_name']); ?> (resigned <?php echo $ro['resigned_at'] ? date('M d, Y', strtotime($ro['resigned_at'])) : 'n/a'; ?>)
                    </option>
                <?php endwhile; ?>
            </select>
            <small style="display:block; margin-top:6px; color:#64748b; font-size:12px;"><i class="fa-solid fa-circle-info" style="margin-right:4px;"></i>Appointments, dental records and schedules will be reassigned to this new dentist. The resigned dentist account is preserved.</small>
        </div>
        <?php endif; ?>
        <?php endif; ?>
        <div class="form-actions">
            <button type="submit" class="btn-primary" style="background:linear-gradient(135deg,#a78bfa,#7c3aed);">
                <i class="fa-solid fa-<?php echo $editDentist ? 'floppy-disk' : 'plus'; ?>"></i>
                <?php echo $editDentist ? 'Save Changes' : 'Create Dentist'; ?>
            </button>
            <?php if ($editDentist): ?>
                <a href="?tab=dentists" class="btn-secondary"><i class="fa-solid fa-xmark"></i> Cancel</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Resign Modal -->
<div id="resignModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); z-index:9999; align-items:center; justify-content:center;">
    <div style="background:#1e293b; border:1px solid rgba(248,113,113,0.25); border-radius:16px; padding:28px 32px; max-width:440px; width:90%; box-shadow:0 24px 60px rgba(0,0,0,0.5);">
        <div style="display:flex; align-items:center; gap:12px; margin-bottom:20px;">
            <div style="width:40px; height:40px; border-radius:10px; background:rgba(248,113,113,0.12); border:1px solid rgba(248,113,113,0.25); display:flex; align-items:center; justify-content:center; color:#f87171; flex-shrink:0;">
                <i class="fa-solid fa-user-slash"></i>
            </div>
            <div>
                <h3 style="margin:0; font-size:16px; color:#f1f5f9;">Mark as Resigned / Inactive</h3>
                <p style="margin:2px 0 0; font-size:12px; color:#64748b;">The dentist account and all records will be preserved.</p>
            </div>
        </div>
        <form method="POST" id="resignForm">
            <input type="hidden" name="action" value="mark_resigned">
            <input type="hidden" name="dentist_id" id="resignDentistId">
            <div class="form-group" style="margin-bottom:20px;">
                <label style="font-size:13px; color:#94a3b8; margin-bottom:6px; display:block;">Reason / Note <span style="color:#64748b;">(optional)</span></label>
                <input type="text" name="resignation_note" id="resignNote" placeholder="e.g. Resigned effective June 2026" style="width:100%; background:#0f172a; border:1px solid rgba(248,113,113,0.2); border-radius:8px; padding:10px 12px; color:#f1f5f9; font-size:14px; outline:none; box-sizing:border-box;">
            </div>
            <div style="display:flex; gap:10px;">
                <button type="submit" style="flex:1; background:linear-gradient(135deg,#f87171,#dc2626); color:#fff; border:none; border-radius:8px; padding:10px 16px; font-size:14px; font-weight:600; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:6px;">
                    <i class="fa-solid fa-user-slash"></i> Confirm Resignation
                </button>
                <button type="button" onclick="closeResignModal()" style="flex:0 0 auto; background:transparent; border:1px solid rgba(100,116,139,0.3); color:#94a3b8; border-radius:8px; padding:10px 16px; font-size:14px; cursor:pointer;">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<div class="table-container hover-glow">
    <div class="table-header">
        <div><h2>Dentist Accounts</h2><p><?php echo $totalDentists; ?> dentist<?php echo $totalDentists != 1 ? 's' : ''; ?> on record.</p></div>
    </div>
    <table>
        <thead>
            <tr><th>ID</th><th>Name</th><th>Email</th><th>Status</th><th>Since</th><th>Actions</th></tr>
        </thead>
        <tbody>
            <?php if ($totalDentists > 0): ?>
                <?php while ($d = mysqli_fetch_assoc($dentistList)): ?>
                <?php $isInactive = ($d['status'] ?? 'active') === 'inactive'; ?>
                <tr style="<?php echo $isInactive ? 'opacity:0.6;' : ''; ?>">
                    <td style="color:#64748b; font-size:12px;"><?php echo htmlspecialchars($d['dentist_id']); ?></td>
                    <td>
                        <div class="service-info">
                            <div class="service-icon" style="background:<?php echo $isInactive ? 'rgba(248,113,113,0.10)' : 'rgba(167,139,250,0.10)'; ?>; color:<?php echo $isInactive ? '#f87171' : '#a78bfa'; ?>; border:1px solid <?php echo $isInactive ? 'rgba(248,113,113,0.18)' : 'rgba(167,139,250,0.18)'; ?>;">
                                <i class="fa-solid fa-<?php echo $isInactive ? 'user-slash' : 'user-doctor'; ?>"></i>
                            </div>
                            <div>
                                <h4><?php echo htmlspecialchars($d['full_name']); ?></h4>
                                <?php if ($isInactive && $d['resignation_note']): ?>
                                    <small style="color:#64748b; font-size:11px;"><i class="fa-solid fa-note-sticky" style="margin-right:3px;"></i><?php echo htmlspecialchars($d['resignation_note']); ?></small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td style="color:#94a3b8; font-size:13px;"><?php echo htmlspecialchars($d['email']); ?></td>
                    <td>
                        <?php if ($isInactive): ?>
                            <span style="display:inline-flex; align-items:center; gap:5px; background:rgba(248,113,113,0.10); border:1px solid rgba(248,113,113,0.20); color:#f87171; border-radius:20px; padding:3px 10px; font-size:12px; font-weight:600;">
                                <i class="fa-solid fa-circle" style="font-size:6px;"></i> Resigned
                            </span>
                        <?php else: ?>
                            <span style="display:inline-flex; align-items:center; gap:5px; background:rgba(74,222,128,0.10); border:1px solid rgba(74,222,128,0.20); color:#4ade80; border-radius:20px; padding:3px 10px; font-size:12px; font-weight:600;">
                                <i class="fa-solid fa-circle" style="font-size:6px;"></i> Active
                            </span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="table-date">
                            <i class="fa-solid fa-calendar-days"></i>
                            <?php if ($isInactive && $d['resigned_at']): ?>
                                Resigned <?php echo date('M d, Y', strtotime($d['resigned_at'])); ?>
                            <?php else: ?>
                                <?php echo date('M d, Y', strtotime($d['created_at'])); ?>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td>
                        <div class="action-group">
                            <?php if (!$isInactive): ?>
                                <a href="?tab=dentists&edit_dentist=<?php echo urlencode($d['dentist_id']); ?>" class="action-btn-sm edit" title="Edit"><i class="fa-solid fa-pen"></i></a>
                                <button type="button" class="action-btn-sm" title="Mark as Resigned"
                                    onclick="openResignModal('<?php echo addslashes($d['dentist_id']); ?>', '<?php echo addslashes($d['full_name']); ?>')"
                                    style="background:rgba(248,113,113,0.08); border:1px solid rgba(248,113,113,0.20); color:#f87171;">
                                    <i class="fa-solid fa-user-slash"></i>
                                </button>
                            <?php else: ?>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Reactivate this dentist account?');">
                                    <input type="hidden" name="action" value="reactivate_dentist">
                                    <input type="hidden" name="dentist_id" value="<?php echo htmlspecialchars($d['dentist_id']); ?>">
                                    <button type="submit" class="action-btn-sm" title="Reactivate" style="background:rgba(74,222,128,0.08); border:1px solid rgba(74,222,128,0.20); color:#4ade80;"><i class="fa-solid fa-rotate-left"></i></button>
                                </form>
                            <?php endif; ?>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Permanently delete this dentist and their schedules? This cannot be undone.');">
                                <input type="hidden" name="action" value="delete_dentist">
                                <input type="hidden" name="dentist_id" value="<?php echo htmlspecialchars($d['dentist_id']); ?>">
                                <button type="submit" class="action-btn-sm cancel-sm" title="Delete"><i class="fa-solid fa-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="6" style="text-align:center; padding:30px; color:#9ca3af;">No dentists found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php elseif ($activeTab === 'resigned'): ?>
<!-- ── RESIGNED DENTISTS ── -->
<div class="table-container hover-glow">
    <div class="table-header">
        <div>
            <h2 style="color:#f87171;"><i class="fa-solid fa-user-slash" style="margin-right:8px;"></i>Resigned Dentists</h2>
            <p>These accounts are inactive. All records tied to them are preserved.</p>
        </div>
    </div>
    <table>
        <thead>
            <tr><th>ID</th><th>Name</th><th>Email</th><th>Resigned On</th><th>Note</th><th>Actions</th></tr>
        </thead>
        <tbody>
            <?php if ($totalInactive > 0): ?>
                <?php while ($d = mysqli_fetch_assoc($inactiveDents)): ?>
                <tr style="opacity:0.75;">
                    <td style="color:#64748b; font-size:12px;"><?php echo htmlspecialchars($d['dentist_id']); ?></td>
                    <td>
                        <div class="service-info">
                            <div class="service-icon" style="background:rgba(248,113,113,0.10); color:#f87171; border:1px solid rgba(248,113,113,0.18);">
                                <i class="fa-solid fa-user-slash"></i>
                            </div>
                            <div><h4><?php echo htmlspecialchars($d['full_name']); ?></h4></div>
                        </div>
                    </td>
                    <td style="color:#94a3b8; font-size:13px;"><?php echo htmlspecialchars($d['email']); ?></td>
                    <td><div class="table-date"><i class="fa-solid fa-calendar-days"></i><?php echo $d['resigned_at'] ? date('M d, Y', strtotime($d['resigned_at'])) : '—'; ?></div></td>
                    <td style="font-size:13px; color:#64748b; max-width:180px;"><?php echo htmlspecialchars($d['resignation_note'] ?: '—'); ?></td>
                    <td>
                        <div class="action-group">
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Reactivate this dentist account?');">
                                <input type="hidden" name="action" value="reactivate_dentist">
                                <input type="hidden" name="dentist_id" value="<?php echo htmlspecialchars($d['dentist_id']); ?>">
                                <button type="submit" class="action-btn-sm" title="Reactivate" style="background:rgba(74,222,128,0.08); border:1px solid rgba(74,222,128,0.20); color:#4ade80;"><i class="fa-solid fa-rotate-left"></i></button>
                            </form>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Permanently delete this resigned dentist account?');">
                                <input type="hidden" name="action" value="delete_dentist">
                                <input type="hidden" name="dentist_id" value="<?php echo htmlspecialchars($d['dentist_id']); ?>">
                                <button type="submit" class="action-btn-sm cancel-sm" title="Delete"><i class="fa-solid fa-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="6" style="text-align:center; padding:30px; color:#9ca3af;">No resigned dentists.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

</div>
</div>

<script>
function openResignModal(dentistId, name) {
    document.getElementById('resignDentistId').value = dentistId;
    document.getElementById('resignNote').placeholder = 'e.g. ' + name + ' resigned effective today';
    const m = document.getElementById('resignModal');
    m.style.display = 'flex';
}
function closeResignModal() {
    document.getElementById('resignModal').style.display = 'none';
}
document.getElementById('resignModal').addEventListener('click', function(e) {
    if (e.target === this) closeResignModal();
});
</script>
</body>
</html>
