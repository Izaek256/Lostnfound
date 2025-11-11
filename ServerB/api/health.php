<?php
/**
 * ServerB Health Check Endpoint
 * Returns status of item management server and database
 */
header('Content-Type: application/json');

// Direct database connection (avoid session_start from config.php)
$db_host = "localhost";
$db_name = "lostfound_db";
$db_user = "root";
$db_pass = "Isaac@1234";

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

$health = [
    'server' => 'ServerB',
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
        $health['services']['users_database'] = $row['count'] . ' users stored';
    } else {
        $health['services']['users_database'] = 'table not accessible';
    }
}

// Check API endpoints
$health['services']['register_user_api'] = file_exists(__DIR__ . '/register_user.php') ? 'active' : 'missing';
$health['services']['verify_user_api'] = file_exists(__DIR__ . '/verify_user.php') ? 'active' : 'missing';
$health['services']['get_all_users_api'] = file_exists(__DIR__ . '/get_all_users.php') ? 'active' : 'missing';

echo json_encode($health);
