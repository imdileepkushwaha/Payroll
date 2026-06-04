<?php require 'includes/header.php'; ?>

<div class="page-header page-header-row">
    <div class="page-header-main">
        <p class="page-eyebrow">Import data</p>
        <h2>Upload Attendance</h2>
        <p>Bulk import employee attendance from CSV or Excel files.</p>
    </div>
</div>

<?php
if (isset($_SESSION['upload_message'])) {
    $alert_class = !empty($_SESSION['upload_success']) ? 'alert-success' : 'alert-error';
    echo "<div class='alert " . $alert_class . " alert-page'>" . htmlspecialchars($_SESSION['upload_message']) . "</div>";
    unset($_SESSION['upload_message']);
    unset($_SESSION['upload_success']);
}
?>

<div class="upload-layout">
    <section class="upload-card">
        <div class="upload-card-head">
            <div class="upload-card-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
            </div>
            <div>
                <h3>Upload file</h3>
                <p>Drag & drop or choose a file from your computer</p>
            </div>
        </div>

        <form action="process_upload.php" method="POST" enctype="multipart/form-data" class="upload-form" id="uploadForm">
            <label class="dropzone" id="dropzone" for="attendance_file">
                <input type="file" name="attendance_file" id="attendance_file" accept=".csv,.xlsx,.xls" required hidden>
                <div class="dropzone-inner">
                    <div class="dropzone-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    </div>
                    <p class="dropzone-title"><span id="fileLabel">Choose file</span> or drag here</p>
                    <p class="dropzone-hint">CSV, XLSX, or XLS — max recommended 5MB</p>
                    <span class="dropzone-btn">Browse files</span>
                </div>
            </label>

            <div class="format-tags">
                <span class="format-tag">.csv</span>
                <span class="format-tag">.xlsx</span>
                <span class="format-tag">.xls</span>
            </div>

            <button type="submit" class="btn btn-block btn-upload">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                Upload &amp; Process
            </button>
        </form>
    </section>

    <aside class="upload-guide">
        <div class="guide-card">
            <h3>File format</h3>
            <p class="guide-intro">First row must be headers. Use these columns in order:</p>
            <ol class="guide-steps">
                <li><strong>Emp ID</strong> — unique employee code</li>
                <li><strong>Name</strong> — employee full name</li>
                <li><strong>Date</strong> — YYYY-MM-DD or Excel date</li>
                <li><strong>Status</strong> — Present or Absent</li>
            </ol>
        </div>

        <div class="panel panel-compact">
            <div class="panel-header">
                <h3>Example preview</h3>
            </div>
            <div class="panel-body">
                <div class="table-wrap">
                    <table class="data-table data-table-compact">
                        <thead>
                            <tr>
                                <th>Emp ID</th>
                                <th>Name</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>EMP001</code></td>
                                <td>John Doe</td>
                                <td>2023-10-01</td>
                                <td><span class="badge badge-present">Present</span></td>
                            </tr>
                            <tr>
                                <td><code>EMP002</code></td>
                                <td>Jane Smith</td>
                                <td>2023-10-01</td>
                                <td><span class="badge badge-absent">Absent</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </aside>
</div>

<script>
(function () {
    var input = document.getElementById('attendance_file');
    var label = document.getElementById('fileLabel');
    var dropzone = document.getElementById('dropzone');

    function setFileName(file) {
        label.textContent = file ? file.name : 'Choose file';
        dropzone.classList.toggle('has-file', !!file);
    }

    input.addEventListener('change', function () {
        setFileName(input.files[0]);
    });

    ['dragenter', 'dragover'].forEach(function (ev) {
        dropzone.addEventListener(ev, function (e) {
            e.preventDefault();
            dropzone.classList.add('drag-over');
        });
    });

    ['dragleave', 'drop'].forEach(function (ev) {
        dropzone.addEventListener(ev, function (e) {
            e.preventDefault();
            dropzone.classList.remove('drag-over');
        });
    });

    dropzone.addEventListener('drop', function (e) {
        if (e.dataTransfer.files.length) {
            input.files = e.dataTransfer.files;
            setFileName(input.files[0]);
        }
    });
})();
</script>

<?php require 'includes/footer.php'; ?>
