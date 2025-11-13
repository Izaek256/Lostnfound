<?php
/**
 * API Client Class
 * Provides object-oriented interface for API requests with retry logic
 */

class APIClient {
    
    private $base_url;
    private $timeout = 30;
    private $connect_timeout = 10;
    private $max_retries = 3;
    private $retry_delay = 1;
    private $verify_ssl = false;
    private $last_response = null;
    private $last_http_code = null;
    private $last_error = null;
    
    /**
     * Constructor
     */
    public function __construct($base_url, $options = []) {
        $this->base_url = rtrim($base_url, '/');
        
        if (isset($options['timeout'])) {
            $this->timeout = $options['timeout'];
        }
        if (isset($options['connect_timeout'])) {
            $this->connect_timeout = $options['connect_timeout'];
        }
        if (isset($options['max_retries'])) {
            $this->max_retries = $options['max_retries'];
        }
        if (isset($options['retry_delay'])) {
            $this->retry_delay = $options['retry_delay'];
        }
        if (isset($options['verify_ssl'])) {
            $this->verify_ssl = $options['verify_ssl'];
        }
    }
    
    /**
     * Make a POST request
     */
    public function post($endpoint, $data = [], $return_json = false) {
        return $this->request('POST', $endpoint, $data, $return_json);
    }
    
    /**
     * Make a GET request
     */
    public function get($endpoint, $params = [], $return_json = false) {
        return $this->request('GET', $endpoint, $params, $return_json);
    }
    
    /**
     * Make a DELETE request
     */
    public function delete($endpoint, $data = [], $return_json = false) {
        return $this->request('DELETE', $endpoint, $data, $return_json);
    }
    
    /**
     * Make a PUT request
     */
    public function put($endpoint, $data = [], $return_json = false) {
        return $this->request('PUT', $endpoint, $data, $return_json);
    }
    
    /**
     * Core request method with retry logic
     */
    private function request($method, $endpoint, $data = [], $return_json = false) {
        $url = $this->base_url . '/' . ltrim($endpoint, '/');
        $attempt = 0;
        
        while ($attempt < $this->max_retries) {
            $attempt++;
            
            try {
                $response = $this->executeRequest($method, $url, $data, $return_json);
                
                // Success - return response
                if ($this->last_http_code >= 200 && $this->last_http_code < 300) {
                    $msg = "Success: $method $endpoint (HTTP " . $this->last_http_code . ")";
                    $this->log($msg);
                    return $response;
                }
                
                // 4xx errors - don't retry
                if ($this->last_http_code >= 400 && $this->last_http_code < 500) {
                    $this->last_error = "Client error: HTTP " . $this->last_http_code;
                    $this->log($this->last_error);
                    return $this->formatError($return_json, $this->last_error);
                }
                
                // 5xx errors and connection issues - retry
                throw new Exception("HTTP " . $this->last_http_code);
                
            } catch (Exception $e) {
                $this->last_error = $e->getMessage();
                $this->log("Attempt $attempt/" . $this->max_retries . " failed: " . $this->last_error);
                
                // Wait before retrying
                if ($attempt < $this->max_retries) {
                    $wait_time = $this->retry_delay * $attempt;
                    $this->log("Waiting " . $wait_time . "s before retry...");
                    sleep($wait_time);
                }
            }
        }
        
        // All retries exhausted
        $this->log("Failed after " . $this->max_retries . " attempts: $method $endpoint");
        return $this->formatError($return_json, $this->last_error);
    }
    
    /**
     * Execute a single HTTP request
     */
    private function executeRequest($method, $url, $data = [], $return_json = false) {
        // Validate URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new Exception("Invalid URL: $url");
        }
        
        $ch = curl_init();
        
        // Configure request based on method
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
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate, br');
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        
        // Headers
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
            'Accept: application/json, text/plain, */*',
            'User-Agent: LostFound-APIClient/2.0'
        ]);
        
        // SSL options
        if (!$this->verify_ssl) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        }
        
        // Execute request
        $response = curl_exec($ch);
        $this->last_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $curl_error = curl_error($ch);
        $curl_errno = curl_errno($ch);
        curl_close($ch);
        
        // Handle errors
        if ($curl_errno !== 0) {
            throw new Exception("cURL error (" . $curl_errno . "): " . $curl_error);
        }
        
        if (empty($response)) {
            throw new Exception("Empty response from server");
        }
        
        $this->last_response = $response;
        
        // Handle JSON response if requested
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
    private function formatError($return_json, $error_message) {
        if ($return_json) {
            return [
                'success' => false,
                'error' => $error_message,
                'http_code' => $this->last_http_code,
                'url' => $this->base_url
            ];
        }
        return "error|" . $error_message;
    }
    
    /**
     * Log a message
     */
    private function log($message) {
        error_log("[APIClient] " . $message);
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
     * Get last response (raw)
     */
    public function getLastResponse() {
        return $this->last_response;
    }
    
    /**
     * Check if last request was successful
     */
    public function isSuccess() {
        return $this->last_http_code >= 200 && $this->last_http_code < 300;
    }
    
    /**
     * Test server connectivity
     */
    public function testConnection() {
        try {
            $response = $this->executeRequest('GET', $this->base_url . '/health.php', [], true);
            return $this->isSuccess();
        } catch (Exception $e) {
            return false;
        }
    }
}
?>
