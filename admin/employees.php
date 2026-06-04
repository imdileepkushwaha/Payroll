<?php
require 'includes/header.php';
require 'config.php';

$search = trim($_GET['q'] ?? '');
$sql = "SELECT * FROM employees";
$params = [];
$types = '';

if ($search !== '') {
    $sql .= " WHERE emp_id LIKE ? OR name LIKE ? OR email LIKE ? OR department LIKE ? OR designation LIKE ?";
    $like = '%' . $search . '%';
    $params = [$like, $like, $like, $like, $like];
    $types = 'sssss';
}

$sql .= " ORDER BY name ASC";
$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$employees = $stmt->get_result();

$total_all = (int) $conn->query("SELECT COUNT(*) AS c FROM employees")->fetch_assoc()['c'];
$with_email = (int) $conn->query("SELECT COUNT(*) AS c FROM employees WHERE email IS NOT NULL AND email != ''")->fetch_assoc()['c'];
$listed = $employees ? $employees->num_rows : 0;
?>
<div class="page-header page-header-row">
    <div class="page-header-main">
        <p class="page-eyebrow">People</p>
        <h2>Employees</h2>
        <p>Manage team profiles, salaries, and view attendance history.</p>
    </div>
    <div class="page-header-actions">
        <button type="button" class="btn btn-header" onclick="document.getElementById('addEmployeeModal').showModal()">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Add Employee
        </button>
    </div>
</div>

<?php if (isset($_SESSION['flash_message'])): ?>
    <div class="alert <?php echo $_SESSION['flash_success'] ? 'alert-success' : 'alert-error'; ?> alert-page">
        <?php echo htmlspecialchars($_SESSION['flash_message']); unset($_SESSION['flash_message'], $_SESSION['flash_success']); ?>
    </div>
<?php endif; ?>

<div class="emp-stats">
    <div class="emp-stat-chip">
        <span class="emp-stat-label">Total staff</span>
        <strong><?php echo $total_all; ?></strong>
    </div>
    <div class="emp-stat-chip">
        <span class="emp-stat-label">With email</span>
        <strong><?php echo $with_email; ?></strong>
    </div>
    <div class="emp-stat-chip">
        <span class="emp-stat-label"><?php echo $search !== '' ? 'Showing' : 'Listed'; ?></span>
        <strong><?php echo $listed; ?></strong>
    </div>
</div>

<div class="panel panel-elevated employees-panel">
    <div class="panel-header employees-panel-head">
        <div class="panel-title-group">
            <h3>Employee directory</h3>
            <span class="panel-badge"><?php echo $listed; ?> records</span>
        </div>
        <form method="GET" class="emp-search">
            <svg class="emp-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="search" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search ID, name, email, dept…" autocomplete="off">
            <button type="submit" class="btn btn-sm">Search</button>
            <?php if ($search !== ''): ?>
                <a href="employees.php" class="btn btn-sm btn-outline">Clear</a>
            <?php endif; ?>
        </form>
    </div>
    <div class="panel-body">
        <?php if ($employees && $employees->num_rows > 0): ?>
            <div class="table-wrap">
                <table class="data-table emp-table">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Role</th>
                            <th>Salary</th>
                            <th>Contact</th>
                            <th class="th-actions">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($emp = $employees->fetch_assoc()): ?>
                            <?php
                            $initial = strtoupper(substr($emp['name'], 0, 1));
                            $has_email = !empty($emp['email']);
                            ?>
                            <tr class="emp-row" data-employee="<?php echo htmlspecialchars(json_encode([
                                'emp_id' => $emp['emp_id'],
                                'name' => $emp['name'],
                                'email' => $emp['email'] ?? '',
                                'phone' => $emp['phone'] ?? '',
                                'department' => $emp['department'] ?? '',
                                'designation' => $emp['designation'] ?? '',
                                'base_salary' => $emp['base_salary'],
                                'joined_date' => $emp['joined_date'] ?? '',
                            ]), ENT_QUOTES, 'UTF-8'); ?>">
                                <td>
                                    <div class="cell-employee">
                                        <span class="emp-avatar"><?php echo htmlspecialchars($initial); ?></span>
                                        <div>
                                            <span class="emp-name"><?php echo htmlspecialchars($emp['name']); ?></span>
                                            <span class="emp-id"><?php echo htmlspecialchars($emp['emp_id']); ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="dept-badge"><?php echo htmlspecialchars($emp['department'] ?: 'General'); ?></span>
                                    <?php if (!empty($emp['designation'])): ?>
                                        <span class="emp-designation"><?php echo htmlspecialchars($emp['designation']); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="salary-pill">₹<?php echo number_format((float) $emp['base_salary'], 0); ?></span>
                                    <span class="salary-sub">/ month</span>
                                </td>
                                <td>
                                    <?php if ($has_email): ?>
                                        <span class="contact-line">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                                            <?php echo htmlspecialchars($emp['email']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="contact-missing">No email</span>
                                    <?php endif; ?>
                                    <?php if (!empty($emp['phone'])): ?>
                                        <span class="contact-line contact-phone">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                                            <?php echo htmlspecialchars($emp['phone']); ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="cell-actions">
                                    <div class="action-btns">
                                        <a href="employee_view.php?emp_id=<?php echo urlencode($emp['emp_id']); ?>" class="btn-action btn-view" title="View profile">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                        </a>
                                        <button type="button" class="btn-action btn-edit" title="Edit employee" onclick="openEditEmployee(this)">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                        </button>
                                        <form method="POST" action="employee_delete.php" class="action-delete-form" onsubmit="return confirmDelete(<?php echo json_encode($emp['name']); ?>, <?php echo json_encode($emp['emp_id']); ?>);">
                                            <input type="hidden" name="emp_id" value="<?php echo htmlspecialchars($emp['emp_id']); ?>">
                                            <button type="submit" class="btn-action btn-delete" title="Delete employee">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </div>
                <h4><?php echo $search !== '' ? 'No matches found' : 'No employees yet'; ?></h4>
                <p><?php echo $search !== '' ? 'Try a different search term or clear filters.' : 'Add an employee or upload attendance to import staff from Excel.'; ?></p>
                <?php if ($search !== ''): ?>
                    <a href="employees.php" class="btn btn-outline">Clear search</a>
                <?php else: ?>
                    <button type="button" class="btn" onclick="document.getElementById('addEmployeeModal').showModal()">Add Employee</button>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<dialog class="modal modal-employee" id="addEmployeeModal">
    <form method="POST" action="employee_save.php" class="modal-form">
        <input type="hidden" name="action" value="add">
        <div class="modal-head">
            <div class="modal-head-content">
                <div class="modal-head-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
                </div>
                <div>
                    <h3>Add New Employee</h3>
                    <p>Register a new team member for payroll &amp; attendance</p>
                </div>
            </div>
            <button type="button" class="modal-close" aria-label="Close" onclick="document.getElementById('addEmployeeModal').close()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div class="modal-body">
            <div class="modal-section">
                <h4 class="modal-section-title">Basic information</h4>
                <div class="form-row">
                    <div class="form-group">
                        <label for="emp_id">Employee ID <span class="req">*</span></label>
                        <input type="text" name="emp_id" id="emp_id" required placeholder="e.g. EMP001" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label for="name">Full Name <span class="req">*</span></label>
                        <input type="text" name="name" id="name" required placeholder="Employee full name">
                    </div>
                </div>
            </div>
            <div class="modal-section">
                <h4 class="modal-section-title">Contact</h4>
                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" name="email" id="email" placeholder="name@company.com">
                        <span class="form-hint">Used for salary slip emails</span>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="tel" name="phone" id="phone" placeholder="+91 98765 43210">
                    </div>
                </div>
            </div>
            <div class="modal-section">
                <h4 class="modal-section-title">Job &amp; compensation</h4>
                <div class="form-row">
                    <div class="form-group">
                        <label for="department">Department</label>
                        <input type="text" name="department" id="department" placeholder="e.g. Engineering">
                    </div>
                    <div class="form-group">
                        <label for="designation">Designation</label>
                        <input type="text" name="designation" id="designation" placeholder="e.g. Developer">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="base_salary">Base Salary (₹) <span class="req">*</span></label>
                        <div class="input-prefix-wrap">
                            <span class="input-prefix">₹</span>
                            <input type="number" name="base_salary" id="base_salary" min="0" step="0.01" value="0" required placeholder="0.00">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="joined_date">Joined Date</label>
                        <input type="date" name="joined_date" id="joined_date">
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-foot">
            <button type="button" class="btn btn-outline" onclick="document.getElementById('addEmployeeModal').close()">Cancel</button>
            <button type="submit" class="btn">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                Save Employee
            </button>
        </div>
    </form>
</dialog>

<dialog class="modal modal-employee" id="editEmployeeModal">
    <form method="POST" action="employee_save.php" class="modal-form">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="emp_id" id="edit_emp_id">
        <div class="modal-head">
            <div class="modal-head-content">
                <div class="modal-head-icon" style="background:linear-gradient(135deg,#0891b2,#06b6d4)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                </div>
                <div>
                    <h3>Edit Employee</h3>
                    <p>Update employee details and salary</p>
                </div>
            </div>
            <button type="button" class="modal-close" aria-label="Close" onclick="document.getElementById('editEmployeeModal').close()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div class="modal-body">
            <div class="modal-section">
                <h4 class="modal-section-title">Basic information</h4>
                <div class="form-row">
                    <div class="form-group">
                        <label>Employee ID</label>
                        <input type="text" id="edit_emp_id_display" disabled class="input-disabled">
                    </div>
                    <div class="form-group">
                        <label for="edit_name">Full Name <span class="req">*</span></label>
                        <input type="text" name="name" id="edit_name" required>
                    </div>
                </div>
            </div>
            <div class="modal-section">
                <h4 class="modal-section-title">Contact</h4>
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_email">Email</label>
                        <input type="email" name="email" id="edit_email">
                    </div>
                    <div class="form-group">
                        <label for="edit_phone">Phone</label>
                        <input type="tel" name="phone" id="edit_phone">
                    </div>
                </div>
            </div>
            <div class="modal-section">
                <h4 class="modal-section-title">Job &amp; compensation</h4>
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_department">Department</label>
                        <input type="text" name="department" id="edit_department">
                    </div>
                    <div class="form-group">
                        <label for="edit_designation">Designation</label>
                        <input type="text" name="designation" id="edit_designation">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_base_salary">Base Salary (₹) <span class="req">*</span></label>
                        <div class="input-prefix-wrap">
                            <span class="input-prefix">₹</span>
                            <input type="number" name="base_salary" id="edit_base_salary" min="0" step="0.01" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="edit_joined_date">Joined Date</label>
                        <input type="date" name="joined_date" id="edit_joined_date">
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-foot">
            <button type="button" class="btn btn-outline" onclick="document.getElementById('editEmployeeModal').close()">Cancel</button>
            <button type="submit" class="btn">Save Changes</button>
        </div>
    </form>
</dialog>

<script>
function confirmDelete(name, empId) {
    return confirm('Delete employee "' + name + '" (' + empId + ')?\n\nThis will also remove their attendance records. This cannot be undone.');
}

function openEditEmployee(btn) {
    var row = btn.closest('tr');
    if (!row || !row.dataset.employee) return;
    var emp = JSON.parse(row.dataset.employee);
    document.getElementById('edit_emp_id').value = emp.emp_id;
    document.getElementById('edit_emp_id_display').value = emp.emp_id;
    document.getElementById('edit_name').value = emp.name || '';
    document.getElementById('edit_email').value = emp.email || '';
    document.getElementById('edit_phone').value = emp.phone || '';
    document.getElementById('edit_department').value = emp.department || '';
    document.getElementById('edit_designation').value = emp.designation || '';
    document.getElementById('edit_base_salary').value = emp.base_salary || 0;
    document.getElementById('edit_joined_date').value = emp.joined_date || '';
    document.getElementById('editEmployeeModal').showModal();
}

document.addEventListener('DOMContentLoaded', function () {
    ['addEmployeeModal', 'editEmployeeModal'].forEach(function (id) {
        var modal = document.getElementById(id);
        if (modal && modal.parentElement !== document.body) {
            document.body.appendChild(modal);
        }
    });
});
</script>

<?php require 'includes/footer.php'; ?>
