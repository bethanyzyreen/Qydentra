<?php
include("../includes/auth_check.php");
include("../config/database.php");

$user_id = $_SESSION['user_id'];

$sql = "SELECT * FROM users WHERE id='$user_id'";
$result = mysqli_query($conn,$sql);

$user = mysqli_fetch_assoc($result);
?>

<?php include("../includes/header.php"); ?>

<body>

<?php include("../includes/sidebar.php"); ?>

<div class="main">

<?php include("../includes/topbar.php"); ?>

<?php if(isset($_GET['success'])){ ?>

<div class="success-message">
    Profile updated successfully.
</div>

<?php } ?>

<?php if(isset($_GET['error'])){ ?>

<div class="error-message">
    Something went wrong.
</div>

<?php } ?>

<div class="table-container hover-glow">

    <!-- PAGE HEADER -->

    <div class="table-header">
        <div>
            <h2>My Profile</h2>
            <p>Manage your account information and security settings</p>
        </div>
    </div>

    <!-- PROFILE OVERVIEW -->

    <div class="profile-hero-card">

        <div class="profile-avatar-wrapper">

            <form
                action="upload_profile_photo.php"
                method="POST"
                enctype="multipart/form-data"
                id="photoForm">

                <div class="profile-avatar">

                    <?php if(!empty($user['profile_photo'])){ ?>

                        <img
                            src="../uploads/profile/<?php echo $user['profile_photo']; ?>"
                            class="profile-avatar-img">

                    <?php } else { ?>

                        <span class="profile-initial">
                            <?php echo strtoupper(substr($user['full_name'] ?? 'U', 0, 1)); ?>
                        </span>

                    <?php } ?>

                    <label
                        for="profilePhoto"
                        class="avatar-camera-btn">

                        <i class="fa-solid fa-camera"></i>

                    </label>

                    <input
                        type="file"
                        id="profilePhoto"
                        name="profile_photo"
                        accept="image/*"
                        hidden>

                </div>

            </form>

        </div>

        <div class="profile-hero-content">

            <h2 class="profile-name">
                <?php echo $user['full_name']; ?>
            </h2>

            <p class="profile-email">
                <?php echo $user['email']; ?>
            </p>

            <span class="role-badge">
                <?php echo ucfirst($user['role']); ?>
            </span>

            <div class="profile-info-box">

                <div class="profile-info-item">
                    <strong>User ID</strong>
                    <p>#<?php echo $user['id']; ?></p>
                </div>

                <div class="profile-info-item">
                    <strong>Account Type</strong>
                    <p><?php echo ucfirst($user['role']); ?></p>
                </div>

            </div>

        </div>

    </div>

    <!-- EDIT PROFILE + CHANGE PASSWORD -->

    <div class="profile-actions-grid">

        <!-- EDIT PROFILE -->

        <div class="profile-card">

            <h3 class="profile-section-title">
                Edit Profile
            </h3>

            <form
                method="POST"
                action="update_profile.php"
                class="profile-form">

                <label>Full Name</label>

                <input
                    type="text"
                    name="full_name"
                    value="<?php echo $user['full_name']; ?>"
                    required
                >

                <label>Email</label>

                <input
                    type="email"
                    name="email"
                    value="<?php echo $user['email']; ?>"
                    required
                >

                <label>Phone Number</label>

                <input
                type="text"
                name="phone"
                value="<?php echo $user['phone'] ?? ''; ?>"
                placeholder="09XXXXXXXXX"
                >

                <button
                    type="submit"
                    class="profile-btn">

                    <i class="fa-solid fa-user-pen"></i>
                    Update Profile

                </button>

            </form>

        </div>

        <!-- CHANGE PASSWORD -->

        <div class="profile-card">

            <h3 class="profile-section-title">
                Change Password
            </h3>

            <form
                method="POST"
                action="change_password.php"
                class="profile-form">

                <label>Current Password</label>

                <input
                    type="password"
                    name="current_password"
                    required
                >

                <label>New Password</label>

                <input
                    type="password"
                    name="new_password"
                    required
                >

                <label>Confirm Password</label>

                <input
                    type="password"
                    name="confirm_password"
                    required
                >

                <button
                    type="submit"
                    class="profile-btn">

                    <i class="fa-solid fa-lock"></i>
                    Change Password

                </button>

            </form>

        </div>

    </div>

</div>

</div>

<script>
document.getElementById('profilePhoto')
.addEventListener('change', function(){

    if(this.files.length > 0){
        document.getElementById('photoForm').submit();
    }

});
</script>

</body>
</html>