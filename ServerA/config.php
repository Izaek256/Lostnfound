<?php
// Simple database configuration
$db_host = "localhost";
$db_name = "lostfound_db";
$db_user = "root";
$db_pass = "kpet";

// Start session
session_start();

// Simple database connection
function connectDB() {
    global $db_host, $db_name, $db_user, $db_pass;
    
    $conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
    
    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }
    
    return $conn;
}
?>
