<?php session_start(); ?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Qydentra Login</title>

<link rel="stylesheet" href="../assets/css/auth.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

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



<form action="login_process.php" method="POST">

<div class="input-group">
<input type="email" name="email" placeholder="Enter your email" required>
</div>

<div class="input-group" style="position: relative;">
<input type="password" id="login_password" name="password" placeholder="Enter your password" required style="padding-right: 40px;">
<span onclick="togglePasswordVisibility()" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #9ca3af;">
    <i class="fa-regular fa-eye-slash" id="togglePasswordIcon"></i>
</span>
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

<?php if(isset($_SESSION['reset_success'])): ?>
<div class="modal-overlay active" id="resetSuccessModal" style="display: flex;">
    <div class="modal-box" style="text-align: center;">
        <h3 style="color: #10b981; margin-bottom: 10px;">Request Processed</h3>
        <p style="color: #e2e8f0;"><?php echo $_SESSION['reset_success']; ?></p>
        <?php if(isset($_SESSION['dev_reset_link'])): ?>
            <div style="margin-top: 15px; padding: 15px; background: rgba(59, 130, 246, 0.1); border: 1px solid rgba(59, 130, 246, 0.3); border-radius: 12px; word-break: break-all;">
                <strong style="color: #60a5fa;">Development Mode Reset Link:</strong><br><br>
                <a href="<?php echo htmlspecialchars($_SESSION['dev_reset_link']); ?>" style="color: #93c5fd; text-decoration: underline; font-size: 13px;">
                    <?php echo htmlspecialchars($_SESSION['dev_reset_link']); ?>
                </a>
            </div>
            <?php unset($_SESSION['dev_reset_link']); ?>
        <?php endif; ?>
        <div class="modal-actions" style="justify-content: center; margin-top: 25px;">
            <button type="button" class="modal-btn-cancel" onclick="document.getElementById('resetSuccessModal').style.display='none'" style="width: 100%;">Close</button>
        </div>
    </div>
</div>
<?php unset($_SESSION['reset_success']); ?>
<?php endif; ?>

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

function togglePasswordVisibility() {
    var passwordInput = document.getElementById('login_password');
    var toggleIcon = document.getElementById('togglePasswordIcon');
    if (passwordInput.type === 'password') {
        // Show password
        passwordInput.type = 'text';
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
    } else {
        // Hide password
        passwordInput.type = 'password';
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
    }
}
</script>

</body>
</html>
