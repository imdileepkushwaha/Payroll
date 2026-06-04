<?php

function get_working_days_per_month($settings)
{
    $days = (int) ($settings['working_days_per_month'] ?? 26);
    return $days > 0 ? $days : 26;
}

function get_attendance_stats_for_period($conn, $emp_id, $year, $month)
{
    $stmt = $conn->prepare("
        SELECT
            SUM(CASE WHEN LOWER(status) = 'present' THEN 1 ELSE 0 END) AS present_days,
            SUM(CASE WHEN LOWER(status) = 'absent' THEN 1 ELSE 0 END) AS absent_days,
            COUNT(*) AS total_records
        FROM attendance
        WHERE emp_id = ?
          AND YEAR(attendance_date) = ?
          AND MONTH(attendance_date) = ?
    ");
    $stmt->bind_param('sii', $emp_id, $year, $month);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    return [
        'present_days' => (int) ($row['present_days'] ?? 0),
        'absent_days' => (int) ($row['absent_days'] ?? 0),
        'total_records' => (int) ($row['total_records'] ?? 0),
    ];
}

function calculate_employee_salary($employee, $stats, $settings)
{
    $base_salary = (float) ($employee['base_salary'] ?? 0);
    $working_days = get_working_days_per_month($settings);
    $present_days = $stats['present_days'];
    $absent_days = $stats['absent_days'];

    $daily_rate = $working_days > 0 ? $base_salary / $working_days : 0;
    $earned_salary = round($daily_rate * $present_days, 2);
    $deduction = round($daily_rate * $absent_days, 2);
    $net_salary = max(0, round($earned_salary, 2));

    return [
        'base_salary' => $base_salary,
        'working_days' => $working_days,
        'daily_rate' => round($daily_rate, 2),
        'present_days' => $present_days,
        'absent_days' => $absent_days,
        'earned_salary' => $earned_salary,
        'deduction' => $deduction,
        'net_salary' => $net_salary,
    ];
}

function format_money($amount)
{
    return number_format((float) $amount, 2);
}

function get_period_label($year, $month)
{
    return date('F Y', mktime(0, 0, 0, $month, 1, $year));
}

function render_salary_slip_html($employee, $salary, $settings, $year, $month)
{
    $company = htmlspecialchars($settings['company_name'] ?? 'Company');
    $period = htmlspecialchars(get_period_label($year, $month));
    $name = htmlspecialchars($employee['name']);
    $emp_id = htmlspecialchars($employee['emp_id']);
    $department = htmlspecialchars($employee['department'] ?? '-');
    $designation = htmlspecialchars($employee['designation'] ?? '-');
    $generated = date('d M Y');

    return '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body style="margin:0;padding:20px;font-family:Segoe UI,Arial,sans-serif;background:#f1f5f9;color:#1e293b;">
    <table width="100%" cellpadding="0" cellspacing="0" style="max-width:680px;margin:0 auto;background:#fff;border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;">
        <tr><td style="background:linear-gradient(135deg,#4f46e5,#4338ca);padding:24px 28px;color:#fff;">
            <div style="font-size:11px;letter-spacing:1px;text-transform:uppercase;opacity:.85;margin-bottom:6px;">Official Salary Slip</div>
            <div style="font-size:22px;font-weight:700;">' . $company . '</div>
            <div style="font-size:14px;margin-top:6px;opacity:.9;">Pay Period: ' . $period . '</div>
        </td></tr>
        <tr><td style="padding:24px 28px;">
            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:20px;border:1px solid #e2e8f0;border-radius:6px;overflow:hidden;">
                <tr><td colspan="2" style="background:#f8fafc;padding:10px 14px;font-size:12px;font-weight:700;color:#64748b;text-transform:uppercase;">Employee Details</td></tr>
                <tr><td style="padding:10px 14px;border-top:1px solid #e2e8f0;width:35%;color:#64748b;font-size:13px;">Name</td><td style="padding:10px 14px;border-top:1px solid #e2e8f0;font-weight:600;">' . $name . '</td></tr>
                <tr><td style="padding:10px 14px;border-top:1px solid #e2e8f0;color:#64748b;font-size:13px;">Employee ID</td><td style="padding:10px 14px;border-top:1px solid #e2e8f0;font-weight:600;">' . $emp_id . '</td></tr>
                <tr><td style="padding:10px 14px;border-top:1px solid #e2e8f0;color:#64748b;font-size:13px;">Department</td><td style="padding:10px 14px;border-top:1px solid #e2e8f0;">' . $department . '</td></tr>
                <tr><td style="padding:10px 14px;border-top:1px solid #e2e8f0;color:#64748b;font-size:13px;">Designation</td><td style="padding:10px 14px;border-top:1px solid #e2e8f0;">' . $designation . '</td></tr>
                <tr><td style="padding:10px 14px;border-top:1px solid #e2e8f0;color:#64748b;font-size:13px;">Generated</td><td style="padding:10px 14px;border-top:1px solid #e2e8f0;">' . $generated . '</td></tr>
            </table>
            <table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e2e8f0;border-radius:6px;overflow:hidden;">
                <tr style="background:#f1f5f9;">
                    <th style="padding:12px 14px;text-align:left;font-size:12px;color:#64748b;text-transform:uppercase;">Description</th>
                    <th style="padding:12px 14px;text-align:right;font-size:12px;color:#64748b;text-transform:uppercase;">Amount (INR)</th>
                </tr>
                <tr><td style="padding:11px 14px;border-top:1px solid #e2e8f0;">Base Salary</td><td style="padding:11px 14px;border-top:1px solid #e2e8f0;text-align:right;">' . format_money($salary['base_salary']) . '</td></tr>
                <tr style="background:#fafbfc;"><td style="padding:11px 14px;border-top:1px solid #e2e8f0;">Working Days / Month</td><td style="padding:11px 14px;border-top:1px solid #e2e8f0;text-align:right;">' . (int) $salary['working_days'] . '</td></tr>
                <tr><td style="padding:11px 14px;border-top:1px solid #e2e8f0;">Daily Rate</td><td style="padding:11px 14px;border-top:1px solid #e2e8f0;text-align:right;">' . format_money($salary['daily_rate']) . '</td></tr>
                <tr style="background:#fafbfc;"><td style="padding:11px 14px;border-top:1px solid #e2e8f0;">Present Days</td><td style="padding:11px 14px;border-top:1px solid #e2e8f0;text-align:right;color:#059669;font-weight:600;">' . (int) $salary['present_days'] . '</td></tr>
                <tr><td style="padding:11px 14px;border-top:1px solid #e2e8f0;">Absent Days</td><td style="padding:11px 14px;border-top:1px solid #e2e8f0;text-align:right;color:#dc2626;">' . (int) $salary['absent_days'] . '</td></tr>
                <tr style="background:#fafbfc;"><td style="padding:11px 14px;border-top:1px solid #e2e8f0;">Earned (Present x Daily Rate)</td><td style="padding:11px 14px;border-top:1px solid #e2e8f0;text-align:right;">' . format_money($salary['earned_salary']) . '</td></tr>
                <tr style="background:#ecfdf5;">
                    <td style="padding:14px;font-weight:700;font-size:15px;color:#065f46;border-top:2px solid #10b981;">NET PAYABLE SALARY</td>
                    <td style="padding:14px;text-align:right;font-weight:700;font-size:16px;color:#065f46;border-top:2px solid #10b981;">Rs. ' . format_money($salary['net_salary']) . '</td>
                </tr>
            </table>
            <p style="margin:20px 0 0;font-size:11px;color:#94a3b8;text-align:center;">Computer-generated document. Formula: (Base / Working Days) x Present Days.</p>
        </td></tr>
        <tr><td style="background:#f8fafc;padding:14px 28px;text-align:center;font-size:11px;color:#94a3b8;border-top:1px solid #e2e8f0;">' . $company . ' &bull; Payroll Department &bull; Confidential</td></tr>
    </table></body></html>';
}

function render_salary_slip_email_html($employee, $salary, $settings, $year, $month)
{
    $company = htmlspecialchars($settings['company_name'] ?? 'Company');
    $period = htmlspecialchars(get_period_label($year, $month));
    $name = htmlspecialchars($employee['name']);
    $emp_id = htmlspecialchars($employee['emp_id']);
    $net = format_money($salary['net_salary']);

    return '<!DOCTYPE html><html><head><meta charset="UTF-8"></head>
    <body style="margin:0;padding:0;font-family:Segoe UI,Arial,sans-serif;background:#eef2f7;">
    <table width="100%" cellpadding="0" cellspacing="0" style="padding:32px 16px;">
        <tr><td align="center">
            <table width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 24px rgba(15,23,42,0.08);">
                <tr><td style="background:linear-gradient(135deg,#4f46e5 0%,#6366f1 100%);padding:28px 32px;text-align:center;">
                    <div style="font-size:13px;color:rgba(255,255,255,0.85);letter-spacing:0.5px;text-transform:uppercase;margin-bottom:8px;">' . $company . '</div>
                    <div style="font-size:22px;font-weight:700;color:#ffffff;">Salary Slip — ' . $period . '</div>
                </td></tr>
                <tr><td style="padding:32px;">
                    <p style="margin:0 0 8px;font-size:15px;color:#1e293b;">Dear <strong>' . $name . '</strong>,</p>
                    <p style="margin:0 0 24px;font-size:14px;color:#64748b;line-height:1.6;">Please find your salary slip for <strong>' . $period . '</strong> attached as a PDF document. A summary is provided below for your quick reference.</p>
                    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;margin-bottom:24px;">
                        <tr><td style="padding:16px 20px;">
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="font-size:12px;color:#64748b;padding:4px 0;">Employee ID</td>
                                    <td style="font-size:13px;font-weight:600;color:#1e293b;text-align:right;padding:4px 0;">' . $emp_id . '</td>
                                </tr>
                                <tr>
                                    <td style="font-size:12px;color:#64748b;padding:4px 0;">Present Days</td>
                                    <td style="font-size:13px;font-weight:600;color:#059669;text-align:right;padding:4px 0;">' . (int) $salary['present_days'] . ' days</td>
                                </tr>
                                <tr>
                                    <td style="font-size:12px;color:#64748b;padding:4px 0;">Absent Days</td>
                                    <td style="font-size:13px;text-align:right;padding:4px 0;color:#64748b;">' . (int) $salary['absent_days'] . ' days</td>
                                </tr>
                            </table>
                        </td></tr>
                    </table>
                    <table width="100%" cellpadding="0" cellspacing="0" style="background:linear-gradient(135deg,#ecfdf5,#d1fae5);border:1px solid #6ee7b7;border-radius:8px;margin-bottom:24px;">
                        <tr><td style="padding:20px 24px;text-align:center;">
                            <div style="font-size:12px;color:#047857;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px;">Net Payable Amount</div>
                            <div style="font-size:28px;font-weight:700;color:#065f46;">Rs. ' . $net . '</div>
                        </td></tr>
                    </table>
                    <table width="100%" cellpadding="0" cellspacing="0" style="background:#fffbeb;border:1px solid #fde68a;border-radius:8px;">
                        <tr><td style="padding:14px 18px;">
                            <p style="margin:0;font-size:13px;color:#92400e;line-height:1.5;">
                                <strong>📎 Attachment:</strong> Your detailed salary slip is attached as <em>PDF</em>. Please save it for your records.
                            </p>
                        </td></tr>
                    </table>
                </td></tr>
                <tr><td style="background:#f8fafc;padding:20px 32px;border-top:1px solid #e2e8f0;text-align:center;">
                    <p style="margin:0 0 6px;font-size:12px;color:#64748b;">This is an automated message from the Payroll System.</p>
                    <p style="margin:0;font-size:11px;color:#94a3b8;">For queries, please contact your HR department.</p>
                    <p style="margin:12px 0 0;font-size:11px;color:#cbd5e1;">&copy; ' . date('Y') . ' ' . $company . '. All rights reserved.</p>
                </td></tr>
            </table>
        </td></tr>
    </table></body></html>';
}
