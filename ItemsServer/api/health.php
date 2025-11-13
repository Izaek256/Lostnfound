<?php
/**
 * ItemsServer Health Check Endpoint
 * Returns status of item management server and database
 */

// Check if request is from browser directly (not AJAX)
$accept_header = $_SERVER['HTTP_ACCEPT'] ?? '';
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

// If request appears to be from browser directly, show a simple HTML page
if (strpos($accept_header, 'text/html') !== false && 
    (strpos($user_agent, 'Mozilla') !== false || strpos($user_agent, 'Chrome') !== false || strpos($user_agent, 'Safari') !== false) &&
    !isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    // Show simple HTML page
    echo "<html><head><title>ItemsServer Health Check</title></head><body>";
    echo "<h1>ItemsServer Health Check</h1>";
    echo "<p>The ItemsServer is running and accessible.</p>";
    echo "<p>For detailed API health information, API clients should access this endpoint directly.</p>";
    echo "</body></html>";
    exit();
}

header('Content-Type: application/json');

// Direct database connection (avoid session_start from config.php)
$db_host = "localhost";
$db_name = "lostfound_db";
$db_user = "root";
$db_pass = "Isaac@1234";

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

$health = [
    'server' => 'ItemsServer',
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

// Check uploads directory (ItemsServer is the Items Service)
$upload_dir = __DIR__ . '/../uploads';
if (is_dir($upload_dir) && is_writable($upload_dir)) {
    $health['services']['uploads_directory'] = 'writable';
} else {
    $health['services']['uploads_directory'] = 'not writable or missing';
}

// Check API endpoints
$health['services']['add_item_api'] = file_exists(__DIR__ . '/add_item.php') ? 'active' : 'missing';
$health['services']['get_items_api'] = file_exists(__DIR__ . '/get_all_items.php') ? 'active' : 'missing';
$health['services']['update_item_api'] = file_exists(__DIR__ . '/update_item.php') ? 'active' : 'missing';
$health['services']['delete_item_api'] = file_exists(__DIR__ . '/delete_item.php') ? 'active' : 'missing';

echo json_encode($health);
