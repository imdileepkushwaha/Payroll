<?php
require 'includes/header.php';
require 'config.php';

// Get counts
$emp_count = $conn->query("SELECT COUNT(*) as count FROM employees")->fetch_assoc()['count'];
$att_count = $conn->query("SELECT COUNT(*) as count FROM attendance")->fetch_assoc()['count'];

// Get recent attendance
$recent_attendance = $conn->query("
    SELECT a.*, e.name 
    FROM attendance a 
    JOIN employees e ON a.emp_id = e.emp_id 
    ORDER BY a.attendance_date DESC, a.id DESC 
    LIMIT 10
");
?>
<h2>Dashboard</h2>
<p style="margin-bottom: 20px;">Welcome to the Payroll Admin Panel.</p>

<div class="card-container">
    <div class="card">
        <h3>Total Employees</h3>
        <p style="font-size: 24px; font-weight: bold;"><?php echo $emp_count; ?></p>
    </div>
    <div class="card">
        <h3>Total Attendance Records</h3>
        <p style="font-size: 24px; font-weight: bold;"><?php echo $att_count; ?></p>
    </div>
</div>

<h3>Recent Attendance</h3>
<table>
    <thead>
        <tr>
            <th>Employee ID</th>
            <th>Name</th>
            <th>Date</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($recent_attendance && $recent_attendance->num_rows > 0): ?>
            <?php while ($row = $recent_attendance->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['emp_id']); ?></td>
                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                    <td><?php echo htmlspecialchars($row['attendance_date']); ?></td>
                    <td>
                        <span style="color: <?php echo ($row['status'] == 'Present') ? 'green' : 'red'; ?>;">
                            <?php echo htmlspecialchars($row['status']); ?>
                        </span>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="4" style="text-align: center;">No attendance records found.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<?php require 'includes/footer.php'; ?>
