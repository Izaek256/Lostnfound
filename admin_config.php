<?php
/**
 * Admin Configuration File
 * 
 * This file handles all admin authentication functions.
 * It manages:
 * - Admin role checking (based on user account role)
 * - Session management
 * - Login/logout functionality
 * - Access control for admin pages
 * 
 * Note: Admin rights are now assigned via the is_admin field in the users table
 */

// Start a session to track if admin is logged in
// Sessions store data across multiple pages for the same user
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if admin is logged in
 * 
 * Returns true if user is logged in and has admin rights
 * Uses $_SESSION to check login status and admin role
 */
function isAdminLoggedIn()
{
    // Check if user is logged in and has admin rights
    if (isset($_SESSION['user_id']) && isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1) {
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
function logoutAdmin()
{
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
function requireAdmin()
{
    // If not logged in, send to login page
    if (!isAdminLoggedIn()) {
        header('Location: admin_login.php');
        exit();
    }
}
