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
