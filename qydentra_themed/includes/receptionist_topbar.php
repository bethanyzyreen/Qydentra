<?php
date_default_timezone_set('Asia/Manila');

// Unread notification count for topbar badge
$topbar_uid = $_SESSION['user_id'] ?? 0;
$topbar_unread_res = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM receptionist_notifications WHERE receptionist_id='$topbar_uid' AND status='Unread'");
$topbar_unread = (int)mysqli_fetch_assoc($topbar_unread_res)['cnt'];

$hour = date("H");

if($hour >= 5 && $hour < 12){
    $greeting = "Good Morning";
}elseif($hour >= 12 && $hour < 18){
    $greeting = "Good Afternoon";
}else{
    $greeting = "Good Evening";
}

$name = $_SESSION['name'] ?? 'Receptionist';
$initial = strtoupper(substr($name, 0, 1));
?>

<div class="topbar">

<div class="topbar-left">
<h1 class="greeting-title">
<span class="greeting-text"><?php echo $greeting; ?>,</span>
<span class="user-name"><?php echo htmlspecialchars($name); ?></span>
</h1>
</div>

<div class="topbar-right-group">

<!-- Theme toggle -->
<button class="theme-toggle-btn" title="Switch to Light Mode">
    <i class="fa-solid fa-sun"></i>
</button>

<a href="notifications.php" class="topbar-bell-wrap">
    <i class="fa-solid fa-bell topbar-bell"></i>
    <?php if($topbar_unread > 0): ?>
    <span class="topbar-notif-badge"><?php echo $topbar_unread; ?></span>
    <?php endif; ?>
</a>

<div class="profile-box hover-glow" onclick="window.location.href='dashboard.php'">

<div class="topbar-avatar">
    <span class="topbar-avatar-initial"><?php echo $initial; ?></span>
    <img src="../assets/img/profile.jpg"
         alt="Profile"
         onload="this.style.display='block'"
         onerror="this.style.display='none'">
</div>

<div>
    <h4><?php echo htmlspecialchars($name); ?></h4>
    <p class="profile-role">Receptionist</p>
</div>
</div>

</div>

</div>

<script src="../assets/js/theme.js"></script>
