<?php

include("../config/database.php");

$name = $_POST['full_name'];
$email = $_POST['email'];
$password = password_hash($_POST['password'], PASSWORD_DEFAULT);

$sql = "INSERT INTO users(full_name,email,password,role)
VALUES('$name','$email','$password','patient')";

if(mysqli_query($conn, $sql)){

    header("Location: login.php");

} else {

    echo "Registration Failed";

}

?>