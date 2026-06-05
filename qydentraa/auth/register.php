<?php if(session_status()===PHP_SESSION_NONE) session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Register</title>

<link rel="stylesheet" href="../assets/css/auth.css">

</head>

<body>

<div class="auth-container">

<div class="auth-card">

<div class="logo">
Qydentra
</div>

<p class="tagline">
Create your dental care account
</p>

<?php if(isset($_SESSION["error"])): ?>
<div class="alert-error"><?php echo $_SESSION["error"]; unset($_SESSION["error"]); ?></div>
<?php endif; ?>
<?php if(isset($_SESSION["success"])): ?>
<div class="alert-success-msg"><?php echo $_SESSION["success"]; unset($_SESSION["success"]); ?></div>
<?php endif; ?>
<form action="register_process.php" method="POST">

<div class="input-group">
<input type="text" name="full_name" placeholder="Full Name" required>
</div>

<div class="input-group">
<input type="email" name="email" placeholder="Email Address" required>
</div>

<div class="input-group">
<input type="password" name="password" placeholder="Password" required>
</div>

<button type="submit">
Create Account
</button>

</form>

<p class="switch">
Already have an account?
<a href="login.php">Login</a>
</p>

</div>

</div>

</body>
</html>