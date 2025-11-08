<?php
/**
 * ServerB API - Get User Items (Proxy to ServerA)
 * 
 * Proxies requests to ServerA to get items for a specific user
 * ServerB handles user-related operations and proxies item queries to ServerA
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
    // Proxy the request to ServerA's item endpoint
    $servera_url = SERVERA_API_URL . '/get_user_items.php';
    $response = makeAPIRequest(
        $servera_url,
        ['user_id' => $user_id],
        'GET',
        ['return_json' => true, 'force_json' => true]
    );
    
    if (is_array($response) && isset($response['success'])) {
        // Forward the response from ServerA
        sendJSONResponse($response);
    } else {
        sendJSONResponse(['error' => 'Unexpected response from ServerA'], 500);
    }
    
} catch (Exception $e) {
    sendJSONResponse(['error' => 'Server error: ' . $e->getMessage()], 500);
}
?>
