<?php
/**
 * ServerB API - Get All Users
 * 
 * Returns a list of all registered users (admin only)
 * This allows ServerC admin dashboard to display user management
 */

require_once '../config.php';

// Set CORS headers for API
setCORSHeaders();

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
