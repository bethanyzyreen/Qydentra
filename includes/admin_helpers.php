<?php

$conn = $conn ?? null;
require_once(__DIR__ . "/../config/database.php");

if (!function_exists('ensure_admin_tables_exist')) {
    function ensure_admin_tables_exist(mysqli $conn): void
    {
        $queries = [
            // Dental services table for service management
            "CREATE TABLE IF NOT EXISTS services (
                service_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                service_name VARCHAR(100) NOT NULL,
                service_description TEXT DEFAULT NULL,
                price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                duration VARCHAR(50) DEFAULT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (service_id),
                UNIQUE KEY (service_name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

            // Dentist schedules table
            "CREATE TABLE IF NOT EXISTS dentist_schedules (
                schedule_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                dentist_id VARCHAR(10) NOT NULL,
                schedule_date DATE DEFAULT NULL,
                start_time TIME NOT NULL,
                end_time TIME NOT NULL,
                notes TEXT DEFAULT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (schedule_id),
                KEY (dentist_id),
                CONSTRAINT fk_schedule_dentist FOREIGN KEY (dentist_id) REFERENCES dentists(dentist_id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

            // Admin notifications table for admin-level messages
            "CREATE TABLE IF NOT EXISTS admin_notifications (
                admin_notification_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                author_id VARCHAR(10) NOT NULL,
                target_type ENUM('patients','receptionists','all') NOT NULL DEFAULT 'all',
                target_id VARCHAR(10) DEFAULT NULL,
                title VARCHAR(100) NOT NULL,
                message TEXT NOT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (admin_notification_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

            // Audit log table for admin actions
            "CREATE TABLE IF NOT EXISTS admin_audit_logs (
                audit_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                admin_id VARCHAR(10) NOT NULL,
                action VARCHAR(100) NOT NULL,
                details TEXT DEFAULT NULL,
                ip_address VARCHAR(45) DEFAULT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (audit_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
        ];

        foreach ($queries as $query) {
            mysqli_query($conn, $query);
        }

        // Ensure backward-compatible schedule date column exists if schema was created earlier.
        mysqli_query($conn, "ALTER TABLE dentist_schedules ADD COLUMN IF NOT EXISTS schedule_date DATE DEFAULT NULL");

        // Ensure dentists table has the resignation/status columns.
        mysqli_query($conn, "ALTER TABLE dentists ADD COLUMN IF NOT EXISTS status ENUM('active','inactive') NOT NULL DEFAULT 'active'");
        mysqli_query($conn, "ALTER TABLE dentists ADD COLUMN IF NOT EXISTS resigned_at DATETIME DEFAULT NULL");
        mysqli_query($conn, "ALTER TABLE dentists ADD COLUMN IF NOT EXISTS resignation_note VARCHAR(255) DEFAULT NULL");

        // Ensure _seq_staff_de exists and is seeded so the dentist trigger never collides.
        mysqli_query($conn, "CREATE TABLE IF NOT EXISTS _seq_staff_de (last_id INT UNSIGNED NOT NULL DEFAULT 0) ENGINE=InnoDB");
        $seqRow = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM _seq_staff_de"));
        if ((int)($seqRow['cnt'] ?? 0) === 0) {
            $maxRow = mysqli_fetch_assoc(mysqli_query($conn, "SELECT MAX(CAST(SUBSTRING(dentist_id,3) AS UNSIGNED)) AS mx FROM dentists WHERE dentist_id REGEXP '^DE[0-9]+$'"));
            $seed   = max(0, (int)($maxRow['mx'] ?? 0));
            mysqli_query($conn, "INSERT INTO _seq_staff_de (last_id) VALUES ($seed)");
        }

        // Create BEFORE INSERT trigger on dentists to auto-assign DE### ids.
        // CREATE TRIGGER IF NOT EXISTS is supported in MySQL 5.7.32+ / MariaDB 10.1.6+.
        mysqli_query($conn,
            "CREATE TRIGGER IF NOT EXISTS trg_dentists_bi\n"
            . "BEFORE INSERT ON dentists\n"
            . "FOR EACH ROW\n"
            . "BEGIN\n"
            . "    DECLARE next_id INT;\n"
            . "    IF NEW.dentist_id IS NULL OR NEW.dentist_id = '' THEN\n"
            . "        UPDATE _seq_staff_de SET last_id = last_id + 1;\n"
            . "        SELECT last_id INTO next_id FROM _seq_staff_de LIMIT 1;\n"
            . "        SET NEW.dentist_id = CONCAT('DE', LPAD(next_id, 3, '0'));\n"
            . "    END IF;\n"
            . "END"
        );
    }
}

if (!function_exists('log_admin_action')) {
    function log_admin_action(mysqli $conn, string $admin_id, string $action, ?string $details = null): void
    {
        $admin_id_safe = mysqli_real_escape_string($conn, $admin_id);
        $action_safe   = mysqli_real_escape_string($conn, $action);
        $details_safe  = mysqli_real_escape_string($conn, $details ?? '');
        $ip            = mysqli_real_escape_string($conn, $_SERVER['REMOTE_ADDR'] ?? 'unknown');

        $sql = "INSERT INTO admin_audit_logs (admin_id, action, details, ip_address) VALUES ('$admin_id_safe', '$action_safe', '$details_safe', '$ip')";
        mysqli_query($conn, $sql);
    }
}

if (!function_exists('safe_input')) {
    function safe_input(mysqli $conn, ?string $value): string
    {
        return mysqli_real_escape_string($conn, trim($value ?? ''));
    }
}

if (!function_exists('get_site_settings')) {
    function get_site_settings(): array
    {
        $settings_file = __DIR__ . '/../config/site_settings.json';
        $defaults = [
            'site_name' => 'Qydentra',
            'contact_email' => 'info@qydentra.com',
            'maintenance_mode' => 0,
            'allow_registration' => 1,
        ];

        if (!file_exists($settings_file)) {
            save_site_settings($defaults);
            return $defaults;
        }

        $content = file_get_contents($settings_file);
        if ($content === false || trim($content) === '') {
            return $defaults;
        }

        $decoded = json_decode($content, true);
        if (!is_array($decoded)) {
            return $defaults;
        }

        return array_merge($defaults, $decoded);
    }
}

if (!function_exists('save_site_settings')) {
    function save_site_settings(array $settings): bool
    {
        $settings_file = __DIR__ . '/../config/site_settings.json';
        $dir = dirname($settings_file);

        if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
            return false;
        }

        $sanitized = [
            'site_name'          => trim((string)($settings['site_name'] ?? 'Qydentra')),
            'clinic_address'     => trim((string)($settings['clinic_address'] ?? '')),
            'contact_email'      => trim((string)($settings['contact_email'] ?? '')),
            'contact_phone'      => trim((string)($settings['contact_phone'] ?? '')),
            'appointment_limit'  => max(1, intval($settings['appointment_limit'] ?? 20)),
            'queue_limit'        => max(1, intval($settings['queue_limit'] ?? 30)),
            'maintenance_mode'   => !empty($settings['maintenance_mode']) ? 1 : 0,
            'allow_registration' => !empty($settings['allow_registration']) ? 1 : 0,
            'allow_cancellation' => !empty($settings['allow_cancellation']) ? 1 : 0,
        ];

        return file_put_contents($settings_file, json_encode($sanitized, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) !== false;
    }
}

if (!function_exists('stream_csv_download')) {
    function stream_csv_download(string $filename, array $headers, array $rows): void
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        fputcsv($output, $headers);
        foreach ($rows as $row) {
            fputcsv($output, $row);
        }
        fclose($output);
        exit;
    }
}

if (!function_exists('build_simple_pdf')) {
    function build_simple_pdf(string $title, array $lines): string
    {
        $escapePdfText = static function (string $value): string {
            return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $value);
        };

        $contentLines = [];
        $y = 760;
        $contentLines[] = 'BT /F1 16 Tf 50 760 Td (' . $escapePdfText($title) . ') Tj ET';

        foreach ($lines as $index => $line) {
            $contentLines[] = 'BT /F1 11 Tf 50 ' . ($y - ($index + 1) * 14) . ' Td (' . $escapePdfText((string)$line) . ') Tj ET';
        }

        $content = implode("\n", $contentLines);
        $objects = [
            '1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj',
            '2 0 obj << /Type /Pages /Kids [3 0 R] /Count 1 >> endobj',
            '3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >> endobj',
            '4 0 obj << /Length ' . strlen($content) . ' >> stream' . "\n" . $content . "\nendstream endobj",
            '5 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> endobj',
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objects as $obj) {
            $offsets[] = strlen($pdf);
            $pdf .= $obj . "\n";
        }

        $xrefPosition = strlen($pdf);
        $pdf .= "xref\n0 6\n0000000000 65535 f \n";
        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }
        $pdf .= "trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n" . $xrefPosition . "\n%%EOF";

        return $pdf;
    }
}

if (!function_exists('stream_pdf_download')) {
    function stream_pdf_download(string $filename, string $title, array $lines): void
    {
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo build_simple_pdf($title, $lines);
        exit;
    }
}
