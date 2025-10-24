<?php
/**
 * Server C - Frontend Server Configuration
 * 
 * This server handles the client frontend interface
 */

// Server A configuration (User Management Server)
$server_a_ip = "localhost";
$server_a_port = "8080";
$server_a_url = "http://localhost:8080";

// Server B configuration (Item Management Server)
$server_b_ip = "localhost";
$server_b_port = "8081";
$server_b_url = "http://localhost:8081";

// Server C configuration (Frontend Server)
$server_c_ip = "localhost";
$server_c_port = "8082";

// API endpoints
$api_endpoints = [
    // User Management APIs (Server A)
    'verify_user' => "$server_a_url/api/verify_user.php",
    'register_user' => "$server_a_url/api/register_user.php",
    'admin_login' => "$server_a_url/admin_login.php",
    'session_status' => "$server_a_url/api/session_status.php",
    
    // Item Management APIs (Server B)
    'get_items' => "$server_b_url/api/get_items.php",
    'add_item' => "$server_b_url/api/add_item.php",
    'update_item' => "$server_b_url/api/update_item.php",
    'delete_item' => "$server_b_url/api/delete_item.php"
];

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// API call helper function
function makeAPICall($endpoint, $data = null, $method = 'GET') {
    global $api_endpoints;
    
    if (!isset($api_endpoints[$endpoint])) {
        return ['error' => 'Invalid API endpoint'];
    }
    
    $url = $api_endpoints[$endpoint];
    static $cache = [];
    $cacheKey = md5($endpoint . '|' . $method . '|' . (is_array($data) || is_object($data) ? json_encode($data) : strval($data)));
    if (isset($cache[$cacheKey])) {
        return $cache[$cacheKey];
    }
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
    curl_setopt($ch, CURLOPT_TIMEOUT, 6);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            if (is_array($data) && isset($data['image'])) {
                // Handle file upload
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
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
    curl_close($ch);
    
    if ($response === false) {
        return ['error' => 'API call failed'];
    }
    
    $decoded = json_decode($response, true);
    if ($decoded === null) {
        return ['error' => 'Invalid API response'];
    }
    
    $cache[$cacheKey] = $decoded;
    return $decoded;
}

// Helper functions for user authentication
function isUserLoggedIn() {
    $result = makeAPICall('session_status');
    return isset($result['success']) && $result['success'] === true;
}

function getCurrentUserId() {
    $result = makeAPICall('session_status');
    if (isset($result['user_id'])) {
        return $result['user_id'];
    }
    return null;
}

function getCurrentUsername() {
    $result = makeAPICall('session_status');
    if (isset($result['username'])) {
        return $result['username'];
    }
    return null;
}

function getCurrentUserEmail() {
    $result = makeAPICall('session_status');
    if (isset($result['email'])) {
        return $result['email'];
    }
    return null;
}

function isCurrentUserAdmin() {
    $result = makeAPICall('session_status');
    return isset($result['is_admin']) && $result['is_admin'] == 1;
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

// Database configuration (used for fallback when API is unavailable)
$db_host = "localhost";
$db_name = "lostfound_db";
$db_user = "root";
$db_pass = "kpet"; // Update if your MySQL password differs

// Database connection function (mirrors Server B)
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
