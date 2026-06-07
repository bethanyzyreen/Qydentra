# Qydentra — Dental Clinic Web App (v4)

## What's New in v4: VARCHAR Prefixed Primary Keys

All primary keys have been migrated from `INT AUTO_INCREMENT` to `VARCHAR(10)` with prefixed IDs. Auto-increment behaviour is preserved via MySQL BEFORE INSERT triggers and helper sequence tables.

### ID Prefix Reference

| Table                         | Column                        | Prefix | Example |
|-------------------------------|-------------------------------|--------|---------|
| `patients`                    | `patient_id`                  | `PT`   | PT001   |
| `staffs` (receptionist)       | `staff_id`                    | `RE`   | RE001   |
| `staffs` (dentist/admin)      | `staff_id`                    | `ST`   | ST001   |
| `appointments`                | `appointment_id`              | `AP`   | AP001   |
| `patient_notifications`       | `notification_id`             | `PN`   | PN001   |
| `receptionist_notifications`  | `receptionist_notification_id`| `RN`   | RN001   |

### Rules
- IDs are **only visible in the database** — never displayed in the application UI.
- All foreign keys updated to `VARCHAR(10)` to match.
- `includes/id_helper.php` provides `get_last_inserted_id($conn, $table)` to retrieve the last inserted prefixed ID (replaces `mysqli_insert_id()` which returns 0 for VARCHAR PKs).
- `includes/notify_helper.php` updated — `notify_patient()` and `notify_receptionists()` accept VARCHAR IDs.

## Database Setup

1. Import `sql/qydentra.sql` into MySQL.
2. The script creates all tables, triggers, sequence tables, and seeds default staff accounts.

## Default Accounts

| Role         | Email                          | Password                |
|--------------|--------------------------------|-------------------------|
| Receptionist | receptionist@qydentraa.com     | qydentra.receptionist   |
| Admin        | admin@qydentra.com             | qydentra.admin          |
| Dentist      | dentist@qydentra.com           | qydentra.dentist        |

## Tech Stack
PHP · MySQL · Bootstrap 5 · Font Awesome
