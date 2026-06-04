<?php
// setup.php
require 'config.php';

echo "<h3>Database Setup</h3>";

// Create Tables
$sql = "
CREATE TABLE IF NOT EXISTS `admin_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";
if ($conn->query($sql) === TRUE) {
    echo "Table 'admin_users' created or already exists.<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

$sql = "
CREATE TABLE IF NOT EXISTS `employees` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `emp_id` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `emp_id` (`emp_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";
if ($conn->query($sql) === TRUE) {
    echo "Table 'employees' created or already exists.<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

$sql = "
CREATE TABLE IF NOT EXISTS `attendance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `emp_id` varchar(50) NOT NULL,
  `attendance_date` date NOT NULL,
  `status` varchar(20) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `emp_date` (`emp_id`, `attendance_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";
if ($conn->query($sql) === TRUE) {
    echo "Table 'attendance' created or already exists.<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

// Insert Default Admin
$username = 'Admin';
$password = 'Admin@6170';
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("SELECT id FROM admin_users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    $stmt = $conn->prepare("INSERT INTO admin_users (username, password) VALUES (?, ?)");
    $stmt->bind_param("ss", $username, $hashed_password);
    if ($stmt->execute()) {
        echo "Default admin user 'Admin' created successfully.<br>";
    } else {
        echo "Error inserting admin user: " . $conn->error . "<br>";
    }
} else {
    echo "Default admin user 'Admin' already exists.<br>";
}

echo "<h3>Setup Complete!</h3>";
echo "<a href='index.php'>Go to Login</a>";
?>
