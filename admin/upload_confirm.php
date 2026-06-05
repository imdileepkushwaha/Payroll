<?php
require_once 'includes/session_auth.php';
enforce_admin_session();
require_once 'includes/csrf_helper.php';
require 'config.php';
require 'includes/attendance_import.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: upload_attendance.php');
    exit;
}

require_csrf_or_redirect('upload_attendance.php');

$pending = $_SESSION['upload_pending'] ?? null;
if (!$pending || empty($pending['rows'])) {
    $_SESSION['upload_success'] = false;
    $_SESSION['upload_message'] = 'No preview data. Please upload the file again.';
    header('Location: upload_attendance.php');
    exit;
}

$result = process_attendance_upload($conn, $pending['rows'], (int) $pending['year'], (int) $pending['month'], false);
unset($_SESSION['upload_pending']);

$upload_month = (int) $pending['month'];
$upload_year = (int) $pending['year'];
$period_label = date('F Y', mktime(0, 0, 0, $upload_month, 1, $upload_year));
$format_note = ($result['format'] ?? '') === 'wide' ? ' (grid format)' : '';

$msg = sprintf(
    'Imported attendance for %s%s: %d records, %d saved.',
    $period_label,
    $format_note,
    $result['row_count'],
    $result['success_count']
);
if ($result['error_count'] > 0) {
    $msg .= ' Errors: ' . $result['error_count'] . '.';
}

$_SESSION['upload_success'] = $result['success_count'] > 0;
$_SESSION['upload_message'] = $msg;
header('Location: upload_attendance.php');
exit;
