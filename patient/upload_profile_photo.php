<?php
$allowed_roles = ['patient'];
include("../includes/auth_check.php");

$user_id = $_SESSION['user_id'];

// ── Make sure a file was actually submitted ──────────────────────────────────
if(!isset($_FILES['profile_photo']) || $_FILES['profile_photo']['error'] !== UPLOAD_ERR_OK){
    $code = $_FILES['profile_photo']['error'] ?? -1;
    // UPLOAD_ERR_NO_FILE = 4
    header("Location: profile.php?error=no_file&code=$code");
    exit();
}

$file    = $_FILES['profile_photo'];
$ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowed = ['jpg','jpeg','png','webp','gif'];

if(!in_array($ext, $allowed)){
    header("Location: profile.php?error=invalid_type");
    exit();
}

if($file['size'] > 5 * 1024 * 1024){
    header("Location: profile.php?error=too_large");
    exit();
}

// ── Resolve the upload folder (always relative to this file's location) ──────
$folder = realpath(__DIR__ . '/../uploads/profile') . DIRECTORY_SEPARATOR;

// Create folder if it doesn't exist
if (!is_dir($folder)) {
    if (!mkdir(__DIR__ . '/../uploads/profile/', 0775, true)) {
        header("Location: profile.php?error=upload_failed&reason=mkdir");
        exit();
    }
    $folder = realpath(__DIR__ . '/../uploads/profile') . DIRECTORY_SEPARATOR;
}

// Check folder is writable
if(!is_writable($folder)){
    header("Location: profile.php?error=upload_failed&reason=not_writable");
    exit();
}

// ── Delete old photo ──────────────────────────────────────────────────────────
$oldRow = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT profile_photo FROM patients WHERE patient_id='$user_id'"
));
if(!empty($oldRow['profile_photo'])){
    $oldPath = $folder . basename($oldRow['profile_photo']);
    if(file_exists($oldPath)) unlink($oldPath);
}

// ── Save new photo ────────────────────────────────────────────────────────────
$filename = time() . '_' . $user_id . '.' . $ext;
$dest     = $folder . $filename;

if(move_uploaded_file($file['tmp_name'], $dest)){
    $filename_esc = mysqli_real_escape_string($conn, $filename);
    mysqli_query($conn,
        "UPDATE patients SET profile_photo='$filename_esc' WHERE patient_id='$user_id'"
    );
    $_SESSION['profile_photo'] = $filename;
    header("Location: profile.php?success=photo");
    exit();
} else {
    header("Location: profile.php?error=upload_failed&reason=move");
    exit();
}
?>