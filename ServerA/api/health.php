<?php
/**
 * ServerA Health Check Endpoint
 * Returns status of authentication server and database
 */
header('Content-Type: application/json');

// Direct database connection (avoid session_start from config.php)
$db_host = "localhost";
$db_name = "lostfound_db";
$db_user = "root";
$db_pass = "kpet";

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

$health = [
    'server' => 'ServerA',
    'status' => 'online',
    'database' => 'disconnected',
    'timestamp' => date('Y-m-d H:i:s'),
    'services' => []
];

// Check database connection
if ($conn && !$conn->connect_error) {
    $health['database'] = 'connected';
    
    // Check users table
    $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM users");
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $health['services']['user_database'] = $row['count'] . ' users registered';
    } else {
        $health['services']['user_database'] = 'table not accessible';
    }
}

// Check API endpoints
$health['services']['register_api'] = file_exists(__DIR__ . '/register_user.php') ? 'active' : 'missing';
$health['services']['verify_api'] = file_exists(__DIR__ . '/verify_user.php') ? 'active' : 'missing';
$health['services']['session_api'] = file_exists(__DIR__ . '/session_status.php') ? 'active' : 'missing';

echo json_encode($health);
