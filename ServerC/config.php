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
// Change these when deploying to different computers:

// ServerA Configuration (User Management)
$server_a_host = "localhost";  // Change to actual IP when on different computer
$server_a_db = "lostfound_db";
$server_a_user = "root";
$server_a_pass = "kpet";

// ServerB Configuration (Item Management)  
$server_b_host = "localhost";  // Change to actual IP when on different computer
$server_b_db = "lostfound_db";
$server_b_user = "root";
$server_b_pass = "kpet";

// ServerC Configuration (Frontend)
$server_c_host = "localhost";  // Change to actual IP when on different computer
$server_c_db = "lostfound_db";
$server_c_user = "root";
$server_c_pass = "kpet";

// ============================================
// DATABASE CONNECTION FUNCTIONS
// ============================================

// Connect to ServerA database (Users)
function connectServerA() {
    global $server_a_host, $server_a_db, $server_a_user, $server_a_pass;
    
    $conn = mysqli_connect($server_a_host, $server_a_user, $server_a_pass, $server_a_db);
    
    if (!$conn) {
        die("ServerA connection failed: " . mysqli_connect_error());
    }
    
    return $conn;
}

// Connect to ServerB database (Items)
function connectServerB() {
    global $server_b_host, $server_b_db, $server_b_user, $server_b_pass;
    
    $conn = mysqli_connect($server_b_host, $server_b_user, $server_b_pass, $server_b_db);
    
    if (!$conn) {
        die("ServerB connection failed: " . mysqli_connect_error());
    }
    
    return $conn;
}

// Connect to ServerC database (Frontend)
function connectServerC() {
    global $server_c_host, $server_c_db, $server_c_user, $server_c_pass;
    
    $conn = mysqli_connect($server_c_host, $server_c_user, $server_c_pass, $server_c_db);
    
    if (!$conn) {
        die("ServerC connection failed: " . mysqli_connect_error());
    }
    
    return $conn;
}

// General connection (defaults to ServerC)
function connectDB() {
    return connectServerC();
}

// ============================================
// API REQUEST FUNCTIONS
// ============================================

// Simple function to make API calls to other servers
function makeAPIRequest($url, $data = [], $method = 'POST') {
    $ch = curl_init($url);
    
    if ($method == 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    } elseif ($method == 'GET' && !empty($data)) {
        $url .= '?' . http_build_query($data);
        curl_setopt($ch, CURLOPT_URL, $url);
    }
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return ['success' => false, 'message' => 'Connection error: ' . $error];
    }
    
    return $response;
}

// API URLs
define('SERVERA_URL', 'http://localhost/Lostnfound/ServerA/api');
define('SERVERB_URL', 'http://localhost/Lostnfound/ServerB/api');

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
