<?php
$allowed_roles = ['patient'];
include("../includes/auth_check.php");

$user_id = $_SESSION['user_id'];
$wants_json = (
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest'
    || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')
);

function photo_response($ok, $payload = []) {
    global $wants_json;
    if ($wants_json) {
        header('Content-Type: application/json');
        echo json_encode(array_merge(['success' => $ok], $payload));
        exit();
    }

    if ($ok) {
        header("Location: profile.php?success=photo");
    } else {
        $error = $payload['error'] ?? 'upload_failed';
        $reason = isset($payload['reason']) ? '&reason=' . urlencode($payload['reason']) : '';
        header("Location: profile.php?error=" . urlencode($error) . $reason);
    }
    exit();
}

// ── Make sure a file was actually submitted ──────────────────────────────────
if(!isset($_FILES['profile_photo']) || $_FILES['profile_photo']['error'] !== UPLOAD_ERR_OK){
    $code = $_FILES['profile_photo']['error'] ?? -1;
    photo_response(false, ['error' => 'no_file', 'code' => $code]);
}

$file    = $_FILES['profile_photo'];
$ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowed = ['jpg','jpeg','png','webp','gif'];

if(!in_array($ext, $allowed)){
    photo_response(false, ['error' => 'invalid_type']);
}

if($file['size'] > 5 * 1024 * 1024){
    photo_response(false, ['error' => 'too_large']);
}

// ── Resolve the upload folder (always relative to this file's location) ──────
$folder = realpath(__DIR__ . '/../uploads/profile') . DIRECTORY_SEPARATOR;

// Create folder if it doesn't exist
if (!is_dir($folder)) {
    if (!mkdir(__DIR__ . '/../uploads/profile/', 0775, true)) {
        photo_response(false, ['error' => 'upload_failed', 'reason' => 'mkdir']);
    }
    $folder = realpath(__DIR__ . '/../uploads/profile') . DIRECTORY_SEPARATOR;
}

// Check folder is writable
if(!is_writable($folder)){
    photo_response(false, ['error' => 'upload_failed', 'reason' => 'not_writable']);
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
    photo_response(true, [
        'filename' => $filename,
        'url' => '../uploads/profile/' . $filename,
    ]);
} else {
    photo_response(false, ['error' => 'upload_failed', 'reason' => 'move']);
}
?>
