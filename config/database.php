<?php

$conn = $conn ?? null;

if (isset($conn) && $conn instanceof mysqli) {
    return;
}

$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "qydentra";

if (!function_exists("mysqli_connect")) {
    die("Database connection failed: PHP mysqli extension is not enabled.");
}

// Enable full error reporting so silent failures are caught
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
} catch (mysqli_sql_exception $e) {
    die("Database connection failed: " . $e->getMessage());
}

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");

// Ensure autocommit is ON so every INSERT/UPDATE commits immediately
mysqli_autocommit($conn, true);

// Set default timezone to Philippines/Manila
date_default_timezone_set('Asia/Manila');
mysqli_query($conn, "SET time_zone = '+08:00'");

// ---------------------------------------------------------------
// Schema migration: ensure dentists table has all required columns
// (guards against importing an older SQL dump that lacks them)
// ---------------------------------------------------------------
@mysqli_query($conn, "ALTER TABLE dentists ADD COLUMN IF NOT EXISTS status ENUM('active','inactive') NOT NULL DEFAULT 'active'");
@mysqli_query($conn, "ALTER TABLE dentists ADD COLUMN IF NOT EXISTS resigned_at DATETIME DEFAULT NULL");
@mysqli_query($conn, "ALTER TABLE dentists ADD COLUMN IF NOT EXISTS resignation_note VARCHAR(255) DEFAULT NULL");

// ---------------------------------------------------------------
// Data migration: dentists must ONLY exist in the `dentists` table.
// Old SQL dumps seeded dentist@qydentra.com into `staffs` too, which
// blocks login because staffs is checked first. Remove any dentist
// rows that crept into staffs.
// ---------------------------------------------------------------
@mysqli_query($conn, "DELETE FROM staffs WHERE role = 'dentist'");
?>
