<?php
/**
 * Admin Configuration File
 * 
 * This file handles all admin authentication functions.
 * It manages:
 * - Admin login credentials
 * - Session management
 * - Login/logout functionality
 * - Access control for admin pages
 * 
 * Security Note: Change the default credentials before deploying to production!
 */

// Start a session to track if admin is logged in
// Sessions store data across multiple pages for the same user
session_start();

// Define admin login credentials
// In production, these should be stored in a database with hashed passwords
define('ADMIN_USERNAME', 'admin');           // Admin username
define('ADMIN_PASSWORD', 'isaacK@12345');   // Admin password

/**
 * Check if admin is logged in
 * 
 * Returns true if admin is logged in, false otherwise
 * Uses $_SESSION to check login status
 */
function isAdminLoggedIn() {
    // Check if the session variable exists and is true
    if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] == true) {
        return true;
    }
    return false;
}

/**
 * Authenticate admin credentials
 * 
 * Checks if provided username and password match the admin credentials
 * If valid, sets session variable to mark admin as logged in
 * 
 * @param string $username - Username entered by user
 * @param string $password - Password entered by user
 * @return bool - True if credentials are valid, false otherwise
 */
function authenticateAdmin($username, $password) {
    // Compare provided credentials with defined constants
    if ($username == ADMIN_USERNAME && $password == ADMIN_PASSWORD) {
        // Set session variable to mark user as logged in
        $_SESSION['admin_logged_in'] = true;
        return true;
    }
    return false;
}

/**
 * Logout admin
 * 
 * Destroys the session and redirects to login page
 * This logs the admin out completely
 */
function logoutAdmin() {
    // session_destroy() removes all session data
    session_destroy();
    
    // Redirect to login page
    header('Location: admin_login.php');
    exit();
}

/**
 * Require admin authentication
 * 
 * Checks if user is logged in as admin
 * If not, redirects to login page
 * Use this function at the top of any admin-only page
 */
function requireAdmin() {
    // If not logged in, send to login page
    if (!isAdminLoggedIn()) {
        header('Location: admin_login.php');
        exit();
    }
}
?>
