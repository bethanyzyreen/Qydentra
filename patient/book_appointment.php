<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
?>
<?php
$allowed_roles = ['patient'];
include("../includes/auth_check.php");
require_once(__DIR__ . "/../includes/id_helper.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $patient_id = $_SESSION['user_id'];  // VARCHAR e.g. PT001

    $service = mysqli_real_escape_string($conn, $_POST['service']);
    $date    = mysqli_real_escape_string($conn, $_POST['date']);
    $time    = mysqli_real_escape_string($conn, $_POST['time']);
    $notes   = mysqli_real_escape_string($conn, $_POST['notes'] ?? '');

    // ── Past date / time validation ──────────────────────────────────────────
    $now           = new DateTime('now');
    $selected_dt   = new DateTime($date . ' ' . $time);
    if ($selected_dt <= $now) {
        $booking_error = "You cannot book an appointment in the past. Please choose a future date and time.";
    }
    // ─────────────────────────────────────────────────────────────────────────

    $service_desc = "";
    switch ($service) {
        case "Teeth Cleaning":       $service_desc = "Routine Dental Care";     break;
        case "Tooth Extraction":     $service_desc = "Tooth Removal";           break;
        case "Dental Filling":       $service_desc = "Tooth Restoration";       break;
        case "Braces Consultation":  $service_desc = "Orthodontic Assessment";  break;
        case "Dental Checkup":       $service_desc = "General Oral Exam";       break;
        default:                     $service_desc = "General Dental Service";
    }

    // Trigger auto-assigns appointment_id (e.g. AP001)
    if (!isset($booking_error)) {
    $sql = "INSERT INTO appointments
    (patient_id, service_type, service_desc, appointment_date, appointment_time, notes, status)
    VALUES
    ('$patient_id', '$service', '$service_desc', '$date', '$time', '$notes', 'Pending')";

    $result = mysqli_query($conn, $sql);
    if ($result) {

        // Retrieve the newly created appointment_id (VARCHAR)
        $new_appt_id = get_last_inserted_id($conn, 'appointments');

        $patient_name = $_SESSION['full_name'] ?? '';
        if (empty($patient_name)) {
            $pid_esc = mysqli_real_escape_string($conn, $patient_id);
            $prow = mysqli_fetch_assoc(mysqli_query($conn,
                "SELECT full_name FROM patients WHERE patient_id = '$pid_esc'"));
            $patient_name = $prow['full_name'] ?? '';
        }

        notify_patient(
            $conn, $patient_id,
            'Appointment Request Submitted',
            notification_patient_request_submitted($patient_name),
            'Appointment', $new_appt_id
        );

        notify_receptionists(
            $conn,
            'New Appointment Booked',
            notification_receptionist_new_appointment_booked($patient_name, $service, $date, $time),
            'Appointment', $new_appt_id
        );

        header("Location: appointments.php");
        exit();

    } else {
        die("DB ERROR: " . mysqli_error($conn));
    }
    } // end past-date check
}
?>

<?php include("../includes/header.php"); ?>

<body>

<?php include("../includes/sidebar.php"); ?>

<div class="main">

<?php include("../includes/topbar.php"); ?>

<div class="booking-layout">

    <!-- BOOK APPOINTMENT CARD -->

    <div class="table-container hover-glow">

        <div class="table-header">

            <div>
                <h2>Book Appointment</h2>

                <p>
                    Schedule your next dental visit quickly and easily.
                </p>
            </div>

        </div>

        <?php if (isset($booking_error)): ?>
        <div class="pat-alert pat-alert-error" style="margin-bottom:16px;">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <?php echo htmlspecialchars($booking_error); ?>
        </div>
        <?php endif; ?>

        <form method="POST" class="booking-form" id="bookingForm">

            <div class="form-group">

                <label>
                    <i class="fa-solid fa-tooth"></i>
                    Dental Service
                </label>

                <select name="service" required>

                    <option value="">Select Service</option>

                    <option>Teeth Cleaning</option>
                    <option>Tooth Extraction</option>
                    <option>Dental Filling</option>
                    <option>Braces Consultation</option>
                    <option>Dental Checkup</option>

                </select>

            </div>

            <div class="form-row">

                <div class="form-group">

                    <label>
                        <i class="fa-solid fa-calendar-days"></i>
                        Date
                    </label>

                    <input
                    type="date"
                    name="date"
                    min="<?php echo date('Y-m-d'); ?>"
                    required>

                </div>

                <div class="form-group">

                    <label>
                        <i class="fa-solid fa-clock"></i>
                        Time
                    </label>

                    <input
                    type="time"
                    name="time"
                    min="08:00"
                    max="17:00"
                    required>

                </div>

            </div>

            <div class="form-group">

                <label>
                    <i class="fa-solid fa-note-sticky"></i>
                    Notes
                </label>

                <textarea
                name="notes"
                placeholder="Describe any dental concerns or special requests..."
                ></textarea>

            </div>

            <button
            type="submit"
            name="submit"
            class="primary-btn hover-glow">

                <i class="fa-solid fa-calendar-plus"></i>

                Book Appointment

            </button>

        </form>

    </div>

    <!-- APPOINTMENT GUIDE CARD -->

    <div class="table-container guide-card hover-glow">

        <div class="table-header">

            <div>
                <h2>Appointment Guide</h2>

                <p>
                    Important information about your appointment.
                </p>
            </div>

        </div>

        <div class="guide-item">

            <div class="guide-icon">
                <i class="fa-solid fa-clock"></i>
            </div>

            <div>
                <h4>Clinic Hours</h4>

                <p>
                    Monday - Saturday<br>
                    8:00 AM - 5:00 PM
                </p>
            </div>

        </div>

        <div class="guide-item">

            <div class="guide-icon">
                <i class="fa-solid fa-circle-info"></i>
            </div>

            <div>
                <h4>Booking Notes</h4>

                <p>
                    Appointment requests are reviewed by the clinic before approval.
                </p>
            </div>

        </div>

        <div class="guide-item">

            <div class="guide-icon">
                <i class="fa-solid fa-clipboard-check"></i>
            </div>

            <div>
                <h4>Requirements</h4>

                <p>
                    Bring a valid ID and arrive at least 15 minutes before your schedule.
                </p>
            </div>

        </div>

        <div class="guide-item">

            <div class="guide-icon">
                <i class="fa-solid fa-phone"></i>
            </div>

            <div>
                <h4>Need Help?</h4>

                <p>
                    Contact the clinic if you need to reschedule or cancel an appointment.
                </p>
            </div>

        </div>

    </div>

</div>

</div>

<script>
// ── Client-side past date/time guard ─────────────────────────────────────────
(function () {
    const dateInput = document.querySelector('input[name="date"]');
    const timeInput = document.querySelector('input[name="time"]');
    const form      = document.getElementById('bookingForm');

    function setMinTime() {
        const today   = new Date();
        const todayStr = today.toISOString().split('T')[0];
        if (dateInput.value === todayStr) {
            // Block times in the past for today
            const hh = String(today.getHours()).padStart(2, '0');
            const mm = String(today.getMinutes()).padStart(2, '0');
            timeInput.min = hh + ':' + mm;
        } else {
            timeInput.min = '08:00';
        }
    }

    dateInput.addEventListener('change', setMinTime);
    setMinTime();

    form.addEventListener('submit', function (e) {
        const selected = new Date(dateInput.value + 'T' + timeInput.value);
        if (selected <= new Date()) {
            e.preventDefault();
            alert('You cannot book an appointment in the past. Please choose a future date and time.');
        }
    });
})();
</script>

</body>
</html>