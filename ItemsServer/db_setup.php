<?php
/**
 * Server A - Database Setup
 * 
 * This file sets up the database and tables for Server A
 * Run this once to initialize the database
 */

require_once 'config.php';

$conn = connectDB();

// Create users table
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    is_admin TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Users table created successfully<br>";
} else {
    echo "Error creating users table: " . $conn->error . "<br>";
}

// Add is_admin column if it doesn't exist (for existing databases)
$checkColumn = $conn->query("SHOW COLUMNS FROM users LIKE 'is_admin'");
if ($checkColumn->num_rows == 0) {
    $sql = "ALTER TABLE users ADD COLUMN is_admin TINYINT(1) DEFAULT 0 AFTER password";
    if ($conn->query($sql) === TRUE) {
        echo "is_admin column added successfully<br>";
    } else {
        echo "Error adding is_admin column: " . $conn->error . "<br>";
    }
}

// Create items table
$sql = "CREATE TABLE IF NOT EXISTS items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    title VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    type ENUM('lost', 'found') NOT NULL,
    location VARCHAR(100) NOT NULL,
    contact VARCHAR(100) NOT NULL,
    image VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "Items table created successfully<br>";
} else {
    echo "Error creating items table: " . $conn->error . "<br>";
}

// Create uploads directory if it doesn't exist
if (!is_dir('uploads')) {
    mkdir('uploads', 0755, true);
    echo "Uploads directory created successfully<br>";
}

$conn->close();

echo "<br><strong>Database setup completed!</strong><br>";
echo "You can now start using the Lost & Found portal.<br>";
echo "<a href='admin_login.php'>Go to Admin Login</a>";
?>
