<?php
/**
 * Server B - Secondary Server Configuration
 * 
 * This file contains configuration for Server B
 * which connects to Server A's database and APIs
 */

// Server A configuration (Main Backend Server)
$server_a_ip = "192.168.1.10"; // Change this to your Server A IP
$server_a_port = "80";
$server_a_url = "http://$server_a_ip:$server_a_port";

// Database configuration (points to Server A's database)
$db_host = $server_a_ip; // Connect to Server A's database
$db_name = "lostfound_db";
$db_user = "root";
$db_pass = "kpet"; // Change this to match Server A's password

// Server B configuration
$server_b_ip = "192.168.1.11"; // Change this to your Server B IP
$server_b_port = "80";

// API endpoints on Server A
$api_endpoints = [
    'verify_user' => "$server_a_url/api/verify_user.php",
    'register_user' => "$server_a_url/api/register_user.php",
    'get_items' => "$server_a_url/api/get_items.php",
    'add_item' => "$server_a_url/api/add_item.php",
    'update_item' => "$server_a_url/api/update_item.php",
    'delete_item' => "$server_a_url/api/delete_item.php",
    'get_user_items' => "$server_a_url/api/get_user_items.php"
];

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Database connection function (connects to Server A)
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
        // If direct DB connection fails, we'll use API calls instead
        return null;
    }
}

// API call helper function
function makeAPICall($endpoint, $data = null, $method = 'GET') {
    global $api_endpoints;
    
    if (!isset($api_endpoints[$endpoint])) {
        return ['error' => 'Invalid API endpoint'];
    }
    
    $url = $api_endpoints[$endpoint];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
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
    
    return $decoded;
}

// Helper functions for user authentication
function isUserLoggedIn() {
    return isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0;
}

function getCurrentUserId() {
    if (isUserLoggedIn()) {
        return $_SESSION['user_id'];
    }
    return null;
}

function getCurrentUsername() {
    if (isset($_SESSION['username'])) {
        return $_SESSION['username'];
    }
    return null;
}

function getCurrentUserEmail() {
    if (isset($_SESSION['user_email'])) {
        return $_SESSION['user_email'];
    }
    return null;
}

function isCurrentUserAdmin() {
    return isUserLoggedIn() && isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
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
