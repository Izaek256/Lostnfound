<?php
/**
 * Get All Items API
 * Returns items with optional filtering by type and search
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

$type = $_GET['type'] ?? '';
$search = $_GET['search'] ?? '';

try {
    $conn = connectDB();
    
    // Build SQL query with filters
    $sql = "SELECT i.*, u.username FROM items i 
            LEFT JOIN users u ON i.user_id = u.id 
            WHERE 1=1";
    
    if (!empty($type) && in_array($type, ['lost', 'found'])) {
        $sql .= " AND i.type = '" . mysqli_real_escape_string($conn, $type) . "'";
    }
    
    if (!empty($search)) {
        $search_escaped = mysqli_real_escape_string($conn, $search);
        $sql .= " AND (i.title LIKE '%$search_escaped%' 
                  OR i.description LIKE '%$search_escaped%' 
                  OR i.location LIKE '%$search_escaped%')";
    }
    
    $sql .= " ORDER BY i.created_at DESC";
    
    $result = mysqli_query($conn, $sql);
    
    if (!$result) {
        mysqli_close($conn);
        sendJSONResponse(['error' => 'Database query failed'], 500);
    }
    
    $items = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $items[] = $row;
    }
    
    // Get statistics
    $stats_sql = "SELECT COUNT(*) as total FROM items";
    $stats_result = mysqli_query($conn, $stats_sql);
    $stats = mysqli_fetch_assoc($stats_result);
    
    $lost_sql = "SELECT COUNT(*) as lost_count FROM items WHERE type = 'lost'";
    $lost_result = mysqli_query($conn, $lost_sql);
    $lost_stats = mysqli_fetch_assoc($lost_result);
    
    $found_sql = "SELECT COUNT(*) as found_count FROM items WHERE type = 'found'";
    $found_result = mysqli_query($conn, $found_sql);
    $found_stats = mysqli_fetch_assoc($found_result);
    
    mysqli_close($conn);
    
    sendJSONResponse([
        'success' => true,
        'items' => $items,
        'count' => count($items),
        'stats' => [
            'total' => $stats['total'],
            'lost_count' => $lost_stats['lost_count'],
            'found_count' => $found_stats['found_count']
        ]
    ]);
    
} catch (Exception $e) {
    sendJSONResponse(['error' => 'Server error: ' . $e->getMessage()], 500);
}
?>