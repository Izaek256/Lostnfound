<?php
/**
 * Server B - Report Lost Item API Endpoint
 * 
 * This endpoint handles lost item reporting requests for Server B
 */

require_once 'config.php';

// Log that the endpoint was accessed
error_log("Server B: report_lost.php accessed via " . $_SERVER['REQUEST_METHOD'] . " method");

// Return simple JSON response indicating the endpoint is working
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // For POST requests, indicate that lost item reporting would be processed
    echo json_encode([
        'server' => 'Server B',
        'endpoint' => 'report_lost',
        'status' => 'Processing lost item report',
        'method' => 'POST',
        'message' => 'Lost item report received. Processing would happen here.',
        'received_data' => array_keys($_POST),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
} else {
    // For GET requests or others, just indicate the endpoint is accessible
    echo json_encode([
        'server' => 'Server B',
        'endpoint' => 'report_lost',
        'status' => 'Ready to receive lost item reports',
        'method' => $_SERVER['REQUEST_METHOD'],
        'message' => 'This is an API endpoint for reporting lost items.',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}

exit();
?>
