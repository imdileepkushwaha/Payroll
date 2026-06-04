<?php
require 'includes/header.php';
require 'config.php';
require 'includes/settings_helper.php';
require 'includes/signature_helper.php';

$settings = get_all_settings($conn);
$signature_url = payslip_signature_url($settings);
$tab = $_GET['tab'] ?? 'smtp';
$smtp_ready = is_smtp_configured($settings);

function render_password_input($name, $attrs = '')
{
    $id = 'pwd_' . preg_replace('/[^a-z0-9]/i', '_', $name);
    ?>
    <div class="password-field">
        <input type="password" name="<?php echo htmlspecialchars($name); ?>" id="<?php echo $id; ?>" <?php echo $attrs; ?>>
        <button type="button" class="password-toggle" data-target="<?php echo $id; ?>" aria-label="Show password">
            <svg class="icon-eye-show" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            <svg class="icon-eye-hide" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
        </button>
    </div>
    <?php
}

$tab_meta = [
    'smtp' => [
        'title' => 'SMTP & Email',
        'desc' => 'Configure outgoing mail for salary slip delivery.',
        'icon' => '<path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/>',
    ],
    'password' => [
        'title' => 'Change Password',
        'desc' => 'Update your admin account credentials.',
        'icon' => '<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
    ],
    'payroll' => [
        'title' => 'Payroll Rules',
        'desc' => 'Company info and salary calculation settings.',
        'icon' => '<line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>',
    ],
];
$active_meta = $tab_meta[$tab] ?? $tab_meta['smtp'];
?>
<div class="page-header page-header-row">
    <div class="page-header-main">
        <p class="page-eyebrow">Configuration</p>
        <h2>Settings</h2>
        <p>SMTP, security, and payroll rules for your organization.</p>
    </div>
</div>

<?php if (isset($_SESSION['flash_message'])): ?>
    <div class="alert <?php echo $_SESSION['flash_success'] ? 'alert-success' : 'alert-error'; ?> alert-page">
        <?php echo htmlspecialchars($_SESSION['flash_message']); unset($_SESSION['flash_message'], $_SESSION['flash_success']); ?>
    </div>
<?php endif; ?>

<div class="settings-status">
    <div class="settings-status-chip <?php echo $smtp_ready ? 'ok' : 'warn'; ?>">
        <span class="status-dot"></span>
        <div>
            <strong><?php echo $smtp_ready ? 'SMTP configured' : 'SMTP not configured'; ?></strong>
            <span><?php echo $smtp_ready ? 'Ready to send salary slips' : 'Set up email to send slips'; ?></span>
        </div>
    </div>
    <div class="settings-status-chip neutral">
        <span class="status-dot"></span>
        <div>
            <strong><?php echo htmlspecialchars($settings['company_name'] ?? 'Company'); ?></strong>
            <span><?php echo (int) ($settings['working_days_per_month'] ?? 26); ?> working days / month</span>
        </div>
    </div>
</div>

<div class="settings-layout">
    <nav class="settings-tabs" aria-label="Settings sections">
        <p class="settings-nav-label">Sections</p>
        <?php foreach ($tab_meta as $key => $meta): ?>
            <a href="?tab=<?php echo $key; ?>" class="settings-tab <?php echo $tab === $key ? 'active' : ''; ?>">
                <span class="settings-tab-icon-wrap">
                    <svg class="settings-tab-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><?php echo $meta['icon']; ?></svg>
                </span>
                <span class="settings-tab-text">
                    <span class="settings-tab-title"><?php echo htmlspecialchars($meta['title']); ?></span>
                    <span class="settings-tab-desc"><?php echo htmlspecialchars($meta['desc']); ?></span>
                </span>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="settings-content">
        <div class="settings-card panel-elevated">
            <div class="settings-card-head">
                <div class="settings-card-icon tab-<?php echo htmlspecialchars($tab); ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><?php echo $active_meta['icon']; ?></svg>
                </div>
                <div>
                    <h3><?php echo htmlspecialchars($active_meta['title']); ?></h3>
                    <p><?php echo htmlspecialchars($active_meta['desc']); ?></p>
                </div>
            </div>

            <?php if ($tab === 'smtp'): ?>
            <div class="settings-tip">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                <p>For <strong>Gmail</strong>: use <code>smtp.gmail.com</code>, port <code>587</code>, TLS, and an <a href="https://myaccount.google.com/apppasswords" target="_blank" rel="noopener">App Password</a>.</p>
            </div>
            <form method="POST" action="settings_save.php" class="stack-form settings-form">
                <input type="hidden" name="section" value="smtp">
                <div class="settings-form-section">
                    <h4>Server</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label>SMTP Host</label>
                            <input type="text" name="smtp_host" value="<?php echo htmlspecialchars($settings['smtp_host'] ?? ''); ?>" placeholder="smtp.gmail.com">
                        </div>
                        <div class="form-group">
                            <label>SMTP Port</label>
                            <input type="number" name="smtp_port" value="<?php echo htmlspecialchars($settings['smtp_port'] ?? '587'); ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Encryption</label>
                        <select name="smtp_encryption">
                            <option value="tls" <?php echo ($settings['smtp_encryption'] ?? 'tls') === 'tls' ? 'selected' : ''; ?>>TLS (recommended)</option>
                            <option value="ssl" <?php echo ($settings['smtp_encryption'] ?? '') === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                            <option value="none" <?php echo ($settings['smtp_encryption'] ?? '') === 'none' ? 'selected' : ''; ?>>None</option>
                        </select>
                    </div>
                </div>
                <div class="settings-form-section">
                    <h4>Authentication</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label>SMTP Username</label>
                            <input type="text" name="smtp_username" value="<?php echo htmlspecialchars($settings['smtp_username'] ?? ''); ?>" placeholder="your@email.com" autocomplete="username">
                        </div>
                        <div class="form-group">
                            <label>SMTP Password</label>
                            <?php
                            $smtp_placeholder = !empty($settings['smtp_password']) ? 'Saved — leave blank to keep' : 'SMTP password';
                            render_password_input('smtp_password', 'placeholder="' . htmlspecialchars($smtp_placeholder) . '" autocomplete="new-password"');
                            ?>
                        </div>
                    </div>
                </div>
                <div class="settings-form-section">
                    <h4>Sender identity</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label>From Email</label>
                            <input type="email" name="smtp_from_email" value="<?php echo htmlspecialchars($settings['smtp_from_email'] ?? ''); ?>" required placeholder="noreply@company.com">
                        </div>
                        <div class="form-group">
                            <label>From Name</label>
                            <input type="text" name="smtp_from_name" value="<?php echo htmlspecialchars($settings['smtp_from_name'] ?? 'Payroll System'); ?>">
                        </div>
                    </div>
                </div>
                <div class="settings-form-actions">
                    <button type="submit" class="btn">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                        Save SMTP Settings
                    </button>
                </div>
            </form>
            <form method="POST" action="test_smtp.php" class="smtp-test-form">
                <h4>Test connection</h4>
                <p class="form-hint">Save SMTP settings first, then send a test email (e.g. payroll@yopmail.com).</p>
                <div class="form-row">
                    <div class="form-group">
                        <label>Send test to</label>
                        <input type="email" name="test_email" value="<?php echo htmlspecialchars($settings['smtp_from_email'] ?? 'payroll@yopmail.com'); ?>" required>
                    </div>
                    <div class="form-group form-group-btn">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-outline">Send Test Email</button>
                    </div>
                </div>
            </form>
            <?php endif; ?>

            <?php if ($tab === 'password'): ?>
            <div class="settings-tip settings-tip-security">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                <p>Use at least 6 characters. Avoid sharing your admin password.</p>
            </div>
            <form method="POST" action="settings_save.php" class="stack-form settings-form">
                <input type="hidden" name="section" value="password">
                <div class="settings-form-section">
                    <div class="form-group">
                        <label>Current Password</label>
                        <?php render_password_input('current_password', 'required autocomplete="current-password"'); ?>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>New Password</label>
                            <?php render_password_input('new_password', 'required minlength="6" autocomplete="new-password"'); ?>
                        </div>
                        <div class="form-group">
                            <label>Confirm New Password</label>
                            <?php render_password_input('confirm_password', 'required minlength="6" autocomplete="new-password"'); ?>
                        </div>
                    </div>
                </div>
                <div class="settings-form-actions">
                    <button type="submit" class="btn">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        Update Password
                    </button>
                </div>
            </form>
            <?php endif; ?>

            <?php if ($tab === 'payroll'): ?>
            <div class="settings-tip">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                <p><strong>Formula:</strong> Net salary = (Base ÷ working days) × present days in the selected month.</p>
            </div>
            <form method="POST" action="settings_save.php" enctype="multipart/form-data" class="stack-form settings-form">
                <input type="hidden" name="section" value="payroll">
                <div class="settings-form-section">
                    <h4>Company &amp; calculation</h4>
                    <div class="form-group">
                        <label>Company Name</label>
                        <input type="text" name="company_name" value="<?php echo htmlspecialchars($settings['company_name'] ?? ''); ?>" placeholder="Shown on salary slips">
                    </div>
                    <div class="form-group">
                        <label>Working Days Per Month</label>
                        <input type="number" name="working_days_per_month" min="1" max="31" value="<?php echo htmlspecialchars($settings['working_days_per_month'] ?? '26'); ?>" required>
                        <span class="form-hint">Typically 22–26 for monthly payroll</span>
                    </div>
                </div>
                <div class="settings-form-section">
                    <h4>Payslip authorized signature</h4>
                    <p class="form-hint" style="margin-bottom:16px">Upload a PNG/JPG signature image. It will appear on the bottom-right of every salary slip PDF.</p>
                    <?php if ($signature_url): ?>
                        <div class="signature-preview-box">
                            <img src="<?php echo htmlspecialchars($signature_url); ?>" alt="Current signature">
                            <div class="signature-preview-meta">
                                <span class="badge badge-present">Signature active</span>
                                <label class="signature-remove-label">
                                    <input type="checkbox" name="remove_signature" value="1"> Remove signature
                                </label>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="signature-preview-box signature-preview-empty">
                            <p>No signature uploaded yet</p>
                        </div>
                    <?php endif; ?>
                    <div class="form-group">
                        <label>Upload signature image</label>
                        <input type="file" name="payslip_signature" accept="image/png,image/jpeg,image/jpg,image/gif">
                        <span class="form-hint">Transparent PNG recommended. Max 2MB.</span>
                    </div>
                    <div class="form-group">
                        <label>Name below signature</label>
                        <input type="text" name="signature_authority_name" value="<?php echo htmlspecialchars($settings['signature_authority_name'] ?? 'Authorized Signatory'); ?>" placeholder="e.g. HR Manager / Director">
                    </div>
                </div>
                <div class="settings-form-actions">
                    <button type="submit" class="btn">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                        Save Payroll Settings
                    </button>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.password-toggle').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var input = document.getElementById(btn.getAttribute('data-target'));
        if (!input) return;
        var show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        btn.classList.toggle('is-visible', show);
        btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
    });
});
</script>

<?php require 'includes/footer.php'; ?>
