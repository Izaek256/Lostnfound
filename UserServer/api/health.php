<?php
/**
 * UserServer Health Check Endpoint
 * Returns status of user management server and database
 */

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
    echo "<html><head><title>UserServer Health Check</title></head><body>";
    echo "<h1>UserServer Health Check</h1>";
    echo "<p>The UserServer is running and accessible.</p>";
    echo "<p>For detailed API health information, API clients should access this endpoint directly.</p>";
    echo "</body></html>";
    exit();
}

header('Content-Type: application/json');

// Direct database connection (avoid session_start from config.php)
$db_host = "localhost";
$db_name = "lostfound";
$db_user = "root";
$db_pass = "Isaac@1234";

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

$health = [
    'server' => 'UserServer',
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
