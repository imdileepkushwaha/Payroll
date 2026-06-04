<?php
session_start();
require 'config.php';
require 'includes/settings_helper.php';
require 'includes/salary_helper.php';
require 'includes/mailer.php';
require 'includes/pdf_slip.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

$month = (int) ($_POST['month'] ?? date('n'));
$year = (int) ($_POST['year'] ?? date('Y'));
$period = get_period_label($year, $month);

if ($month < 1 || $month > 12 || $year < 2000) {
    $_SESSION['flash_message'] = 'Invalid month or year selected.';
    $_SESSION['flash_success'] = false;
    header('Location: dashboard.php');
    exit;
}

$settings = get_all_settings($conn);

if (!is_smtp_configured($settings)) {
    $_SESSION['flash_message'] = 'Configure SMTP in Settings (host, username, from email, password) before sending.';
    $_SESSION['flash_success'] = false;
    header('Location: dashboard.php');
    exit;
}

if (empty($settings['smtp_password'])) {
    $_SESSION['flash_message'] = 'SMTP password is missing. Open Settings → SMTP and save your password (Gmail needs App Password).';
    $_SESSION['flash_success'] = false;
    header('Location: dashboard.php');
    exit;
}

$employees = $conn->query("SELECT * FROM employees ORDER BY name");
$to_send = [];

while ($emp = $employees->fetch_assoc()) {
    if (empty($emp['email'])) {
        continue;
    }
    if ((float) $emp['base_salary'] <= 0) {
        continue;
    }
    $stats = get_attendance_stats_for_period($conn, $emp['emp_id'], $year, $month);
    if ($stats['total_records'] === 0) {
        continue;
    }
    $to_send[] = $emp;
}

$skipped = ($employees ? $employees->num_rows : 0) - count($to_send);
$sent = 0;
$failed = 0;
$errors = [];

if (count($to_send) === 0) {
    $_SESSION['flash_message'] = "No employees ready for {$period}. Need email, base salary, and attendance for that month.";
    $_SESSION['flash_success'] = false;
    header('Location: dashboard.php');
    exit;
}

$mailer = new SmtpMailer($settings);
if (!$mailer->connect()) {
    $_SESSION['flash_message'] = 'SMTP connection failed: ' . ($mailer->getLastError() ?? 'Unknown error');
    $_SESSION['flash_success'] = false;
    header('Location: dashboard.php');
    exit;
}

$subject_period = $period;
foreach ($to_send as $emp) {
    $stats = get_attendance_stats_for_period($conn, $emp['emp_id'], $year, $month);
    $salary = calculate_employee_salary($emp, $stats, $settings);
    $subject = 'Salary Slip - ' . $subject_period . ' - ' . $emp['name'];
    $email_html = render_salary_slip_email_html($emp, $salary, $settings, $year, $month);
    $pdf_binary = generate_salary_slip_pdf($emp, $salary, $settings, $year, $month);
    $pdf_filename = salary_slip_pdf_filename($emp, $year, $month);

    if ($mailer->send($emp['email'], $emp['name'], $subject, $email_html, $pdf_binary, $pdf_filename)) {
        $sent++;
        $log = $conn->prepare("
            INSERT INTO salary_slip_logs (emp_id, period_month, period_year, net_salary, sent_to, status)
            VALUES (?, ?, ?, ?, ?, 'sent')
        ");
        $log->bind_param('siids', $emp['emp_id'], $month, $year, $salary['net_salary'], $emp['email']);
        $log->execute();
    } else {
        $failed++;
        $errors[] = $emp['emp_id'] . ': ' . ($mailer->getLastError() ?? 'Failed');
        break;
    }
}

$mailer->disconnect();

$msg = "Salary slips for {$period}: {$sent} sent";
if ($skipped > 0) {
    $msg .= ", {$skipped} skipped";
}
if ($failed > 0) {
    $msg .= ", {$failed} failed";
    if (!empty($errors)) {
        $msg .= ' — ' . $errors[0];
    }
}

$_SESSION['flash_message'] = $msg;
$_SESSION['flash_success'] = $sent > 0 && $failed === 0;
header('Location: dashboard.php');
exit;
