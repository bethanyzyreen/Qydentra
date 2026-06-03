<?php
include("../config/database.php");
include("../includes/auth_check.php");

$user_id = $_SESSION['user_id'];

$status = $_GET['status'] ?? 'all';
$search = trim($_GET['search'] ?? '');

$sql = "SELECT * FROM appointments WHERE patient_id='$user_id'";

// ================= STATUS FILTER =================
if ($status !== 'all') {
    $sql .= " AND status='$status'";
}

// ================= SEARCH =================
if (!empty($search)) {

    $search = mysqli_real_escape_string($conn, $search);

    $sql .= " AND (
        service_type LIKE '%$search%'
        OR service_desc LIKE '%$search%'
        OR status LIKE '%$search%'
        OR appointment_date LIKE '%$search%'
        OR appointment_time LIKE '%$search%'
        OR queue_number LIKE '%$search%'

        -- formatted date search (MONTH name)
        OR DATE_FORMAT(appointment_date, '%M') LIKE '%$search%'
        OR DATE_FORMAT(appointment_date, '%M %d') LIKE '%$search%'
        OR DATE_FORMAT(appointment_date, '%M %d %Y') LIKE '%$search%'

        -- time search (AM/PM format match)
        OR DATE_FORMAT(appointment_time, '%h:%i %p') LIKE '%$search%'
        OR DATE_FORMAT(appointment_time, '%l:%i %p') LIKE '%$search%'
    )";
}

// ================= ORDER =================
$sql .= " ORDER BY appointment_date DESC, appointment_time DESC";

$result = mysqli_query($conn, $sql);

// ================= NO RESULTS =================
if (mysqli_num_rows($result) == 0) {
    echo "<tr><td colspan='6' style='text-align:center; padding:20px;'>No appointments found.</td></tr>";
    exit;
}

// ================= OUTPUT ROWS =================
while ($row = mysqli_fetch_assoc($result)) {

    $service = strtolower($row['service_type']);

    $icon = "fa-tooth";
    $serviceClass = "cleaning";
    $serviceDesc = "Routine Dental Care";

    if (str_contains($service, "consultation")) {
        $icon = "fa-user-doctor";
        $serviceClass = "consultation";
        $serviceDesc = "Initial Checkup";
    }
    elseif (str_contains($service, "cleaning")) {
        $icon = "fa-tooth";
        $serviceClass = "cleaning";
        $serviceDesc = "Routine Dental Care";
    }
    elseif (str_contains($service, "checkup")) {
        $icon = "fa-stethoscope";
        $serviceClass = "checkup";
        $serviceDesc = "General Oral Exam";
    }
    elseif (str_contains($service, "filling")) {
        $icon = "fa-syringe";
        $serviceClass = "filling";
        $serviceDesc = "Tooth Restoration";
    }
    elseif (str_contains($service, "braces")) {
        $icon = "fa-teeth";
        $serviceClass = "braces";
        $serviceDesc = "Orthodontic Assessment";
    }
    elseif (str_contains($service, "extraction")) {
        $icon = "fa-teeth-open";
        $serviceClass = "extraction";
        $serviceDesc = "Tooth Removal";
    }

    echo "
    <tr>

        <td>
            <div class='service-info'>
                <div class='service-icon $serviceClass'>
                    <i class='fa-solid $icon'></i>
                </div>
                <div>
                    <h4>{$row['service_type']}</h4>
                    <p>$serviceDesc</p>
                </div>
            </div>
        </td>

        <td>
            <div class='table-date'>
                <i class='fa-solid fa-calendar-days'></i>
                " . date("F d, Y", strtotime($row['appointment_date'])) . "
            </div>
        </td>

        <td>
            <div class='table-date'>
                <i class='fa-solid fa-clock'></i>
                " . date("g:i A", strtotime($row['appointment_time'])) . "
            </div>
        </td>

        <td>
            <div class='status-pill " . strtolower($row['status']) . "'>
                <i class='fa-solid fa-circle-check'></i>
                " . ucfirst($row['status']) . "
            </div>
        </td>

        <td>
            <div class='queue-pill'>
                " . (!empty($row['queue_number']) ? "#".$row['queue_number'] : "—") . "
            </div>
        </td>

        <td class='action-cell'>
        ";

        if(
            $row['status'] == 'Pending' ||
            $row['status'] == 'Approved'
        ){

            echo "
            <a
                href='cancel_appointment.php?id={$row['id']}'
                class='cancel-btn'
                onclick='return confirm(\"Cancel this appointment?\")'
            >
                Cancel
            </a>
            ";

        }else{

            echo "<span class='action-empty'>—</span>";

        }

        echo "
        </td>
        </tr>
        ";
    }
?>