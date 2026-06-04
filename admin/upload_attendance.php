<?php require 'includes/header.php'; ?>

<h2>Upload Attendance</h2>
<p style="margin-bottom: 20px;">Upload a CSV file to process employee attendance.</p>

<div class="form-container">
    <?php
    if (isset($_SESSION['upload_message'])) {
        echo "<div class='alert " . ($_SESSION['upload_success'] ? "alert-success" : "alert-error") . "'>" . $_SESSION['upload_message'] . "</div>";
        unset($_SESSION['upload_message']);
        unset($_SESSION['upload_success']);
    }
    ?>
    <form action="process_upload.php" method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label for="attendance_file">Select CSV File:</label>
            <input type="file" name="attendance_file" id="attendance_file" accept=".csv" required>
            <small style="color: #666; display: block; margin-top: 5px;">Format: Emp ID, Employee Name, Date (YYYY-MM-DD), Status</small>
        </div>
        <button type="submit" class="btn">Upload & Process</button>
    </form>
</div>

<div style="margin-top: 30px;">
    <h3>Expected CSV Format Example</h3>
    <table>
        <thead>
            <tr>
                <th>Emp ID (Col 1)</th>
                <th>Name (Col 2)</th>
                <th>Date (Col 3)</th>
                <th>Status (Col 4)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>EMP001</td>
                <td>John Doe</td>
                <td>2023-10-01</td>
                <td>Present</td>
            </tr>
            <tr>
                <td>EMP002</td>
                <td>Jane Smith</td>
                <td>2023-10-01</td>
                <td>Absent</td>
            </tr>
        </tbody>
    </table>
</div>

<?php require 'includes/footer.php'; ?>
