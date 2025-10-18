<?php
/**
 * Database Connection File
 * 
 * This file establishes a connection to the MySQL database using MySQLi.
 * It is included in all pages that need database access.
 * 
 * MySQLi provides a simple procedural interface for database operations,
 * making it beginner-friendly and easy to understand.
 */

// Database configuration settings
$host = 'localhost';        // Database server address (localhost for XAMPP)
$username = 'root';         // MySQL username (default is 'root' for XAMPP)
$password = 'isaacK@12345';         // MySQL password (empty by default in XAMPP)
$database = 'lostfound_db'; // Name of our database

// Create a new MySQLi connection
// mysqli_connect() creates a connection to the MySQL server
// Parameters: host, username, password, database name
$conn = mysqli_connect($host, $username, $password, $database);

// Check if the connection was successful
// If connection fails, mysqli_connect() returns false
if (!$conn) {
    // If connection fails, stop execution and show error message
    die("Connection failed: " . mysqli_connect_error());
}

// Optional: Set character encoding to UTF-8 to support special characters
// This ensures proper handling of international characters and emojis
mysqli_set_charset($conn, "utf8mb4");
?>
