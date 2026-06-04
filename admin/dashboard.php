<?php
require 'includes/header.php';
require 'config.php';
require 'includes/settings_helper.php';

$settings = get_all_settings($conn);
$smtp_ready = is_smtp_configured($settings);
$emp_count = $conn->query("SELECT COUNT(*) as count FROM employees")->fetch_assoc()['count'];
$att_count = $conn->query("SELECT COUNT(*) as count FROM attendance")->fetch_assoc()['count'];
$present_count = $conn->query("SELECT COUNT(*) as count FROM attendance WHERE LOWER(status) = 'present'")->fetch_assoc()['count'];

$recent_attendance = $conn->query("
    SELECT a.*, e.name 
    FROM attendance a 
    JOIN employees e ON a.emp_id = e.emp_id 
    ORDER BY a.attendance_date DESC, a.id DESC 
    LIMIT 10
");
?>
<div class="page-header page-header-row">
    <div class="page-header-main">
        <p class="page-eyebrow">Overview</p>
        <h2>Dashboard</h2>
        <p>Track employees and the latest attendance entries at a glance.</p>
    </div>
    <div class="page-header-actions">
        <a href="employees.php" class="btn btn-outline">Employees</a>
        <a href="upload_attendance.php" class="btn btn-header">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
            Upload Attendance
        </a>
    </div>
</div>

<?php if (isset($_SESSION['flash_message'])): ?>
    <div class="alert <?php echo $_SESSION['flash_success'] ? 'alert-success' : 'alert-error'; ?> alert-page">
        <?php echo htmlspecialchars($_SESSION['flash_message']); unset($_SESSION['flash_message'], $_SESSION['flash_success']); ?>
    </div>
<?php endif; ?>

<div class="card-container">
    <div class="stat-card stat-card-primary">
        <div class="stat-icon-wrap employees">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        </div>
        <div class="stat-body">
            <h3>Total Employees</h3>
            <p class="stat-value"><?php echo (int) $emp_count; ?></p>
            <span class="stat-meta">Registered in system</span>
        </div>
    </div>
    <div class="stat-card stat-card-accent">
        <div class="stat-icon-wrap attendance">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        </div>
        <div class="stat-body">
            <h3>Attendance Records</h3>
            <p class="stat-value"><?php echo (int) $att_count; ?></p>
            <span class="stat-meta">All uploaded entries</span>
        </div>
    </div>
    <div class="stat-card stat-card-success">
        <div class="stat-icon-wrap present">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        </div>
        <div class="stat-body">
            <h3>Present Marked</h3>
            <p class="stat-value"><?php echo (int) $present_count; ?></p>
            <span class="stat-meta">Present status count</span>
        </div>
    </div>
</div>

<div class="panel panel-elevated panel-action">
    <div class="panel-header">
        <div class="panel-title-group">
            <h3>Send Salary Slips by Email</h3>
            <span class="panel-badge"><?php echo $smtp_ready ? 'SMTP ready' : 'SMTP not configured'; ?></span>
        </div>
    </div>
    <div class="panel-body padded">
        <p class="form-hint" style="margin-bottom:16px">
            Calculates salary from attendance and emails a professional message with PDF salary slip attached.
        </p>
        <form method="POST" action="send_slips.php" class="slip-form">
            <div class="form-row slip-form-row">
                <div class="form-group">
                    <label>Month</label>
                    <select name="month">
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?php echo $m; ?>" <?php echo $m === (int) date('n') ? 'selected' : ''; ?>><?php echo date('F', mktime(0, 0, 0, $m, 1)); ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Year</label>
                    <select name="year">
                        <?php for ($y = (int) date('Y'); $y >= (int) date('Y') - 2; $y--): ?>
                            <option value="<?php echo $y; ?>"><?php echo $y; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group form-group-btn">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn" <?php echo $smtp_ready ? '' : 'disabled title="Configure SMTP in Settings"'; ?>>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                        Send All Salary Slips
                    </button>
                </div>
            </div>
        </form>
        <?php if (!$smtp_ready): ?>
            <p class="form-hint warn-hint">→ <a href="settings.php?tab=smtp">Configure SMTP</a> first. <a href="seed_demo_data.php">Fill demo employee data</a> to test slips.</p>
        <?php else: ?>
            <p class="form-hint">Need test data? <a href="seed_demo_data.php">Fill all employee details &amp; attendance</a></p>
        <?php endif; ?>
    </div>
</div>

<div class="panel panel-elevated">
    <div class="panel-header">
        <div class="panel-title-group">
            <h3>Recent Attendance</h3>
            <span class="panel-badge">Last 10 records</span>
        </div>
        <a href="upload_attendance.php" class="btn btn-sm btn-outline">View upload</a>
    </div>
    <div class="panel-body">
        <?php if ($recent_attendance && $recent_attendance->num_rows > 0): ?>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $recent_attendance->fetch_assoc()): ?>
                            <?php
                            $is_present = strtolower($row['status']) === 'present';
                            $badge_class = $is_present ? 'badge-present' : 'badge-absent';
                            $initial = strtoupper(substr($row['name'], 0, 1));
                            ?>
                            <tr>
                                <td>
                                    <div class="cell-employee">
                                        <span class="emp-avatar"><?php echo htmlspecialchars($initial); ?></span>
                                        <div>
                                            <span class="emp-name"><?php echo htmlspecialchars($row['name']); ?></span>
                                            <span class="emp-id"><?php echo htmlspecialchars($row['emp_id']); ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="cell-date"><?php echo htmlspecialchars($row['attendance_date']); ?></span></td>
                                <td><span class="badge <?php echo $badge_class; ?>"><?php echo htmlspecialchars($row['status']); ?></span></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                </div>
                <h4>No attendance yet</h4>
                <p>Upload a CSV or Excel file to start tracking employee attendance.</p>
                <a href="upload_attendance.php" class="btn">Upload Attendance</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require 'includes/footer.php'; ?>
