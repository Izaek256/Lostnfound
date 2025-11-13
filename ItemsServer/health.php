<?php
/**
 * ServerA Health Check Endpoint
 * Returns JSON status of ServerA (Authentication Server)
 */

require_once 'deployment_config.php';

// Set JSON headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$health_data = [
    'server' => 'ServerA',
    'role' => 'Authentication Server',
    'status' => 'online',
    'timestamp' => date('Y-m-d H:i:s'),
    'deployment_mode' => DEPLOYMENT_MODE,
    'services' => []
];

// Test database connection
try {
    $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn) {
        $health_data['services']['database_connection'] = 'active';
        mysqli_close($conn);
    } else {
        $health_data['services']['database_connection'] = 'failed: ' . mysqli_connect_error();
        $health_data['status'] = 'degraded';
        http_response_code(503);
    }
} catch (Exception $e) {
    $health_data['services']['database_connection'] = 'error: ' . $e->getMessage();
    $health_data['status'] = 'degraded';
    http_response_code(503);
}

// Test API endpoint
$api_health_url = SERVERA_API_URL . '/health.php';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_health_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 3);
$api_response = curl_exec($ch);
$api_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$health_data['services']['api_endpoint'] = ($api_http_code == 200) ? 'active' : 'failed';

echo json_encode($health_data, JSON_PRETTY_PRINT);
?>