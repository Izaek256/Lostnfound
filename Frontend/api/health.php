<?php
/**
 * Frontend API Health Check Endpoint
 * Returns JSON status of Frontend API services
 */

require_once '../config.php';

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
    'database' => 'not_applicable',  // Frontend doesn't connect to database directly
    'services' => []
];

// Note: Frontend is a client and does NOT connect directly to the database
// It communicates with ItemsServer and UserServer through APIs
// So we check API connectivity instead of database connectivity

// Test ItemsServer connectivity
$itemsserver_status = testServerConnection(ITEMSSERVER_URL);
$health_data['services']['itemsserver_api'] = $itemsserver_status['success'] ? 'reachable' : 'unreachable';

// Test UserServer connectivity  
$userserver_status = testServerConnection(USERSERVER_URL);
$health_data['services']['userserver_api'] = $userserver_status['success'] ? 'reachable' : 'unreachable';

// Test uploads directory access
$uploads_accessible = is_dir(UPLOADS_PATH) && is_readable(UPLOADS_PATH);
$health_data['services']['uploads_directory'] = $uploads_accessible ? 'accessible' : 'inaccessible';

// Overall health status
$all_services_ok = (
    $itemsserver_status['success'] &&
    $userserver_status['success']
);

if (!$all_services_ok) {
    $health_data['status'] = 'degraded';
    http_response_code(503); // Service Unavailable
}

echo json_encode($health_data, JSON_PRETTY_PRINT);
?>