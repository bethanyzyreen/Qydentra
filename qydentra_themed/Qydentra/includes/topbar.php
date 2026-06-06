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
$hasPhoto = !empty($user['profile_photo'] ?? '');
?>

<div class="topbar">

<div class="topbar-left">
<h1 class="greeting-title">
<span class="greeting-text"><?php echo $greeting; ?>,</span>
<span class="user-name"><?php echo htmlspecialchars($name); ?></span>
</h1>
<p>Here's your dental care overview.</p>
</div>

<div class="profile-box hover-glow" onclick="window.location.href='profile.php'">

<div class="topbar-avatar">
    <!-- Always show initial as base -->
    <span class="topbar-avatar-initial"><?php echo $initial; ?></span>

    <?php if($hasPhoto): ?>
        <!-- Only overlay with uploaded photo if patient has one -->
        <img src="../uploads/profile/<?php echo htmlspecialchars($user['profile_photo']); ?>"
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
