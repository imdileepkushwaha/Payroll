<?php
session_start();
require 'config.php';
require 'includes/settings_helper.php';
require 'includes/signature_helper.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: settings.php');
    exit;
}

$section = $_POST['section'] ?? '';

if ($section === 'smtp') {
    set_setting($conn, 'smtp_host', trim($_POST['smtp_host'] ?? ''));
    set_setting($conn, 'smtp_port', trim($_POST['smtp_port'] ?? '587'));
    set_setting($conn, 'smtp_encryption', trim($_POST['smtp_encryption'] ?? 'tls'));
    set_setting($conn, 'smtp_username', trim($_POST['smtp_username'] ?? ''));
    set_setting($conn, 'smtp_from_email', trim($_POST['smtp_from_email'] ?? ''));
    set_setting($conn, 'smtp_from_name', trim($_POST['smtp_from_name'] ?? 'Payroll System'));

    $new_pass = $_POST['smtp_password'] ?? '';
    if ($new_pass !== '') {
        set_setting($conn, 'smtp_password', $new_pass);
    }

    $_SESSION['flash_message'] = 'SMTP settings saved.';
    $_SESSION['flash_success'] = true;
    header('Location: settings.php?tab=smtp');
    exit;
}

if ($section === 'password') {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($new !== $confirm) {
        $_SESSION['flash_message'] = 'New passwords do not match.';
        $_SESSION['flash_success'] = false;
        header('Location: settings.php?tab=password');
        exit;
    }

    if (strlen($new) < 6) {
        $_SESSION['flash_message'] = 'Password must be at least 6 characters.';
        $_SESSION['flash_success'] = false;
        header('Location: settings.php?tab=password');
        exit;
    }

    $username = $_SESSION['admin_username'];
    $stmt = $conn->prepare("SELECT password FROM admin_users WHERE username = ?");
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if (!$row || !password_verify($current, $row['password'])) {
        $_SESSION['flash_message'] = 'Current password is incorrect.';
        $_SESSION['flash_success'] = false;
        header('Location: settings.php?tab=password');
        exit;
    }

    $hash = password_hash($new, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE admin_users SET password = ? WHERE username = ?");
    $stmt->bind_param('ss', $hash, $username);
    $stmt->execute();

    $_SESSION['flash_message'] = 'Password updated successfully.';
    $_SESSION['flash_success'] = true;
    header('Location: settings.php?tab=password');
    exit;
}

if ($section === 'payroll') {
    set_setting($conn, 'company_name', trim($_POST['company_name'] ?? ''));
    set_setting($conn, 'working_days_per_month', trim($_POST['working_days_per_month'] ?? '26'));
    set_setting($conn, 'signature_authority_name', trim($_POST['signature_authority_name'] ?? 'Authorized Signatory'));

    $messages = ['Payroll settings saved.'];

    if (!empty($_POST['remove_signature'])) {
        remove_payslip_signature();
        set_setting($conn, 'payslip_signature', '');
        $messages[] = 'Signature removed.';
    } elseif (isset($_FILES['payslip_signature']) && $_FILES['payslip_signature']['error'] !== UPLOAD_ERR_NO_FILE) {
        $result = save_payslip_signature_upload($_FILES['payslip_signature']);
        if ($result['success']) {
            set_setting($conn, 'payslip_signature', $result['path']);
            $messages[] = 'Signature uploaded for payslip PDF.';
        } else {
            $_SESSION['flash_message'] = $result['message'];
            $_SESSION['flash_success'] = false;
            header('Location: settings.php?tab=payroll');
            exit;
        }
    }

    $_SESSION['flash_message'] = implode(' ', $messages);
    $_SESSION['flash_success'] = true;
    header('Location: settings.php?tab=payroll');
    exit;
}

header('Location: settings.php');
exit;
