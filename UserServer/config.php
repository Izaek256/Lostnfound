<?php
/**
 * UserServer Configuration
 * Handles user operations and connects to ItemsServer for item-related tasks
 */

require_once __DIR__ . '/deployment_config.php';
session_start();

// Database credentials
$db_host = DB_HOST;
$db_name = DB_NAME;
$db_user = DB_USER;
$db_pass = DB_PASS;

// ItemsServer API URL
if (!defined('ITEMSSERVER_API_URL')) {
    define('ITEMSSERVER_API_URL', ITEMSSERVER_API_URL);
}

// Database connection
function connectDB() {
    global $db_host, $db_name, $db_user, $db_pass;
    
    $conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
    
    if (!$conn) {
        die("Database connection failed: " . mysqli_connect_error());
    }
    
    return $conn;
}

function connectServerA() {
    return connectDB();
}

function connectServerB() {
    return connectDB();
}

function connectServerC() {
    return connectDB();
}

// User session helpers
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

// API request handler with retry logic
function makeAPIRequest($url, $data = [], $method = 'POST', $options = []) {
    // Default options
    $retry_count = $options['retry_count'] ?? 3;
    $retry_delay = $options['retry_delay'] ?? 1; // seconds
    $timeout = $options['timeout'] ?? 30;
    $connect_timeout = $options['connect_timeout'] ?? 10;
    $return_json = $options['return_json'] ?? false;
    $verify_ssl = $options['verify_ssl'] ?? false;
    
    $attempt = 0;
    $last_error = null;
    
    while ($attempt < $retry_count) {
        $attempt++;
        
        try {
            $ch = curl_init();
            
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                throw new Exception("Invalid URL format: $url");
            }
            
            if ($method === 'POST') {
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, true);
                
                $is_json = $options['send_json'] ?? false;
                if ($is_json) {
                    $post_data = json_encode($data);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        'Content-Type: application/json',
                        'Accept: application/json',
                        'User-Agent: LostFound-UserServer/2.0'
                    ]);
                } else {
                    $post_data = http_build_query($data);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        'Content-Type: application/x-www-form-urlencoded',
                        'Accept: */*',
                        'User-Agent: LostFound-UserServer/2.0'
                    ]);
                }
                curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
                
            } elseif ($method === 'GET') {
                if (!empty($data)) {
                    $url .= '?' . http_build_query($data);
                }
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_HTTPGET, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Accept: */*',
                    'User-Agent: LostFound-UserServer/2.0'
                ]);
            }
            
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $connect_timeout);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
            
            if (!$verify_ssl) {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            }
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_error = curl_error($ch);
            $curl_errno = curl_errno($ch);
            curl_close($ch);
            
            if ($curl_errno !== 0) {
                throw new Exception("cURL error ($curl_errno): $curl_error");
            }
            
            if (empty($response)) {
                throw new Exception("Empty response from server (HTTP $http_code)");
            }
            
            if ($http_code >= 500) {
                throw new Exception("Server error (HTTP $http_code): " . substr($response, 0, 100));
            } elseif ($http_code >= 400) {
                throw new Exception("Client error (HTTP $http_code): " . substr($response, 0, 100));
            } elseif ($http_code == 0) {
                throw new Exception("No HTTP response received. Server may be unreachable.");
            }
            
            error_log("[APIRequest] Success: $method $url | HTTP $http_code");
            
            if ($return_json && (strpos(curl_getinfo($ch, CURLINFO_CONTENT_TYPE), 'application/json') !== false || $options['force_json'] ?? false)) {
                $decoded = json_decode($response, true);
                if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
                    error_log("[APIRequest] Warning: Failed to parse JSON. Returning raw response.");
                    return $response;
                }
                return $decoded;
            }
            
            return $response;
            
        } catch (Exception $e) {
            $last_error = $e->getMessage();
            error_log("[APIRequest] Attempt $attempt/$retry_count failed: $last_error");
            
            if (isset($http_code) && $http_code >= 400 && $http_code < 500) {
                break;
            }
            
            if ($attempt < $retry_count) {
                $wait_time = $retry_delay * $attempt;
                error_log("[APIRequest] Waiting {$wait_time}s before retry...");
                sleep($wait_time);
            }
        }
    }
    
    $error_message = $last_error ?? "Unknown error after $retry_count attempts";
    error_log("[APIRequest] Final error for $method $url: $error_message");
    
    if ($return_json) {
        return [
            'success' => false,
            'error' => $error_message,
            'url' => $url,
            'method' => $method
        ];
    }
    
    return "error|$error_message";
}

// User session helpers
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

// API response helpers
function sendJSONResponse($data, $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    echo json_encode($data);
    exit();
}

function setCORSHeaders() {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    header('Access-Control-Allow-Credentials: false');
    header('Access-Control-Max-Age: 86400');
    
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
        http_response_code(200);
        exit();
    }
}
?>