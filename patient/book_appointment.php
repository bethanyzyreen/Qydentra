<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
?>
<?php
$allowed_roles = ['patient'];
include("../includes/auth_check.php");
require_once(__DIR__ . "/../includes/id_helper.php");

// Queue Number => [time value (HH:MM 24h), display label]
$queue_schedule = [
    1  => ['08:00', '8:00 AM'],
    2  => ['09:00', '9:00 AM'],
    3  => ['10:00', '10:00 AM'],
    4  => ['11:00', '11:00 AM'],
    5  => ['12:00', '12:00 PM'],
    6  => ['13:00', '1:00 PM'],
    7  => ['14:00', '2:00 PM'],
    8  => ['15:00', '3:00 PM'],
    9  => ['16:00', '4:00 PM'],
    10 => ['17:00', '5:00 PM'],
];

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $patient_id = $_SESSION['user_id'];  // VARCHAR e.g. PT001

    $service = mysqli_real_escape_string($conn, $_POST['service']);
    $date    = mysqli_real_escape_string($conn, $_POST['date']);
    $notes   = mysqli_real_escape_string($conn, $_POST['notes'] ?? '');

    $selected_date = $date;

    // ── Basic date validation ────────────────────────────────────────────────
    $now = new DateTime('now');
    $today = new DateTime($now->format('Y-m-d'));
    $picked_date = null;
    try {
        $picked_date = new DateTime($date);
    } catch (Exception $e) {
        $picked_date = null;
    }

    if ($date === '' || $picked_date === null) {
        $booking_error = "Please select a valid date.";
    } elseif ($picked_date < $today) {
        $booking_error = "You cannot book an appointment in the past. Please choose a future date.";
    } elseif ($picked_date->format('N') == 7) {
        $booking_error = "Appointments are available Monday to Saturday only.";
    }

    // ── Duplicate booking check (same patient, same date) ──────────────────────
    if (!isset($booking_error)) {
        $pid_esc = mysqli_real_escape_string($conn, $patient_id);
        $dup_sql = "SELECT COUNT(*) AS cnt FROM appointments
            WHERE appointment_date = '$date'
              AND patient_id = '$pid_esc'
              AND status NOT IN ('Cancelled')";
        $dup_check = mysqli_fetch_assoc(mysqli_query($conn, $dup_sql));
        if ($dup_check && $dup_check['cnt'] > 0) {
            $booking_error = "You already have an appointment booked on this date. Please choose another date.";
        }
    }

    // ── Determine the next available queue number for this date ────────────────
    $queue_number  = null;
    $time          = null;
    $time_label    = null;

    if (!isset($booking_error)) {

        // Get all booked (non-cancelled) queue numbers for this date
        $booked_result = mysqli_query($conn,
            "SELECT queue_number FROM appointments
             WHERE appointment_date = '$date'
               AND status NOT IN ('Cancelled')"
        );
        $booked_queues = [];
        while ($r = mysqli_fetch_assoc($booked_result)) {
            $booked_queues[(int)$r['queue_number']] = true;
        }

        foreach ($queue_schedule as $qnum => $info) {
            list($time_val, $label) = $info;
            $slot_dt = new DateTime($date . ' ' . $time_val);
            if (!isset($booked_queues[$qnum]) && $slot_dt > $now) {
                $queue_number = $qnum;
                $time         = $time_val;
                $time_label   = $label;
                break;
            }
        }

        if ($queue_number === null) {
            $booking_error = "This date is Fully Booked. Please choose another date.";
        }
    }

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
    (patient_id, service_type, service_desc, appointment_date, appointment_time, notes, status, queue_number)
    VALUES
    ('$patient_id', '$service', '$service_desc', '$date', '$time', '$notes', 'Pending', '$queue_number')";

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

        // Flash confirmation details for the appointments page
        $_SESSION['booking_confirmation'] = [
            'queue_number' => $queue_number,
            'time_label'   => $time_label,
            'date'         => $date,
        ];

        header("Location: appointments.php");
        exit();

    } else {
        die("DB ERROR: " . mysqli_error($conn));
    }
    } // end queue assignment check
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
                <h2><i class="fa-solid fa-calendar-plus" style="color:#ffffff; margin-right:8px;"></i>Book Appointment</h2>

                <p>
                    Schedule your next dental visit quickly and easily.
                </p>
            </div>

        </div>

        <?php if (isset($booking_error)): ?>
        <div data-toast="<?php echo htmlspecialchars($booking_error); ?>" data-toast-type="error"></div>
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

            <div class="form-group">

                    <label>
                        <i class="fa-solid fa-calendar-days"></i>
                        Date
                    </label>

                    <input
                    type="date"
                    name="date"
                    id="dateInput"
                    min="<?php echo date('Y-m-d'); ?>"
                    value="<?php echo htmlspecialchars($selected_date ?? ''); ?>"
                    required>

                    <p class="field-hint">
                        <i class="fa-solid fa-circle-info"></i>
                        No time selection needed. Your queue number and appointment time are assigned automatically. Appointments are available Monday to Saturday.
                    </p>

                </div>

                <div class="form-group">

                    <div id="queueInfo" class="queue-card queue-card-empty">
                        <i class="fa-solid fa-calendar-day queue-card-icon"></i>
                        <div class="queue-card-text">Select a date to see slot availability and your queue assignment.</div>
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
            id="bookingSubmitBtn"
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
                <h2><i class="fa-solid fa-circle-info" style="color:#ffffff; margin-right:8px;"></i>Appointment Guide</h2>

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
                <i class="fa-solid fa-list-ol"></i>
            </div>

            <div>
                <h4>Queue-Based Scheduling</h4>

                <p>
                    Your appointment time is automatically assigned based on the next available queue number for your chosen date (Q#1 = 8:00 AM ... Q#10 = 5:00 PM).
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

<style>
.field-hint {
    color: #9ca3af;
    font-size: 12.5px;
    margin-top: 8px;
    line-height: 1.5;
    display: flex;
    gap: 6px;
    align-items: flex-start;
}
.field-hint i { margin-top: 2px; color: #818cf8; }

/* ── Queue / Availability Card ───────────────────────────────────────── */
.queue-card {
    margin-top: 4px;
    padding: 16px;
    border-radius: 14px;
    border: 1px solid rgba(255,255,255,0.12);
    background: rgba(255,255,255,0.04);
    color: #e2e8f0;
    font-family: 'Poppins', sans-serif;
}
.queue-card-empty,
.queue-card-loading {
    display: flex;
    align-items: center;
    gap: 12px;
    color: #9ca3af;
    font-size: 13px;
    padding: 18px 16px;
}
.queue-card-icon { font-size: 20px; color: #2563EB; flex-shrink: 0; }

.queue-card-message {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    font-size: 13.5px;
    font-weight: 500;
    line-height: 1.5;
    padding: 6px 2px;
}
.queue-card-message i { font-size: 18px; margin-top: 1px; }

.queue-card-error { border-color: rgba(239,68,68,0.35); background: rgba(239,68,68,0.08); }
.queue-card-error .queue-card-message { color: #fca5a5; }

.queue-card-warning { border-color: rgba(234,179,8,0.35); background: rgba(234,179,8,0.08); }
.queue-card-warning .queue-card-message { color: #fde68a; }

/* Availability badge */
.availability-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 12px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 600;
    letter-spacing: 0.02em;
    margin-bottom: 14px;
}
.availability-available { background: rgba(34,197,94,0.15); color: #4ade80; border: 1px solid rgba(34,197,94,0.35); }
.availability-limited   { background: rgba(234,179,8,0.15); color: #fbbf24; border: 1px solid rgba(234,179,8,0.35); }
.availability-full      { background: rgba(239,68,68,0.15); color: #f87171; border: 1px solid rgba(239,68,68,0.35); }

.queue-card-success { border-color: rgba(37,99,235,0.3); background: rgba(37,99,235,0.06); }

/* Stat grid */
.queue-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 10px;
}
.queue-stat {
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 10px;
    padding: 10px 12px;
}
.queue-stat-label {
    font-size: 11px;
    color: #9ca3af;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    margin-bottom: 4px;
    display: flex;
    align-items: center;
    gap: 5px;
}
.queue-stat-value {
    font-size: 16px;
    font-weight: 600;
    color: #f1f5f9;
}
.queue-stat-highlight {
    background: linear-gradient(135deg, rgba(96,165,250,0.25), rgba(37,99,235,0.18));
    border-color: rgba(37,99,235,0.5);
}
.queue-number-badge {
    font-size: 24px;
    font-weight: 800;
    color: #bfdbfe;
    letter-spacing: 0.02em;
}
.queue-stat-sub {
    font-size: 11px;
    color: #93c5fd;
    margin-top: 2px;
    font-weight: 500;
}

/* Explainer */
.queue-explainer {
    margin-top: 14px;
    padding-top: 12px;
    border-top: 1px dashed rgba(255,255,255,0.1);
    font-size: 12px;
    color: #9ca3af;
    line-height: 1.6;
    display: flex;
    gap: 8px;
    align-items: flex-start;
}
.queue-explainer i { margin-top: 2px; color: #60A5FA; }

/* Remaining slots meter */
.slots-meter {
    margin-top: 14px;
}
.slots-meter-track {
    width: 100%;
    height: 8px;
    border-radius: 999px;
    background: rgba(255,255,255,0.08);
    overflow: hidden;
    margin-top: 6px;
}
.slots-meter-fill {
    height: 100%;
    border-radius: 999px;
}
.slots-meter-fill.fill-available { background: linear-gradient(90deg,#22c55e,#4ade80); }
.slots-meter-fill.fill-limited   { background: linear-gradient(90deg,#f59e0b,#fbbf24); }
.slots-meter-fill.fill-full      { background: linear-gradient(90deg,#ef4444,#f87171); }
.slots-meter-label {
    display: flex;
    justify-content: space-between;
    font-size: 12px;
    color: #9ca3af;
}
</style>

<script>
(function () {
    const dateInput  = document.getElementById('dateInput');
    const queueInfo  = document.getElementById('queueInfo');
    const form       = document.getElementById('bookingForm');

    let canBook = false;

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function formatDateLabel(dateStr) {
        const d = new Date(dateStr + 'T00:00:00');
        return d.toLocaleDateString('en-US', { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' });
    }

    function renderEmpty() {
        queueInfo.className = 'queue-card queue-card-empty';
        queueInfo.innerHTML =
            '<i class="fa-solid fa-calendar-day queue-card-icon"></i>' +
            '<div class="queue-card-text">Select a date to see slot availability and your queue assignment.</div>';
    }

    function renderLoading() {
        queueInfo.className = 'queue-card queue-card-loading';
        queueInfo.innerHTML =
            '<i class="fa-solid fa-spinner fa-spin queue-card-icon"></i>' +
            '<div class="queue-card-text">Checking availability…</div>';
    }

    function renderMessage(type, icon, text) {
        queueInfo.className = 'queue-card queue-card-' + type;
        queueInfo.innerHTML =
            '<div class="queue-card-message"><i class="fa-solid ' + icon + '"></i><span>' + text + '</span></div>';
    }

    function renderSuccess(data, dateStr) {
        const level = data.availability_level; // available | limited | full
        const remaining = data.remaining;
        const total = data.total_slots;
        const pct = Math.round((remaining / total) * 100);

        const badgeMap = {
            available: { cls: 'availability-available', icon: 'fa-circle-check', label: 'Slots Available' },
            limited:   { cls: 'availability-limited',   icon: 'fa-triangle-exclamation', label: 'Limited Slots' },
        };
        const fillMap = {
            available: 'fill-available',
            limited:   'fill-limited',
        };
        const badge = badgeMap[level] || badgeMap.available;
        const fillCls = fillMap[level] || 'fill-available';

        queueInfo.className = 'queue-card queue-card-success';
        queueInfo.innerHTML =
            '<div class="availability-badge ' + badge.cls + '"><i class="fa-solid ' + badge.icon + '"></i> ' + badge.label + '</div>' +
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
                    '<div class="queue-stat-value">' + formatDateLabel(dateStr) + '</div>' +
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
            '<span>Queue numbers are assigned automatically in order — Q#1 is 8:00 AM and each next number adds one hour. ' +
            'Booking now will give you <strong>Q#' + data.next_queue + '</strong>, estimated for <strong>' + escapeHtml(data.next_time_label) + '</strong> on ' + formatDateLabel(dateStr) + '.</span></div>';
    }

    async function loadQueueInfo(date) {
        canBook = false;
        renderLoading();

        try {
            const res  = await fetch('get_slots.php?date=' + encodeURIComponent(date));
            const data = await res.json();

            if (data.error) {
                renderMessage('error', 'fa-circle-exclamation', 'Unable to check availability. Please try again.');
                return;
            }

            if (data.is_sunday) {
                renderMessage('error', 'fa-calendar-xmark', data.error_msg || 'Appointments are available Monday to Saturday only.');
                return;
            }

            if (data.already_booked) {
                renderMessage('warning', 'fa-circle-exclamation', 'You already have an appointment on this date. Please choose another date.');
                return;
            }

            if (data.fully_booked) {
                queueInfo.className = 'queue-card queue-card-success';
                queueInfo.innerHTML =
                    '<div class="availability-badge availability-full"><i class="fa-solid fa-ban"></i> Fully Booked</div>' +
                    '<div class="queue-card-message" style="padding-top:0;">' +
                    '<i class="fa-solid fa-circle-exclamation" style="color:#f87171;"></i>' +
                    '<span>All ' + data.total_slots + ' queue slots for ' + formatDateLabel(date) + ' are taken. Please choose another date.</span>' +
                    '</div>';
                return;
            }

            canBook = true;
            renderSuccess(data, date);

        } catch (e) {
            renderMessage('error', 'fa-circle-exclamation', 'Unable to check availability. Please try again.');
        }
    }

    // Block Sunday selection immediately on change (before hitting the server)
    function isSunday(dateStr) {
        const d = new Date(dateStr + 'T00:00:00');
        return d.getDay() === 0; // 0 = Sunday
    }

    // Poll every 15s to reflect real-time changes
    let pollInterval = null;
    dateInput.addEventListener('change', function () {
        clearInterval(pollInterval);

        if (!this.value) {
            canBook = false;
            renderEmpty();
            return;
        }

        if (isSunday(this.value)) {
            canBook = false;
            renderMessage('error', 'fa-calendar-xmark', 'Appointments are available Monday to Saturday only.');
            showToast('Appointments are available Monday to Saturday only.', 'error');
            this.value = '';
            return;
        }

        loadQueueInfo(this.value);
        pollInterval = setInterval(function () {
            if (dateInput.value) loadQueueInfo(dateInput.value);
        }, 15000);
    });

    // Auto-load if date is pre-filled (e.g. after failed POST)
    if (dateInput.value) {
        if (isSunday(dateInput.value)) {
            dateInput.value = '';
            renderEmpty();
        } else {
            loadQueueInfo(dateInput.value);
            pollInterval = setInterval(function () {
                if (dateInput.value) loadQueueInfo(dateInput.value);
            }, 15000);
        }
    }

    form.addEventListener('submit', function (e) {
        if (!dateInput.value) {
            e.preventDefault();
            showToast('Please select a date.', 'error');
            return;
        }
        if (isSunday(dateInput.value)) {
            e.preventDefault();
            showToast('Appointments are available Monday to Saturday only.', 'error');
            return;
        }
        if (!canBook) {
            e.preventDefault();
            showToast('This date is not available for booking. Please choose another date.', 'error');
        }
    });
})();
</script>

</body>
</html>
