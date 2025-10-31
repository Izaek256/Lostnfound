<?php
/**
 * ServerC Health Check Endpoint
 * Returns JSON status of ServerC and its connectivity to other servers
 */

require_once 'config.php';

// Set JSON headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$health_data = [
    'server' => 'ServerC',
    'role' => 'User Interface Server',
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

// Test ServerA connectivity
$servera_status = testServerConnection(SERVERA_URL);
$health_data['services']['servera_api'] = $servera_status ? 'reachable' : 'unreachable';

// Test ServerB connectivity  
$serverb_status = testServerConnection(SERVERB_URL);
$health_data['services']['serverb_api'] = $serverb_status ? 'reachable' : 'unreachable';

// Test uploads directory access
$uploads_accessible = is_dir(UPLOADS_PATH) && is_readable(UPLOADS_PATH);
$health_data['services']['uploads_directory'] = $uploads_accessible ? 'accessible' : 'inaccessible';

// Overall health status
$all_services_ok = (
    $health_data['database'] === 'connected' &&
    $servera_status &&
    $serverb_status
);

if (!$all_services_ok) {
    $health_data['status'] = 'degraded';
    http_response_code(503); // Service Unavailable
}

echo json_encode($health_data, JSON_PRETTY_PRINT);
?>