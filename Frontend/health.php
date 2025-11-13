<?php
/**
 * Frontend Health Check Endpoint
 * Returns JSON status of Frontend and its connectivity to other servers
 */

require_once 'config.php';

// Set JSON headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$health_data = [
    'server' => 'Frontend',
    'role' => 'User Interface Server',
    'status' => 'online',
    'timestamp' => date('Y-m-d H:i:s'),
    'database' => 'not_applicable',
    'services' => []
];

// Note: Frontend is a client and does NOT connect directly to the database
// It communicates with ItemsServer and UserServer through APIs
// So we check API connectivity instead of database connectivity

// Test ItemsServer connectivity
$servera_status = testServerConnection(ITEMSSERVER_URL);
$health_data['services']['servera_api'] = $servera_status ? 'reachable' : 'unreachable';

// Test UserServer connectivity  
$serverb_status = testServerConnection(USERSERVER_URL);
$health_data['services']['serverb_api'] = $serverb_status ? 'reachable' : 'unreachable';

// Test uploads directory access
$uploads_accessible = is_dir(UPLOADS_PATH) && is_readable(UPLOADS_PATH);
$health_data['services']['uploads_directory'] = $uploads_accessible ? 'accessible' : 'inaccessible';

// Overall health status
$all_services_ok = (
    $servera_status &&
    $serverb_status
);

if (!$all_services_ok) {
    $health_data['status'] = 'degraded';
    http_response_code(503); // Service Unavailable
}

echo json_encode($health_data, JSON_PRETTY_PRINT);
?>