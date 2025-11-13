<?php
/**
 * Frontend API Health Check Endpoint
 * Returns JSON status of Frontend API services
 */

require_once '../config.php';

// Check if request is from browser directly (not AJAX)
$accept_header = $_SERVER['HTTP_ACCEPT'] ?? '';
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

// If request appears to be from browser directly, show a simple HTML page
if (strpos($accept_header, 'text/html') !== false && 
    (strpos($user_agent, 'Mozilla') !== false || strpos($user_agent, 'Chrome') !== false || strpos($user_agent, 'Safari') !== false) &&
    !isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    // Show simple HTML page
    echo "<html><head><title>Frontend Health Check</title></head><body>";
    echo "<h1>Frontend Health Check</h1>";
    echo "<p>The Frontend is running and accessible.</p>";
    echo "<p>For detailed API health information, API clients should access this endpoint directly.</p>";
    echo "</body></html>";
    exit();
}

// Set JSON headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$health_data = [
    'server' => 'Frontend',
    'role' => 'User Interface Server - API',
    'status' => 'online',
    'timestamp' => date('Y-m-d H:i:s'),
    'database' => 'connected',
    'services' => []
];

// Test database connection
try {
    $conn = connectDB();
    if ($conn) {
        $health_data['database'] = 'connected';
        $health_data['services']['database_connection'] = 'active';
        mysqli_close($conn);
    } else {
        $health_data['database'] = 'failed';
        $health_data['services']['database_connection'] = 'failed';
    }
} catch (Exception $e) {
    $health_data['database'] = 'error';
    $health_data['services']['database_connection'] = 'error: ' . $e->getMessage();
}

// Test ItemsServer connectivity
$itemsserver_status = testServerConnection(ITEMSSERVER_URL);
$health_data['services']['itemsserver_api'] = $itemsserver_status ? 'reachable' : 'unreachable';

// Test UserServer connectivity  
$userserver_status = testServerConnection(USERSERVER_URL);
$health_data['services']['userserver_api'] = $userserver_status ? 'reachable' : 'unreachable';

// Test uploads directory access
$uploads_accessible = is_dir(UPLOADS_PATH) && is_readable(UPLOADS_PATH);
$health_data['services']['uploads_directory'] = $uploads_accessible ? 'accessible' : 'inaccessible';

// Overall health status
$all_services_ok = (
    $health_data['database'] === 'connected' &&
    $itemsserver_status &&
    $userserver_status
);

if (!$all_services_ok) {
    $health_data['status'] = 'degraded';
    http_response_code(503); // Service Unavailable
}

echo json_encode($health_data, JSON_PRETTY_PRINT);
?>