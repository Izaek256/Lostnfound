<?php
/**
 * UserServer API - Get All Users
 * 
 * Returns a list of all registered users (admin only)
 * This allows Frontend admin dashboard to display user management
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
    // Redirect to frontend admin dashboard page
    $frontend_url = 'http://' . $_SERVER['SERVER_ADDR'] . '/Lostnfound/Frontend/admin_dashboard.php';
    header("Location: $frontend_url");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendJSONResponse(['error' => 'Only GET method is allowed'], 405);
}

try {
    $conn = connectDB();
    
    // Get all users sorted by creation date
    $sql = "SELECT id, username, email, is_admin, created_at FROM users ORDER BY created_at DESC";
    
    $result = mysqli_query($conn, $sql);
    
    if (!$result) {
        mysqli_close($conn);
        sendJSONResponse(['error' => 'Database query failed'], 500);
    }
    
    $users = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $users[] = $row;
    }
    
    // Calculate statistics
    $total_users = count($users);
    $admin_users = count(array_filter($users, function($user) { 
        return $user['is_admin'] == 1; 
    }));
    $regular_users = $total_users - $admin_users;
    
    mysqli_close($conn);
    
    sendJSONResponse([
        'success' => true,
        'users' => $users,
        'stats' => [
            'total_users' => $total_users,
            'admin_users' => $admin_users,
            'regular_users' => $regular_users
        ]
    ]);
    
} catch (Exception $e) {
    sendJSONResponse(['error' => 'Server error: ' . $e->getMessage()], 500);
}
?>