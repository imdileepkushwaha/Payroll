<?php
require_once 'includes/session_auth.php';
enforce_admin_session();
require_once 'includes/csrf_helper.php';
require 'config.php';
require 'includes/settings_helper.php';
require 'includes/salary_helper.php';
require 'includes/mailer.php';
require 'includes/pdf_slip.php';
require 'includes/employee_helper.php';

function send_slips_wants_json()
{
    return !empty($_POST['ajax'])
        || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
}

function send_slips_json_response($success, $message, $extra = [])
{
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge([
        'success' => (bool) $success,
        'message' => $message,
    ], $extra));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

$is_ajax = send_slips_wants_json();

if (!$is_ajax) {
    require_csrf_or_redirect('dashboard.php');
} else {
    if (!verify_csrf()) {
        send_slips_json_response(false, 'Security check failed. Refresh the page and try again.');
    }
}

$month = (int) ($_POST['month'] ?? date('n'));
$year = (int) ($_POST['year'] ?? date('Y'));
$period = get_period_label($year, $month);
$selected_ids = isset($_POST['emp_ids']) && is_array($_POST['emp_ids']) ? array_map('trim', $_POST['emp_ids']) : [];

if ($month < 1 || $month > 12 || $year < 2000) {
    if ($is_ajax) {
        send_slips_json_response(false, 'Invalid month or year selected.');
    }
    $_SESSION['flash_message'] = 'Invalid month or year selected.';
    $_SESSION['flash_success'] = false;
    header('Location: dashboard.php');
    exit;
}

$settings = get_all_settings($conn);

if (!is_smtp_configured($settings) || empty($settings['smtp_password'])) {
    if ($is_ajax) {
        send_slips_json_response(false, 'Configure SMTP and save password in Settings before sending.');
    }
    $_SESSION['flash_message'] = 'Configure SMTP and save password in Settings before sending.';
    $_SESSION['flash_success'] = false;
    header('Location: dashboard.php');
    exit;
}

$employees = $conn->query('SELECT * FROM employees ORDER BY name');
$to_send = [];

while ($emp = $employees->fetch_assoc()) {
    if (!empty($selected_ids) && !in_array($emp['emp_id'], $selected_ids, true)) {
        continue;
    }
    if (!employee_is_active($emp)) {
        continue;
    }
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

if (count($to_send) === 0) {
    $msg = "No eligible employees for {$period}. Need active, email, salary, and attendance.";
    if ($is_ajax) {
        send_slips_json_response(false, $msg);
    }
    $_SESSION['flash_message'] = $msg;
    $_SESSION['flash_success'] = false;
    header('Location: dashboard.php?month=' . $month . '&year=' . $year);
    exit;
}

$mailer = new SmtpMailer($settings);
if (!$mailer->connect()) {
    $err = 'SMTP connection failed: ' . ($mailer->getLastError() ?? 'Unknown error');
    if ($is_ajax) {
        send_slips_json_response(false, $err);
    }
    $_SESSION['flash_message'] = $err;
    $_SESSION['flash_success'] = false;
    header('Location: dashboard.php');
    exit;
}

$sent = 0;
$failed = 0;
$errors = [];

foreach ($to_send as $emp) {
    $stats = get_attendance_stats_for_period($conn, $emp['emp_id'], $year, $month);
    $salary = calculate_employee_salary($emp, $stats, $settings);
    $subject = 'Salary Slip - ' . $period . ' - ' . $emp['name'];
    $email_html = render_salary_slip_email_html($emp, $salary, $settings, $year, $month);
    $pdf_binary = generate_salary_slip_pdf($emp, $salary, $settings, $year, $month);
    $pdf_filename = salary_slip_pdf_filename($emp, $year, $month);

    if ($mailer->send($emp['email'], $emp['name'], $subject, $email_html, $pdf_binary, $pdf_filename)) {
        $sent++;
        $log = $conn->prepare("
            INSERT INTO salary_slip_logs (emp_id, period_month, period_year, net_salary, sent_to, status)
            VALUES (?, ?, ?, ?, ?, 'sent')
        ");
        $net = $salary['net_salary'];
        $log->bind_param('siids', $emp['emp_id'], $month, $year, $net, $emp['email']);
        $log->execute();
    } else {
        $failed++;
        $errors[] = $emp['emp_id'] . ': ' . ($mailer->getLastError() ?? 'Failed');
        $log = $conn->prepare("
            INSERT INTO salary_slip_logs (emp_id, period_month, period_year, net_salary, sent_to, status)
            VALUES (?, ?, ?, ?, ?, 'failed')
        ");
        $net = $salary['net_salary'];
        $email = $emp['email'];
        $log->bind_param('siids', $emp['emp_id'], $month, $year, $net, $email);
        $log->execute();
    }
}

$mailer->disconnect();

$msg = "Salary slips for {$period}: {$sent} sent";
if ($failed > 0) {
    $msg .= ", {$failed} failed";
    if (count($errors) <= 3) {
        $msg .= ' — ' . implode('; ', $errors);
    } else {
        $msg .= ' — ' . implode('; ', array_slice($errors, 0, 2)) . '…';
    }
}

$all_ok = $sent > 0 && $failed === 0;

if ($is_ajax) {
    $_SESSION['flash_message'] = $msg;
    $_SESSION['flash_success'] = $all_ok;
    send_slips_json_response($all_ok, $msg, [
        'sent' => $sent,
        'failed' => $failed,
        'total' => count($to_send),
        'month' => $month,
        'year' => $year,
    ]);
}

$_SESSION['flash_message'] = $msg;
$_SESSION['flash_success'] = $all_ok;
header('Location: dashboard.php?month=' . $month . '&year=' . $year);
exit;
