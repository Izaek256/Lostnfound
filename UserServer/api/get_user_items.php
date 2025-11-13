<?php
/**
 * UserServer API - Get User Items (Proxy to ItemsServer)
 * 
 * Proxies requests to ItemsServer to get items for a specific user
 * UserServer handles user-related operations and proxies item queries to ItemsServer
 */

require_once '../config.php';

// Set CORS headers for API
setCORSHeaders();

// Check if request is from browser directly (not AJAX)
$accept_header = $_SERVER['HTTP_ACCEPT'] ?? '';
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$x_requested_with = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';

// If request appears to be from browser directly, redirect to frontend
if (strpos($accept_header, 'text/html') !== false && 
    (strpos($user_agent, 'Mozilla') !== false || strpos($user_agent, 'Chrome') !== false || strpos($user_agent, 'Safari') !== false) &&
    (empty($x_requested_with) || stripos($x_requested_with, 'XMLHttpRequest') === false)) {
    // Redirect to frontend user dashboard page
    $frontend_url = 'http://' . $_SERVER['SERVER_ADDR'] . '/Lostnfound/Frontend/user_dashboard.php';
    header("Location: $frontend_url");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendJSONResponse(['error' => 'Only GET method is allowed'], 405);
}

$user_id = $_GET['user_id'] ?? null;

if (!$user_id) {
    sendJSONResponse(['error' => 'user_id parameter is required'], 400);
}

// Validate user_id is numeric
if (!is_numeric($user_id)) {
    sendJSONResponse(['error' => 'Invalid user_id'], 400);
}

try {
    // Proxy the request to ItemsServer's item endpoint
    $itemsserver_url = ITEMSSERVER_API_URL . '/get_user_items.php';
    $response = makeAPIRequest(
        $itemsserver_url,
        ['user_id' => $user_id],
        'GET',
        ['return_json' => true, 'force_json' => true]
    );
    
    if (is_array($response) && isset($response['success'])) {
        // Forward the response from ItemsServer
        sendJSONResponse($response);
    } else {
        sendJSONResponse(['error' => 'Unexpected response from ItemsServer'], 500);
    }
    
} catch (Exception $e) {
    sendJSONResponse(['error' => 'Server error: ' . $e->getMessage()], 500);
}
?>