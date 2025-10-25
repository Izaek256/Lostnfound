<?php
/**
 * Multi-Server Configuration (Simple for Beginners)
 * 
 * This single file contains ALL server configurations.
 * Easy to deploy on different computers by changing the IPs/hosts below.
 */

// Start session
session_start();

// ============================================
// SERVER DEPLOYMENT CONFIGURATION
// ============================================
// ServerC connects to ServerB's centralized database

// Database Configuration (Points to ServerB)
$db_host = "192.168.5.242";   // PC 1's IP address
$db_name = "lostfound_db";   // Database name on ServerB
$db_user = "root";           // Database username
$db_pass = "kpet";           // Database password
// ============================================
// DATABASE CONNECTION FUNCTIONS
// ============================================
// ServerC connects to the centralized database on ServerB

// Main database connection - connects to ServerB's database
function connectDB() {
    global $db_host, $db_name, $db_user, $db_pass;
    
    $conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
    
    if (!$conn) {
        die("Database connection failed: " . mysqli_connect_error());
    }
    
    return $conn;
}

// Legacy function names for backward compatibility
function connectServerA() {
    return connectDB();
}

function connectServerB() {
    return connectDB();
}

function connectServerC() {
    return connectDB();
}

// ============================================
// IMAGE PATH HELPER FUNCTIONS
// ============================================

// Get image URL for browser display (uses local mount if available)
function getImageUrl($filename) {
    if (empty($filename)) return '';
    
    // Check if local mount exists and is accessible
    $localPath = UPLOADS_PATH . $filename;
    if (file_exists($localPath)) {
        return UPLOADS_URL . $filename;  // Use local/mounted path
    }
    
    // Fallback to HTTP URL if mount not available
    return UPLOADS_HTTP_URL . $filename;
}

// Get image path for file_exists checks
function getImagePath($filename) {
    if (empty($filename)) return '';
    return UPLOADS_PATH . $filename;
}

// Check if image exists (checks local mount)
function imageExists($filename) {
    if (empty($filename)) return false;
    return file_exists(getImagePath($filename));
}

// ============================================
// API REQUEST FUNCTIONS
// ============================================

// Simple function to make API calls to other servers
function makeAPIRequest($url, $data = [], $method = 'POST') {
    // Initialize cURL
    $ch = curl_init();
    
    if ($method == 'POST') {
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    } elseif ($method == 'GET') {
        if (!empty($data)) {
            $url .= '?' . http_build_query($data);
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPGET, true);
    } elseif ($method == 'DELETE') {
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        if (!empty($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }
    }
    
    // Set cURL options
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
    
    // Execute request
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    // Handle errors
    if ($error) {
        error_log("API Request Error to $url: $error");
        return "error|Connection failed: $error";
    }
    
    if ($http_code >= 400) {
        error_log("API Request HTTP Error $http_code to $url");
        return "error|Server error (HTTP $http_code)";
    }
    
    // Return response
    return $response;
}

// API URLs - Update these when deploying to different computers
// Example:// SPLIT SERVER DEPLOYMENT - ServerC on PC2, ServerA+B on PC1 (192.168.5.242)
define('SERVERA_URL', 'http://192.168.5.242/Lostnfound/ServerA/api');
define('SERVERB_URL', 'http://192.168.5.242/Lostnfound/ServerB/api');

// Upload paths - supports both network mount and HTTP access
define('UPLOADS_PATH', '../ServerB/uploads/');  // Local/mounted path for file operations
define('UPLOADS_URL', '../ServerB/uploads/');   // Browser path (works if mounted locally)
define('UPLOADS_HTTP_URL', 'http://192.168.5.242/Lostnfound/ServerB/uploads/');  // HTTP fallback

// Simple user check functions
function isUserLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

function getCurrentUsername() {
    return $_SESSION['username'] ?? null;
}

function getCurrentUserEmail() {
    return $_SESSION['user_email'] ?? null;
}

function isCurrentUserAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
}

function requireUser() {
    if (!isUserLoggedIn()) {
        header('Location: user_login.php');
        exit();
    }
}

function logoutUser() {
    session_destroy();
    header('Location: index.php');
    exit();
}
?>
