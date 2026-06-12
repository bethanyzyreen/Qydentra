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
