<?php
/**
 * ServerB API - Register User
 * 
 * Creates a new user account in the database
 * ServerB handles all user-related operations
 */

require_once '../config.php';

// Set CORS headers for API
setCORSHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSONResponse(['error' => 'Only POST method is allowed'], 405);
}

$username = $_POST['username'] ?? '';
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

// Validation
if (empty($username) || empty($email) || empty($password)) {
    sendJSONResponse(['error' => 'Please fill all required fields'], 400);
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendJSONResponse(['error' => 'Invalid email format'], 400);
}

try {
    $conn = connectDB();
    
    // Check if user exists
    $check_sql = "SELECT id FROM users WHERE username = '" . mysqli_real_escape_string($conn, $username) . "' 
                  OR email = '" . mysqli_real_escape_string($conn, $email) . "'";
    $check_result = mysqli_query($conn, $check_sql);
    
    if (!$check_result) {
        mysqli_close($conn);
        sendJSONResponse(['error' => 'Database query failed'], 500);
    }
    
    if (mysqli_num_rows($check_result) > 0) {
        mysqli_close($conn);
        sendJSONResponse(['error' => 'Username or email already exists'], 400);
    }
    
    // Hash password and insert user
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $username_escaped = mysqli_real_escape_string($conn, $username);
    $email_escaped = mysqli_real_escape_string($conn, $email);
    
    $sql = "INSERT INTO users (username, email, password, is_admin, created_at) 
            VALUES ('$username_escaped', '$email_escaped', '$hashed_password', 0, NOW())";
    
    if (mysqli_query($conn, $sql)) {
        $user_id = mysqli_insert_id($conn);
        mysqli_close($conn);
        sendJSONResponse([
            'success' => true,
            'user_id' => $user_id,
            'username' => $username,
            'email' => $email,
            'message' => 'User registered successfully'
        ]);
    } else {
        mysqli_close($conn);
        sendJSONResponse(['error' => 'Failed to register user: ' . mysqli_error($conn)], 500);
    }
    
} catch (Exception $e) {
    sendJSONResponse(['error' => 'Server error: ' . $e->getMessage()], 500);
}
?>
