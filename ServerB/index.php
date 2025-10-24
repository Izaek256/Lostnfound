<?php
/**
 * Server B - Index API Endpoint
 * 
 * This endpoint indicates that Server B is running and ready to handle requests
 */

require_once 'config.php';

// Log that the endpoint was accessed
error_log("Server B: index.php accessed via " . $_SERVER['REQUEST_METHOD'] . " method");

// Return simple JSON response indicating the server is working
header('Content-Type: application/json');

echo json_encode([
    'server' => 'Server B',
    'status' => 'Running',
    'message' => 'Server B is handling requests. Use API endpoints for functionality.',
    'timestamp' => date('Y-m-d H:i:s')
]);

exit();
?>
