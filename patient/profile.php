<?php
$allowed_roles = ['patient'];
include("../includes/auth_check.php");

$user_id = $_SESSION['user_id'];

$sql = "SELECT * FROM patients WHERE patient_id='$user_id'";
$result = mysqli_query($conn,$sql);

$user = mysqli_fetch_assoc($result);
?> 

<?php include("../includes/header.php"); ?>

<body>

<?php include("../includes/sidebar.php"); ?>

<div class="main">

<?php include("../includes/topbar.php"); ?>

<?php
$success_msgs = [
    'photo'        => 'Profile photo updated successfully.',
    '1'            => 'Profile updated successfully.',
    'true'         => 'Profile updated successfully.',
    'password'     => 'Password changed successfully.',
];
$error_msgs = [
    'invalid_type'     => 'Invalid file type. Please upload JPG, PNG, or WEBP.',
    'too_large'        => 'File is too large. Maximum size is 5MB.',
    'upload_failed'    => 'Upload failed. Please check folder permissions or try again.',
    'no_file'          => 'No file was selected. Please choose an image.',
    'invalid_name'     => 'Full name is required.',
    'invalid_email'    => 'Please enter a valid email address.',
    'invalid_phone'    => 'Phone number must be exactly 11 digits (e.g. 09XXXXXXXXX).',
    'wrong_password'   => 'Current password is incorrect.',
    'password_mismatch'=> 'New passwords do not match.',
    'weak_password'    => 'New password must be at least 8 characters long.',
    '1'                => 'Something went wrong. Please try again.',
];
if(isset($_GET['success'])): $sk = $_GET['success']; ?>
<div data-toast="<?php echo htmlspecialchars($success_msgs[$sk] ?? 'Action completed.'); ?>" data-toast-type="success"></div>
<?php endif; ?>
<?php if(isset($_GET['error'])): $ek = $_GET['error']; ?>
<div data-toast="<?php echo htmlspecialchars($error_msgs[$ek] ?? 'Something went wrong.'); ?>" data-toast-type="error"></div>
<?php endif; ?>

<div class="table-container hover-glow">

    <!-- PAGE HEADER -->

    <div class="table-header">
        <div>
            <h2><i class="fa-solid fa-user-circle" style="color:#ffffff; margin-right:8px;"></i>My Profile</h2>
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

                <!-- Outer shell keeps position:relative so camera btn is NOT clipped -->
                <div class="profile-avatar-shell">

                    <div class="profile-avatar">

                        <?php if(!empty($user['profile_photo'])){ ?>

                            <img
                                src="../uploads/profile/<?php echo htmlspecialchars($user['profile_photo']); ?>"
                                id="profileAvatarImg"
                                class="profile-avatar-img"
                                onerror="this.style.display='none';document.getElementById('profile-initial-span').style.display='flex'">
                            <span id="profile-initial-span" class="profile-initial" style="display:none;">
                                <?php echo strtoupper(substr($user['full_name'] ?? 'U', 0, 1)); ?>
                            </span>

                        <?php } else { ?>

                            <span id="profile-initial-span" class="profile-initial">
                                <?php echo strtoupper(substr($user['full_name'] ?? 'U', 0, 1)); ?>
                            </span>

                        <?php } ?>

                    </div>

                    <!-- Camera button sits on the SHELL (not inside overflow:hidden avatar) -->
                    <label
                        for="profilePhoto"
                        class="avatar-camera-btn"
                        title="Change profile photo">

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
                name="phone_number"
                value="<?php echo $user['phone_number'] ?? ''; ?>"
                placeholder="09XXXXXXXXX"
                maxlength="11"
                pattern="\d{11}"
                title="Phone number must be exactly 11 digits"
                inputmode="numeric"
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
                    minlength="8"
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
.addEventListener('change', async function(){

    if(this.files.length === 0){
        return;
    }

    const form = document.getElementById('photoForm');
    const data = new FormData(form);

    try {
        const response = await fetch(form.action, {
            method: 'POST',
            body: data,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        });

        const result = await response.json();

        if (!result.success) {
            showToast('Unable to update profile photo. Please try again.', 'error');
            return;
        }

        const cacheBustUrl = result.url + '?v=' + Date.now();
        const avatar = document.querySelector('.profile-avatar');
        let profileImg = document.getElementById('profileAvatarImg');
        const initial = document.getElementById('profile-initial-span');

        if (!profileImg) {
            profileImg = document.createElement('img');
            profileImg.id = 'profileAvatarImg';
            profileImg.className = 'profile-avatar-img';
            avatar.prepend(profileImg);
        }

        profileImg.src = cacheBustUrl;
        profileImg.style.display = 'block';
        if (initial) initial.style.display = 'none';

        let topbarImg = document.querySelector('.topbar-avatar img');
        const topbarAvatar = document.querySelector('.topbar-avatar');
        if (!topbarImg && topbarAvatar) {
            topbarImg = document.createElement('img');
            topbarImg.className = 'js-topbar-avatar-img';
            topbarImg.alt = 'Profile';
            topbarImg.onload = function(){ this.style.display = 'block'; };
            topbarImg.onerror = function(){ this.style.display = 'none'; };
            topbarAvatar.appendChild(topbarImg);
        }

        if (topbarImg) {
            topbarImg.src = cacheBustUrl;
            topbarImg.style.display = 'block';
        }

        showToast('Profile photo updated successfully.', 'success');
        this.value = '';
    } catch (error) {
        showToast('Unable to update profile photo. Please try again.', 'error');
    }
});
</script>

</body>
</html>
