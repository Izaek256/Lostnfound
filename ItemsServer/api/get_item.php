<?php
/**
 * ItemsServer API - Get Item by ID
 * 
 * Returns a specific item's details
 * This allows Frontend to retrieve item data without direct database access
 */

require_once '../config.php';

// Set CORS headers for API
setCORSHeaders();

// Check if request is from browser directly (not AJAX)
$accept_header = $_SERVER['HTTP_ACCEPT'] ?? '';
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$x_requested_with = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';

// Enhanced browser detection - redirect to frontend if it looks like a direct browser request
$is_browser_request = (
    // Accept header contains text/html (browser requests HTML by default)
    (strpos($accept_header, 'text/html') !== false && strpos($accept_header, 'application/json') === false) &&
    // User agent indicates a browser
    (strpos($user_agent, 'Mozilla') !== false || strpos($user_agent, 'Chrome') !== false || strpos($user_agent, 'Safari') !== false || strpos($user_agent, 'Firefox') !== false || strpos($user_agent, 'Edge') !== false) &&
    // Not an AJAX request
    (empty($x_requested_with) || stripos($x_requested_with, 'XMLHttpRequest') === false)
);

if ($is_browser_request) {
    // Redirect to frontend items page
    $frontend_url = 'http://' . $_SERVER['SERVER_ADDR'] . '/Lostnfound/Frontend/items.php';
    header("Location: $frontend_url");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendJSONResponse(['error' => 'Only GET method is allowed'], 405);
}

$item_id = $_GET['item_id'] ?? null;

if (!$item_id || !is_numeric($item_id)) {
    sendJSONResponse(['error' => 'Valid item_id parameter is required'], 400);
}

try {
    $conn = connectDB();
    
    $sql = "SELECT i.*, u.username FROM items i 
            LEFT JOIN users u ON i.user_id = u.id 
            WHERE i.id = '" . mysqli_real_escape_string($conn, $item_id) . "'";
    
    $result = mysqli_query($conn, $sql);
    
    if (!$result) {
        mysqli_close($conn);
        sendJSONResponse(['error' => 'Database query failed'], 500);
    }
    
    if (mysqli_num_rows($result) === 0) {
        mysqli_close($conn);
        sendJSONResponse(['error' => 'Item not found'], 404);
    }
    
    $item = mysqli_fetch_assoc($result);
    mysqli_close($conn);
    
    sendJSONResponse([
        'success' => true,
        'item' => $item
    ]);
    
} catch (Exception $e) {
    sendJSONResponse(['error' => 'Server error: ' . $e->getMessage()], 500);
}
?>