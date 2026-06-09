<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>

<div class="sidebar">

<div class="sidebar-top">

<h2>
<i class="fa-solid fa-tooth"></i>
Qydentra
</h2>

<div class="nav-links">

<a href="dashboard.php"
class="<?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
<i class="fa-solid fa-house"></i>
<span>Dashboard</span>
</a>

<a href="schedule.php"
class="<?php echo ($current_page == 'schedule.php') ? 'active' : ''; ?>">
<i class="fa-solid fa-calendar-day"></i>
<span>Today's Schedule</span>
</a>

<a href="queue.php"
class="<?php echo ($current_page == 'queue.php') ? 'active' : ''; ?>">
<i class="fa-solid fa-users"></i>
<span>Patient Queue</span>
</a>

<a href="records.php"
class="<?php echo ($current_page == 'records.php') ? 'active' : ''; ?>">
<i class="fa-solid fa-folder-open"></i>
<span>Patient Records</span>
</a>

<a href="profile.php"
class="<?php echo ($current_page == 'profile.php') ? 'active' : ''; ?>">
<i class="fa-solid fa-user-circle"></i>
<span>My Profile</span>
</a>

<!-- Note: consultation.php is normally accessed via Schedule/Queue, not the main sidebar -->

</div>

</div>

<div class="nav-links">

<a href="../includes/logout.php" class="logout-link">
<i class="fa-solid fa-right-from-bracket"></i>
<span>Logout</span>
</a>

</div>

</div>
