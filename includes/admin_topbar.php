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

$name = $_SESSION['name'] ?? 'Admin';
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
<div class="profile-box hover-glow" onclick="window.location.href='dashboard.php'">

<div class="topbar-avatar">
    <span class="topbar-avatar-initial"><?php echo $initial; ?></span>
    <img src="../assets/img/receptionist.png"
         alt="Profile"
         onload="this.style.display='block'"
         onerror="this.style.display='none'">
</div>

<div>
    <h4><?php echo htmlspecialchars($name); ?></h4>
    <p class="profile-role">Admin</p>
</div>
</div>

</div>

</div>
