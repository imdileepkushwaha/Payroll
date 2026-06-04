<?php
session_start();
require 'config.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['attendance_file'])) {
    $file = $_FILES['attendance_file'];
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['upload_success'] = false;
        $_SESSION['upload_message'] = "File upload error.";
        header("Location: upload_attendance.php");
        exit;
    }

    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if ($file_ext === 'csv') {
        $handle = fopen($file['tmp_name'], "r");
        
        if ($handle !== FALSE) {
            $row_count = 0;
            $success_count = 0;
            $error_count = 0;
            
            // Assuming first row is header
            $header = fgetcsv($handle, 1000, ",");
            
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                // Adjust index based on actual CSV format
                // Expected: Emp ID, Employee Name, Date (YYYY-MM-DD), Status
                if (count($data) >= 4) {
                    $emp_id = trim($data[0]);
                    $name = trim($data[1]);
                    $date = date('Y-m-d', strtotime(trim($data[2])));
                    $status = trim($data[3]);
                    
                    if (empty($emp_id) || empty($date)) {
                        $error_count++;
                        continue;
                    }

                    // Insert or ignore employee
                    $stmt_emp = $conn->prepare("INSERT IGNORE INTO employees (emp_id, name) VALUES (?, ?)");
                    $stmt_emp->bind_param("ss", $emp_id, $name);
                    $stmt_emp->execute();
                    
                    // Insert or update attendance
                    $stmt_att = $conn->prepare("INSERT INTO attendance (emp_id, attendance_date, status) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE status=?");
                    $stmt_att->bind_param("ssss", $emp_id, $date, $status, $status);
                    
                    if ($stmt_att->execute()) {
                        $success_count++;
                    } else {
                        $error_count++;
                    }
                } else {
                    $error_count++;
                }
                $row_count++;
            }
            fclose($handle);
            
            $_SESSION['upload_success'] = true;
            $_SESSION['upload_message'] = "Processed $row_count rows. Success: $success_count, Errors/Skipped: $error_count.";
            header("Location: upload_attendance.php");
            exit;
        } else {
            $_SESSION['upload_success'] = false;
            $_SESSION['upload_message'] = "Could not read the file.";
            header("Location: upload_attendance.php");
            exit;
        }
    } else {
        $_SESSION['upload_success'] = false;
        $_SESSION['upload_message'] = "Invalid file format. Please upload a CSV file.";
        header("Location: upload_attendance.php");
        exit;
    }
}

header("Location: upload_attendance.php");
exit;
?>
