<?php
/**
 * ItemsServer Health Check Endpoint
 * Returns status of item management server and database
 */

// Load deployment configuration
require_once __DIR__ . '/../deployment_config.php';

// Check if request is from browser directly (not AJAX)
$accept_header = $_SERVER['HTTP_ACCEPT'] ?? '';
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$x_requested_with = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';

// Enhanced browser detection - show HTML page if it looks like a direct browser request
$is_browser_request = (
    // Accept header contains text/html (browser requests HTML by default)
    (strpos($accept_header, 'text/html') !== false && strpos($accept_header, 'application/json') === false) &&
    // User agent indicates a browser
    (strpos($user_agent, 'Mozilla') !== false || strpos($user_agent, 'Chrome') !== false || strpos($user_agent, 'Safari') !== false || strpos($user_agent, 'Firefox') !== false || strpos($user_agent, 'Edge') !== false) &&
    // Not an AJAX request
    (empty($x_requested_with) || stripos($x_requested_with, 'XMLHttpRequest') === false)
);

if ($is_browser_request) {
    // Show simple HTML page
    echo "<html><head><title>ItemsServer Health Check</title></head><body>";
    echo "<h1>ItemsServer Health Check</h1>";
    echo "<p>The ItemsServer is running and accessible.</p>";
    echo "<p>For detailed API health information, API clients should access this endpoint directly.</p>";
    echo "</body></html>";
    exit();
}

header('Content-Type: application/json');

// Use database configuration from deployment_config.php
$db_host = DB_HOST;
$db_name = DB_NAME;
$db_user = DB_USER;
$db_pass = DB_PASS;

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
?>