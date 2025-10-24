<?php
/**
 * Server A - Main Backend Configuration
 * 
 * This file contains database configuration for Server A
 * which hosts the main MySQL database and APIs
 */

// Database configuration for Server A (Main Server)
$db_host = "localhost";
$db_name = "lostfound_db";
$db_user = "root";
$db_pass = "kpet"; // Change this to your MySQL password

// Server A configuration
$server_a_ip = "192.168.1.10"; // Change this to your Server A IP
$server_a_port = "80";

// CORS configuration - Allow requests from Server B and clients
$allowed_origins = [
    "http://192.168.1.11", // Server B IP
    "http://localhost",
    "http://127.0.0.1"
];

// Enable CORS for cross-origin requests
function enableCORS() {
    global $allowed_origins;
    
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    
    if (in_array($origin, $allowed_origins)) {
        header("Access-Control-Allow-Origin: $origin");
    }
    
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
    header("Access-Control-Allow-Credentials: true");
    
    // Handle preflight requests
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
        http_response_code(200);
        exit();
    }
}

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
        http_response_code(500);
        echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
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

// Enable CORS for all API requests
enableCORS();
?>
