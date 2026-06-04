<?php
require 'includes/header.php';
require 'config.php';
require 'includes/settings_helper.php';
require 'includes/salary_helper.php';

$emp_id = trim($_GET['emp_id'] ?? '');
if ($emp_id === '') {
    header('Location: employees.php');
    exit;
}

$stmt = $conn->prepare("SELECT * FROM employees WHERE emp_id = ?");
$stmt->bind_param('s', $emp_id);
$stmt->execute();
$employee = $stmt->get_result()->fetch_assoc();

if (!$employee) {
    $_SESSION['flash_message'] = 'Employee not found.';
    $_SESSION['flash_success'] = false;
    header('Location: employees.php');
    exit;
}

$settings = get_all_settings($conn);
$year = (int) ($_GET['year'] ?? date('Y'));
$month = (int) ($_GET['month'] ?? date('n'));

$stats = get_attendance_stats_for_period($conn, $emp_id, $year, $month);
$salary = calculate_employee_salary($employee, $stats, $settings);

$att_stmt = $conn->prepare("
    SELECT * FROM attendance
    WHERE emp_id = ?
    ORDER BY attendance_date DESC
    LIMIT 100
");
$att_stmt->bind_param('s', $emp_id);
$att_stmt->execute();
$attendance = $att_stmt->get_result();

$initial = strtoupper(substr($employee['name'], 0, 1));
?>
<div class="page-header page-header-row">
    <div class="page-header-main">
        <p class="page-eyebrow"><a href="employees.php">Employees</a> / Profile</p>
        <h2><?php echo htmlspecialchars($employee['name']); ?></h2>
        <p><?php echo htmlspecialchars($employee['emp_id']); ?> · <?php echo htmlspecialchars($employee['designation'] ?: 'Staff'); ?></p>
    </div>
    <div class="page-header-actions">
        <a href="#edit-form" class="btn btn-outline">Edit</a>
        <form method="POST" action="employee_delete.php" class="inline-delete-form" onsubmit="return confirm('Delete ' + <?php echo json_encode($employee['name']); ?> + ' and all attendance records? This cannot be undone.');">
            <input type="hidden" name="emp_id" value="<?php echo htmlspecialchars($employee['emp_id']); ?>">
            <button type="submit" class="btn btn-danger">Delete</button>
        </form>
        <a href="employees.php" class="btn btn-outline">← Back</a>
    </div>
</div>

<?php if (isset($_SESSION['flash_message'])): ?>
    <div class="alert <?php echo $_SESSION['flash_success'] ? 'alert-success' : 'alert-error'; ?> alert-page">
        <?php echo htmlspecialchars($_SESSION['flash_message']); unset($_SESSION['flash_message'], $_SESSION['flash_success']); ?>
    </div>
<?php endif; ?>

<div class="detail-grid">
    <div class="detail-card profile-card">
        <div class="profile-hero">
            <span class="emp-avatar emp-avatar-lg"><?php echo htmlspecialchars($initial); ?></span>
            <div>
                <h3><?php echo htmlspecialchars($employee['name']); ?></h3>
                <span class="emp-id"><?php echo htmlspecialchars($employee['emp_id']); ?></span>
            </div>
        </div>
        <dl class="detail-list">
            <div><dt>Email</dt><dd><?php echo htmlspecialchars($employee['email'] ?: '—'); ?></dd></div>
            <div><dt>Phone</dt><dd><?php echo htmlspecialchars($employee['phone'] ?: '—'); ?></dd></div>
            <div><dt>Department</dt><dd><?php echo htmlspecialchars($employee['department'] ?: '—'); ?></dd></div>
            <div><dt>Designation</dt><dd><?php echo htmlspecialchars($employee['designation'] ?: '—'); ?></dd></div>
            <div><dt>Joined</dt><dd><?php echo htmlspecialchars($employee['joined_date'] ?: '—'); ?></dd></div>
            <div><dt>Base Salary</dt><dd><strong>₹<?php echo number_format((float) $employee['base_salary'], 2); ?></strong></dd></div>
        </dl>
    </div>

    <div class="detail-card" id="edit-form">
        <h3 class="card-title">Edit employee details</h3>
        <form method="POST" action="employee_save.php" class="stack-form">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="emp_id" value="<?php echo htmlspecialchars($employee['emp_id']); ?>">
            <div class="form-row">
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($employee['name']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($employee['email'] ?? ''); ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" name="phone" value="<?php echo htmlspecialchars($employee['phone'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Department</label>
                    <input type="text" name="department" value="<?php echo htmlspecialchars($employee['department'] ?? ''); ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Designation</label>
                    <input type="text" name="designation" value="<?php echo htmlspecialchars($employee['designation'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Joined Date</label>
                    <input type="date" name="joined_date" value="<?php echo htmlspecialchars($employee['joined_date'] ?? ''); ?>">
                </div>
            </div>
            <div class="form-group">
                <label>Base Salary (₹)</label>
                <input type="number" name="base_salary" min="0" step="0.01" value="<?php echo htmlspecialchars($employee['base_salary']); ?>" required>
            </div>
            <button type="submit" class="btn">Save Changes</button>
        </form>
    </div>
</div>

<div class="panel panel-elevated">
    <div class="panel-header">
        <div class="panel-title-group">
            <h3>Salary for <?php echo htmlspecialchars(get_period_label($year, $month)); ?></h3>
            <span class="panel-badge">Based on attendance</span>
        </div>
        <form method="GET" class="inline-filter">
            <input type="hidden" name="emp_id" value="<?php echo htmlspecialchars($emp_id); ?>">
            <select name="month">
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?php echo $m; ?>" <?php echo $m === $month ? 'selected' : ''; ?>><?php echo date('F', mktime(0, 0, 0, $m, 1)); ?></option>
                <?php endfor; ?>
            </select>
            <select name="year">
                <?php for ($y = (int) date('Y'); $y >= (int) date('Y') - 3; $y--): ?>
                    <option value="<?php echo $y; ?>" <?php echo $y === $year ? 'selected' : ''; ?>><?php echo $y; ?></option>
                <?php endfor; ?>
            </select>
            <button type="submit" class="btn btn-sm">Apply</button>
        </form>
    </div>
    <div class="panel-body padded">
        <div class="salary-summary">
            <div class="salary-stat"><span>Present days</span><strong><?php echo (int) $salary['present_days']; ?></strong></div>
            <div class="salary-stat"><span>Absent days</span><strong><?php echo (int) $salary['absent_days']; ?></strong></div>
            <div class="salary-stat"><span>Daily rate</span><strong>₹<?php echo format_money($salary['daily_rate']); ?></strong></div>
            <div class="salary-stat highlight"><span>Net salary</span><strong>₹<?php echo format_money($salary['net_salary']); ?></strong></div>
        </div>
        <p class="form-hint">Formula: (Base ÷ <?php echo (int) $salary['working_days']; ?> working days) × present days = net payable</p>
    </div>
</div>

<div class="panel panel-elevated">
    <div class="panel-header">
        <h3>Attendance History</h3>
        <span class="panel-badge"><?php echo $attendance ? $attendance->num_rows : 0; ?> records</span>
    </div>
    <div class="panel-body">
        <?php if ($attendance && $attendance->num_rows > 0): ?>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr><th>Date</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $attendance->fetch_assoc()): ?>
                            <?php
                            $is_present = strtolower($row['status']) === 'present';
                            $badge = $is_present ? 'badge-present' : 'badge-absent';
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['attendance_date']); ?></td>
                                <td><span class="badge <?php echo $badge; ?>"><?php echo htmlspecialchars($row['status']); ?></span></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state compact">
                <p>No attendance records for this employee yet.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require 'includes/footer.php'; ?>
