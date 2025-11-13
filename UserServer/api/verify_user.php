<?php
/**
 * Verify User API
 * Authenticates a user with username and password
 */

require_once '../config.php';

// Set CORS headers for API
setCORSHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSONResponse(['error' => 'Only POST method is allowed'], 405);
}

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

// Validation
if (empty($username) || empty($password)) {
    sendJSONResponse(['error' => 'Username and password are required'], 400);
}

try {
    $conn = connectDB();
    
    // Get user by username
    $sql = "SELECT id, username, email, password, is_admin FROM users 
            WHERE username = '" . mysqli_real_escape_string($conn, $username) . "'";
    $result = mysqli_query($conn, $sql);
    
    if (!$result) {
        mysqli_close($conn);
        sendJSONResponse(['error' => 'Database query failed'], 500);
    }
    
    if (mysqli_num_rows($result) == 0) {
        mysqli_close($conn);
        sendJSONResponse(['error' => 'User not found'], 401);
    }
    
    $user = mysqli_fetch_assoc($result);
    
    // Verify password
    if (!password_verify($password, $user['password'])) {
        mysqli_close($conn);
        sendJSONResponse(['error' => 'Invalid password'], 401);
    }
    
    mysqli_close($conn);
    
    // Return user data
    sendJSONResponse([
        'success' => true,
        'user_id' => $user['id'],
        'username' => $user['username'],
        'email' => $user['email'],
        'is_admin' => (int)$user['is_admin'],
        'message' => 'User verified successfully'
    ]);
    
} catch (Exception $e) {
    sendJSONResponse(['error' => 'Server error: ' . $e->getMessage()], 500);
}
?>
