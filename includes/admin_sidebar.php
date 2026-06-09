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

<a href="user_management.php"
class="<?php echo ($current_page == 'user_management.php') ? 'active' : ''; ?>">
<i class="fa-solid fa-users"></i>
<span>User Management</span>
</a>

<a href="staff_dentist_management.php"
class="<?php echo ($current_page == 'staff_dentist_management.php') ? 'active' : ''; ?>">
<i class="fa-solid fa-user-doctor"></i>
<span>Staff & Dentist Management</span>
</a>

<a href="appointment_reports.php"
class="<?php echo ($current_page == 'appointment_reports.php') ? 'active' : ''; ?>">
<i class="fa-solid fa-chart-line"></i>
<span>Appointment Reports</span>
</a>

<a href="queue_reports.php"
class="<?php echo ($current_page == 'queue_reports.php') ? 'active' : ''; ?>">
<i class="fa-solid fa-users-viewfinder"></i>
<span>Queue Reports</span>
</a>

<a href="service_management.php"
class="<?php echo ($current_page == 'service_management.php') ? 'active' : ''; ?>">
<i class="fa-solid fa-briefcase-medical"></i>
<span>Service Management</span>
</a>

<a href="dentist_schedule_management.php"
class="<?php echo ($current_page == 'dentist_schedule_management.php') ? 'active' : ''; ?>">
<i class="fa-solid fa-calendar-days"></i>
<span>Dentist Schedule Management</span>
</a>

<a href="notifications.php"
class="<?php echo ($current_page == 'notifications.php') ? 'active' : ''; ?>">
<i class="fa-solid fa-bell"></i>
<span>Notifications</span>
</a>

<a href="audit_logs.php"
class="<?php echo ($current_page == 'audit_logs.php') ? 'active' : ''; ?>">
<i class="fa-solid fa-file-lines"></i>
<span>Audit Logs</span>
</a>

<a href="system_settings.php"
class="<?php echo ($current_page == 'system_settings.php') ? 'active' : ''; ?>">
<i class="fa-solid fa-sliders"></i>
<span>System Settings</span>
</a>

</div>

</div>

<div class="nav-links">

<a href="../includes/logout.php" class="logout-link">
<i class="fa-solid fa-right-from-bracket"></i>
<span>Logout</span>
</a>

</div>

</div>
