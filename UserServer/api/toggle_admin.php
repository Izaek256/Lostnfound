<?php
/**
 * UserServer API - Toggle User Admin Status
 * 
 * Changes a user's admin status (make admin or remove admin)
 * Admin only operation
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
    // Redirect to frontend admin dashboard page
    $frontend_url = 'http://' . $_SERVER['SERVER_ADDR'] . '/Lostnfound/Frontend/admin_dashboard.php';
    header("Location: $frontend_url");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSONResponse(['error' => 'Only POST method is allowed'], 405);
}

$user_id = $_POST['user_id'] ?? null;
$is_admin = $_POST['is_admin'] ?? null;

// Validation
if (!$user_id || !is_numeric($user_id)) {
    sendJSONResponse(['error' => 'Valid user_id parameter is required'], 400);
}

if ($is_admin === null || ($is_admin != 0 && $is_admin != 1)) {
    sendJSONResponse(['error' => 'is_admin parameter must be 0 or 1'], 400);
}

try {
    $conn = connectDB();
    
    // Check if user exists
    $check_sql = "SELECT id FROM users WHERE id = '" . mysqli_real_escape_string($conn, $user_id) . "'";
    $check_result = mysqli_query($conn, $check_sql);
    
    if (!$check_result || mysqli_num_rows($check_result) === 0) {
        mysqli_close($conn);
        sendJSONResponse(['error' => 'User not found'], 404);
    }
    
    // Update user admin status
    $update_sql = "UPDATE users SET is_admin = '" . mysqli_real_escape_string($conn, $is_admin) . "' 
                   WHERE id = '" . mysqli_real_escape_string($conn, $user_id) . "'";
    
    if (!mysqli_query($conn, $update_sql)) {
        mysqli_close($conn);
        sendJSONResponse(['error' => 'Failed to update user status'], 500);
    }
    
    mysqli_close($conn);
    
    $status_text = $is_admin == 1 ? 'promoted to admin' : 'removed from admin';
    
    sendJSONResponse([
        'success' => true,
        'message' => 'User status updated successfully',
        'user_id' => $user_id,
        'is_admin' => (int)$is_admin,
        'status_text' => $status_text
    ]);
    
} catch (Exception $e) {
    sendJSONResponse(['error' => 'Server error: ' . $e->getMessage()], 500);
}
?>