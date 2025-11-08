<?php
/**
 * Deployment Configuration for Multi-Server Setup
 * 
 * INSTRUCTIONS FOR DEPLOYMENT:
 * 1. Copy this file to each server
 * 2. Update the IP addresses below to match your actual server locations
 * 3. Include this file in your config.php files
 */

// ============================================
// DEPLOYMENT SCENARIOS
// ============================================

// SCENARIO 1: All servers on same computer (Development)
if (!defined('DEPLOYMENT_MODE')) {
    define('DEPLOYMENT_MODE', 'local'); // Options: 'local', 'split', 'production'
}

switch (DEPLOYMENT_MODE) {
    case 'local':
        // All servers on localhost (development)
        define('SERVERA_IP', 'localhost');
        define('SERVERB_IP', 'localhost');
        define('SERVERC_IP', 'localhost');
        break;
        
    case 'split':
        // Split deployment across 2 computers with ngrok
        define('SERVERA_IP', 'awfully-ophthalmoscopical-brittny.ngrok-free.dev');  // Ngrok tunnel
        define('SERVERB_IP', 'awfully-ophthalmoscopical-brittny.ngrok-free.dev');  // Ngrok tunnel
        define('SERVERC_IP', 'nonformal-nontemporally-marjorie.ngrok-free.dev');  // ServerC ngrok tunnel
        break;
        
    case 'production':
        // Production deployment with actual server IPs
        define('SERVERA_IP', '192.168.72.225');     // Production Server A
        define('SERVERB_IP', '192.168.72.225');     // Production Server B (database)
        define('SERVERC_IP', '192.168.72.170');     // Production Server C
        break;
        
    default:
        // Fallback to localhost
        define('SERVERA_IP', 'localhost');
        define('SERVERB_IP', 'localhost');
        define('SERVERC_IP', '192.168.72.170');
}

// ============================================
// GENERATED CONFIGURATION
// ============================================

// Database connection (all servers connect to ServerB)
define('DB_HOST', SERVERB_IP);
define('DB_NAME', 'lostfound_db');
define('DB_USER', 'root');
define('DB_PASS', 'Isaac@1234');

// API URLs - Use HTTPS for ngrok domains, HTTP for local IPs
$protocol_a = (strpos(SERVERA_IP, '.ngrok') !== false) ? 'https' : 'http';
$protocol_b = (strpos(SERVERB_IP, '.ngrok') !== false) ? 'https' : 'http';
$protocol_c = (strpos(SERVERC_IP, '.ngrok') !== false) ? 'https' : 'http';

define('SERVERA_API_URL', $protocol_a . '://' . SERVERA_IP . '/Lostnfound/ServerA/api');
define('SERVERB_API_URL', $protocol_b . '://' . SERVERB_IP . '/Lostnfound/ServerB/api');
define('SERVERC_API_URL', $protocol_c . '://' . SERVERC_IP . '/Lostnfound/ServerC/api');

// Upload URLs (ServerB hosts uploads)
define('UPLOADS_BASE_URL', $protocol_b . '://' . SERVERB_IP . '/Lostnfound/ServerB/uploads/');

// Health check URLs
define('SERVERA_HEALTH_URL', $protocol_a . '://' . SERVERA_IP . '/Lostnfound/ServerA/api/health.php');
define('SERVERB_HEALTH_URL', $protocol_b . '://' . SERVERB_IP . '/Lostnfound/ServerB/api/health.php');
define('SERVERC_HEALTH_URL', $protocol_c . '://' . SERVERC_IP . '/Lostnfound/ServerC/health.php');

// ============================================
// DEPLOYMENT VALIDATION
// ============================================

function validateDeploymentConfig() {
    $errors = [];
    
    // Check if required constants are defined
    $required_constants = ['SERVERA_IP', 'SERVERB_IP', 'SERVERC_IP'];
    foreach ($required_constants as $constant) {
        if (!defined($constant)) {
            $errors[] = "Missing required constant: $constant";
        }
    }
    
    // Validate IP addresses
    $ips = [SERVERA_IP, SERVERB_IP, SERVERC_IP];
    foreach ($ips as $ip) {
        if ($ip !== 'localhost' && !filter_var($ip, FILTER_VALIDATE_IP)) {
            $errors[] = "Invalid IP address: $ip";
        }
    }
    
    return $errors;
}

// ============================================
// HELPER FUNCTIONS
// ============================================

function getCurrentServerRole() {
    $current_dir = basename(dirname(__FILE__));
    switch ($current_dir) {
        case 'ServerA':
            return 'Authentication Server';
        case 'ServerB':
            return 'Database & File Server';
        case 'ServerC':
            return 'User Interface Server';
        default:
            return 'Unknown Server';
    }
}

function getDeploymentInfo() {
    return [
        'mode' => DEPLOYMENT_MODE,
        'server_role' => getCurrentServerRole(),
        'servera_ip' => SERVERA_IP,
        'serverb_ip' => SERVERB_IP,
        'serverc_ip' => SERVERC_IP,
        'database_host' => DB_HOST,
        'validation_errors' => validateDeploymentConfig()
    ];
}

// Auto-validate on include
$validation_errors = validateDeploymentConfig();
if (!empty($validation_errors)) {
    error_log("Deployment Configuration Errors: " . implode(', ', $validation_errors));
}
?>