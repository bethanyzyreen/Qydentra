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

<a href="patient_notifications.php"
class="<?php echo ($current_page == 'patient_notifications.php') ? 'active' : ''; ?>">

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

<a href="javascript:void(0);" onclick="openLogoutModal();" class="logout-link">

<i class="fa-solid fa-right-from-bracket"></i>

<span>
Logout
</span>

</a>

<script>
function openLogoutModal() {
    document.getElementById("logoutModal").style.display = "flex";
}
function closeLogoutModal() {
    document.getElementById("logoutModal").style.display = "none";
}
document.getElementById("logoutModal").addEventListener("click", function(e) {
    if (e.target === this) closeLogoutModal();
});
</script>

<!-- LOGOUT CONFIRMATION MODAL -->
<div class="logout-modal-overlay" id="logoutModal" style="display:none;">
    <div class="logout-modal-card">
        <div class="logout-modal-header">
            <h3><i class="fa-solid fa-question-circle"></i> Confirm Logout</h3>
        </div>
        <div class="logout-modal-body">
            <p>Are you sure you want to log out?</p>
            <p style="font-size: 13px; color: #94a3b8; margin-top: 10px;">You will need to log in again to access your account.</p>
        </div>
        <div class="logout-modal-footer">
            <button onclick="closeLogoutModal();" class="logout-modal-btn cancel">Cancel</button>
            <a href="../includes/logout.php" class="logout-modal-btn confirm">Yes, Logout</a>
        </div>
    </div>
</div>

<style>
.logout-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.6);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}
.logout-modal-card {
    background: linear-gradient(135deg, rgba(15,23,42,0.95), rgba(20,30,48,0.95));
    border: 1px solid rgba(96,165,250,0.2);
    border-radius: 20px;
    padding: 0;
    width: 90%;
    max-width: 400px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.4);
    backdrop-filter: blur(10px);
}
.logout-modal-header {
    padding: 24px 24px 16px;
    border-bottom: 1px solid rgba(96,165,250,0.1);
}
.logout-modal-header h3 {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
    color: #f8fafc;
}
.logout-modal-header i {
    color: #f59e0b;
    margin-right: 8px;
}
.logout-modal-body {
    padding: 20px 24px;
    color: #e2e8f0;
    font-size: 15px;
    line-height: 1.6;
}
.logout-modal-body p {
    margin: 0;
}
.logout-modal-footer {
    padding: 16px 24px;
    display: flex;
    gap: 12px;
    justify-content: flex-end;
    border-top: 1px solid rgba(96,165,250,0.1);
}
.logout-modal-btn {
    padding: 10px 20px;
    border: none;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
    display: inline-block;
}
.logout-modal-btn.cancel {
    background: rgba(255,255,255,0.08);
    color: #d1d5db;
    border: 1px solid rgba(255,255,255,0.1);
}
.logout-modal-btn.cancel:hover {
    background: rgba(255,255,255,0.12);
    color: #f8fafc;
}
.logout-modal-btn.confirm {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
    border: 1px solid rgba(239,68,68,0.3);
}
.logout-modal-btn.confirm:hover {
    background: linear-gradient(135deg, #dc2626, #b91c1c);
    box-shadow: 0 0 20px rgba(239,68,68,0.3);
}
</style>

</div>

</div>