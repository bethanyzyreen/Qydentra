<?php
$allowed_roles = ['admin'];
include("../includes/auth_check.php");
require_once("../includes/admin_helpers.php");
ensure_admin_tables_exist($conn);

$settings = get_site_settings();
$message = '';
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newSettings = [
        'site_name'           => trim($_POST['site_name'] ?? 'Qydentra'),
        'clinic_address'      => trim($_POST['clinic_address'] ?? ''),
        'contact_email'       => trim($_POST['contact_email'] ?? ''),
        'contact_phone'       => trim($_POST['contact_phone'] ?? ''),
        'appointment_limit'   => max(1, intval($_POST['appointment_limit'] ?? 20)),
        'queue_limit'         => max(1, intval($_POST['queue_limit'] ?? 30)),
        'maintenance_mode'    => isset($_POST['maintenance_mode']) ? 1 : 0,
        'allow_registration'  => isset($_POST['allow_registration']) ? 1 : 0,
        'allow_cancellation'  => isset($_POST['allow_cancellation']) ? 1 : 0,
    ];

    if (save_site_settings($newSettings)) {
        $message = 'System settings saved successfully.';
        $settings = $newSettings;
        log_admin_action($conn, $_SESSION['user_id'], 'Update system settings', 'General configuration updated');
    } else {
        $message = 'Unable to save settings. Please check folder permissions.';
        $messageType = 'error';
    }
}

$defaults = [
    'site_name'          => 'Qydentra',
    'clinic_address'     => '',
    'contact_email'      => '',
    'contact_phone'      => '',
    'appointment_limit'  => 20,
    'queue_limit'        => 30,
    'maintenance_mode'   => 0,
    'allow_registration' => 1,
    'allow_cancellation' => 1,
];
$settings = array_merge($defaults, $settings);
?>
<?php include("../includes/admin_header.php"); ?>
<body>
<?php include("../includes/admin_sidebar.php"); ?>

<div class="main">
<?php include("../includes/admin_topbar.php"); ?>

<?php if ($message !== ''): ?>
<div data-toast="<?php echo htmlspecialchars($message); ?>" data-toast-type="<?php echo $messageType; ?>"></div>
<?php endif; ?>

<form method="POST">

<!-- Clinic Identity -->
<div class="form-card hover-glow" style="margin-bottom:20px;">
    <h2 style="margin-bottom:4px;">
        <i class="fa-solid fa-building-user" style="color:#ffffff; margin-right:8px;"></i>Clinic Information
    </h2>
    <p style="color:#d1d5db; font-size:13px; margin-bottom:20px;">Basic identity shown across the system and in patient-facing pages.</p>

    <div class="form-grid-2">
        <div class="form-group">
            <label>Clinic / App Name</label>
            <input type="text" name="site_name" value="<?php echo htmlspecialchars($settings['site_name']); ?>" required placeholder="e.g. Qydentra Dental Clinic">
        </div>
        <div class="form-group">
            <label>Contact Email</label>
            <input type="email" name="contact_email" value="<?php echo htmlspecialchars($settings['contact_email']); ?>" placeholder="clinic@example.com">
        </div>
    </div>
    <div class="form-grid-2">
        <div class="form-group">
            <label>Contact Phone</label>
            <input type="text" name="contact_phone" value="<?php echo htmlspecialchars($settings['contact_phone']); ?>" placeholder="e.g. +63 912 345 6789">
        </div>
        <div class="form-group">
            <label>Clinic Address</label>
            <input type="text" name="clinic_address" value="<?php echo htmlspecialchars($settings['clinic_address']); ?>" placeholder="e.g. 123 Rizal St, Dasmariñas, Cavite">
        </div>
    </div>
</div>

<!-- Booking & Queue -->
<div class="form-card hover-glow" style="margin-bottom:20px;">
    <h2 style="margin-bottom:4px;">
        <i class="fa-solid fa-calendar-check" style="color:#ffffff; margin-right:8px;"></i>Booking & Queue Limits
    </h2>
    <p style="color:#d1d5db; font-size:13px; margin-bottom:20px;">Control how many appointments and queue slots are allowed per day.</p>

    <div class="form-grid-2" style="max-width:560px;">
        <div class="form-group">
            <label>Max Appointments per Day</label>
            <input type="number" name="appointment_limit" value="<?php echo intval($settings['appointment_limit']); ?>" min="1" max="500" placeholder="20">
            <small style="color:#d1d5db; font-size:12px; margin-top:4px; display:block;">Patients won't be able to book beyond this daily limit.</small>
        </div>
        <div class="form-group">
            <label>Max Queue Slots per Day</label>
            <input type="number" name="queue_limit" value="<?php echo intval($settings['queue_limit']); ?>" min="1" max="500" placeholder="30">
            <small style="color:#d1d5db; font-size:12px; margin-top:4px; display:block;">Walk-in queue will close once this number is reached.</small>
        </div>
    </div>
</div>

<!-- System Toggles -->
<div class="form-card hover-glow" style="margin-bottom:20px;">
    <h2 style="margin-bottom:4px;">
        <i class="fa-solid fa-toggle-on" style="color:#ffffff; margin-right:8px;"></i>System Controls
    </h2>
    <p style="color:#d1d5db; font-size:13px; margin-bottom:20px;">Enable or disable key system behaviours.</p>

    <div style="display:flex; flex-direction:column; gap:16px;">
        <label class="toggle-row">
            <div class="toggle-wrap">
                <input type="checkbox" name="allow_registration" value="1" <?php echo !empty($settings['allow_registration']) ? 'checked' : ''; ?> id="tog_reg">
                <span class="toggle-slider"></span>
            </div>
            <div>
                <strong>Allow New Patient Registration</strong>
                <p>When enabled, new patients can self-register from the login page.</p>
            </div>
        </label>
        <label class="toggle-row">
            <div class="toggle-wrap">
                <input type="checkbox" name="allow_cancellation" value="1" <?php echo !empty($settings['allow_cancellation']) ? 'checked' : ''; ?> id="tog_cancel">
                <span class="toggle-slider"></span>
            </div>
            <div>
                <strong>Allow Patients to Cancel Appointments</strong>
                <p>When disabled, only staff can cancel appointments.</p>
            </div>
        </label>
        <label class="toggle-row" style="border-color:rgba(248,113,113,0.2);">
            <div class="toggle-wrap">
                <input type="checkbox" name="maintenance_mode" value="1" <?php echo !empty($settings['maintenance_mode']) ? 'checked' : ''; ?> id="tog_maint">
                <span class="toggle-slider" style="--slider-on:#f87171;"></span>
            </div>
            <div>
                <strong style="color:<?php echo !empty($settings['maintenance_mode']) ? '#f87171' : 'inherit'; ?>">Maintenance Mode</strong>
                <p>Locks out all non-admin users with a maintenance notice. Use during updates.</p>
            </div>
        </label>
    </div>
</div>

<div class="form-actions" style="padding:0 0 32px;">
    <button type="submit" class="btn-primary" style="min-width:160px;">
        <i class="fa-solid fa-floppy-disk"></i> Save All Settings
    </button>
</div>

</form>

</div>
</div>

<style>
.toggle-row {
    display: flex;
    align-items: flex-start;
    gap: 16px;
    padding: 14px 16px;
    border-radius: 10px;
    border: 1px solid rgba(100,116,139,0.15);
    background: rgba(15,23,42,0.3);
    cursor: pointer;
    transition: border-color 0.2s;
}
.toggle-row:hover { border-color: rgba(96,165,250,0.25); }
.toggle-row > div:last-child { flex:1; }
.toggle-row strong { display:block; font-size:14px; color:#e2e8f0; margin-bottom:2px; }
.toggle-row p { margin:0; font-size:12px; color:#d1d5db; line-height:1.4; }
.toggle-wrap {
    position: relative;
    width: 44px;
    height: 24px;
    flex-shrink: 0;
    margin-top: 2px;
}
.toggle-wrap input { opacity:0; width:0; height:0; position:absolute; }
.toggle-slider {
    position: absolute;
    inset: 0;
    background: rgba(100,116,139,0.25);
    border-radius: 34px;
    cursor: pointer;
    transition: background 0.2s;
    --slider-on: #60a5fa;
}
.toggle-slider::before {
    content: '';
    position: absolute;
    height: 18px; width: 18px;
    left: 3px; bottom: 3px;
    background: #fff;
    border-radius: 50%;
    transition: transform 0.2s;
}
.toggle-wrap input:checked + .toggle-slider { background: var(--slider-on, #60a5fa); }
.toggle-wrap input:checked + .toggle-slider::before { transform: translateX(20px); }
</style>

</body>
</html>
