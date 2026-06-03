<?php
// ================================================
// ONE-TIME SETUP SCRIPT — DELETE AFTER RUNNING
// Run this once: http://localhost/qydentracopy/reset_receptionist.php
// ================================================

include("config/database.php");

$email    = 'receptionist@qydentra.com';
$password = 'receptionist123';
$hash     = password_hash($password, PASSWORD_DEFAULT);
$name     = 'Clinic Receptionist';
$role     = 'receptionist';

// Check if account already exists
$check = mysqli_query($conn, "SELECT id FROM users WHERE email='$email'");

if(mysqli_num_rows($check) > 0){
    // Update existing account with fresh hash
    $row = mysqli_fetch_assoc($check);
    $id  = $row['id'];
    mysqli_query($conn,
        "UPDATE users SET password='$hash', role='$role', full_name='$name' WHERE id='$id'"
    );
    echo "<h2 style='font-family:sans-serif;color:green;'>Password reset successfully!</h2>";
    echo "<p style='font-family:sans-serif;'>Account updated:<br>
          <strong>Email:</strong> $email<br>
          <strong>Password:</strong> $password<br>
          <strong>Role:</strong> $role</p>";
} else {
    // Insert new account
    mysqli_query($conn,
        "INSERT INTO users (full_name, email, password, role)
         VALUES ('$name', '$email', '$hash', '$role')"
    );
    echo "<h2 style='font-family:sans-serif;color:green;'>Receptionist account created!</h2>";
    echo "<p style='font-family:sans-serif;'>Account created:<br>
          <strong>Email:</strong> $email<br>
          <strong>Password:</strong> $password<br>
          <strong>Role:</strong> $role</p>";
}

echo "<p style='font-family:sans-serif;color:red;'><strong>Delete this file after running it!</strong></p>";
echo "<p style='font-family:sans-serif;'><a href='auth/login.php'>Go to Login</a></p>";
?>
