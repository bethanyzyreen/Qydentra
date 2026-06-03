<?php

session_start();

// Include database connection so $conn is available in every page after this
if(!isset($conn)){
    include_once(__DIR__ . "/../config/database.php");
}

if(!isset($_SESSION['user_id'])){
    header("Location: ../auth/login.php");
    exit();
}

// Role-based access control:
// Pages set $allowed_roles = ['patient'] or ['receptionist'] BEFORE including this file.
if(isset($allowed_roles) && is_array($allowed_roles)){
    $userRole = $_SESSION['role'] ?? null;
    if(!$userRole || !in_array($userRole, $allowed_roles)){
        switch($userRole){
            case 'patient':
                header("Location: ../patient/dashboard.php");
                exit();
            case 'receptionist':
                header("Location: ../receptionist/dashboard.php");
                exit();
            default:
                header("Location: ../auth/login.php");
                exit();
        }
    }
}

?>