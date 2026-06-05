<?php
$allowed_roles = ['patient'];
include("../includes/auth_check.php");
?>

<?php include("../includes/header.php"); ?>

<body>

<?php include("../includes/sidebar.php"); ?>

<div class="main">

<?php include("../includes/topbar.php"); ?>

<!-- ================= TOOLBAR ================= -->

<div class="appointments-toolbar">

    <div class="filter-bar">
        <button class="filter-btn active" data-status="all">All</button>
        <button class="filter-btn" data-status="Pending">Pending</button>
        <button class="filter-btn" data-status="Approved">Approved</button>
        <button class="filter-btn" data-status="Completed">Completed</button>
        <button class="filter-btn" data-status="Cancelled">Cancelled</button>
    </div>

    <input
        type="text"
        id="searchBox"
        class="search-box"
        placeholder="Search service, status, date..."
    >

</div>

<!-- ================= TABLE ================= -->

<div class="table-container hover-glow">

    <div class="table-header">

        <div>
            <h2>My Appointments</h2>
            <p>All your dental appointments with status and queue tracking.</p>
        </div>

        <a href="book_appointment.php" class="table-btn">
            <i class="fa-solid fa-calendar-plus"></i>
            Book New
        </a>

    </div>

    <table>
        <thead>
            <tr>
                <th>Service</th>
                <th>Date</th>
                <th>Time</th>
                <th>Status</th>
                <th>Queue</th>
                <th>Action</th>
            </tr>
        </thead>

        <tbody id="appointmentsTable">
            <!-- AJAX results here -->
        </tbody>

    </table>

</div>

</div>

<!-- ================= AJAX SCRIPT ================= -->

<script>
let currentStatus = "all";
let searchQuery = "";

const searchBox = document.getElementById("searchBox");
const tableBody = document.getElementById("appointmentsTable");
const buttons = document.querySelectorAll(".filter-btn");

// debounce function
function debounce(func, delay) {
    let timeout;
    return function(...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), delay);
    };
}

// fetch data
function fetchAppointments() {
    fetch(`search_appointments.php?status=${currentStatus}&search=${encodeURIComponent(searchQuery)}`)
        .then(res => res.text())
        .then(data => {
            tableBody.innerHTML = data;
        });
}

// live search
searchBox.addEventListener("input", debounce(function(e) {
    searchQuery = e.target.value;
    fetchAppointments();
}, 300));

// filter buttons
buttons.forEach(btn => {
    btn.addEventListener("click", function() {

        buttons.forEach(b => b.classList.remove("active"));
        this.classList.add("active");

        currentStatus = this.dataset.status;
        fetchAppointments();
    });
});

// initial load
fetchAppointments();
</script>

</body>
</html>