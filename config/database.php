<?php

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
?>
