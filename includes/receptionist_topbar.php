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

$name = $_SESSION['name'] ?? 'Receptionist';
?>

<div class="topbar">

<div class="topbar-left">

<h1 class="greeting-title">
<span class="greeting-text"><?php echo $greeting; ?>,</span>
<span class="user-name"><?php echo $name; ?></span>
</h1>

<p>Manage appointments, queue, and patient flow.</p>

</div>

<div class="profile-box hover-glow"
onclick="window.location.href='../patient/profile.php'">

<div class="profile-circle">
<?php echo strtoupper(substr($name,0,1)); ?>
</div>

<div>
<h4><?php echo $name; ?></h4>
<p class="profile-role">Receptionist</p>
</div>

</div>

</div>
