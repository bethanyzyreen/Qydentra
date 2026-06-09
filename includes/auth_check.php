<?php

// Start session only if not already started
if(session_status() === PHP_SESSION_NONE){
    session_start();
}

// Always ensure $conn is available
$conn = $conn ?? null;
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

// ---------------------------------------------------------------
// MAINTENANCE MODE: block all non-admin users
// ---------------------------------------------------------------
require_once(__DIR__ . "/../includes/admin_helpers.php");
$_site_settings = get_site_settings();
if (!empty($_site_settings['maintenance_mode'])) {
    $currentRole = $_SESSION['role'] ?? '';
    if ($currentRole !== 'admin') {
        // Destroy session so they cannot bypass by navigating directly
        session_unset();
        session_destroy();
        header("Location: ../auth/login.php?maintenance=1");
        exit();
    }
}

// ---------------------------------------------------------------
// INACTIVE/RESIGNED DENTIST: block login access
// ---------------------------------------------------------------
if (($_SESSION['role'] ?? '') === 'dentist') {
    $did_safe = mysqli_real_escape_string($conn, $_SESSION['user_id']);
    $dRow = mysqli_fetch_assoc(mysqli_query($conn, "SELECT status FROM dentists WHERE dentist_id='$did_safe' LIMIT 1"));
    if ($dRow && ($dRow['status'] ?? 'active') === 'inactive') {
        session_unset();
        session_destroy();
        header("Location: ../auth/login.php?inactive=1");
        exit();
    }
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
