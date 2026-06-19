<?php
require_once("../config/database.php");
/** @var mysqli $conn */
$allowed_roles = ['dentist'];
include("../includes/auth_check.php");

$user_id = $_SESSION['user_id'];
$uid_esc = mysqli_real_escape_string($conn, $user_id);
$success = $_GET['success'] ?? '';
$error   = $_GET['error'] ?? '';

function redirect_profile($params = []) {
    $query = http_build_query($params);
    header("Location: profile.php" . ($query ? "?$query" : ""));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $full_name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $specialization = trim($_POST['specialization'] ?? '');
        $contact_number = trim($_POST['contact_number'] ?? '');

        if ($full_name === '' || $email === '') {
            redirect_profile(['error' => 'missing_required']);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            redirect_profile(['error' => 'invalid_email']);
        }

        $full_name_esc = mysqli_real_escape_string($conn, $full_name);
        $email_esc = mysqli_real_escape_string($conn, $email);
        $specialization_esc = mysqli_real_escape_string($conn, $specialization);
        $contact_number_esc = mysqli_real_escape_string($conn, $contact_number);

        $email_check = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT dentist_id FROM dentists
             WHERE email='$email_esc' AND dentist_id <> '$uid_esc'
             LIMIT 1"
        ));

        if ($email_check) {
            redirect_profile(['error' => 'email_taken']);
        }

        mysqli_query($conn,
            "UPDATE dentists
             SET full_name='$full_name_esc',
                 email='$email_esc',
                 specialization='$specialization_esc',
                 contact_number='$contact_number_esc'
             WHERE dentist_id='$uid_esc'"
        );

        $_SESSION['name'] = $full_name;
        $_SESSION['full_name'] = $full_name;
        redirect_profile(['success' => 'profile']);
    }

    if ($action === 'upload_photo') {
        if (!isset($_FILES['profile_photo']) || $_FILES['profile_photo']['error'] !== UPLOAD_ERR_OK) {
            redirect_profile(['error' => 'upload_failed']);
        }

        $file = $_FILES['profile_photo'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','webp'];

        if (!in_array($ext, $allowed, true)) {
            redirect_profile(['error' => 'invalid_type']);
        }

        if ($file['size'] > 5 * 1024 * 1024) {
            redirect_profile(['error' => 'too_large']);
        }

        $upload_dir = __DIR__ . '/../uploads/profile/';
        if (!is_dir($upload_dir) && !mkdir($upload_dir, 0775, true)) {
            redirect_profile(['error' => 'upload_failed']);
        }

        $folder = realpath($upload_dir);
        if (!$folder || !is_writable($folder)) {
            redirect_profile(['error' => 'upload_failed']);
        }

        $folder .= DIRECTORY_SEPARATOR;

        $oldRow = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT profile_photo FROM dentists WHERE dentist_id='$uid_esc'"
        ));

        $filename = time() . '_' . $user_id . '.' . $ext;
        $dest = $folder . $filename;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            redirect_profile(['error' => 'upload_failed']);
        }

        $fn_safe = mysqli_real_escape_string($conn, $filename);
        mysqli_query($conn,
            "UPDATE dentists SET profile_photo='$fn_safe' WHERE dentist_id='$uid_esc'"
        );

        if (!empty($oldRow['profile_photo'])) {
            $oldPath = $folder . basename($oldRow['profile_photo']);
            if (is_file($oldPath) && basename($oldPath) !== $filename) {
                unlink($oldPath);
            }
        }

        $_SESSION['profile_photo'] = $filename;
        redirect_profile(['success' => 'photo']);
    }
}

$dentist = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT * FROM dentists WHERE dentist_id='$uid_esc' LIMIT 1"
));

if (!$dentist) {
    redirect_profile(['error' => 'not_found']);
}

$success_msgs = [
    'photo' => 'Profile photo updated successfully.',
    'profile' => 'Profile information updated successfully.',
];

$error_msgs = [
    'upload_failed' => 'Upload failed. Please check the image and try again.',
    'invalid_type' => 'Invalid file type. Please use JPG, PNG, or WebP.',
    'too_large' => 'File too large. Maximum size is 5MB.',
    'missing_required' => 'Name and email are required.',
    'invalid_email' => 'Please enter a valid email address.',
    'email_taken' => 'That email is already used by another dentist.',
    'not_found' => 'Unable to load dentist profile.',
];
?>
<?php include("../includes/dentist_header.php"); ?>
<body>
<?php include("../includes/dentist_sidebar.php"); ?>
<div class="main">
<?php include("../includes/dentist_topbar.php"); ?>

<?php if ($success !== ''): ?>
<div class="alert-msg success">
    <i class="fa-solid fa-circle-check"></i>
    <?php echo htmlspecialchars($success_msgs[$success] ?? 'Profile updated.'); ?>
</div>
<?php endif; ?>

<?php if ($error !== ''): ?>
<div class="alert-msg error">
    <i class="fa-solid fa-circle-exclamation"></i>
    <?php echo htmlspecialchars($error_msgs[$error] ?? 'An error occurred.'); ?>
</div>
<?php endif; ?>

<div class="profile-actions-grid dentist-profile-grid">

    <div class="profile-card dentist-profile-summary">
        <div class="profile-hero-card dentist-profile-hero">
            <div class="profile-avatar-shell">
                <div class="profile-avatar">
                    <?php if (!empty($dentist['profile_photo'])): ?>
                        <img
                            src="../uploads/profile/<?php echo htmlspecialchars($dentist['profile_photo']); ?>"
                            alt="Profile"
                            class="profile-avatar-img">
                    <?php else: ?>
                        <span class="profile-initial">
                            <?php echo strtoupper(substr($dentist['full_name'] ?? 'D', 0, 1)); ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="profile-hero-content">
                <h2 class="profile-name">Dr. <?php echo htmlspecialchars($dentist['full_name'] ?? ''); ?></h2>
                <p class="profile-email"><?php echo htmlspecialchars($dentist['email'] ?? ''); ?></p>
                <span class="role-badge">Dentist</span>
            </div>
        </div>

        <form method="POST" enctype="multipart/form-data" class="profile-form">
            <input type="hidden" name="action" value="upload_photo">
            <label><i class="fa-solid fa-camera"></i> Profile Photo</label>
            <input type="file" name="profile_photo" accept=".jpg,.jpeg,.png,.webp" required>
            <small class="field-help">JPG, PNG, or WebP. Maximum size is 5MB.</small>
            <button type="submit" class="profile-btn">
                <i class="fa-solid fa-upload"></i>
                Save Photo
            </button>
        </form>
    </div>

    <div class="profile-card">
        <h3 class="profile-section-title">
            <i class="fa-solid fa-user-pen"></i>
            Profile Details
        </h3>

        <form method="POST" class="profile-form">
            <input type="hidden" name="action" value="update_profile">

            <label>Full Name</label>
            <input
                type="text"
                name="full_name"
                value="<?php echo htmlspecialchars($dentist['full_name'] ?? ''); ?>"
                required>

            <label>Email</label>
            <input
                type="email"
                name="email"
                value="<?php echo htmlspecialchars($dentist['email'] ?? ''); ?>"
                required>

            <label>Specialization</label>
            <input
                type="text"
                name="specialization"
                value="<?php echo htmlspecialchars($dentist['specialization'] ?? ''); ?>"
                placeholder="e.g. General Dentistry">

            <label>Contact Number</label>
            <input
                type="text"
                name="contact_number"
                value="<?php echo htmlspecialchars($dentist['contact_number'] ?? ''); ?>"
                placeholder="09XXXXXXXXX">

            <button type="submit" class="profile-btn">
                <i class="fa-solid fa-floppy-disk"></i>
                Save Changes
            </button>
        </form>
    </div>

</div>

</div>
</body>
</html>
