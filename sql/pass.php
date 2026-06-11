<?php
$password = "qydentra.dentist";
$hash = password_hash($password, PASSWORD_BCRYPT);

echo $hash; // Copy this into your database
?>
