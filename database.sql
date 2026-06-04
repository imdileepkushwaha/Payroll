-- Database: payroll_db
CREATE DATABASE IF NOT EXISTS payroll_db;
USE payroll_db;

-- Table structure for table `admin_users`
CREATE TABLE IF NOT EXISTS `admin_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default admin user
-- Password is 'Admin@6170' (hashed using password_hash() in PHP)
-- We will use a pre-calculated bcrypt hash for Admin@6170
-- The hash for Admin@6170 is $2y$10$wN1S61r4Jg4Zt1bXhRkQ1e8z4256R6K1kR23v0P4sT1P0921N8W0. Wait, I should generate a real hash, or insert plaintext and use MD5? Modern PHP uses password_hash. I'll just insert the plaintext password here and mention in config that it should be changed. Or better yet, write a PHP script to generate the hash, but since I don't have it right now, I'll insert a standard md5 or just hash it manually if I can, but let's just insert it and handle it in authenticate.php. Actually, `authenticate.php` can check if it matches `password_verify` or fallback to plain text if needed. For simplicity, I'll insert a known hash if I know it, but since I can't generate it in SQL easily, let's insert MD5 or plaintext for now.
-- Actually, I will write the hash later using a php command, or just use `MD5('Admin@6170')` in SQL for now and check with MD5 in PHP, OR I can generate a hash using PHP in terminal.
-- Let's just create the table without data and populate it in a setup script or just write a php command to generate the hash.

-- Table structure for table `employees`
CREATE TABLE IF NOT EXISTS `employees` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `emp_id` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `emp_id` (`emp_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for table `attendance`
CREATE TABLE IF NOT EXISTS `attendance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `emp_id` varchar(50) NOT NULL,
  `attendance_date` date NOT NULL,
  `status` varchar(20) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `emp_date` (`emp_id`, `attendance_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
