# Git Diff Master Log — Lahat ng Binagong Code (Old vs New)

Ito ang kumpletong log ng lahat ng binagong code (Old vs New) sa iyong mga file ngayon, kasama ang layunin at dahilan ng bawat isa.

---

## 1. File: `includes/admin_header.php`
* **Diff:**
  ```diff
   <title>Qydentra — Admin</title>
  -<link rel="stylesheet" href="../assets/css/dashboard.css">
  +<link rel="stylesheet" href="../assets/css/dashboard.css?v=1.0.1">
   <link rel="stylesheet" href="../assets/css/style.css">
  -<link rel="stylesheet" href="../assets/css/admin.css">
  -<link rel="stylesheet" href="../assets/css/receptionist.css">
  +<link rel="stylesheet" href="../assets/css/admin.css?v=1.0.1">
  +<link rel="stylesheet" href="../assets/css/receptionist.css?v=1.0.1">
  ```
* **Dating Code (Old Code):**
  ```html
  <link rel="stylesheet" href="../assets/css/dashboard.css">
  <link rel="stylesheet" href="../assets/css/admin.css">
  <link rel="stylesheet" href="../assets/css/receptionist.css">
  ```
* **Para Saan:** Nilagyan ng version query parameter (`?v=1.0.1`) ang CSS links para pilitin ang browser na i-bypass ang cache at gamitin ang pinakabagong design styles.
* **Bagong Code (New Code):**
  ```html
  <link rel="stylesheet" href="../assets/css/dashboard.css?v=1.0.1">
  <link rel="stylesheet" href="../assets/css/admin.css?v=1.0.1">
  <link rel="stylesheet" href="../assets/css/receptionist.css?v=1.0.1">
  ```
* **Para Saan (Why):** Nilagyan ng version query parameter (`?v=1.0.1`) ang CSS links para pilitin ang browser na i-bypass ang aggressive cache nito at gamitin ang pinakabagong design styles agad-agad nang hindi kinakailangang mag-hard refresh.

---

## 2. File: `admin/dashboard.php`
* **Diff (Paths & Caching overrides):**
  ```diff
  -include("../includes/auth_check.php");
  -require_once("../includes/admin_helpers.php");
  +include_once(__DIR__ . "/../includes/auth_check.php");
  +require_once(__DIR__ . "/../config/database.php");
  +require_once(__DIR__ . "/../includes/admin_helpers.php");
   ensure_admin_tables_exist($conn);
   ...
  -<?php include("../includes/admin_header.php"); ?>
  +<?php include(__DIR__ . "/../includes/admin_header.php"); ?>
  +<style>
  +/* Inline override to bypass aggressive browser caching */
  +.card:nth-child(3)::before {
      background: #f97316 !important;
  }
  +.card:nth-child(3) .card-icon {
      background: rgba(249, 115, 22, 0.15) !important;
      color: #fb923c !important;
      border: 1px solid rgba(251, 146, 60, 0.3) !important;
  }
  +.card:nth-child(3) h1 {
      color: #fb923c !important;
  }
  +</style>
   <body>
  -<?php include("../includes/admin_sidebar.php"); ?>
  +<?php include(__DIR__ . "/../includes/admin_sidebar.php"); ?>
  ```
* **Dating Code (Old Code):**
  ```php
  include("../includes/auth_check.php");
  require_once("../includes/admin_helpers.php");
  ...
  <?php include("../includes/admin_header.php"); ?>
  ...
  <?php include("../includes/admin_sidebar.php"); ?>
  ```
* **Para Saan:** Inaayos ang file paths gamit ang `__DIR__` para hindi maligaw ang includes, at pinupwersa ang Active Appointments dashboard card na maging kulay Orange (`#f97316`).
* **Bagong Code (New Code):**
  ```php
  include_once(__DIR__ . "/../includes/auth_check.php");
  require_once(__DIR__ . "/../config/database.php");
  require_once(__DIR__ . "/../includes/admin_helpers.php");
  ensure_admin_tables_exist($conn);
  ...
  <?php include(__DIR__ . "/../includes/admin_header.php"); ?>
  <style>
  /* Inline override to bypass aggressive browser caching */
  .card:nth-child(3)::before {
      background: #f97316 !important;
  }
  .card:nth-child(3) .card-icon {
      background: rgba(249, 115, 22, 0.15) !important;
      color: #fb923c !important;
      border: 1px solid rgba(251, 146, 60, 0.3) !important;
  }
  .card:nth-child(3) h1 {
      color: #fb923c !important;
  }
  </style>
  ...
  <?php include(__DIR__ . "/../includes/admin_sidebar.php"); ?>
  ```
* **Para Saan (Why):** Inaayos ang file paths gamit ang `__DIR__` para maiwasan ang maling file linking at mapagana ang database connection. Naglagay din ng `<style>` override para sa active appointments dashboard card para pilitin itong gumamit ng kulay Orange (`#f97316`) na tugma sa active state nito.

---

## 3. File: `admin/audit_logs.php`
* **Diff (Paths & Today's Logs number color):**
  ```diff
  -include("../includes/auth_check.php");
  -require_once("../includes/admin_helpers.php");
  +include_once(__DIR__ . "/../includes/auth_check.php");
  +require_once(__DIR__ . "/../config/database.php");
  +require_once(__DIR__ . "/../includes/admin_helpers.php");
   ensure_admin_tables_exist($conn);
   ...
  -<?php include("../includes/admin_header.php"); ?>
  +<?php include(__DIR__ . "/../includes/admin_header.php"); ?>
   <body>
  -<?php include("../includes/admin_sidebar.php"); ?>
  +<?php include(__DIR__ . "/../includes/admin_sidebar.php"); ?>
   ...
       <div class="stat-card hover-glow"><h3>Showing</h3><p style="color:#fbbf24;"><?php echo $totalLogs; ?></p></div>
       <div class="stat-card hover-glow"><h3>Today's Logs</h3>
  -        <p style="color:#ffffff;"><?php
  +        <p style="color:#a78bfa;"><?php
               $todayLogs = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM admin_audit_logs WHERE DATE(created_at)=CURDATE()"))['total'];
  ```
* **Dating Code (Old Code):**
  ```php
  include("../includes/auth_check.php");
  require_once("../includes/admin_helpers.php");
  ...
  <?php include("../includes/admin_header.php"); ?>
  ...
  <?php include("../includes/admin_sidebar.php"); ?>
  ...
  <p style="color:#ffffff;"><?php
      $todayLogs = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM admin_audit_logs WHERE DATE(created_at)=CURDATE()"))['total'];
  ```
* **Para Saan:** Inaayos ang file paths gamit ang `__DIR__`, at kinukulayan ng purple (`#a78bfa`) ang bilang ng Today's Logs.
* **Bagong Code (New Code):**
  ```php
  include_once(__DIR__ . "/../includes/auth_check.php");
  require_once(__DIR__ . "/../config/database.php");
  require_once(__DIR__ . "/../includes/admin_helpers.php");
  ensure_admin_tables_exist($conn);
  ...
  <?php include(__DIR__ . "/../includes/admin_header.php"); ?>
  ...
  <?php include(__DIR__ . "/../includes/admin_sidebar.php"); ?>
  ...
  <p style="color:#a78bfa;"><?php
      $todayLogs = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM admin_audit_logs WHERE DATE(created_at)=CURDATE()"))['total'];
  ```
* **Para Saan (Why):** Ginagamit ang `__DIR__` para sa safe absolute paths, at ginawang purple (`#a78bfa`) ang kulay ng Today's Logs number para sa mas magandang visual hierarchy.

---

## 4. File: `assets/css/receptionist.css`
* **Diff:**
  ```diff
   .service-info > div:last-child {
       display: flex;
       flex-direction: column;
  +    align-items: flex-start;
       min-width: 0;
   }
  ```
* **Dating Code (Old Code):**
  ```css
  .service-info > div:last-child {
      display: flex;
      flex-direction: column;
      min-width: 0;
  }
  ```
* **Para Saan:** Pinipigilan ang pahalang (horizontal) na pag-stretch ng Patient role badge sa tabi ng pangalan sa table.
* **Bagong Code (New Code):**
  ```css
  .service-info > div:last-child {
      display: flex;
      flex-direction: column;
      align-items: flex-start;
      min-width: 0;
  }
  ```
* **Para Saan (Why):** Pinipigilan ang badge tag (tulad ng "Patient" badge) na mag-stretch at humaba nang pahalang sa buong cell ng table.

---

## 5. File: `admin/user_management.php`

#### A. Email Duplicate check & Exception handling (Add & Edit Patient)
* **Diff:**
  ```diff
       if ($action === 'add_patient') {
           ...
  -        if ($full_name === '' || $email === '' || $password === '') {
  -            $message = 'Please provide name, email, and password.'; $messageType = 'error';
  -        } else {
  -            $hash = password_hash($password, PASSWORD_DEFAULT);
  -            $sql = "INSERT INTO patients (full_name,email,password,role,phone_number,medical_history) VALUES ('$full_name','$email','$hash','patient','$phone_number','$medical_history')";
  -            if (mysqli_query($conn, $sql)) {
  -                log_admin_action($conn, $_SESSION['user_id'], 'Add patient', "Added patient $email");
  -                $message = 'Patient account created successfully.';
  -            } else {
  -                $message = 'Error: ' . mysqli_error($conn); $messageType = 'error';
  -            }
  -        }
  +        if ($full_name === '' || $email === '' || $password === '') {
  +            $message = 'Please provide name, email, and password.'; $messageType = 'error';
  +        } else {
  +            $checkEmail = mysqli_query($conn, "SELECT patient_id FROM patients WHERE email='$email' LIMIT 1");
  +            if (mysqli_num_rows($checkEmail) > 0) {
  +                $message = 'Error: That email is already registered.';
  +                $messageType = 'error';
  +            } else {
  +                $hash = password_hash($password, PASSWORD_DEFAULT);
  +                $sql = "INSERT INTO patients (full_name,email,password,role,phone_number,medical_history) VALUES ('$full_name','$email','$hash','patient','$phone_number','$medical_history')";
  +                try {
  +                    if (mysqli_query($conn, $sql)) {
  +                        log_admin_action($conn, $_SESSION['user_id'], 'Add patient', "Added patient $email");
  +                        $message = 'Patient account created successfully.';
  +                    } else {
  +                        $message = 'Error: ' . mysqli_error($conn); $messageType = 'error';
  +                    }
  +                } catch (mysqli_sql_exception $e) {
  +                    if ($e->getCode() == 1062) {
  +                        $message = 'Error: That email is already registered.';
  +                    } else { $message = 'Error: ' . $e->getMessage(); }
  +                    $messageType = 'error';
  +                }
  +            }
  +        }
       }
  ```
* **Dating Code (Old Code):**
  ```php
  if ($action === 'add_patient') {
      if ($full_name === '' || $email === '' || $password === '') {
          $message = 'Please provide name, email, and password.'; $messageType = 'error';
      } else {
          $hash = password_hash($password, PASSWORD_DEFAULT);
          $sql = "INSERT INTO patients (full_name,email,password,role,phone_number,medical_history) VALUES ('$full_name','$email','$hash','patient','$phone_number','$medical_history')";
          if (mysqli_query($conn, $sql)) {
              log_admin_action($conn, $_SESSION['user_id'], 'Add patient', "Added patient $email");
              $message = 'Patient account created successfully.';
          } else {
              $message = 'Error: ' . mysqli_error($conn); $messageType = 'error';
          }
      }
  }
  ```
  *(May katulad na try-catch at pre-check block din para sa `edit_patient` flow).*
* **Para Saan:** Nilagyan ng query checking at try-catch exception block para saluhin ang duplicate email entry crashes.
* **Bagong Code (New Code):**
  ```php
  if ($action === 'add_patient') {
      if ($full_name === '' || $email === '' || $password === '') {
          $message = 'Please provide name, email, and password.'; $messageType = 'error';
      } else {
          // Pre-check duplicate email
          $checkEmail = mysqli_query($conn, "SELECT patient_id FROM patients WHERE email='$email' LIMIT 1");
          if (mysqli_num_rows($checkEmail) > 0) {
              $message = 'Error: That email is already registered.';
              $messageType = 'error';
          } else {
              $hash = password_hash($password, PASSWORD_DEFAULT);
              $sql = "INSERT INTO patients (full_name,email,password,role,phone_number,medical_history) VALUES ('$full_name','$email','$hash','patient','$phone_number','$medical_history')";
              try {
                  if (mysqli_query($conn, $sql)) {
                      log_admin_action($conn, $_SESSION['user_id'], 'Add patient', "Added patient $email");
                      $message = 'Patient account created successfully.';
                  } else {
                      $message = 'Error: ' . mysqli_error($conn); $messageType = 'error';
                  }
              } catch (mysqli_sql_exception $e) {
                  if ($e->getCode() == 1062) {
                      $message = 'Error: That email is already registered.';
                  } else {
                      $message = 'Error: ' . $e->getMessage();
                  }
                  $messageType = 'error';
              }
          }
      }
  }
  ```
* **Para Saan (Why):** Nilagyan ng query checking at try-catch exception block para saluhin ang code `1062` (Duplicate entry). Pinipigilan nito ang PHP 8.1+ na mag-throw ng fatal crash page kung may magrehistro ng email na umiiral na sa database.

#### B. Form ID, Phone Max Length, at Toasts
* **Diff:**
  ```diff
  -<?php if ($message !== ''): ?>
  -<div class="alert-msg <?php echo $messageType; ?>">
  -    <i class="fa-solid fa-<?php echo $messageType === 'success' ? 'circle-check' : 'circle-exclamation'; ?>"></i>
  -    <?php echo htmlspecialchars($message); ?>
  -</div>
  -<?php endif; ?>
  +<?php if ($message !== ''): ?>
  +<div data-toast="<?php echo htmlspecialchars($message); ?>" data-toast-type="<?php echo $messageType; ?>"></div>
  +<?php endif; ?>
   ...
  -    <form method="POST">
  +    <form method="POST" id="patient-form">
   ...
               <div class="form-group">
                   <label>Phone Number</label>
  -                <input type="text" name="phone_number" placeholder="09XX XXX XXXX" value="<?php echo htmlspecialchars($editPatient['phone_number'] ?? ''); ?>">
  +                <input type="text" name="phone_number" placeholder="09XX XXX XXXX" value="<?php echo htmlspecialchars($editPatient['phone_number'] ?? ''); ?>" maxlength="11">
               </div>
  ```
* **Dating Code (Old Code):**
  ```php
  <?php if ($message !== ''): ?>
  <div class="alert-msg <?php echo $messageType; ?>">
      <i class="fa-solid fa-... "></i>
      <?php echo htmlspecialchars($message); ?>
  </div>
  <?php endif; ?>
  ...
  <form method="POST">
  ...
  <input type="text" name="phone_number" placeholder="09XX XXX XXXX" value="...">
  ```
* **Para Saan:** Pinalitan ang static alert banners ng toast notifications, at nilagyan ng maxlength ang phone field.
* **Bagong Code (New Code):**
  ```php
  <?php if ($message !== ''): ?>
  <div data-toast="<?php echo htmlspecialchars($message); ?>" data-toast-type="<?php echo $messageType; ?>"></div>
  <?php endif; ?>
  ...
  <form method="POST" id="patient-form">
  ...
  <input type="text" name="phone_number" placeholder="09XX XXX XXXX" value="..." maxlength="11">
  ```
* **Para Saan (Why):** Pinalitan ang static warning block alerts ng flexible toast notifications (`data-toast`), nagtalaga ng form ID para sa JavaScript targeting, at nagdagdag ng `maxlength="11"` restriction sa phone field.

#### C. Table Header Alignment at Inline JS Validation scripts
* **Diff:**
  ```diff
   <thead>
       <tr>
           <th>ID</th>
  -        <th>Patient</th>
  +        <th style="text-align: left !important;">Patient</th>
           <th>Email</th>
  ```
* **Dating Code (Old Code):** Walang ginagawang verification sa client-side bago ipadala ang form sa server. Pwede maglagay ng kahit anong text o kulang na digits.
* **Para Saan:** Pagdaragdag ng live JS filters at validation para sa patient form.
* **Bagong Code (New Code):**
  Nagdagdag ng JavaScript code sa dulo ng file na ginagawa ang sumusunod:
  1. Nililimitahan ang full name input sa mga titik, espasyo, at ang character na `ñ`/`Ñ` lamang.
  2. Nililinis ang phone input sa pamamagitan ng pagbura ng anumang letra o simbolo sa real-time gamit ang `.replace(/\D/g, '')`.
  3. Sineseguro na ang phone number ay may eksaktong 11 digits kung may inilagay.
  4. Nire-require ang email na sumunod sa tamang email format at ang password na may minimum na 8 characters na may uppercase, lowercase, at numero.
* **Para Saan (Why):** Pinapaganda ang user experience sa pamamagitan ng pagbibigay ng real-time errors nang hindi na kailangang mag-reload ang pahina.

---

## 6. File: `admin/staff_dentist_management.php`
* **Diff (Duplicates checks, try-catch, at toasts para sa Staff at Dentist registration):**
  Naglagay ng `SELECT` pre-query checks at `try-catch` exception handling blocks sa:
  * **Add Staff** (Lines 26-51)
  * **Edit Staff** (Lines 42-70)
  * **Add Dentist** (Lines 72-108)
  * **Edit Dentist** (Lines 105-139)
  * **Toasts Conversion:**
  ```diff
  -<?php if ($message !== ''): ?>
  -<div class="alert-msg <?php echo $messageType; ?>">
  -    <i class="fa-solid fa-<?php echo $messageType === 'success' ? 'circle-check' : 'circle-exclamation'; ?>"></i>
  -    <?php echo htmlspecialchars($message); ?>
  -</div>
  -<?php endif; ?>
  +<?php if ($message !== ''): ?>
  +<div data-toast="<?php echo htmlspecialchars($message); ?>" data-toast-type="<?php echo $messageType; ?>"></div>
  +<?php endif; ?>
  ```
* **Dating Code (Old Code):**
  ```php
  // Walang SELECT validation at try-catch wrappers para sa Add Staff, Edit Staff, Add Dentist, at Edit Dentist. Gumagamit ng alert-msg blocks.
  ```
* **Para Saan:** Safe duplicate check logic at conversion sa toast layout.
* **Bagong Code (New Code):**
  ```php
  // Nagdagdag ng SELECT statements para makita kung may duplicate email bago mag-insert o update.
  // Binalot sa try { ... } catch (mysqli_sql_exception $e) { ... } ang queries.
  // Pinalitan ang alert-msg divs ng data-toast triggers.
  ```
* **Para Saan (Why):** Sineseguro na ligtas at hindi magka-crash ang staff at dentist registration/edit processes kapag may na-enter na email na gamit na sa ibang account.

---

## 7. File: `admin/notifications.php`
* **Diff (Toasts at Card backdrop blur):**
  ```diff
  -<?php if ($message !== ''): ?>
  -<div class="alert-msg <?php echo $messageType; ?>">
  -    <i class="fa-solid fa-<?php echo $messageType === 'success' ? 'circle-check' : 'circle-exclamation'; ?>"></i>
  -    <?php echo htmlspecialchars($message); ?>
  -</div>
  -<?php endif; ?>
  +<?php if ($message !== ''): ?>
  +<div data-toast="<?php echo htmlspecialchars($message); ?>" data-toast-type="<?php echo $messageType; ?>"></div>
  +<?php endif; ?>
   ...
  -    <div style="padding:20px 24px; border-radius:18px; background:rgba(96,165,250,0.06); border:1px solid rgba(96,165,250,0.12);">
  +    <div style="padding:20px 24px; border-radius:18px; background:rgba(96,165,250,0.06); border:1px solid rgba(96,165,250,0.12); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px);">
  ```
* **Dating Code (Old Code):** Gumagamit ng static banner alerts, at ang audience guide card ay walang visual glassmorphism style.
* **Para Saan:** Pinapalitan ang block alerts ng toasts, at naglalapat ng blur style sa guide box.
* **Bagong Code (New Code):**
  1. Pinalitan ang banner alerts ng toast signals (`data-toast`).
  2. Dinagdag ang `backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px);` sa inline style ng audience guide card.
* **Para Saan (Why):** Iniaangkop ang interface sa modernong aesthetic system (glassmorphism at clean overlays).

---

## 8. Files: `dentist_schedule_management.php`, `system_settings.php`, `service_management.php`
* **Diff (Toasts):**
  ```diff
  -<?php if ($message !== ''): ?>
  -<div class="alert-msg <?php echo $messageType; ?>">
  -    <i class="fa-solid fa-<?php echo $messageType === 'success' ? 'circle-check' : 'circle-exclamation'; ?>"></i>
  -    <?php echo htmlspecialchars($message); ?>
  -</div>
  -<?php endif; ?>
  +<?php if ($message !== ''): ?>
  +<div data-toast="<?php echo htmlspecialchars($message); ?>" data-toast-type="<?php echo $messageType; ?>"></div>
  +<?php endif; ?>
  ```
* **Dating Code (Old Code):** Gumagamit ng `.alert-msg` static blocks para sa warning at success messages.
* **Para Saan:** Toasts conversion.
* **Bagong Code (New Code):**
  ```php
  <?php if ($message !== ''): ?>
  <div data-toast="<?php echo htmlspecialchars($message); ?>" data-toast-type="<?php echo $messageType; ?>"></div>
  <?php endif; ?>
  ```
* **Para Saan (Why):** Ginawang toasts ang lahat ng notice system para sa consistency ng modern responsive design.

---

## 9. File: `assets/css/admin.css`
* **Diff (Table alignments overrides at the bottom of the file):**
  ```diff
  +/* Align all table columns and headers to the left for clean vertical alignment */
  +.table-container table th,
  +.table-container table td {
  +    text-align: left !important;
  +}
  +.table-container table th:last-child,
  +.table-container table td:last-child {
  +    text-align: center !important;
  +}
  +.table-container .table-date {
  +    justify-content: flex-start !important;
  +}
  ```
* **Dating Code (Old Code):** Walang alignments rules para sa table columns at dates, kaya ang cells ay naka-center o inconsistent ang layout.
* **Para Saan:** Table alignment tuning.
* **Bagong Code (New Code):**
  ```css
  /* Align all table columns and headers to the left for clean vertical alignment */
  .table-container table th,
  .table-container table td {
      text-align: left !important;
  }
  .table-container table th:last-child,
  .table-container table td:last-child {
      text-align: center !important;
  }
  .table-container .table-date {
      justify-content: flex-start !important;
  }
  ```
* **Para Saan (Why):** Inilalapat sa kaliwa (left align) ang alignment ng lahat ng table fields at date blocks upang magkaroon ng malinis, tuwid, at mukhang propesyonal na vertical lines sa pagbabasa ng tables.
