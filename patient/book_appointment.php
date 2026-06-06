<?php
$allowed_roles = ['patient'];
include("../includes/auth_check.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $patient_id = $_SESSION['user_id'];

    $service = mysqli_real_escape_string($conn, $_POST['service']);
    $date = mysqli_real_escape_string($conn, $_POST['date']);
    $time = mysqli_real_escape_string($conn, $_POST['time']);
    $notes = mysqli_real_escape_string($conn, $_POST['notes']);

    // FIX: map service to description
    $service_desc = "";

    switch ($service) {
        case "Teeth Cleaning":
            $service_desc = "Routine Dental Care";
            break;

        case "Tooth Extraction":
            $service_desc = "Tooth Removal";
            break;

        case "Dental Filling":
            $service_desc = "Tooth Restoration";
            break;

        case "Braces Consultation":
            $service_desc = "Orthodontic Assessment";
            break;

        case "Dental Checkup":
            $service_desc = "General Oral Exam";
            break;

        default:
            $service_desc = "General Dental Service";
    }

    $sql = "INSERT INTO appointments 
    (patient_id, service_type, service_desc, appointment_date, appointment_time, notes, status)
    VALUES 
    ('$patient_id', '$service', '$service_desc', '$date', '$time', '$notes', 'Pending')";

    if (mysqli_query($conn, $sql)) {

        // Notify the patient
        $patient_name = mysqli_real_escape_string($conn, $_SESSION['full_name'] ?? '');
        if(empty($patient_name)){
            $patient_res = mysqli_query($conn, "SELECT full_name FROM patients WHERE patient_id = '$patient_id'");
            $patient_row = mysqli_fetch_assoc($patient_res);
            $patient_name = mysqli_real_escape_string($conn, $patient_row['full_name']);
        }
        $notif = notification_patient_request_submitted($patient_name);
        $notif_esc = mysqli_real_escape_string($conn, $notif);
        mysqli_query($conn, "INSERT INTO patient_notifications (patient_id, message) VALUES ('$patient_id', '$notif_esc')");

        // Notify all receptionists via receptionist_notifications table
        $recep_title = "New Appointment Booked";
        $recep_msg   = notification_receptionist_new_appointment_booked($patient_name, $service, $date, $time);
        $recep_title_esc = mysqli_real_escape_string($conn, $recep_title);
        $recep_msg_esc   = mysqli_real_escape_string($conn, $recep_msg);

        $recep_result = mysqli_query($conn, "SELECT staff_id AS user_id FROM staffs WHERE role = 'receptionist'");
        while ($recep_row = mysqli_fetch_assoc($recep_result)) {
            $recep_id = $recep_row['user_id'];
            mysqli_query($conn, "INSERT INTO receptionist_notifications
                (receptionist_id, title, message, type, status)
                VALUES ('$recep_id', '$recep_title_esc', '$recep_msg_esc', 'Appointment', 'Unread')");
        }

        header("Location: appointments.php");
        exit();

    } else {
        die("DB ERROR: " . mysqli_error($conn));
    }
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

        <form method="POST" class="booking-form">

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

</body>
</html>