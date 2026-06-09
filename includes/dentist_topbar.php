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

$name = $_SESSION['name'] ?? 'Dentist';
$initial = strtoupper(substr($name, 0, 1));
$dentist_has_photo = !empty($_SESSION['profile_photo'] ?? '');
?>

<div class="topbar">

<div class="topbar-left">
<h1 class="greeting-title">
<span class="greeting-text"><?php echo $greeting; ?>,</span>
<span class="user-name">Dr. <?php echo htmlspecialchars($name); ?></span>
</h1>
</div>

<div class="topbar-right-group">
<!-- Dentist doesn't use receptionist notifications -->
<div class="profile-box hover-glow" onclick="window.location.href='profile.php'">

<div class="topbar-avatar">
    <span class="topbar-avatar-initial"><?php echo $initial; ?></span>
    <?php if($dentist_has_photo): ?>
        <img src="../uploads/profile/<?php echo htmlspecialchars($_SESSION['profile_photo']); ?>"
             alt="Profile"
             onload="this.style.display='block'"
             onerror="this.style.display='none'">
    <?php else: ?>
        <img src="../assets/img/dentist.png"
             alt="Profile"
             onload="this.style.display='block'"
             onerror="this.style.display='none'">
    <?php endif; ?>
</div>

<div>
    <h4><?php echo htmlspecialchars($name); ?></h4>
    <p class="profile-role">Dentist</p>
</div>
</div>

</div>

</div>
