<?php
date_default_timezone_set('Asia/Manila');

$hour = date("H");

if($hour >= 5 && $hour < 12){
    $greeting = "Good Morning";
}
elseif($hour >= 12 && $hour < 18){
    $greeting = "Good Afternoon";
}
else{
    $greeting = "Good Evening";
}

$name = $_SESSION['name'] ?? 'User';
?>

<div class="topbar">

<div class="topbar-left">

<h1 class="greeting-title">

<span class="greeting-text">
<?php echo $greeting; ?>,
</span>

<span class="user-name">
<?php echo $name; ?>
</span>

</h1>

<p>
Here's your dental care overview.
</p>

</div>

<div class="profile-box hover-glow"
onclick="window.location.href='/qydentra/patient/profile.php'">

<?php if(!empty($user['profile_photo'])){ ?>

<div class="profile-photo">
    <img src="../uploads/profiles/<?php echo $user['profile_photo']; ?>">
</div>

<?php } else { ?>

<div class="profile-circle">
    <?php echo strtoupper(substr($name,0,1)); ?>
</div>

<?php } ?>

<div>

<h4>
<?php echo $name; ?>
</h4>

<p class="profile-role">
Patient
</p>

</div>

</div>

</div>