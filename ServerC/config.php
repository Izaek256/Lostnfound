<?php
/**
 * Server C - Single Server Configuration
 * 
 * This configuration works when all servers run on the same WAMP installation
 */

// Single server configuration (WAMP default)
$base_url = "http://localhost/Lostnfound";

// API endpoints for single server setup
$api_endpoints = [
    // User Management APIs (ServerA)
    'verify_user' => "$base_url/ServerA/api/verify_user.php",
    'register_user' => "$base_url/ServerA/api/register_user.php",
    'admin_login' => "$base_url/ServerA/admin_login.php",
    'session_status' => "$base_url/ServerA/api/session_status.php",
    
    // Item Management APIs (ServerB)
    'get_items' => "$base_url/ServerB/api/get_items.php",
    'add_item' => "$base_url/ServerB/api/add_item.php",
    'update_item' => "$base_url/ServerB/api/update_item.php",
    'delete_item' => "$base_url/ServerB/api/delete_item.php"
];

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// API call helper function for single server
function makeAPICall($endpoint, $data = null, $method = 'GET') {
    global $api_endpoints;
    
    // Handle endpoints with query parameters
    $endpointBase = $endpoint;
    $queryParams = '';
    if (strpos($endpoint, '?') !== false) {
        list($endpointBase, $queryParams) = explode('?', $endpoint, 2);
        $queryParams = '?' . $queryParams;
    }
    
    if (!isset($api_endpoints[$endpointBase])) {
        return ['error' => 'Invalid API endpoint'];
    }
    
    $url = $api_endpoints[$endpointBase] . $queryParams;
    
    // Debug: Show what URL we're calling
    error_log("makeAPICall: Calling URL: " . $url);
    
    // Remove caching for debugging
    // static $cache = [];
    // $cacheKey = md5($endpoint . '|' . $method . '|' . (is_array($data) || is_object($data) ? json_encode($data) : strval($data)));
    // if (isset($cache[$cacheKey])) {
    //     return $cache[$cacheKey];
    // }
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    // TEMPORARILY REMOVED PROBLEMATIC OPTIONS FOR DEBUGGING
    // curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    // curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
    // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    // curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    // curl_setopt($ch, CURLOPT_VERBOSE, false);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            if (is_array($data) && isset($data['image'])) {
                // Handle file upload - send as multipart/form-data
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                // Don't set Content-Type header for multipart/form-data
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json'
                ]);
            }
        }
    }
    
    // Include session cookie for authentication
    if (isset($_COOKIE['PHPSESSID'])) {
        curl_setopt($ch, CURLOPT_COOKIE, 'PHPSESSID=' . $_COOKIE['PHPSESSID']);
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    $curlInfo = curl_getinfo($ch);
    curl_close($ch);
    
    // Debug logging
    error_log("makeAPICall Debug - URL: $url, HTTP Code: $httpCode, CURL Error: $curlError");
    
    if ($response === false) {
        return ['error' => 'API call failed: ' . $curlError, 'debug_info' => $curlInfo];
    }
    
    if ($httpCode === 0) {
        return ['error' => 'Cannot connect to server: ' . $curlError, 'debug_info' => $curlInfo];
    }
    
    if ($httpCode >= 400) {
        return ['error' => 'HTTP Error ' . $httpCode . ': ' . $response, 'debug_info' => $curlInfo];
    }
    
    $decoded = json_decode($response, true);
    if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
        return ['error' => 'Invalid JSON response: ' . json_last_error_msg() . ' - Raw response: ' . substr($response, 0, 200)];
    }
    
    // Remove caching for debugging
    // $cache[$cacheKey] = $decoded;
    return $decoded;
}

// Helper functions for user authentication
function isUserLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
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
    // Clear all session variables
    $_SESSION = array();
    
    // Destroy the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy the session
    session_destroy();
    header('Location: index.php');
    exit();
}

// Database configuration (used for fallback when API is unavailable)
$db_host = "localhost";
$db_name = "lostfound_db";
$db_user = "root";
$db_pass = "kpet"; // Update if your MySQL password differs

// Database connection function
function getDBConnection() {
    global $db_host, $db_name, $db_user, $db_pass;

    try {
        $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }

        $conn->set_charset("utf8mb4");
        return $conn;
    } catch (Exception $e) {
        return null;
    }
}
?>
