<?php
$allowed_roles = ['patient'];
include("../includes/auth_check.php");
?>

<?php include("../includes/header.php"); ?>

<body>

<?php include("../includes/sidebar.php"); ?>

<div class="main">

<?php include("../includes/topbar.php"); ?>

<?php if (!empty($_GET['cancelled'])): ?>
<div data-toast="Your appointment has been cancelled. That time slot is now available for booking again." data-toast-type="success"></div>
<?php endif; ?>

<?php if (!empty($_GET['rescheduled'])): ?>
<?php
    $res_msg = "Reschedule request submitted! Queue #" . (int)($_GET['queue'] ?? 0) . " at " . htmlspecialchars($_GET['time'] ?? '') . " on " . date('M j, Y', strtotime($_GET['date'] ?? date('Y-m-d'))) . ".";
?>
<div data-toast="<?php echo htmlspecialchars($res_msg); ?>" data-toast-type="success"></div>
<?php endif; ?>

<?php if (!empty($_GET['reschedule_error'])): ?>
<?php
$reschedule_errors = [
    'missing'      => 'Please choose an appointment and a new date.',
    'invalid_date' => 'Please select a valid reschedule date.',
    'past'         => 'You cannot reschedule to a past date.',
    'sunday'       => 'Appointments are available Monday to Saturday only.',
    'not_found'    => 'That appointment is no longer available for rescheduling.',
    'full'         => 'That date is fully booked. Please choose another date.',
];
$res_error_key = $_GET['reschedule_error'];
?>
<div data-toast="<?php echo htmlspecialchars($reschedule_errors[$res_error_key] ?? 'Unable to reschedule. Please try again.'); ?>" data-toast-type="error"></div>
<?php endif; ?>

<?php if (!empty($_SESSION['booking_confirmation'])): ?>
<?php
    $bc = $_SESSION['booking_confirmation'];
    unset($_SESSION['booking_confirmation']);
    $bc_msg = "Appointment booked! Queue #" . $bc['queue_number'] . " — " . $bc['time_label'] . " on " . date('M j, Y', strtotime($bc['date'])) . ".";
?>
<div data-toast="<?php echo htmlspecialchars($bc_msg); ?>" data-toast-type="success"></div>
<?php endif; ?>

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
            <h2><i class="fa-solid fa-calendar-check" style="color:#ffffff; margin-right:8px;"></i>My Appointments</h2>
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
                <th>Dentist</th>
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

<!-- ================= RESCHEDULE MODAL ================= -->

<div class="modal-overlay" id="patientRescheduleModal">
    <div class="modal-card reschedule-modal-card">
        <div class="modal-header">
            <h3><i class="fa-solid fa-calendar-pen"></i> Reschedule Appointment</h3>
            <button class="modal-close" type="button" onclick="closePatientRescheduleModal()">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <p id="rescheduleServiceText" class="modal-subtitle"></p>

        <form method="POST" action="reschedule_appointment.php" id="patientRescheduleForm" class="booking-form">
            <input type="hidden" name="appointment_id" id="rescheduleAppointmentId">

            <div class="form-group">
                <label><i class="fa-solid fa-calendar-days"></i> New Appointment Date</label>
                <input type="date" name="new_date" id="rescheduleDateInput" min="<?php echo date('Y-m-d'); ?>" required>
            </div>

            <div class="form-group">
                <div id="rescheduleQueueInfo" class="queue-card queue-card-empty">
                    <i class="fa-solid fa-calendar-day queue-card-icon"></i>
                    <div class="queue-card-text">Choose a date to preview the next open queue slot.</div>
                </div>
            </div>

            <button type="submit" class="primary-btn hover-glow" id="rescheduleSubmitBtn" disabled>
                <i class="fa-solid fa-calendar-pen"></i>
                Submit Request
            </button>
        </form>
    </div>
</div>

<!-- ================= AJAX SCRIPT ================= -->

<script>
let currentStatus = "all";
let searchQuery = "";

const searchBox = document.getElementById("searchBox");
const tableBody = document.getElementById("appointmentsTable");
const buttons = document.querySelectorAll(".filter-btn");
const rescheduleModal = document.getElementById("patientRescheduleModal");
const rescheduleDateInput = document.getElementById("rescheduleDateInput");
const rescheduleQueueInfo = document.getElementById("rescheduleQueueInfo");
const rescheduleSubmitBtn = document.getElementById("rescheduleSubmitBtn");
let activeRescheduleId = "";

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
            if (window.QydentraUI) {
                window.QydentraUI.refreshPagination(document);
            }
        })
        .catch(() => {
            tableBody.innerHTML = "<tr><td colspan='7' style='text-align:center; padding:20px;'>Unable to load appointments. Please try again.</td></tr>";
        });
}

function escapeHtml(str) {
    const div = document.createElement("div");
    div.textContent = str || "";
    return div.innerHTML;
}

function formatDateLabel(dateStr) {
    const d = new Date(dateStr + "T00:00:00");
    return d.toLocaleDateString("en-US", { weekday: "short", year: "numeric", month: "short", day: "numeric" });
}

function isSunday(dateStr) {
    return new Date(dateStr + "T00:00:00").getDay() === 0;
}

function setRescheduleMessage(type, icon, a, b) {
    // Backwards-compatible: setRescheduleMessage(type, icon, text)
    let title = '';
    let text = '';
    if (typeof b === 'undefined') {
        text = a || '';
    } else {
        title = a || '';
        text = b || '';
    }

    rescheduleQueueInfo.className = 'queue-card queue-card-' + type;

    if (title) {
        rescheduleQueueInfo.innerHTML =
            '<div class="queue-card-message"><i class="fa-solid ' + icon + '"></i>' +
            '<div>' +
                '<strong style="display:block; font-size:14px; margin-bottom:6px;">' + escapeHtml(title) + '</strong>' +
                '<div style="color:#cbd5e1; font-size:13px; line-height:1.45;">' + text + '</div>' +
            '</div>' +
            '</div>';
    } else {
        rescheduleQueueInfo.innerHTML = '<div class="queue-card-message"><i class="fa-solid ' + icon + '"></i><span>' + text + '</span></div>';
    }
}

function setRescheduleEmpty() {
    rescheduleSubmitBtn.disabled = true;
    rescheduleQueueInfo.className = "queue-card queue-card-empty";
    rescheduleQueueInfo.innerHTML = '<i class="fa-solid fa-calendar-day queue-card-icon"></i><div class="queue-card-text">Choose a date to preview the next open queue slot.</div>';
}

async function loadRescheduleQueue(date) {
    rescheduleSubmitBtn.disabled = true;
    rescheduleQueueInfo.className = "queue-card queue-card-loading";
    rescheduleQueueInfo.innerHTML = '<i class="fa-solid fa-spinner fa-spin queue-card-icon"></i><div class="queue-card-text">Checking the next available slot...</div>';

    try {
        const res = await fetch("get_slots.php?date=" + encodeURIComponent(date) + "&exclude_id=" + encodeURIComponent(activeRescheduleId));
        const data = await res.json();

        if (data.is_sunday) {
            setRescheduleMessage("error", "fa-calendar-xmark", "No Appointments on Sunday", data.error_msg || "Appointments are available Monday to Saturday only.");
            return;
        }

        if (data.fully_booked) {
            const totalSlots = data.total_slots || data.total || 'all';
            setRescheduleMessage("error", "fa-ban", "Fully Booked", 'All ' + totalSlots + ' queue slots for ' + formatDateLabel(date) + ' are taken. Please choose another date.');
            return;
        }

        if (data.already_booked) {
            setRescheduleMessage("warning", "fa-circle-exclamation", "Existing Appointment", 'You already have another active appointment on this date. Choose a different date to avoid conflicts, or <a href="appointments.php" target="_self" class="queue-link">view My Appointments</a> to manage it.');
            return;
        }

        rescheduleSubmitBtn.disabled = false;
        rescheduleQueueInfo.className = "queue-card queue-card-success";
        const remaining = data.remaining || 0;
        const total = data.total_slots || data.total || 10;
        const pct = Math.round((remaining / total) * 100);
        const fillCls = (remaining / total) > 0.5 ? 'fill-available' : 'fill-limited';

        rescheduleQueueInfo.innerHTML =
            '<div class="availability-badge availability-available"><i class="fa-solid fa-circle-check"></i> Slot Available</div>' +
            '<div class="queue-grid">' +
                '<div class="queue-stat queue-stat-highlight">' +
                    '<div class="queue-stat-label"><i class="fa-solid fa-hashtag"></i> Queue Number</div>' +
                    '<div class="queue-number-badge">Q#' + data.next_queue + '</div>' +
                    '<div class="queue-stat-sub">Your position in line</div>' +
                '</div>' +
                '<div class="queue-stat">' +
                    '<div class="queue-stat-label"><i class="fa-solid fa-clock"></i> Appointment Time</div>' +
                    '<div class="queue-stat-value">' + escapeHtml(data.next_time_label) + '</div>' +
                '</div>' +
                '<div class="queue-stat">' +
                    '<div class="queue-stat-label"><i class="fa-solid fa-calendar-day"></i> Date</div>' +
                    '<div class="queue-stat-value">' + formatDateLabel(date) + '</div>' +
                '</div>' +
                '<div class="queue-stat">' +
                    '<div class="queue-stat-label"><i class="fa-solid fa-users"></i> Remaining Slots</div>' +
                    '<div class="queue-stat-value">' + remaining + ' / ' + total + '</div>' +
                '</div>' +
            '</div>' +
            '<div class="slots-meter">' +
                '<div class="slots-meter-label"><span>Daily capacity</span><span>' + remaining + ' of ' + total + ' open</span></div>' +
                '<div class="slots-meter-track"><div class="slots-meter-fill ' + fillCls + '" style="width:' + pct + '%;"></div></div>' +
            '</div>' +
            '<div class="queue-explainer"><i class="fa-solid fa-circle-info"></i>' +
            '<span>This request will move your appointment to Pending review and reserve the next open queue slot for that date. Booking now will give you <strong>Q#' + data.next_queue + '</strong>, estimated for <strong>' + escapeHtml(data.next_time_label) + '</strong> on ' + formatDateLabel(date) + '.</span></div>';
    } catch (e) {
        setRescheduleMessage("error", "fa-circle-exclamation", "Unable to check availability. Please try again.");
    }
}

function openPatientRescheduleModal(id, service, currentDate) {
    activeRescheduleId = id;
    document.getElementById("rescheduleAppointmentId").value = id;
    document.getElementById("rescheduleServiceText").textContent = "Request a new date for " + service + ". Your queue will be recalculated automatically.";
    rescheduleDateInput.value = currentDate || "";
    rescheduleModal.classList.add("active");
    if (rescheduleDateInput.value) {
        loadRescheduleQueue(rescheduleDateInput.value);
    } else {
        setRescheduleEmpty();
    }
}

function closePatientRescheduleModal() {
    rescheduleModal.classList.remove("active");
    activeRescheduleId = "";
    rescheduleDateInput.value = "";
    setRescheduleEmpty();
}

rescheduleDateInput.addEventListener("change", function() {
    if (!this.value) {
        setRescheduleEmpty();
        return;
    }
    if (isSunday(this.value)) {
        this.value = "";
        setRescheduleMessage("error", "fa-calendar-xmark", "Appointments are available Monday to Saturday only.");
        return;
    }
    loadRescheduleQueue(this.value);
});

rescheduleModal.addEventListener("click", function(e) {
    if (e.target === rescheduleModal) closePatientRescheduleModal();
});

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
