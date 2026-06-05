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

<span>
Dashboard
</span>

</a>

<a href="appointments.php"
class="<?php echo ($current_page == 'appointments.php') ? 'active' : ''; ?>">

<i class="fa-solid fa-calendar-days"></i>

<span>
My Appointments
</span>

</a>

<a href="book_appointment.php"
class="<?php echo ($current_page == 'book_appointment.php') ? 'active' : ''; ?>">

<i class="fa-solid fa-calendar-plus"></i>

<span>
Book Appointment
</span>

</a>

<a href="queue.php"
class="<?php echo ($current_page == 'queue.php') ? 'active' : ''; ?>">

<i class="fa-solid fa-clock"></i>

<span>
Queue Status
</span>

</a>

<a href="notifications.php"
class="<?php echo ($current_page == 'notifications.php') ? 'active' : ''; ?>">

<i class="fa-solid fa-bell"></i>

<span>
Notifications
</span>

</a>

<a href="profile.php"
class="<?php echo ($current_page == 'profile.php') ? 'active' : ''; ?>">

<i class="fa-solid fa-user"></i>

<span>
My Profile
</span>

</a>

</div>

</div>

<div class="nav-links">

<a href="../includes/logout.php" class="logout-link">

<i class="fa-solid fa-right-from-bracket"></i>

<span>
Logout
</span>

</a>

</div>

</div>