<?php
$current_page = basename($_SERVER['PHP_SELF']);

// Count unread receptionist notifications for badge
$sidebar_user_id = $_SESSION['user_id'] ?? 0;
$unread_res = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM receptionist_notifications WHERE receptionist_id = '$sidebar_user_id' AND status = 'Unread'");
$unread_row = mysqli_fetch_assoc($unread_res);
$unread_count = (int)$unread_row['cnt'];
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

<a href="pending_appointments.php"
class="<?php echo ($current_page == 'pending_appointments.php') ? 'active' : ''; ?>">
<i class="fa-solid fa-clock"></i>
<span>Pending Appointments</span>
</a>

<a href="appointment_management.php"
class="<?php echo ($current_page == 'appointment_management.php') ? 'active' : ''; ?>">
<i class="fa-solid fa-calendar-check"></i>
<span>Appointment Management</span>
</a>

<a href="queue_management.php"
class="<?php echo ($current_page == 'queue_management.php') ? 'active' : ''; ?>">
<i class="fa-solid fa-users"></i>
<span>Queue Management</span>
</a>

<a href="walkin_registration.php"
class="<?php echo ($current_page == 'walkin_registration.php') ? 'active' : ''; ?>">
<i class="fa-solid fa-person-walking-arrow-right"></i>
<span>Walk-in Registration</span>
</a>

<a href="patient_records.php"
class="<?php echo ($current_page == 'patient_records.php') ? 'active' : ''; ?>">
<i class="fa-solid fa-folder-open"></i>
<span>Patient Records</span>
</a>

<a href="notifications.php"
class="<?php echo ($current_page == 'notifications.php') ? 'active' : ''; ?>">
<i class="fa-solid fa-bell"></i>
<span>Notifications</span>
<?php if ($unread_count > 0): ?>
<span class="notif-badge"><?php echo $unread_count; ?></span>
<?php endif; ?>
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
