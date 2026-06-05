<?php
date_default_timezone_set('Asia/Manila');

$hour = date("H");

if($hour >= 5 && $hour < 12){
    $greeting = "Good Morning";
}elseif($hour >= 12 && $hour < 18){
    $greeting = "Good Afternoon";
}else{
    $greeting = "Good Evening";
}

$name = $_SESSION['name'] ?? 'User';
$initial = strtoupper(substr($name, 0, 1));
$hasPhoto = !empty($_SESSION['profile_photo'] ?? '');
$topbar_pat_id = $_SESSION['user_id'] ?? 0;
$topbar_pat_unread_res = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM notifications WHERE user_id='$topbar_pat_id' AND is_read=0");
$topbar_pat_unread = (int)mysqli_fetch_assoc($topbar_pat_unread_res)['cnt'];
?>

<div class="topbar">

<div class="topbar-left">
<h1 class="greeting-title">
<span class="greeting-text"><?php echo $greeting; ?>,</span>
<span class="user-name"><?php echo htmlspecialchars($name); ?></span>
</h1>
<p>Here's your dental care overview.</p>
</div>

<div class="topbar-right-group">

<!-- Theme toggle -->
<button class="theme-toggle-btn" title="Switch to Light Mode">
    <i class="fa-solid fa-sun"></i>
</button>

<!-- Profile: plain text, no background box -->
<div class="profile-box-plain" onclick="window.location.href='profile.php'" title="View Profile">

<div class="topbar-avatar">
    <span class="topbar-avatar-initial"><?php echo $initial; ?></span>

    <?php if($hasPhoto): ?>
        <img src="../uploads/profile/<?php echo htmlspecialchars($_SESSION['profile_photo'] ?? ''); ?>"
             alt="Profile"
             onload="this.style.display='block'"
             onerror="this.style.display='none'">
    <?php endif; ?>
</div>

<div>
    <p class="topbar-name-text"><?php echo htmlspecialchars($name); ?></p>
    <p class="topbar-role-text">Patient</p>
</div>
</div>

</div>

</div>

<script src="../assets/js/theme.js"></script>
