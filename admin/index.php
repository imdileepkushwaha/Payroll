<?php
session_start();
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Payroll System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="login-wrapper">
        <div class="login-card">
            <h2>Admin Login</h2>
            <?php
            if (isset($_SESSION['login_error'])) {
                echo "<div class='alert alert-error'>" . $_SESSION['login_error'] . "</div>";
                unset($_SESSION['login_error']);
            }
            ?>
            <form action="authenticate.php" method="POST">
                <div class="form-group">
                    <input type="text" name="username" placeholder="Username" required>
                </div>
                <div class="form-group">
                    <input type="password" name="password" placeholder="Password" required>
                </div>
                <button type="submit" class="btn" style="width: 100%;">Login</button>
            </form>
            <p style="margin-top: 20px; font-size: 12px; color: #666;">First time setup? <a href="setup.php">Run Setup</a></p>
        </div>
    </div>
</body>
</html>
