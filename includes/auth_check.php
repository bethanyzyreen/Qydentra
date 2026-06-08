<?php

// Start session only if not already started
if(session_status() === PHP_SESSION_NONE){
    session_start();
}

// Always ensure $conn is available
require_once(__DIR__ . "/../config/database.php");

// Load shared notification formatting helpers
require_once(__DIR__ . "/notification_templates.php");

// Load notification INSERT wrappers (with error logging)
require_once(__DIR__ . "/notify_helper.php");

// Load ID prefix formatter  — fmt_id('PT', $id) → "PT001"
require_once(__DIR__ . "/id_format.php");

// Redirect to login if not authenticated
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
            case 'dentist':
                header("Location: ../dentist/dashboard.php");
                exit();
            default:
                header("Location: ../auth/login.php");
                exit();
        }
    }
}
?>
