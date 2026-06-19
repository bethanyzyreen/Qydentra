<?php
$current_page = basename($_SERVER['PHP_SELF']);

// Count unread receptionist notifications for badge
$sidebar_user_id = $_SESSION['user_id'] ?? 0;
$sidebar_user_id_safe = mysqli_real_escape_string($conn, $sidebar_user_id);
$unread_res = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM receptionist_notifications WHERE receptionist_id = '$sidebar_user_id_safe' AND LOWER(status) <> 'read'");
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

<a href="receptionist_notifications.php"
class="<?php echo ($current_page == 'receptionist_notifications.php') ? 'active' : ''; ?>">
<i class="fa-solid fa-bell"></i>
<span>Notifications</span>
</a>

</div>

</div>

<div class="nav-links">

<a href="javascript:void(0);" onclick="openLogoutModal();" class="logout-link">
<i class="fa-solid fa-right-from-bracket"></i>
<span>Logout</span>
</a>

</div>

</div>

<!-- LOGOUT CONFIRMATION MODAL -->
<div id="logoutModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.55); z-index:99999; align-items:center; justify-content:center;">
  <div style="background:linear-gradient(180deg, rgba(18,26,46,0.98), rgba(12,18,34,0.98)); border:1px solid rgba(96,165,250,0.15); border-radius:16px; padding:1.75rem 1.5rem 1.5rem; width:300px; text-align:center; box-shadow:0 20px 60px rgba(0,0,0,0.5); backdrop-filter:blur(10px);">
    <div style="width:44px; height:44px; border-radius:50%; background:rgba(239,68,68,0.12); display:flex; align-items:center; justify-content:center; margin:0 auto 1rem;">
      <i class="fa-solid fa-right-from-bracket" style="font-size:18px; color:#f87171;"></i>
    </div>
    <p style="font-size:15px; font-weight:600; color:#f8fafc; margin:0 0 0.4rem;">Confirm logout</p>
    <p style="font-size:13px; color:#94a3b8; margin:0 0 1.5rem; line-height:1.6;">Are you sure you want to log out?<br>You'll need to sign in again to continue.</p>
    <div style="display:flex; gap:8px;">
      <button onclick="closeLogoutModal();" style="flex:1; padding:9px 0; border-radius:8px; background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.1); color:#cbd5e1; font-size:13px; font-weight:500; cursor:pointer;">
        Cancel
      </button>
      <a href="../includes/logout.php" style="flex:1; padding:9px 0; border-radius:8px; background:rgba(239,68,68,0.12); border:1px solid rgba(239,68,68,0.25); color:#f87171; font-size:13px; font-weight:600; cursor:pointer; text-decoration:none; display:flex; align-items:center; justify-content:center;">
        Log out
      </a>
    </div>
  </div>
</div>

<script>
function openLogoutModal() {
  document.getElementById('logoutModal').style.display = 'flex';
}
function closeLogoutModal() {
  document.getElementById('logoutModal').style.display = 'none';
}
document.addEventListener('click', function(e) {
  var modal = document.getElementById('logoutModal');
  if (modal && e.target === modal) closeLogoutModal();
});
</script>
