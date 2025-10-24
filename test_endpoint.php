<?php
/**
 * Simple test endpoint to verify PHP execution
 */

// Set content type
header('Content-Type: application/json');

// Simple response
echo json_encode([
    'success' => true,
    'message' => 'Test endpoint working',
    'timestamp' => date('Y-m-d H:i:s'),
    'server_info' => [
        'php_version' => PHP_VERSION,
        'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown'
    ]
]);
?>
