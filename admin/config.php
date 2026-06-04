<?php
// config.php
session_start();

$db_host = 'localhost';
$db_user = 'root'; // Change if required
$db_pass = ''; // Change if required
$db_name = 'payroll_db';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
