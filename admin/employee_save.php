<?php
session_start();
require 'config.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: employees.php');
    exit;
}

$action = $_POST['action'] ?? '';

if ($action === 'add') {
    $emp_id = trim($_POST['emp_id'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $designation = trim($_POST['designation'] ?? '');
    $base_salary = (float) ($_POST['base_salary'] ?? 0);
    $joined_date = trim($_POST['joined_date'] ?? '');
    $joined_date = $joined_date !== '' ? $joined_date : null;

    if ($emp_id === '' || $name === '') {
        $_SESSION['flash_message'] = 'Employee ID and name are required.';
        $_SESSION['flash_success'] = false;
        header('Location: employees.php');
        exit;
    }

    $stmt = $conn->prepare("
        INSERT INTO employees (emp_id, name, email, phone, department, designation, base_salary, joined_date)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param('ssssssds', $emp_id, $name, $email, $phone, $department, $designation, $base_salary, $joined_date);

    if ($stmt->execute()) {
        $_SESSION['flash_message'] = 'Employee added successfully.';
        $_SESSION['flash_success'] = true;
    } else {
        $_SESSION['flash_message'] = 'Could not add employee. ID may already exist.';
        $_SESSION['flash_success'] = false;
    }
    header('Location: employees.php');
    exit;
}

if ($action === 'update') {
    $emp_id = trim($_POST['emp_id'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $designation = trim($_POST['designation'] ?? '');
    $base_salary = (float) ($_POST['base_salary'] ?? 0);

    $joined_date = trim($_POST['joined_date'] ?? '');
    $joined_date = $joined_date !== '' ? $joined_date : null;

    $stmt = $conn->prepare("
        UPDATE employees SET name=?, email=?, phone=?, department=?, designation=?, base_salary=?, joined_date=?
        WHERE emp_id=?
    ");
    $stmt->bind_param('ssssssds', $name, $email, $phone, $department, $designation, $base_salary, $joined_date, $emp_id);

    if ($stmt->execute()) {
        $_SESSION['flash_message'] = 'Employee updated successfully.';
        $_SESSION['flash_success'] = true;
    } else {
        $_SESSION['flash_message'] = 'Update failed.';
        $_SESSION['flash_success'] = false;
    }
    header('Location: employee_view.php?emp_id=' . urlencode($emp_id));
    exit;
}

header('Location: employees.php');
exit;
