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
    
    // Check items table
    $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM items");
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $health['services']['items_database'] = $row['count'] . ' items stored';
    } else {
        $health['services']['items_database'] = 'table not accessible';
    }
}

// Check upload directory
$upload_dir = __DIR__ . '/../uploads';
if (is_dir($upload_dir) && is_writable($upload_dir)) {
    $health['services']['upload_directory'] = 'writable';
} else {
    $health['services']['upload_directory'] = 'not writable or missing';
}

// Check API endpoints
$health['services']['add_item_api'] = file_exists(__DIR__ . '/add_item.php') ? 'active' : 'missing';
$health['services']['get_items_api'] = file_exists(__DIR__ . '/get_items.php') ? 'active' : 'missing';
$health['services']['delete_item_api'] = file_exists(__DIR__ . '/delete_item.php') ? 'active' : 'missing';

echo json_encode($health);
