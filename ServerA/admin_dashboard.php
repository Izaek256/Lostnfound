<?php
/**
 * Server A - Admin Dashboard API Endpoint
 * 
 * This endpoint handles admin dashboard requests for Server A
 */

require_once 'config.php';

// Log that the endpoint was accessed
error_log("Server A: admin_dashboard.php accessed via " . $_SERVER['REQUEST_METHOD'] . " method");

// Return simple JSON response indicating the endpoint is working
header('Content-Type: application/json');

// Check if user is logged in as admin (simplified for API response)
$isAdmin = isset($_SESSION['user_id']) && isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // For POST requests, indicate that admin actions would be processed
    echo json_encode([
        'server' => 'Server A',
        'endpoint' => 'admin_dashboard',
        'status' => 'Processing admin request',
        'method' => 'POST',
        'message' => 'Admin action request received. Processing would happen here.',
        'is_admin' => $isAdmin,
        'received_data' => array_keys($_POST),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
} else {
    // For GET requests or others, just indicate the endpoint is accessible
    echo json_encode([
        'server' => 'Server A',
        'endpoint' => 'admin_dashboard',
        'status' => 'Ready to receive admin requests',
        'method' => $_SERVER['REQUEST_METHOD'],
        'message' => 'This is an API endpoint for admin dashboard functionality.',
        'is_admin' => $isAdmin,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}

exit();
?>
