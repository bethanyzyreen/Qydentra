<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check database connection exists
require_once(__DIR__ . "/../config/database.php");

// Validate connection
if (!$conn) {
    $_SESSION['error'] = "Database connection failed. Please try again.";
    header("Location: register.php");
    exit();
}

// Get and sanitize input
$name = trim($_POST['full_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$raw_password = $_POST['password'] ?? '';

// Validate all fields are filled
if (empty($name) || empty($email) || empty($raw_password)) {
    $_SESSION['error'] = "Please fill in all fields.";
    header("Location: register.php");
    exit();
}

// Validate password length
if (strlen($raw_password) < 6) {
    $_SESSION['error'] = "Password must be at least 6 characters.";
    header("Location: register.php");
    exit();
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = "Invalid email format.";
    header("Location: register.php");
    exit();
}

// Hash password
$password = password_hash($raw_password, PASSWORD_DEFAULT);

// Block registration if email already exists in staffs
$staffStmt = $conn->prepare("SELECT staff_id FROM staffs WHERE email = ? LIMIT 1");
if (!$staffStmt) {
    error_log("[Qydentra Registration Error] Prepare failed: " . $conn->error);
    $_SESSION['error'] = "Database error. Please try again.";
    header("Location: register.php");
    exit();
}

$staffStmt->bind_param("s", $email);
if (!$staffStmt->execute()) {
    error_log("[Qydentra Registration Error] Staff check failed: " . $staffStmt->error);
    $_SESSION['error'] = "Database error. Please try again.";
    header("Location: register.php");
    exit();
}

$staffResult = $staffStmt->get_result();
if ($staffResult->num_rows > 0) {
    $_SESSION['error'] = "This email is already registered as a staff account. Please log in directly.";
    header("Location: login.php");
    exit();
}
$staffStmt->close();

// Block duplicate patient registration
$patientCheckStmt = $conn->prepare("SELECT patient_id FROM patients WHERE email = ? LIMIT 1");
if (!$patientCheckStmt) {
    error_log("[Qydentra Registration Error] Prepare failed: " . $conn->error);
    $_SESSION['error'] = "Database error. Please try again.";
    header("Location: register.php");
    exit();
}

$patientCheckStmt->bind_param("s", $email);
if (!$patientCheckStmt->execute()) {
    error_log("[Qydentra Registration Error] Patient check failed: " . $patientCheckStmt->error);
    $_SESSION['error'] = "Database error. Please try again.";
    header("Location: register.php");
    exit();
}

$patientResult = $patientCheckStmt->get_result();
if ($patientResult->num_rows > 0) {
    $_SESSION['error'] = "Email is already registered.";
    header("Location: register.php");
    exit();
}
$patientCheckStmt->close();

// Generate patient_id using sequence table (matches your trigger logic)
$generateIdStmt = $conn->prepare("UPDATE _seq_patients SET last_id = last_id + 1");
if (!$generateIdStmt->execute()) {
    error_log("[Qydentra Registration Error] Sequence update failed: " . $generateIdStmt->error);
    $_SESSION['error'] = "Database error. Please try again.";
    header("Location: register.php");
    exit();
}
$generateIdStmt->close();

// Get the new ID
$getIdStmt = $conn->prepare("SELECT last_id FROM _seq_patients LIMIT 1");
if (!$getIdStmt->execute()) {
    error_log("[Qydentra Registration Error] Sequence read failed: " . $getIdStmt->error);
    $_SESSION['error'] = "Database error. Please try again.";
    header("Location: register.php");
    exit();
}

$idResult = $getIdStmt->get_result();
$idRow = $idResult->fetch_assoc();
$getIdStmt->close();

if (!$idRow) {
    error_log("[Qydentra Registration Error] Failed to generate patient_id");
    $_SESSION['error'] = "Database error. Please try again.";
    header("Location: register.php");
    exit();
}

// Format patient_id: PT + 3-digit padded number (e.g., PT001)
$patient_id = 'PT' . str_pad($idRow['last_id'], 3, '0', STR_PAD_LEFT);

// Insert new patient account with generated patient_id
$insertStmt = $conn->prepare(
    "INSERT INTO patients (patient_id, full_name, email, password, role, status) 
     VALUES (?, ?, ?, ?, 'patient', 'active')"
);

if (!$insertStmt) {
    error_log("[Qydentra Registration Error] Prepare failed: " . $conn->error);
    $_SESSION['error'] = "Registration failed. Please try again or contact support.";
    header("Location: register.php");
    exit();
}

$insertStmt->bind_param("ssss", $patient_id, $name, $email, $password);

if ($insertStmt->execute()) {
    $_SESSION['success'] = "Registration successful!";
    $insertStmt->close();
    // Auto-login using existing authentication system
    require_once 'login_process.php';
    exit();
} else {
    error_log("[Qydentra Registration Error] Insert failed: " . $insertStmt->error);
    $_SESSION['error'] = "Registration failed. Please try again or contact support.";
    header("Location: register.php");
    exit();
}

$insertStmt->close();
$conn->close();
?>