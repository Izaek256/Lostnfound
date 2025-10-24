<?php
/**
 * Server A - Index Page
 * 
 * This is the main entry point for Server A
 */

require_once 'config.php';

// Simple response indicating server is running
header('Content-Type: application/json');
echo json_encode([
    'server' => 'Server A',
    'status' => 'Running',
    'message' => 'Server A is handling requests. Use API endpoints for functionality.',
    'timestamp' => date('Y-m-d H:i:s')
]);
exit();
?>