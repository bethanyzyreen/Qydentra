# Qydentra

### ID Prefix Reference

| Table                         | Column                        | Prefix | Example |
|-------------------------------|-------------------------------|--------|---------|
| `patients`                    | `patient_id`                  | `PT`   | PT001   |
| `staffs` (receptionist)       | `staff_id`                    | `RE`   | RE001   |
| `staffs` (dentist)            | `staff_id`                    | `DE`   | DE001   |
| `staffs` (admin)              | `staff_id`                    | `AD`   | AD001   |
| `appointments`                | `appointment_id`              | `AP`   | AP001   |
| `patient_notifications`       | `notification_id`             | `PN`   | PN001   |
| `receptionist_notifications`  | `receptionist_notification_id`| `RN`   | RN001   |


## Default Accounts

| Role         | Email                          | Password                |
|--------------|--------------------------------|-------------------------|
| Receptionist | receptionist@qydentraa.com     | qydentra.receptionist   |
| Admin        | admin@qydentra.com             | qydentra.admin          |
| Dentist      | dentist@qydentra.com           | qydentra.dentist        |


We can remove the separate patient_notification table in database because notifications can be stored and updated directly in the receptionist, dentist, and admin tables. The notification sidebar will remain the same. The only change is that patient_notification.php will connect to the respective role tables (receptionist, dentist, admin) to fetch and update their notifications. Currently, patient notifications are already connected to the receptionist table.
