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
