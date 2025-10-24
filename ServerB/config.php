<?php
/**
 * Server B - Item Management Server Configuration
 * 
 * This server handles all item-related operations
 */

// Database configuration
$db_host = "localhost";
$db_name = "lostfound_db";
$db_user = "root";
$db_pass = "kpet"; // Change this to your MySQL password

// Server B configuration
$server_b_ip = "localhost";
$server_b_port = "8081";

// Server A configuration (User Management Server)
$server_a_ip = "localhost";
$server_a_port = "8080";
$server_a_url = "http://localhost:8080";

// Server C configuration (Frontend Server)
$server_c_ip = "localhost";
$server_c_port = "8082";
$server_c_url = "http://localhost:8082";

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

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

// API call helper function to Server A (User Management)
function makeUserAPICall($endpoint, $data = null, $method = 'GET') {
    global $server_a_url;
    
    $url = "$server_a_url/api/$endpoint";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json'
            ]);
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

// Helper functions for user authentication (calls Server A)
function isUserLoggedIn() {
    $result = makeUserAPICall('verify_user.php');
    return isset($result['success']) && $result['success'] === true;
}

function getCurrentUserId() {
    $result = makeUserAPICall('verify_user.php');
    if (isset($result['user_id'])) {
        return $result['user_id'];
    }
    return null;
}

function getCurrentUsername() {
    $result = makeUserAPICall('verify_user.php');
    if (isset($result['username'])) {
        return $result['username'];
    }
    return null;
}

function isCurrentUserAdmin() {
    $result = makeUserAPICall('verify_user.php');
    return isset($result['is_admin']) && $result['is_admin'] == 1;
}

function requireUser() {
    if (!isUserLoggedIn()) {
        header('Location: ' . $GLOBALS['server_c_url'] . '/user_login.php');
        exit();
    }
}

// JSON response helper
function sendJSONResponse($data, $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}
?>