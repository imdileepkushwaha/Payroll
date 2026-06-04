<?php

require_once __DIR__ . '/../lib/fpdf.php';
require_once __DIR__ . '/signature_helper.php';

function pdf_set_fill($pdf, $r, $g, $b)
{
    $pdf->SetFillColor($r, $g, $b);
}

function pdf_set_draw($pdf, $r, $g, $b)
{
    $pdf->SetDrawColor($r, $g, $b);
}

function pdf_set_text($pdf, $r, $g, $b)
{
    $pdf->SetTextColor($r, $g, $b);
}

function pdf_section_title($pdf, $title, $y = null)
{
    if ($y !== null) {
        $pdf->SetY($y);
    }
    pdf_set_fill($pdf, 248, 250, 252);
    pdf_set_draw($pdf, 226, 232, 240);
    pdf_set_text($pdf, 51, 65, 85);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(190, 7, '  ' . strtoupper($title), 'LTR', 1, 'L', true);
    pdf_set_draw($pdf, 226, 232, 240);
    $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
}

function pdf_amount_row($pdf, $label, $value, $bold = false, $highlight = false)
{
    $rowH = 8;
    if ($highlight) {
        pdf_set_fill($pdf, 238, 242, 255);
        pdf_set_text($pdf, 79, 70, 229);
        $pdf->SetFont('Arial', 'B', 10);
    } elseif ($bold) {
        pdf_set_fill($pdf, 255, 255, 255);
        pdf_set_text($pdf, 30, 41, 59);
        $pdf->SetFont('Arial', 'B', 9);
    } else {
        pdf_set_fill($pdf, 255, 255, 255);
        pdf_set_text($pdf, 51, 65, 85);
        $pdf->SetFont('Arial', '', 9);
    }
    pdf_set_draw($pdf, 226, 232, 240);
    $pdf->Cell(125, $rowH, '  ' . $label, 1, 0, 'L', true);
    $pdf->Cell(65, $rowH, $value, 1, 1, 'R', true);
}

function generate_salary_slip_pdf($employee, $salary, $settings, $year, $month)
{
    $company = $settings['company_name'] ?? 'Company';
    $period = get_period_label($year, $month);
    $generated = date('d M Y, h:i A');
    $email = trim($employee['email'] ?? '');

    $pdf = new FPDF('P', 'mm', 'A4');
    $pdf->SetMargins(10, 10, 10);
    $pdf->AddPage();
    $pdf->SetAutoPageBreak(true, 22);

    // Page frame
    pdf_set_draw($pdf, 226, 232, 240);
    $pdf->Rect(10, 10, 190, 277);

    // Header block
    pdf_set_fill($pdf, 30, 41, 59);
    $pdf->Rect(10, 10, 190, 32, 'F');
    pdf_set_fill($pdf, 79, 70, 229);
    $pdf->Rect(10, 10, 6, 32, 'F');

    pdf_set_text($pdf, 255, 255, 255);
    $pdf->SetFont('Arial', 'B', 20);
    $pdf->SetXY(20, 15);
    $pdf->Cell(120, 9, $company, 0, 1, 'L');

    $pdf->SetFont('Arial', '', 10);
    $pdf->SetX(20);
    $pdf->Cell(120, 5, 'Salary Statement', 0, 1, 'L');

    $pdf->SetFont('Arial', 'B', 11);
    $pdf->SetXY(138, 14);
    $pdf->Cell(56, 6, 'PAYSLIP', 0, 1, 'R');
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetX(138);
    $pdf->Cell(56, 5, $period, 0, 1, 'R');
    $pdf->SetFont('Arial', '', 8);
    $pdf->SetX(138);
    $pdf->Cell(56, 5, 'Doc: ' . date('Ymd-His'), 0, 1, 'R');

    // Meta strip under header
    $pdf->SetY(44);
    pdf_set_fill($pdf, 241, 245, 249);
    pdf_set_draw($pdf, 226, 232, 240);
    pdf_set_text($pdf, 71, 85, 105);
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(63, 6, 'Employee ID: ' . $employee['emp_id'], 1, 0, 'L', true);
    $pdf->Cell(64, 6, 'Pay Period: ' . $period, 1, 0, 'C', true);
    $pdf->Cell(63, 6, 'Generated: ' . date('d M Y'), 1, 1, 'R', true);

    $pdf->Ln(4);

    // Employee details — two columns
    pdf_section_title($pdf, 'Employee Details');
    $pdf->SetFont('Arial', '', 9);

    $left = [
        ['Full Name', $employee['name']],
        ['Department', $employee['department'] ?: '-'],
        ['Designation', $employee['designation'] ?: '-'],
    ];
    $right = [
        ['Employee ID', $employee['emp_id']],
        ['Email', $email !== '' ? $email : '-'],
        ['Phone', trim($employee['phone'] ?? '') ?: '-'],
    ];

    for ($i = 0; $i < count($left); $i++) {
        $y = $pdf->GetY();
        pdf_set_fill($pdf, 248, 250, 252);
        pdf_set_text($pdf, 100, 116, 139);
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(28, 7, $left[$i][0], 1, 0, 'L', true);
        pdf_set_fill($pdf, 255, 255, 255);
        pdf_set_text($pdf, 30, 41, 59);
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(67, 7, $left[$i][1], 1, 0, 'L');

        pdf_set_fill($pdf, 248, 250, 252);
        pdf_set_text($pdf, 100, 116, 139);
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(28, 7, $right[$i][0], 1, 0, 'L', true);
        pdf_set_fill($pdf, 255, 255, 255);
        pdf_set_text($pdf, 30, 41, 59);
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(67, 7, $right[$i][1], 1, 1, 'L');
        if ($pdf->GetY() <= $y) {
            $pdf->SetY($y + 7);
        }
    }

    $pdf->Ln(5);

    // Attendance summary cards
    pdf_section_title($pdf, 'Attendance Summary');
    $cardW = 62;
    $cardH = 18;
    $startX = 10;
    $startY = $pdf->GetY();

    $cards = [
        ['Present Days', (string) (int) $salary['present_days'], [16, 185, 129], [209, 250, 229], [6, 95, 70]],
        ['Absent Days', (string) (int) $salary['absent_days'], [239, 68, 68], [254, 226, 226], [153, 27, 27]],
        ['Working Days', (string) (int) $salary['working_days'], [79, 70, 229], [238, 242, 255], [67, 56, 202]],
    ];

    foreach ($cards as $idx => $card) {
        $x = $startX + ($idx * ($cardW + 2));
        pdf_set_draw($pdf, 226, 232, 240);
        pdf_set_fill($pdf, $card[3][0], $card[3][1], $card[3][2]);
        $pdf->Rect($x, $startY, $cardW, $cardH, 'DF');
        pdf_set_fill($pdf, $card[2][0], $card[2][1], $card[2][2]);
        $pdf->Rect($x, $startY, $cardW, 4, 'F');
        pdf_set_text($pdf, $card[4][0], $card[4][1], $card[4][2]);
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetXY($x + 3, $startY + 6);
        $pdf->Cell($cardW - 6, 4, strtoupper($card[0]), 0, 1, 'L');
        pdf_set_text($pdf, 30, 41, 59);
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->SetX($x + 3);
        $pdf->Cell($cardW - 6, 8, $card[1], 0, 0, 'L');
    }

    $pdf->SetY($startY + $cardH + 6);

    // Salary breakdown
    pdf_section_title($pdf, 'Earnings & Calculation');
    pdf_set_fill($pdf, 30, 41, 59);
    pdf_set_text($pdf, 255, 255, 255);
    pdf_set_draw($pdf, 30, 41, 59);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell(125, 7, '  Description', 1, 0, 'L', true);
    $pdf->Cell(65, 7, 'Amount (INR)  ', 1, 1, 'R', true);

    pdf_amount_row($pdf, 'Monthly Base Salary', format_money($salary['base_salary']));
    pdf_amount_row($pdf, 'Working Days in Month', (string) (int) $salary['working_days']);
    pdf_amount_row($pdf, 'Daily Rate (Base / Working Days)', format_money($salary['daily_rate']));
    pdf_amount_row($pdf, 'Days Present', (string) (int) $salary['present_days']);
    pdf_amount_row($pdf, 'Earned Salary (Present x Daily Rate)', format_money($salary['earned_salary']), true);

    $pdf->Ln(2);

    // Net payable highlight
    pdf_set_fill($pdf, 79, 70, 229);
    pdf_set_draw($pdf, 67, 56, 202);
    pdf_set_text($pdf, 255, 255, 255);
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(125, 12, '  NET PAYABLE AMOUNT', 1, 0, 'L', true);
    $pdf->SetFont('Arial', 'B', 13);
    $pdf->Cell(65, 12, 'Rs. ' . format_money($salary['net_salary']), 1, 1, 'R', true);

    $pdf->Ln(1);
    pdf_set_text($pdf, 100, 116, 139);
    $pdf->SetFont('Arial', 'I', 8);
    $pdf->MultiCell(
        190,
        4,
        'Calculation: Net Payable = (Base Salary / Working Days) x Present Days. All amounts in Indian Rupees (INR).',
        0,
        'L'
    );

    $sigPath = payslip_signature_absolute_path($settings);
    $authorityName = trim($settings['signature_authority_name'] ?? 'Authorized Signatory');

    $pdf->Ln(8);
    $yBlock = $pdf->GetY();

    pdf_set_draw($pdf, 226, 232, 240);
    $pdf->Line(10, $yBlock, 200, $yBlock);
    $pdf->Ln(6);
    $yBlock = $pdf->GetY();

    if ($sigPath) {
        $imgW = 48;
        $imgX = 152;
        $pdf->Image($sigPath, $imgX, $yBlock, $imgW);
        $imgH = 16;
        $lineY = $yBlock + $imgH + 1;
        pdf_set_draw($pdf, 148, 163, 184);
        $pdf->Line($imgX, $lineY, $imgX + $imgW, $lineY);
        $pdf->SetXY($imgX, $lineY + 2);
        pdf_set_text($pdf, 51, 65, 85);
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell($imgW, 5, $authorityName, 0, 1, 'C');
        $pdf->SetFont('Arial', '', 8);
        pdf_set_text($pdf, 148, 163, 184);
        $pdf->SetX($imgX);
        $pdf->Cell($imgW, 4, 'Authorized Signatory', 0, 1, 'C');

        $pdf->SetY($yBlock);
        pdf_set_text($pdf, 100, 116, 139);
        $pdf->SetFont('Arial', '', 8);
        $pdf->MultiCell(
            120,
            4,
            "This is a computer-generated salary slip and is valid without a physical signature unless your organization's policy requires otherwise.",
            0,
            'L'
        );
        $pdf->SetY(max($pdf->GetY(), $lineY + 14));
    } else {
        pdf_set_text($pdf, 100, 116, 139);
        $pdf->SetFont('Arial', '', 8);
        $pdf->MultiCell(
            190,
            4,
            'This is a computer-generated document. Upload an authorized signature in Settings to display it on payslips.',
            0,
            'C'
        );
    }

    // Footer
    $pdf->SetY(-18);
    pdf_set_draw($pdf, 226, 232, 240);
    $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
    $pdf->Ln(3);
    pdf_set_text($pdf, 148, 163, 184);
    $pdf->SetFont('Arial', '', 7);
    $pdf->Cell(0, 4, $company . '  |  Confidential — For employee use only  |  Payroll System', 0, 1, 'C');
    $pdf->Cell(0, 4, 'Generated on ' . $generated, 0, 1, 'C');

    return $pdf->Output('S');
}

function salary_slip_pdf_filename($employee, $year, $month)
{
    $safe_id = preg_replace('/[^a-zA-Z0-9_-]/', '_', $employee['emp_id']);
    return 'Salary_Slip_' . $safe_id . '_' . $year . '_' . str_pad((string) $month, 2, '0', STR_PAD_LEFT) . '.pdf';
}
