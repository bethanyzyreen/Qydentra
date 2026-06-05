<?php

if(isset($conn) && $conn instanceof mysqli){
    return;
}

$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "qydentra";

if(!function_exists("mysqli_connect")){
    die("Database connection failed: PHP mysqli extension is not enabled.");
}

if(function_exists("mysqli_report")){
    mysqli_report(MYSQLI_REPORT_OFF);
}

$conn = @mysqli_connect($db_host, $db_user, $db_pass, $db_name);

if(!$conn){
    die("Database connection failed: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");
?>
