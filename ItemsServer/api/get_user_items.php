<?php
/**
 * ItemsServer API - Get User's Items
 * 
 * Returns all items posted by a specific user
 * This allows Frontend to retrieve user items without direct database access
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
    $conn = connectDB();
    
    // Get user's items
    $sql = "SELECT i.*, u.username FROM items i 
            LEFT JOIN users u ON i.user_id = u.id 
            WHERE i.user_id = '" . mysqli_real_escape_string($conn, $user_id) . "' 
            ORDER BY i.created_at DESC";
    
    $result = mysqli_query($conn, $sql);
    
    if (!$result) {
        mysqli_close($conn);
        sendJSONResponse(['error' => 'Database query failed'], 500);
    }
    
    $userItems = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $userItems[] = $row;
    }
    
    // Calculate statistics
    $total = count($userItems);
    $lost_count = 0;
    $found_count = 0;
    
    foreach ($userItems as $item) {
        if ($item['type'] == 'lost') {
            $lost_count++;
        } else {
            $found_count++;
        }
    }
    
    mysqli_close($conn);
    
    sendJSONResponse([
        'success' => true,
        'items' => $userItems,
        'stats' => [
            'total' => $total,
            'lost_count' => $lost_count,
            'found_count' => $found_count
        ]
    ]);
    
} catch (Exception $e) {
    sendJSONResponse(['error' => 'Server error: ' . $e->getMessage()], 500);
}
?>