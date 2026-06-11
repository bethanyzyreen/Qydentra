<?php
require_once("../config/database.php");
/** @var mysqli $conn */
$allowed_roles = ['dentist'];
include("../includes/auth_check.php");

$user_id = $_SESSION['user_id'];
$success = $_GET['success'] ?? '';
$error   = $_GET['error'] ?? '';

// Handle photo upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_photo'])) {
    $file = $_FILES['profile_photo'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        header("Location: profile.php?error=upload_failed");
        exit();
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','webp'];

    if (!in_array($ext, $allowed)) {
        header("Location: profile.php?error=invalid_type");
        exit();
    }

    if ($file['size'] > 5 * 1024 * 1024) {
        header("Location: profile.php?error=too_large");
        exit();
    }

    $folder = realpath(__DIR__ . '/../uploads/profile') . DIRECTORY_SEPARATOR;
    if (!is_dir($folder)) {
        mkdir(__DIR__ . '/../uploads/profile/', 0775, true);
        $folder = realpath(__DIR__ . '/../uploads/profile') . DIRECTORY_SEPARATOR;
    }

    // Delete old photo
    $oldRow = mysqli_fetch_assoc(mysqli_query($conn, "SELECT profile_photo FROM dentists WHERE dentist_id='$user_id'"));
    if (!empty($oldRow['profile_photo'])) {
        $oldPath = $folder . basename($oldRow['profile_photo']);
        if (file_exists($oldPath)) unlink($oldPath);
    }

    $filename = time() . '_' . $user_id . '.' . $ext;
    $dest = $folder . $filename;

    if (move_uploaded_file($file['tmp_name'], $dest)) {
        $fn_safe = mysqli_real_escape_string($conn, $filename);
        mysqli_query($conn, "UPDATE dentists SET profile_photo='$fn_safe' WHERE dentist_id='$user_id'");
        $_SESSION['profile_photo'] = $filename;
        header("Location: profile.php?success=photo");
        exit();
    } else {
        header("Location: profile.php?error=upload_failed");
        exit();
    }
}

// Load current dentist data
$dentist = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM dentists WHERE dentist_id='$user_id' LIMIT 1"));
?>
<?php include("../includes/dentist_header.php"); ?>
<body>
<?php include("../includes/dentist_sidebar.php"); ?>
<div class="main">
<?php include("../includes/dentist_topbar.php"); ?>

<?php if ($success === 'photo'): ?>
<div class="alert-msg success" style="margin-bottom:20px;">
    <i class="fa-solid fa-circle-check"></i> Profile photo updated successfully.
</div>
<?php endif; ?>
<?php if ($error !== ''): ?>
<div class="alert-msg error" style="margin-bottom:20px;">
    <i class="fa-solid fa-circle-exclamation"></i>
    <?php
    $msgs = [
        'upload_failed' => 'Upload failed. Check folder permissions.',
        'invalid_type'  => 'Invalid file type. Please use JPG, PNG, or WebP.',
        'too_large'     => 'File too large. Maximum size is 5MB.',
    ];
    echo htmlspecialchars($msgs[$error] ?? 'An error occurred.');
    ?>
</div>
<?php endif; ?>

<div class="form-card hover-glow" style="max-width:520px;">
    <h2 style="margin-bottom:4px;">
        <i class="fa-solid fa-user-doctor" style="color:#ffffff; margin-right:8px;"></i>My Profile
    </h2>
    <p style="color:#d1d5db; font-size:13px; margin-bottom:24px;">Update your profile photo.</p>

    <!-- Current photo display -->
    <div style="display:flex; align-items:center; gap:20px; margin-bottom:28px;">
        <div style="position:relative; width:80px; height:80px; flex-shrink:0;">
            <?php if (!empty($dentist['profile_photo'])): ?>
                <img src="../uploads/profile/<?php echo htmlspecialchars($dentist['profile_photo']); ?>"
                     alt="Profile"
                     style="width:80px; height:80px; border-radius:50%; object-fit:cover; border:2px solid rgba(59,130,246,0.30);">
            <?php else: ?>
                <div style="width:80px; height:80px; border-radius:50%; background:linear-gradient(135deg,#3B82F6,#2563EB); display:flex; align-items:center; justify-content:center; font-size:28px; font-weight:700; color:#fff; border:2px solid rgba(59,130,246,0.30);">
                    <?php echo strtoupper(substr($dentist['full_name'] ?? 'D', 0, 1)); ?>
                </div>
            <?php endif; ?>
        </div>
        <div>
            <h3 style="margin:0 0 4px; color:#f1f5f9; font-size:18px;">Dr. <?php echo htmlspecialchars($dentist['full_name'] ?? ''); ?></h3>
            <p style="margin:0; color:#d1d5db; font-size:13px;"><?php echo htmlspecialchars($dentist['email'] ?? ''); ?></p>
            <p style="margin:4px 0 0; color:#ffffff; font-size:12px; font-weight:600;">Dentist — <?php echo htmlspecialchars($user_id); ?></p>
        </div>
    </div>

    <!-- Upload form -->
    <form method="POST" enctype="multipart/form-data">
        <div class="form-group" style="margin-bottom:20px;">
            <label style="font-size:13px; color:#94a3b8; margin-bottom:8px; display:block;">
                <i class="fa-solid fa-camera" style="margin-right:5px; color:#ffffff;"></i>Upload New Photo
            </label>
            <input type="file" name="profile_photo" accept=".jpg,.jpeg,.png,.webp"
                   style="width:100%; background:#0f172a; border:1px solid rgba(59,130,246,0.20); border-radius:10px; padding:10px 12px; color:#f1f5f9; font-size:13px; cursor:pointer; box-sizing:border-box;">
            <small style="display:block; margin-top:6px; color:#475569; font-size:12px;">
                JPG, PNG or WebP · Max 5MB
            </small>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn-primary" style="background:linear-gradient(135deg,#3B82F6,#2563EB);">
                <i class="fa-solid fa-upload"></i> Save Photo
            </button>
        </div>
    </form>
</div>

</div>
</div>
</body>
</html>
