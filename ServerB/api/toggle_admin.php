<?php
/**
 * ServerB API - Toggle User Admin Status
 * 
 * Changes a user's admin status (make admin or remove admin)
 * Admin only operation
 */

require_once '../config.php';

// Set CORS headers for API
setCORSHeaders();

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
