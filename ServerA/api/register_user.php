<?php
/**
 * API: Register New User
 * 
 * POST /api/register_user.php
 * 
 * Parameters:
 * - username: string
 * - email: string
 * - password: string
 * 
 * Returns:
 * - success: boolean
 * - message: string
 * - error: string (if failed)
 */

require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSONResponse(['error' => 'Method not allowed'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['username']) || !isset($input['email']) || !isset($input['password'])) {
    sendJSONResponse(['error' => 'Username, email, and password required'], 400);
}

$username = trim($input['username']);
$email = trim($input['email']);
$password = $input['password'];

// Validate input
if (empty($username) || empty($email) || empty($password)) {
    sendJSONResponse(['error' => 'All fields are required'], 400);
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendJSONResponse(['error' => 'Invalid email format'], 400);
}

// Validate password length
if (strlen($password) < 6) {
    sendJSONResponse(['error' => 'Password must be at least 6 characters'], 400);
}

$conn = getDBConnection();

// Check if username or email already exists
$username = $conn->real_escape_string($username);
$email = $conn->real_escape_string($email);

$sql = "SELECT id FROM users WHERE username = '$username' OR email = '$email'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    sendJSONResponse(['error' => 'Username or email already exists'], 409);
}

// Hash password for security
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Insert new user
$sql = "INSERT INTO users (username, email, password) VALUES ('$username', '$email', '$hashedPassword')";

if ($conn->query($sql) === TRUE) {
    sendJSONResponse([
        'success' => true,
        'message' => 'Account created successfully'
    ]);
} else {
    sendJSONResponse(['error' => 'Error creating account: ' . $conn->error], 500);
}

$conn->close();
?>
