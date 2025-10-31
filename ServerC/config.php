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
$db_host = "localhost";   // PC 1's IP address
$db_name = "lostfound_db";   // Database name on ServerB
$db_user = "root";           // Database username
$db_pass = "isaacK@12345";           // Database password
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

// Enhanced function to make API calls to other servers with better cross-server support
function makeAPIRequest($url, $data = [], $method = 'POST') {
    // Initialize cURL
    $ch = curl_init();
    
    // Set method-specific options
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
    
    // Enhanced cURL options for cross-server communication
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
    
    // Headers for better cross-server compatibility
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded',
        'Accept: */*',
        'User-Agent: LostFound-ServerC/1.0'
    ]);
    
    // SSL and security options (for HTTPS if needed)
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    
    // Execute request
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    
    // Enhanced error handling
    if ($error) {
        error_log("API Request cURL Error to $url: $error");
        return "error|Connection failed: $error";
    }
    
    if ($http_code == 0) {
        error_log("API Request Connection Error to $url: No response received");
        return "error|No response from server. Check if server is running and accessible.";
    }
    
    if ($http_code >= 400) {
        error_log("API Request HTTP Error $http_code to $url. Response: " . substr($response, 0, 200));
        return "error|Server error (HTTP $http_code)";
    }
    
    // Log successful requests for debugging
    error_log("API Request Success to $url: HTTP $http_code");
    
    return $response;
}

// API URLs - Update these when deploying to different computers
// DEPLOYMENT CONFIGURATION: Update these IPs to match your server locations
define('SERVERA_URL', 'http://localhost/Lostnfound/ServerA/api');  // Change to ServerA IP (e.g., http://192.168.1.10/Lostnfound/ServerA/api)
define('SERVERB_URL', 'http://localhost/Lostnfound/ServerB/api');  // Change to ServerB IP (e.g., http://192.168.1.20/Lostnfound/ServerB/api)

// Upload paths - supports both network mount and HTTP access
define('UPLOADS_PATH', '../ServerB/uploads/');  // Local/mounted path for file operations (if servers share filesystem)
define('UPLOADS_URL', '../ServerB/uploads/');   // Browser path (works if mounted locally)
define('UPLOADS_HTTP_URL', 'http://localhost/Lostnfound/ServerB/uploads/');  // Change to ServerB IP (e.g., http://192.168.1.20/Lostnfound/ServerB/uploads/)

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

// ============================================
// SERVER CONNECTIVITY FUNCTIONS
// ============================================

// Test if a server is reachable
function testServerConnection($server_url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $server_url . '/health.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    
    curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    return ($error === '' && $http_code == 200);
}

// Get server status with details
function getServerStatus($server_url, $server_name) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $server_url . '/health.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
    
    $start_time = microtime(true);
    $response = curl_exec($ch);
    $response_time = round((microtime(true) - $start_time) * 1000, 2);
    
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    return [
        'name' => $server_name,
        'url' => $server_url,
        'online' => ($error === '' && $http_code == 200),
        'response_time' => $response_time,
        'http_code' => $http_code,
        'error' => $error,
        'response' => $response
    ];
}
?>
