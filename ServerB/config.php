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
// ServerB hosts the CENTRALIZED DATABASE
// All other servers connect to this database

// Database Configuration (Hosted on ServerB)
$db_host = "localhost";  // This is localhost since DB is on this server
$db_name = "lostfound_db";
$db_user = "root";
$db_pass = "isaacK@12345";

// Note: When deploying to separate computers:
// - Keep this as "localhost" on ServerB (where database is)
// - Other servers will use ServerB's IP address to connect here

// ============================================
// DATABASE CONNECTION FUNCTIONS
// ============================================
// This server (ServerB) hosts the centralized database

// Main database connection - local database on this server
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
// API HELPER FUNCTIONS
// ============================================

// Send JSON response with proper headers
function sendJSONResponse($data, $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    echo json_encode($data);
    exit();
}

// Set CORS headers for API endpoints - Enhanced for cross-server communication
function setCORSHeaders() {
    // Allow requests from any origin (adjust for production security)
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    header('Access-Control-Allow-Credentials: false');
    header('Access-Control-Max-Age: 86400'); // Cache preflight for 24 hours
    
    // Handle preflight OPTIONS request
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
        http_response_code(200);
        exit();
    }
}
?>