<?php

function notification_format_date($date) {
    return date("F d, Y", strtotime($date));
}

function notification_format_time($time) {
    return date("g:i A", strtotime($time));
}

function notification_patient_request_submitted($patient_name) {
    return "Patient {$patient_name} submitted an appointment request successfully.";
}

function notification_patient_new_appointment_booked($patient_name, $service, $date, $time) {
    $formatted_date = notification_format_date($date);
    $formatted_time = notification_format_time($time);
    return "Patient {$patient_name} booked a {$service} appointment on {$formatted_date} at {$formatted_time}.";
}

function notification_patient_appointment_approved($patient_name, $service, $date, $time) {
    $formatted_date = notification_format_date($date);
    $formatted_time = notification_format_time($time);
    return "Clinic Receptionist approved the {$service} appointment for Patient {$patient_name} on {$formatted_date} at {$formatted_time}.";
}

function notification_patient_appointment_rescheduled($patient_name, $service, $date, $time) {
    $formatted_date = notification_format_date($date);
    $formatted_time = notification_format_time($time);
    return "Clinic Receptionist rescheduled the {$service} appointment for Patient {$patient_name} to {$formatted_date} at {$formatted_time}.";
}

function notification_patient_walkin_recorded($patient_name, $service, $date, $time) {
    $formatted_date = notification_format_date($date);
    $formatted_time = notification_format_time($time);
    return "Clinic Receptionist registered a walk-in appointment for Patient {$patient_name} on {$formatted_date} at {$formatted_time}.";
}

function notification_receptionist_new_appointment_booked($patient_name, $service, $date, $time) {
    $formatted_date = notification_format_date($date);
    $formatted_time = notification_format_time($time);
    return "Patient {$patient_name} booked a {$service} appointment on {$formatted_date} at {$formatted_time}.";
}

function notification_receptionist_appointment_approved($patient_name, $service, $date, $time) {
    $formatted_date = notification_format_date($date);
    $formatted_time = notification_format_time($time);
    return "Clinic Receptionist approved the {$service} appointment for Patient {$patient_name} on {$formatted_date} at {$formatted_time}.";
}

function notification_receptionist_appointment_rescheduled($patient_name, $service, $date, $time) {
    $formatted_date = notification_format_date($date);
    $formatted_time = notification_format_time($time);
    return "Clinic Receptionist rescheduled the {$service} appointment for Patient {$patient_name} to {$formatted_date} at {$formatted_time}.";
}

function notification_receptionist_walkin_recorded($patient_name, $service, $date, $time) {
    $formatted_date = notification_format_date($date);
    $formatted_time = notification_format_time($time);
    return "Clinic Receptionist registered a walk-in appointment for Patient {$patient_name} on {$formatted_date} at {$formatted_time}.";
}

function notification_receptionist_appointment_cancelled_by_patient($patient_name, $service, $date, $time) {
    $formatted_date = notification_format_date($date);
    $formatted_time = notification_format_time($time);
    return "Patient {$patient_name} cancelled their {$service} appointment on {$formatted_date} at {$formatted_time}.";
}

function notification_patient_appointment_cancelled($patient_name, $service, $date, $time) {
    $formatted_date = notification_format_date($date);
    $formatted_time = notification_format_time($time);
    return "Clinic Receptionist cancelled the {$service} appointment for Patient {$patient_name} on {$formatted_date} at {$formatted_time}.";
}

function notification_receptionist_appointment_cancelled($patient_name, $service, $date, $time) {
    $formatted_date = notification_format_date($date);
    $formatted_time = notification_format_time($time);
    return "Clinic Receptionist cancelled the {$service} appointment for Patient {$patient_name} on {$formatted_date} at {$formatted_time}.";
}
