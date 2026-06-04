<?php

function normalize_attendance_date($value)
{
    $value = trim((string) $value);
    if ($value === '') {
        return null;
    }

    if (is_numeric($value) && (float) $value > 0) {
        $serial = (float) $value;
        if ($serial > 59) {
            $unix = (int) round(($serial - 25569) * 86400);
            return gmdate('Y-m-d', $unix);
        }
    }

    $timestamp = strtotime($value);
    if ($timestamp === false) {
        return null;
    }

    return date('Y-m-d', $timestamp);
}

function process_attendance_rows($conn, array $rows, $skip_header = true)
{
    $row_count = 0;
    $success_count = 0;
    $error_count = 0;
    $is_first = true;

    foreach ($rows as $data) {
        if (!is_array($data)) {
            continue;
        }

        if ($skip_header && $is_first) {
            $is_first = false;
            continue;
        }
        $is_first = false;

        if (count($data) < 4) {
            $error_count++;
            continue;
        }

        $emp_id = trim((string) $data[0]);
        $name = trim((string) $data[1]);
        $date = normalize_attendance_date($data[2]);
        $status = trim((string) $data[3]);

        if ($emp_id === '' || $date === null) {
            $error_count++;
            continue;
        }

        $stmt_emp = $conn->prepare("INSERT IGNORE INTO employees (emp_id, name) VALUES (?, ?)");
        $stmt_emp->bind_param("ss", $emp_id, $name);
        $stmt_emp->execute();

        $stmt_att = $conn->prepare(
            "INSERT INTO attendance (emp_id, attendance_date, status) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE status=?"
        );
        $stmt_att->bind_param("ssss", $emp_id, $date, $status, $status);

        if ($stmt_att->execute()) {
            $success_count++;
        } else {
            $error_count++;
        }

        $row_count++;
    }

    return [
        'row_count' => $row_count,
        'success_count' => $success_count,
        'error_count' => $error_count,
    ];
}

function read_attendance_file_rows($tmp_path, $extension)
{
    $extension = strtolower($extension);

    if ($extension === 'csv') {
        $handle = fopen($tmp_path, 'r');
        if ($handle === false) {
            return ['error' => 'Could not read the CSV file.'];
        }

        $rows = [];
        while (($data = fgetcsv($handle, 1000, ',')) !== false) {
            $rows[] = $data;
        }
        fclose($handle);

        return ['rows' => $rows];
    }

    if ($extension === 'xlsx') {
        require_once __DIR__ . '/../lib/SimpleXLSX.php';

        $xlsx = \Shuchkin\SimpleXLSX::parse($tmp_path);
        if (!$xlsx) {
            return ['error' => 'Could not read the Excel file: ' . \Shuchkin\SimpleXLSX::parseError()];
        }

        return ['rows' => $xlsx->rows()];
    }

    if ($extension === 'xls') {
        require_once __DIR__ . '/../lib/SimpleXLS.php';

        $xls = SimpleXLS::parse($tmp_path);
        if (!$xls) {
            return ['error' => 'Could not read the Excel file: ' . SimpleXLS::parseError()];
        }

        return ['rows' => $xls->rows()];
    }

    return ['error' => 'Invalid file format. Please upload a CSV or Excel file (.csv, .xlsx, .xls).'];
}
