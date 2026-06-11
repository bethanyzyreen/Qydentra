<?php session_start(); ?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Qydentra Login</title>

<link rel="stylesheet" href="../assets/css/auth.css">

</head>

<body>

<div class="auth-container">

<div class="auth-card">

<div class="logo">
Qydentra
</div>

<p class="tagline">
Dental Appointment &amp; Queue System
</p>

<?php if(isset($_SESSION["error"])): ?>
<div class="alert-error"><?php echo $_SESSION["error"]; unset($_SESSION["error"]); ?></div>
<?php endif; ?>

<?php if (!empty($_GET['maintenance'])): ?>
<div class="alert-error" style="background:rgba(251,191,36,0.12); border-color:rgba(251,191,36,0.30); color:#fbbf24;">
    <i class="fa-solid fa-triangle-exclamation" style="margin-right:6px;"></i>
    The system is currently under maintenance. Please try again later.
</div>
<?php endif; ?>

<?php if (!empty($_GET['inactive'])): ?>
<div class="alert-error">
    <i class="fa-solid fa-ban" style="margin-right:6px;"></i>
    Your account has been deactivated. Please contact the administrator.
</div>
<?php endif; ?>

<?php if (!empty($_GET['reset'])): ?>
<div class="alert-success-msg">
    If that email is registered, a reset link has been sent.
</div>
<?php endif; ?>

<form action="login_process.php" method="POST">

<div class="input-group">
<input type="email" name="email" placeholder="Enter your email" required>
</div>

<div class="input-group">
<input type="password" name="password" placeholder="Enter your password" required>
</div>

<div class="forgot-password">
<a href="#" onclick="openForgotModal(); return false;">Forgot password?</a>
</div>

<button type="submit">
Sign In
</button>

</form>

<p class="switch">
Don't have an account?
<a href="register.php">Sign Up</a>
</p>

</div>

</div>

<!-- Forgot Password Modal -->
<div class="modal-overlay" id="forgotModal">
<div class="modal-box">
<h3>Reset Password</h3>
<p>Enter your email address and we'll send you a link to reset your password.</p>
<form action="forgot_password.php" method="POST">
<input type="email" name="reset_email" placeholder="Enter your email address" required>
<div class="modal-actions">
<button type="submit" class="modal-btn-send">Send Reset Link</button>
<button type="button" class="modal-btn-cancel" onclick="closeForgotModal()">Cancel</button>
</div>
</form>
</div>
</div>

<script>
function openForgotModal() {
    document.getElementById('forgotModal').classList.add('active');
}
function closeForgotModal() {
    document.getElementById('forgotModal').classList.remove('active');
}
// Close on overlay click
document.getElementById('forgotModal').addEventListener('click', function(e) {
    if (e.target === this) closeForgotModal();
});
</script>

</body>
</html>
