<?php
session_start();
require 'config.php';
require 'includes/attendance_import.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['attendance_file'])) {
    $file = $_FILES['attendance_file'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['upload_success'] = false;
        $_SESSION['upload_message'] = "File upload error.";
        header("Location: upload_attendance.php");
        exit;
    }

    $allowed = ['csv', 'xlsx', 'xls'];
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($file_ext, $allowed, true)) {
        $_SESSION['upload_success'] = false;
        $_SESSION['upload_message'] = "Invalid file format. Please upload CSV or Excel (.csv, .xlsx, .xls).";
        header("Location: upload_attendance.php");
        exit;
    }

    $parsed = read_attendance_file_rows($file['tmp_name'], $file_ext);

    if (isset($parsed['error'])) {
        $_SESSION['upload_success'] = false;
        $_SESSION['upload_message'] = $parsed['error'];
        header("Location: upload_attendance.php");
        exit;
    }

    $result = process_attendance_rows($conn, $parsed['rows']);

    $_SESSION['upload_success'] = true;
    $_SESSION['upload_message'] = sprintf(
        "Processed %d rows. Success: %d, Errors/Skipped: %d.",
        $result['row_count'],
        $result['success_count'],
        $result['error_count']
    );
    header("Location: upload_attendance.php");
    exit;
}

header("Location: upload_attendance.php");
exit;
