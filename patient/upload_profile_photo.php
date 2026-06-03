<?php
$allowed_roles = ['patient'];
include("../includes/auth_check.php");

$user_id = $_SESSION['user_id'];

if(isset($_FILES['profile_photo'])){

    $file = $_FILES['profile_photo'];

    if($file['error'] == 0){

        $ext = strtolower(
            pathinfo(
                $file['name'],
                PATHINFO_EXTENSION
            )
        );

        $allowed = ['jpg','jpeg','png','webp'];

        if(in_array($ext,$allowed)){

            $filename =
                time().'_'.$user_id.'.'.$ext;

            $folder =
                "../uploads/profile/";

            if(!file_exists($folder)){
                mkdir($folder,0777,true);
            }

            move_uploaded_file(
                $file['tmp_name'],
                $folder.$filename
            );

            mysqli_query($conn,"
                UPDATE users
                SET profile_photo='$filename'
                WHERE id='$user_id'
            ");

        }

    }

}

header("Location: profile.php");
exit();
?>