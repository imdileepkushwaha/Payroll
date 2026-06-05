<?php
require_once 'includes/session_auth.php';
enforce_admin_session();
require_once 'includes/csrf_helper.php';
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: employees.php');
    exit;
}

require_csrf_or_redirect('employees.php');

$emp_id = trim($_POST['emp_id'] ?? '');
$date = trim($_POST['attendance_date'] ?? '');
$status = trim($_POST['status'] ?? '');
$action = $_POST['attendance_action'] ?? 'save';
$month = (int) ($_POST['month'] ?? date('n'));
$year = (int) ($_POST['year'] ?? date('Y'));
$redirect = 'employee_view.php?emp_id=' . urlencode($emp_id) . '&month=' . $month . '&year=' . $year;

if ($emp_id === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    $_SESSION['flash_message'] = 'Invalid employee or date.';
    $_SESSION['flash_success'] = false;
    header('Location: ' . $redirect);
    exit;
}

$check = $conn->prepare('SELECT emp_id FROM employees WHERE emp_id = ?');
$check->bind_param('s', $emp_id);
$check->execute();
if (!$check->get_result()->fetch_assoc()) {
    $_SESSION['flash_message'] = 'Employee not found.';
    $_SESSION['flash_success'] = false;
    header('Location: employees.php');
    exit;
}

if ($action === 'delete') {
    $del = $conn->prepare('DELETE FROM attendance WHERE emp_id = ? AND attendance_date = ?');
    $del->bind_param('ss', $emp_id, $date);
    $del->execute();
    $_SESSION['flash_message'] = 'Attendance record removed.';
    $_SESSION['flash_success'] = true;
    header('Location: ' . $redirect);
    exit;
}

$allowed = ['Present', 'Absent', 'Half day', 'Leave'];
if (!in_array($status, $allowed, true)) {
    $_SESSION['flash_message'] = 'Invalid status. Use Present, Absent, Half day, or Leave.';
    $_SESSION['flash_success'] = false;
    header('Location: ' . $redirect);
    exit;
}

$stmt = $conn->prepare('
    INSERT INTO attendance (emp_id, attendance_date, status) VALUES (?, ?, ?)
    ON DUPLICATE KEY UPDATE status = VALUES(status)
');
$stmt->bind_param('sss', $emp_id, $date, $status);

if ($stmt->execute()) {
    $_SESSION['flash_message'] = 'Attendance saved for ' . date('d M Y', strtotime($date)) . '.';
    $_SESSION['flash_success'] = true;
} else {
    $_SESSION['flash_message'] = 'Could not save attendance.';
    $_SESSION['flash_success'] = false;
}

header('Location: ' . $redirect);
exit;
