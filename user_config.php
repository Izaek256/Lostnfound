<?php
/**
 * User Configuration File
 * 
 * This file handles user authentication functions.
 * It manages:
 * - User registration
 * - User login/logout
 * - Session management
 * - Access control for user pages
 */

// Start session for user tracking
session_start();

/**
 * Check if current user is an admin
 * 
 * Returns true if user is logged in and has admin rights
 */
function isCurrentUserAdmin() {
    return isUserLoggedIn() && isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
}

/**
 * Check if user is logged in
 * 
 * Returns true if user is logged in, false otherwise
 */
function isUserLoggedIn() {
    return isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0;
}

/**
 * Get current logged in user ID
 * 
 * Returns user ID or null if not logged in
 */
function getCurrentUserId() {
    if (isUserLoggedIn()) {
        return $_SESSION['user_id'];
    }
    return null;
}

/**
 * Get current logged in username
 * 
 * Returns username or null if not logged in
 */
function getCurrentUsername() {
    if (isset($_SESSION['username'])) {
        return $_SESSION['username'];
    }
    return null;
}

/**
 * Get current logged in user email
 * 
 * Returns email or null if not logged in
 */
function getCurrentUserEmail() {
    if (isset($_SESSION['user_email'])) {
        return $_SESSION['user_email'];
    }
    return null;
}

/**
 * Register a new user
 * 
 * Creates a new user account with hashed password
 * Returns error message if failed, empty string if successful
 */
function registerUser($conn, $username, $email, $password) {
    // Validate input
    if (empty($username) || empty($email) || empty($password)) {
        return 'All fields are required';
    }
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return 'Invalid email format';
    }
    
    // Validate password length
    if (strlen($password) < 6) {
        return 'Password must be at least 6 characters';
    }
    
    // Check if username already exists
    $username = mysqli_real_escape_string($conn, $username);
    $email = mysqli_real_escape_string($conn, $email);
    
    $sql = "SELECT id FROM users WHERE username = '$username' OR email = '$email'";
    $result = mysqli_query($conn, $sql);
    
    if (mysqli_num_rows($result) > 0) {
        return 'Username or email already exists';
    }
    
    // Hash password for security
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert new user
    $sql = "INSERT INTO users (username, email, password) 
            VALUES ('$username', '$email', '$hashedPassword')";
    
    if (mysqli_query($conn, $sql)) {
        return ''; // Success
    } else {
        return 'Error creating account: ' . mysqli_error($conn);
    }
}

/**
 * Authenticate user login
 * 
 * Checks credentials and creates session if valid
 * Returns error message if failed, empty string if successful
 */
function loginUser($conn, $username, $password) {
    // Validate input
    if (empty($username) || empty($password)) {
        return 'Please enter username and password';
    }
    
    // Escape username
    $username = mysqli_real_escape_string($conn, $username);
    
    // Get user from database
    $sql = "SELECT id, username, email, password, is_admin FROM users WHERE username = '$username'";
    $result = mysqli_query($conn, $sql);
    
    if (mysqli_num_rows($result) === 0) {
        return 'Invalid username or password';
    }
    
    $user = mysqli_fetch_assoc($result);
    
    // Verify password
    if (password_verify($password, $user['password'])) {
        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['is_admin'] = $user['is_admin'];
        return ''; // Success
    } else {
        return 'Invalid username or password';
    }
}

/**
 * Logout user
 * 
 * Destroys session and redirects to home page
 */
function logoutUser() {
    session_destroy();
    header('Location: index.php');
    exit();
}

/**
 * Require user authentication
 * 
 * Redirects to login page if user is not logged in
 */
function requireUser() {
    if (!isUserLoggedIn()) {
        header('Location: user_login.php');
        exit();
    }
}
?>
