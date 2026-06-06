<?php
date_default_timezone_set('Asia/Manila');

$hour = date("H");

if($hour >= 5 && $hour < 12){
    $greeting = "Good Day";
}elseif($hour >= 12 && $hour < 18){
    $greeting = "Good Day";
}else{
    $greeting = "Good Day";
}

$name = $_SESSION['name'] ?? 'User';
$initial = strtoupper(substr($name, 0, 1));
$hasPhoto = !empty($_SESSION['profile_photo'] ?? '');
$topbar_pat_id = $_SESSION['user_id'] ?? 0;
$topbar_pat_unread_res = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM patient_notifications WHERE patient_id='$topbar_pat_id' AND is_read=0");
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
<div class="profile-box hover-glow" onclick="window.location.href='profile.php'">

<div class="topbar-avatar">
    <!-- Always show initial as base -->
    <span class="topbar-avatar-initial"><?php echo $initial; ?></span>

    <?php if($hasPhoto): ?>
        <!-- Only overlay with uploaded photo if patient has one -->
        <img src="/uploads/profile/<?php echo htmlspecialchars($_SESSION['profile_photo'] ?? ''); ?>"
             alt="Profile"
             onload="this.style.display='block'"
             onerror="this.style.display='none'">
    <?php endif; ?>
    <!-- No fallback to profile.jpg for patients — initial letter is the default -->
</div>

<div>
    <h4><?php echo htmlspecialchars($name); ?></h4>
    <p class="profile-role">Patient</p>
</div>
</div>

</div>

</div>
