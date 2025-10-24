<?php
/**
 * API: Verify User Login
 * 
 * POST /api/verify_user.php
 * 
 * Parameters:
 * - username: string
 * - password: string
 * 
 * Returns:
 * - success: boolean
 * - user_id: int (if successful)
 * - username: string (if successful)
 * - email: string (if successful)
 * - is_admin: int (if successful)
 * - error: string (if failed)
 */

require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSONResponse(['error' => 'Method not allowed'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['username']) || !isset($input['password'])) {
    sendJSONResponse(['error' => 'Username and password required'], 400);
}

$username = trim($input['username']);
$password = $input['password'];

if (empty($username) || empty($password)) {
    sendJSONResponse(['error' => 'Username and password cannot be empty'], 400);
}

$conn = getDBConnection();

// Escape username for security
$username = $conn->real_escape_string($username);

// Get user from database
$sql = "SELECT id, username, email, password, is_admin FROM users WHERE username = '$username'";
$result = $conn->query($sql);

if ($result->num_rows === 0) {
    sendJSONResponse(['error' => 'Invalid username or password'], 401);
}

$user = $result->fetch_assoc();

// Verify password
if (password_verify($password, $user['password'])) {
    // Set session variables
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['is_admin'] = $user['is_admin'];
    
    sendJSONResponse([
        'success' => true,
        'user_id' => $user['id'],
        'username' => $user['username'],
        'email' => $user['email'],
        'is_admin' => $user['is_admin']
    ]);
} else {
    sendJSONResponse(['error' => 'Invalid username or password'], 401);
}

$conn->close();
?>
