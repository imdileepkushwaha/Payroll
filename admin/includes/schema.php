<?php

function ensure_database_schema($conn)
{
    $messages = [];

    $tables = [
        "CREATE TABLE IF NOT EXISTS `admin_users` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `username` varchar(50) NOT NULL,
            `password` varchar(255) NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `username` (`username`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS `employees` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `emp_id` varchar(50) NOT NULL,
            `name` varchar(100) NOT NULL,
            `email` varchar(150) DEFAULT NULL,
            `phone` varchar(30) DEFAULT NULL,
            `department` varchar(100) DEFAULT NULL,
            `designation` varchar(100) DEFAULT NULL,
            `base_salary` decimal(12,2) NOT NULL DEFAULT 0.00,
            `joined_date` date DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `emp_id` (`emp_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS `attendance` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `emp_id` varchar(50) NOT NULL,
            `attendance_date` date NOT NULL,
            `status` varchar(20) NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `emp_date` (`emp_id`, `attendance_date`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS `settings` (
            `setting_key` varchar(100) NOT NULL,
            `setting_value` text,
            PRIMARY KEY (`setting_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS `salary_slip_logs` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `emp_id` varchar(50) NOT NULL,
            `period_month` tinyint NOT NULL,
            `period_year` smallint NOT NULL,
            `net_salary` decimal(12,2) NOT NULL,
            `sent_to` varchar(150) DEFAULT NULL,
            `sent_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `status` varchar(20) NOT NULL DEFAULT 'sent',
            PRIMARY KEY (`id`),
            KEY `period` (`period_year`, `period_month`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    ];

    foreach ($tables as $sql) {
        $conn->query($sql);
    }

    $employee_columns = [
        'email' => "ALTER TABLE `employees` ADD COLUMN `email` varchar(150) DEFAULT NULL",
        'phone' => "ALTER TABLE `employees` ADD COLUMN `phone` varchar(30) DEFAULT NULL",
        'department' => "ALTER TABLE `employees` ADD COLUMN `department` varchar(100) DEFAULT NULL",
        'designation' => "ALTER TABLE `employees` ADD COLUMN `designation` varchar(100) DEFAULT NULL",
        'base_salary' => "ALTER TABLE `employees` ADD COLUMN `base_salary` decimal(12,2) NOT NULL DEFAULT 0.00",
        'joined_date' => "ALTER TABLE `employees` ADD COLUMN `joined_date` date DEFAULT NULL",
        'created_at' => "ALTER TABLE `employees` ADD COLUMN `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP",
    ];

    foreach ($employee_columns as $column => $sql) {
        if (!column_exists($conn, 'employees', $column)) {
            $conn->query($sql);
        }
    }

    seed_default_settings($conn);

    return $messages;
}

function column_exists($conn, $table, $column)
{
    $table = $conn->real_escape_string($table);
    $column = $conn->real_escape_string($column);
    $result = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $result && $result->num_rows > 0;
}

function seed_default_settings($conn)
{
    $defaults = [
        'company_name' => 'Payroll Company',
        'working_days_per_month' => '26',
        'smtp_host' => '',
        'smtp_port' => '587',
        'smtp_encryption' => 'tls',
        'smtp_username' => '',
        'smtp_password' => '',
        'smtp_from_email' => '',
        'smtp_from_name' => 'Payroll System',
        'payslip_signature' => '',
        'signature_authority_name' => 'Authorized Signatory',
    ];

    foreach ($defaults as $key => $value) {
        $stmt = $conn->prepare("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES (?, ?)");
        $stmt->bind_param('ss', $key, $value);
        $stmt->execute();
    }
}
