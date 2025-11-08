<?php
/**
 * CENTRAL CONTROL SYSTEM - Master Control for All Server Connectivity
 * 
 * Single point of control for:
 * - All server connections (ServerA, ServerB, ServerC)
 * - Database connections and queries
 * - API requests and cross-server communication
 * - Connection testing and monitoring
 * - Unified error handling and logging
 * 
 * USAGE:
 * 1. Replace all makeAPIRequest() calls with: $cc->api()->get/post/put/delete()
 * 2. Replace all database connections with: $cc->db()->query()
 * 3. Test servers with: $cc->health()->testAll() or $cc->health()->test('ServerA')
 * 4. Check server status anywhere: $cc->status()->getAll()
 */

class CentralControl {
    
    private $deployment_config = null;
    private $db_connections = [];
    private $api_client = null;
    private $servers = ['ServerA', 'ServerB', 'ServerC'];
    private $log_file = null;
    private $debug_mode = false;
    
    // ============================================
    // INITIALIZATION
    // ============================================
    
    public function __construct($debug = false) {
        $this->debug_mode = $debug;
        $this->log_file = sys_get_temp_dir() . '/lostfound_central_control.log';
        
        // Load deployment configuration
        $this->loadDeploymentConfig();
        
        // Initialize API client
        $this->api_client = new CentralAPIClient($this->getServerURL('ServerA'), [
            'timeout' => 30,
            'connect_timeout' => 10,
            'max_retries' => 3,
            'retry_delay' => 1,
            'verify_ssl' => false
        ]);
    }
    
    /**
     * Load deployment configuration from any of the server configs
     */
    private function loadDeploymentConfig() {
        $config_files = [
            __DIR__ . '/ServerA/deployment_config.php',
            __DIR__ . '/ServerB/deployment_config.php',
            __DIR__ . '/ServerC/deployment_config.php',
        ];
        
        foreach ($config_files as $file) {
            if (file_exists($file)) {
                require_once $file;
                $this->log("Loaded deployment config from: $file");
                break;
            }
        }
        
        // Verify required constants are defined
        if (!defined('DEPLOYMENT_MODE')) {
            define('DEPLOYMENT_MODE', 'local');
            $this->log("Warning: DEPLOYMENT_MODE not defined, using 'local'", 'warning');
        }
    }
    
    // ============================================
    // DATABASE MANAGEMENT
    // ============================================
    
    /**
     * Get or create database connection
     * $server = 'ServerA', 'ServerB', 'ServerC', or 'default'
     */
    public function db($server = 'default') {
        if ($server === 'default') {
            $server = 'ServerB'; // Database is on ServerB
        }
        
        // Return existing connection if available
        if (isset($this->db_connections[$server]) && $this->db_connections[$server]) {
            return new DatabaseManager($this->db_connections[$server], $this);
        }
        
        // Create new connection
        $conn = $this->createDatabaseConnection($server);
        if ($conn) {
            $this->db_connections[$server] = $conn;
            return new DatabaseManager($conn, $this);
        }
        
        $this->log("Failed to connect to database on $server", 'error');
        return null;
    }
    
    /**
     * Create a database connection for a specific server
     */
    private function createDatabaseConnection($server) {
        $db_host = defined('DB_HOST') ? DB_HOST : 'localhost';
        $db_name = defined('DB_NAME') ? DB_NAME : 'lostfound_db';
        $db_user = defined('DB_USER') ? DB_USER : 'root';
        $db_pass = defined('DB_PASS') ? DB_PASS : 'Isaac@1234';
        
        // If connecting to a remote server, adjust host
        if ($server !== 'ServerB' && $server !== 'default') {
            $server_ip = constant(strtoupper($server) . '_IP');
            $db_host = $server_ip;
        }
        
        try {
            $conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
            
            if (!$conn) {
                $this->log("Database connection failed for $server: " . mysqli_connect_error(), 'error');
                return null;
            }
            
            // Set charset
            mysqli_set_charset($conn, 'utf8mb4');
            $this->log("Database connected on $server ($db_host)");
            
            return $conn;
        } catch (Exception $e) {
            $this->log("Exception connecting to database: " . $e->getMessage(), 'error');
            return null;
        }
    }
    
    /**
     * Close all database connections
     */
    public function closeAllConnections() {
        foreach ($this->db_connections as $server => $conn) {
            if ($conn) {
                /** @var mysqli $conn */
                mysqli_close($conn);
                unset($this->db_connections[$server]);
                $this->log("Closed database connection: $server");
            }
        }
    }
    
    /**
     * Destructor - close connections on exit
     */
    public function __destruct() {
        $this->closeAllConnections();
    }
    
    // ============================================
    // API MANAGEMENT
    // ============================================
    
    /**
     * Get API client for making cross-server requests
     * Auto-detects which server's API to use based on context
     */
    public function api($server = 'ServerA') {
        $url = $this->getServerURL($server);
        
        return new CentralAPIClient($url, [
            'timeout' => 30,
            'connect_timeout' => 10,
            'max_retries' => 3,
            'retry_delay' => 1,
            'verify_ssl' => false
        ]);
    }
    
    /**
     * Get full API URL for a server
     */
    public function getServerURL($server = 'ServerA') {
        $constant = strtoupper($server) . '_API_URL';
        
        if (defined($constant)) {
            return constant($constant);
        }
        
        // Fallback to manual construction
        $server_ip = constant(strtoupper($server) . '_IP');
        $use_https = defined('USE_HTTPS') ? (bool)constant('USE_HTTPS') : false;
        $protocol = $use_https ? 'https' : 'http';
        $base_path = defined('BASE_PATH') ? (string)constant('BASE_PATH') : '/Lostnfound';
        
        return "$protocol://$server_ip$base_path/$server/api";
    }
    
    // ============================================
    // HEALTH CHECKING & MONITORING
    // ============================================
    
    /**
     * Health check object for testing server connectivity
     */
    public function health() {
        return new HealthChecker($this);
    }
    
    /**
     * Server status object for monitoring
     */
    public function status() {
        return new StatusMonitor($this);
    }
    
    // ============================================
    // CONFIGURATION HELPERS
    // ============================================
    
    /**
     * Get current deployment mode
     */
    public function getDeploymentMode() {
        return defined('DEPLOYMENT_MODE') ? DEPLOYMENT_MODE : 'local';
    }
    
    /**
     * Get deployment info
     */
    public function getDeploymentInfo() {
        return [
            'mode' => $this->getDeploymentMode(),
            'servera_ip' => defined('SERVERA_IP') ? SERVERA_IP : 'unknown',
            'serverb_ip' => defined('SERVERB_IP') ? SERVERB_IP : 'unknown',
            'serverc_ip' => defined('SERVERC_IP') ? SERVERC_IP : 'unknown',
            'db_host' => defined('DB_HOST') ? DB_HOST : 'unknown',
            'db_name' => defined('DB_NAME') ? DB_NAME : 'unknown',
            'is_local' => $this->getDeploymentMode() === 'local',
            'is_production' => $this->getDeploymentMode() === 'production'
        ];
    }
    
    /**
     * Check if in production mode
     */
    public function isProduction() {
        return $this->getDeploymentMode() === 'production';
    }
    
    /**
     * Check if in local mode
     */
    public function isLocal() {
        return $this->getDeploymentMode() === 'local';
    }
    
    // ============================================
    // LOGGING
    // ============================================
    
    /**
     * Log message to central log
     */
    public function log($message, $level = 'info') {
        $timestamp = date('Y-m-d H:i:s');
        $log_entry = "[$timestamp] [$level] $message\n";
        
        if ($this->debug_mode || $level !== 'debug') {
            error_log($log_entry, 3, $this->log_file);
        }
        
        if ($this->debug_mode) {
            echo $log_entry;
        }
    }
    
    /**
     * Get central log file path
     */
    public function getLogFile() {
        return $this->log_file;
    }
}

// ============================================
// DATABASE MANAGER CLASS
// ============================================

class DatabaseManager {
    
    private $connection;
    private $central_control;
    
    public function __construct($connection, $central_control) {
        $this->connection = $connection;
        $this->central_control = $central_control;
    }
    
    /**
     * Execute a query
     */
    public function query($sql, $params = []) {
        if (!$this->connection) {
            $this->central_control->log("Database connection not available", 'error');
            return null;
        }
        
        try {
            // Prepare statement for security
            $stmt = $this->connection->prepare($sql);
            
            if (!$stmt) {
                $this->central_control->log("Query prepare failed: " . $this->connection->error, 'error');
                return null;
            }
            
            // Bind parameters if provided
            if (!empty($params)) {
                $types = '';
                $bind_params = [];
                
                foreach ($params as $param) {
                    if (is_int($param)) {
                        $types .= 'i';
                    } elseif (is_float($param)) {
                        $types .= 'd';
                    } else {
                        $types .= 's';
                    }
                    $bind_params[] = $param;
                }
                
                // Use call_user_func_array for dynamic binding
                $bind_values = array_merge([$types], $bind_params);
                call_user_func_array([$stmt, 'bind_param'], $bind_values);
            }
            
            // Execute
            if (!$stmt->execute()) {
                $this->central_control->log("Query execution failed: " . $stmt->error, 'error');
                $stmt->close();
                return null;
            }
            
            // Get results
            $result = $stmt->get_result();
            $stmt->close();
            
            return $result;
            
        } catch (Exception $e) {
            $this->central_control->log("Database query exception: " . $e->getMessage(), 'error');
            return null;
        }
    }
    
    /**
     * Get single row
     */
    public function getRow($sql, $params = []) {
        $result = $this->query($sql, $params);
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        return null;
    }
    
    /**
     * Get all rows
     */
    public function getRows($sql, $params = []) {
        $result = $this->query($sql, $params);
        if (!$result) return [];
        
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        return $rows;
    }
    
    /**
     * Insert record
     */
    public function insert($table, $data) {
        $columns = array_keys($data);
        $values = array_values($data);
        $placeholders = implode(',', array_fill(0, count($columns), '?'));
        
        $sql = "INSERT INTO $table (" . implode(',', $columns) . ") VALUES ($placeholders)";
        
        $result = $this->query($sql, $values);
        
        if ($result !== null) {
            return $this->connection->insert_id;
        }
        return null;
    }
    
    /**
     * Update record
     */
    public function update($table, $data, $where_sql, $where_params = []) {
        $updates = [];
        $values = [];
        
        foreach ($data as $key => $value) {
            $updates[] = "$key = ?";
            $values[] = $value;
        }
        
        $sql = "UPDATE $table SET " . implode(', ', $updates) . " WHERE $where_sql";
        $all_params = array_merge($values, $where_params);
        
        return $this->query($sql, $all_params) !== null;
    }
    
    /**
     * Delete record
     */
    public function delete($table, $where_sql, $where_params = []) {
        $sql = "DELETE FROM $table WHERE $where_sql";
        return $this->query($sql, $where_params) !== null;
    }
    
    /**
     * Count records
     */
    public function count($table, $where_sql = '', $where_params = []) {
        $sql = "SELECT COUNT(*) as count FROM $table";
        
        if (!empty($where_sql)) {
            $sql .= " WHERE $where_sql";
        }
        
        $row = $this->getRow($sql, $where_params);
        return $row ? $row['count'] : 0;
    }
    
    /**
     * Check if table exists
     */
    public function tableExists($table) {
        $sql = "SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?";
        $result = $this->getRow($sql, [$table]);
        return $result !== null;
    }
    
    /**
     * Get connection object
     */
    public function getConnection() {
        return $this->connection;
    }
}

// ============================================
// API CLIENT CLASS
// ============================================

class CentralAPIClient {
    
    private $base_url;
    private $timeout = 30;
    private $connect_timeout = 10;
    private $max_retries = 3;
    private $retry_delay = 1;
    private $verify_ssl = false;
    private $last_http_code = null;
    private $last_error = null;
    private $last_response = null;
    
    public function __construct($base_url, $options = []) {
        $this->base_url = rtrim($base_url, '/');
        
        if (isset($options['timeout'])) $this->timeout = $options['timeout'];
        if (isset($options['connect_timeout'])) $this->connect_timeout = $options['connect_timeout'];
        if (isset($options['max_retries'])) $this->max_retries = $options['max_retries'];
        if (isset($options['retry_delay'])) $this->retry_delay = $options['retry_delay'];
        if (isset($options['verify_ssl'])) $this->verify_ssl = $options['verify_ssl'];
    }
    
    /**
     * Make GET request
     */
    public function get($endpoint, $params = [], $return_json = false) {
        return $this->request('GET', $endpoint, $params, $return_json);
    }
    
    /**
     * Make POST request
     */
    public function post($endpoint, $data = [], $return_json = false) {
        return $this->request('POST', $endpoint, $data, $return_json);
    }
    
    /**
     * Make PUT request
     */
    public function put($endpoint, $data = [], $return_json = false) {
        return $this->request('PUT', $endpoint, $data, $return_json);
    }
    
    /**
     * Make DELETE request
     */
    public function delete($endpoint, $data = [], $return_json = false) {
        return $this->request('DELETE', $endpoint, $data, $return_json);
    }
    
    /**
     * Core request method
     */
    private function request($method, $endpoint, $data = [], $return_json = false) {
        $url = $this->base_url . '/' . ltrim($endpoint, '/');
        $attempt = 0;
        
        while ($attempt < $this->max_retries) {
            $attempt++;
            
            try {
                $response = $this->executeRequest($method, $url, $data, $return_json);
                
                if ($this->last_http_code >= 200 && $this->last_http_code < 300) {
                    return $response;
                }
                
                if ($this->last_http_code >= 400 && $this->last_http_code < 500) {
                    return $this->formatError($return_json, "HTTP {$this->last_http_code}");
                }
                
                throw new Exception("HTTP {$this->last_http_code}");
                
            } catch (Exception $e) {
                $this->last_error = $e->getMessage();
                
                if ($attempt < $this->max_retries) {
                    sleep($this->retry_delay * $attempt);
                }
            }
        }
        
        return $this->formatError($return_json, $this->last_error);
    }
    
    /**
     * Execute single HTTP request
     */
    private function executeRequest($method, $url, $data = [], $return_json = false) {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new Exception("Invalid URL: $url");
        }
        
        $ch = curl_init();
        
        // Set method-specific options
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        } elseif ($method === 'GET') {
            if (!empty($data)) {
                $url .= '?' . http_build_query($data);
            }
            curl_setopt($ch, CURLOPT_HTTPGET, true);
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
            if (!empty($data)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            }
        } elseif ($method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            if (!empty($data)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            }
        }
        
        // Standard options
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->connect_timeout);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
        
        // Headers
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
            'Accept: application/json',
            'User-Agent: LostFound-CentralControl/1.0'
        ]);
        
        // SSL options
        if (!$this->verify_ssl) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        }
        
        // Execute
        $response = curl_exec($ch);
        $this->last_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        $curl_errno = curl_errno($ch);
        curl_close($ch);
        
        // Handle errors
        if ($curl_errno !== 0) {
            throw new Exception("cURL error ($curl_errno): $curl_error");
        }
        
        if (empty($response)) {
            throw new Exception("Empty response from server");
        }
        
        $this->last_response = $response;
        
        // Parse JSON if requested
        if ($return_json) {
            $decoded = json_decode($response, true);
            if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
                return $response;
            }
            return $decoded;
        }
        
        return $response;
    }
    
    /**
     * Format error response
     */
    private function formatError($return_json, $message) {
        if ($return_json) {
            return [
                'success' => false,
                'error' => $message,
                'http_code' => $this->last_http_code
            ];
        }
        return "error|$message";
    }
    
    /**
     * Get last HTTP code
     */
    public function getLastHttpCode() {
        return $this->last_http_code;
    }
    
    /**
     * Get last error
     */
    public function getLastError() {
        return $this->last_error;
    }
    
    /**
     * Check if last request was successful
     */
    public function isSuccess() {
        return $this->last_http_code >= 200 && $this->last_http_code < 300;
    }
}

// ============================================
// HEALTH CHECKER CLASS
// ============================================

class HealthChecker {
    
    private $central_control;
    
    public function __construct($central_control) {
        $this->central_control = $central_control;
    }
    
    /**
     * Test all servers
     */
    public function testAll() {
        $results = [];
        $servers = ['ServerA', 'ServerB', 'ServerC'];
        
        foreach ($servers as $server) {
            $results[$server] = $this->test($server);
        }
        
        return $results;
    }
    
    /**
     * Test single server
     */
    public function test($server) {
        $url = $this->central_control->getServerURL($server) . '/health.php';
        
        $start_time = microtime(true);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        $response_time = round((microtime(true) - $start_time) * 1000, 2);
        curl_close($ch);
        
        $result = [
            'server' => $server,
            'url' => $url,
            'online' => ($http_code === 200 && !$curl_error),
            'http_code' => $http_code,
            'response_time' => $response_time,
            'error' => $curl_error,
            'response' => null
        ];
        
        if ($http_code === 200 && !$curl_error) {
            $result['response'] = json_decode($response, true);
        }
        
        $this->central_control->log(
            "Health check $server: " . ($result['online'] ? 'ONLINE' : 'OFFLINE') . " ({$response_time}ms)"
        );
        
        return $result;
    }
    
    /**
     * Check if all servers are online
     */
    public function allOnline() {
        $results = $this->testAll();
        foreach ($results as $result) {
            if (!$result['online']) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * Get status summary
     */
    public function getSummary() {
        $results = $this->testAll();
        $online_count = 0;
        $total = count($results);
        
        foreach ($results as $result) {
            if ($result['online']) {
                $online_count++;
            }
        }
        
        return [
            'all_online' => $online_count === $total,
            'online_count' => $online_count,
            'total_servers' => $total,
            'percentage' => ($online_count / $total) * 100,
            'details' => $results
        ];
    }
}

// ============================================
// STATUS MONITOR CLASS
// ============================================

class StatusMonitor {
    
    private $central_control;
    
    public function __construct($central_control) {
        $this->central_control = $central_control;
    }
    
    /**
     * Get all server statuses
     */
    public function getAll() {
        $health_results = $this->central_control->health()->testAll();
        $deployment_info = $this->central_control->getDeploymentInfo();
        
        $status = [
            'deployment' => $deployment_info,
            'servers' => $health_results,
            'overall_health' => $this->calculateOverallHealth($health_results),
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        return $status;
    }
    
    /**
     * Calculate overall system health
     */
    private function calculateOverallHealth($results) {
        $online_count = 0;
        $total = count($results);
        
        foreach ($results as $result) {
            if ($result['online']) {
                $online_count++;
            }
        }
        
        if ($online_count === $total) {
            return 'healthy';
        } elseif ($online_count >= ceil($total / 2)) {
            return 'degraded';
        } else {
            return 'critical';
        }
    }
}

// ============================================
// GLOBAL SINGLETON INSTANCE
// ============================================

$GLOBALS['_central_control'] = null;

/**
 * Get global CentralControl instance
 */
function getCentralControl($debug = false) {
    if ($GLOBALS['_central_control'] === null) {
        $GLOBALS['_central_control'] = new CentralControl($debug);
    }
    return $GLOBALS['_central_control'];
}

/**
 * Quick database access
 */
function cc_db($server = 'default') {
    return getCentralControl()->db($server);
}

/**
 * Quick API access
 */
function cc_api($server = 'ServerA') {
    return getCentralControl()->api($server);
}

/**
 * Quick health check
 */
function cc_health() {
    return getCentralControl()->health();
}

/**
 * Quick status monitoring
 */
function cc_status() {
    return getCentralControl()->status();
}

?>
