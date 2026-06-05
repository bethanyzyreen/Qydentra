<?php
$allowed_roles = ['patient'];
include("../includes/auth_check.php");

$user_id = $_SESSION['user_id'];

if(isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0){

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

    $folder = __DIR__ . "/../uploads/profile/";
    if(!is_dir($folder)){
        mkdir($folder, 0777, true);
    }

    // Delete old photo if exists
    $oldRow = mysqli_fetch_assoc(mysqli_query($conn,"SELECT profile_photo FROM users WHERE user_id='$user_id'"));
    if(!empty($oldRow['profile_photo'])){
        $oldPath = $folder . $oldRow['profile_photo'];
        if(file_exists($oldPath)) unlink($oldPath);
    }

    $filename = time() . '_' . $user_id . '.' . $ext;
    $dest     = $folder . $filename;

    if(move_uploaded_file($file['tmp_name'], $dest)){
        $filename_esc = mysqli_real_escape_string($conn, $filename);
        mysqli_query($conn,"UPDATE users SET profile_photo='$filename_esc' WHERE user_id='$user_id'");
        // Update session so topbar shows new photo immediately
        $_SESSION['profile_photo'] = $filename;
        header("Location: profile.php?success=photo");
        exit();
    } else {
        header("Location: profile.php?error=upload_failed");
        exit();
    }

} else {
    header("Location: profile.php?error=no_file");
    exit();
}
?>
