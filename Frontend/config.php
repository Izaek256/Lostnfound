<?php
/**
 * Frontend Configuration
 * Communicates with ItemsServer and UserServer via API calls only
 */

require_once __DIR__ . '/deployment_config.php';
session_start();

// Frontend is a client and does not connect to database directly
function connectDB() {
    die("ERROR: Frontend cannot connect directly to the database. Use API calls.");
}

function connectItemsServer() {
    die("ERROR: Frontend cannot connect directly to the database.");
}

function connectUserServer() {
    die("ERROR: Frontend cannot connect directly to the database.");
}

function connectFrontend() {
    die("ERROR: Frontend cannot connect directly to the database.");
}

// Image handling helpers
function getImageUrl($filename) {
    if (empty($filename)) return '';
    
    $localPath = UPLOADS_PATH . $filename;
    if (file_exists($localPath)) {
        return UPLOADS_URL . $filename;
    }
    
    return UPLOADS_HTTP_URL . $filename;
}

function getImagePath($filename) {
    if (empty($filename)) return '';
    return UPLOADS_PATH . $filename;
}

function imageExists($filename) {
    if (empty($filename)) return false;
    return file_exists(getImagePath($filename));
}

// API request handler with retry logic and timeout management
function makeAPIRequest($url, $data = [], $method = 'POST', $options = []) {
    $retry_count = $options['retry_count'] ?? 3;
    $retry_delay = $options['retry_delay'] ?? 1;
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
            
            // Validate URL
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                throw new Exception("Invalid URL format: $url");
            }
            
            // Add a custom header to identify API requests from frontend
            $headers = [
                'User-Agent: LostFound-Frontend/2.0',
                'X-Requested-With: XMLHttpRequest'
            ];
            
            if ($method === 'POST') {
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, true);
                
                $is_json = $options['send_json'] ?? false;
                if ($is_json) {
                    $post_data = json_encode($data);
                    $headers[] = 'Content-Type: application/json';
                    $headers[] = 'Accept: application/json';
                } else {
                    $post_data = http_build_query($data);
                    $headers[] = 'Content-Type: application/x-www-form-urlencoded';
                    $headers[] = 'Accept: application/json';
                }
                curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
                
            } elseif ($method === 'GET') {
                if (!empty($data)) {
                    $url .= '?' . http_build_query($data);
                }
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_HTTPGET, true);
                $headers[] = 'Accept: application/json';
                
            } elseif ($method === 'DELETE') {
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                if (!empty($data)) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
                }
                $headers[] = 'Content-Type: application/x-www-form-urlencoded';
                $headers[] = 'Accept: application/json';
                
            } elseif ($method === 'PUT') {
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                $post_data = http_build_query($data);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
                $headers[] = 'Content-Type: application/x-www-form-urlencoded';
                $headers[] = 'Accept: application/json';
            }
            
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
            curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate, br');
            curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
            
            if (!$verify_ssl) {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            }
            
            $start_time = microtime(true);
            $response = curl_exec($ch);
            $elapsed_time = round((microtime(true) - $start_time) * 1000, 2);
            
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_error = curl_error($ch);
            $curl_errno = curl_errno($ch);
            $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            curl_close($ch);
            
            if ($curl_errno !== 0) {
                $error_msg = "cURL error ($curl_errno): $curl_error";
                throw new Exception($error_msg);
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
            
            error_log("[APIRequest] Success: $method $url | HTTP $http_code | {$elapsed_time}ms");
            
            if ($return_json) {
                // Check if we should force JSON parsing regardless of content type
                $should_parse_json = ($options['force_json'] ?? false) || 
                                   (strpos($content_type, 'application/json') !== false);
                                    
                if ($should_parse_json) {
                    $decoded = json_decode($response, true);
                    if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
                        error_log("[APIRequest] Warning: Failed to parse JSON. Returning structured error.");
                        return [
                            'success' => false,
                            'error' => 'Failed to parse JSON response',
                            'raw_response' => $response
                        ];
                    }
                    return $decoded;
                }
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
    
    // Return structured error instead of raw error string
    return [
        'success' => false,
        'error' => $error_message
    ];
}

// API URLs
define('ITEMSSERVER_URL', ITEMSSERVER_API_URL);
define('USERSERVER_URL', USERSERVER_API_URL);

// Upload paths
define('UPLOADS_PATH', __DIR__ . '/../ItemsServer/uploads/');
define('UPLOADS_URL', '../ItemsServer/uploads/');
define('UPLOADS_HTTP_URL', UPLOADS_BASE_URL);

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

// Server connectivity testing
function testServerConnection($server_url, $timeout = 5) {
    if (empty($server_url)) {
        return [
            'success' => false,
            'error' => 'Empty server URL provided',
            'response_time' => 0
        ];
    }
    
    try {
        $health_url = $server_url . '/health.php';
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $health_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, min($timeout, 3));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        // Add headers to identify this as an API request
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'User-Agent: LostFound-Frontend/2.0',
            'X-Requested-With: XMLHttpRequest'
        ]);
        
        $start_time = microtime(true);
        $response = curl_exec($ch);
        $response_time = round((microtime(true) - $start_time) * 1000, 2);
        
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return [
                'success' => false,
                'error' => $error,
                'http_code' => $http_code,
                'response_time' => $response_time
            ];
        }
        
        return [
            'success' => ($http_code === 200),
            'http_code' => $http_code,
            'error' => $http_code !== 200 ? "HTTP $http_code" : '',
            'response_time' => $response_time,
            'response' => $response
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage(),
            'response_time' => 0
        ];
    }
}

function getServerStatus($server_url, $server_name, $timeout = 5) {
    $result = testServerConnection($server_url, $timeout);
    
    return [
        'name' => $server_name,
        'url' => $server_url,
        'online' => $result['success'],
        'response_time' => $result['response_time'],
        'http_code' => $result['http_code'] ?? 0,
        'error' => $result['error'] ?? '',
        'response' => $result['response'] ?? ''
    ];
}

function areAllServersOnline() {
    $itemsserver_check = testServerConnection(ITEMSSERVER_URL, 3);
    $userserver_check = testServerConnection(USERSERVER_URL, 3);
    
    return $itemsserver_check['success'] && $userserver_check['success'];
}
?>