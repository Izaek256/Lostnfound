<?php
/**
 * CENTRAL CONTROL USAGE GUIDE
 * 
 * This file shows examples of how to use the CentralControl system
 * throughout your application instead of scattered connectivity code.
 * 
 * Simply include at the top of any file:
 *    require_once 'CentralControl.php';
 *    $cc = getCentralControl();
 */

// ============================================
// 1. DATABASE OPERATIONS
// ============================================

// Old way (scattered across files):
// $conn = mysqli_connect('localhost', 'root', '', 'lostfound_db');
// $result = mysqli_query($conn, "SELECT * FROM users");

// New way (centralized):
require_once 'CentralControl.php';
$cc = getCentralControl();

// Get a database connection
$db = $cc->db();  // Defaults to ServerB's database

// Execute query with parameters (safe)
$result = $db->getRows("SELECT * FROM users WHERE email = ?", [$email]);

// Get single row
$user = $db->getRow("SELECT * FROM users WHERE id = ?", [$user_id]);

// Insert data
$new_user_id = $db->insert('users', [
    'username' => $username,
    'email' => $email,
    'password' => password_hash($password, PASSWORD_DEFAULT)
]);

// Update data
$db->update('users', 
    ['last_login' => date('Y-m-d H:i:s')],
    'id = ?',
    [$user_id]
);

// Delete data
$db->delete('users', 'id = ?', [$user_id]);

// Count records
$user_count = $db->count('users');

// Check if table exists
if ($db->tableExists('users')) {
    echo "Users table exists";
}

// ============================================
// 2. API REQUESTS TO OTHER SERVERS
// ============================================

// Old way (scattered makeAPIRequest calls):
// $response = makeAPIRequest(SERVERA_URL . '/api/register_user.php', [...]);

// New way (centralized):

// Register a user via ServerA
$api_a = $cc->api('ServerA');
$response = $api_a->post('register_user.php', [
    'username' => $username,
    'email' => $email,
    'password' => $password
], true); // true = return JSON

// Get items from ServerB
$api_b = $cc->api('ServerB');
$items = $api_b->get('get_items.php', ['limit' => 10], true);

// Add item to ServerB
$api_b->post('add_item.php', [
    'name' => $item_name,
    'category' => $category
], true);

// Update item
$api_b->put('update_item.php', [
    'id' => $item_id,
    'name' => $new_name
], true);

// Delete item
$api_b->delete('delete_item.php', ['id' => $item_id], true);

// Check if request was successful
if ($api_a->isSuccess()) {
    echo "Success! Response: " . $api_a->getLastHttpCode();
} else {
    echo "Error: " . $api_a->getLastError();
}

// ============================================
// 3. SERVER HEALTH & MONITORING
// ============================================

// Old way (multiple scattered health check functions):
// function checkServerHealth($server_url) { ... }

// New way (centralized):

// Test a single server
$health = $cc->health()->test('ServerA');
if ($health['online']) {
    echo "ServerA is online ({$health['response_time']}ms)";
}

// Test all servers
$all_health = $cc->health()->testAll();
foreach ($all_health as $server => $result) {
    echo "$server: " . ($result['online'] ? 'ONLINE' : 'OFFLINE') . "\n";
}

// Check if all servers are online
if ($cc->health()->allOnline()) {
    echo "All systems operational!";
}

// Get health summary
$summary = $cc->health()->getSummary();
echo "System Health: {$summary['percentage']}% online";

// ============================================
// 4. SYSTEM STATUS MONITORING
// ============================================

// Get complete system status
$status = $cc->status()->getAll();

// Check overall system health
if ($status['overall_health'] === 'healthy') {
    echo "All systems healthy";
} elseif ($status['overall_health'] === 'degraded') {
    echo "Some systems are down";
} else {
    echo "Critical - system failure";
}

// Access deployment info
echo "Running in: " . $status['deployment']['mode'] . " mode";
echo "Database: " . $status['deployment']['db_host'];

// ============================================
// 5. CONFIGURATION & DEPLOYMENT INFO
// ============================================

// Get deployment information
$deployment = $cc->getDeploymentInfo();
echo "Mode: " . $deployment['mode'];
echo "Is Local: " . ($deployment['is_local'] ? 'Yes' : 'No');
echo "Is Production: " . ($deployment['is_production'] ? 'Yes' : 'No');

// Check deployment mode
if ($cc->isLocal()) {
    echo "Running in local development mode";
}

if ($cc->isProduction()) {
    echo "Running in production mode";
}

// Get server URL
$api_url = $cc->getServerURL('ServerB');
echo "API URL: $api_url";

// ============================================
// 6. QUICK ACCESS FUNCTIONS (Shortcuts)
// ============================================

// These are global helper functions for quick access:

// Database
$db = cc_db();  // or cc_db('ServerB'), cc_db('ServerA'), etc.

// API
$api = cc_api('ServerA');  // or cc_api('ServerB'), cc_api('ServerC')

// Health
$health_summary = cc_health()->getSummary();

// Status
$system_status = cc_status()->getAll();

// ============================================
// 7. ERROR HANDLING
// ============================================

// Database errors
$db = $cc->db();
if ($db === null) {
    echo "Failed to connect to database";
}

// API errors
$api = $cc->api('ServerA');
$response = $api->post('register_user.php', ['username' => 'test'], true);

if (!$api->isSuccess()) {
    $error = $api->getLastError();
    $http_code = $api->getLastHttpCode();
    error_log("API Error: $error (HTTP $http_code)");
}

// ============================================
// 8. LOGGING & DEBUGGING
// ============================================

// Enable debug mode
$cc = new CentralControl(true);  // true = debug mode

// Manual logging
$cc->log("User logged in successfully");
$cc->log("Critical error occurred", 'error');
$cc->log("Server might be slow", 'warning');

// View logs
$log_file = $cc->getLogFile();
echo "Check logs at: $log_file";

// ============================================
// 9. CONNECTION CLEANUP (Optional)
// ============================================

// Manually close all database connections when done
$cc->closeAllConnections();

// Or let the destructor handle it automatically

// ============================================
// 10. PRACTICAL EXAMPLES
// ============================================

// EXAMPLE 1: User Registration Flow
function registerUser($username, $email, $password) {
    $cc = getCentralControl();
    
    // Check if user exists
    $db = $cc->db();
    $existing = $db->getRow("SELECT id FROM users WHERE email = ?", [$email]);
    
    if ($existing) {
        return ['success' => false, 'error' => 'Email already registered'];
    }
    
    // Register user via ServerA API
    $api_a = $cc->api('ServerA');
    $response = $api_a->post('register_user.php', [
        'username' => $username,
        'email' => $email,
        'password' => $password
    ], true);
    
    if ($api_a->isSuccess()) {
        return ['success' => true, 'user_id' => $response['user_id']];
    } else {
        return ['success' => false, 'error' => $api_a->getLastError()];
    }
}

// EXAMPLE 2: System Health Dashboard
function getSystemDashboard() {
    $cc = getCentralControl();
    
    return [
        'deployment' => $cc->getDeploymentInfo(),
        'health' => $cc->health()->getSummary(),
        'status' => $cc->status()->getAll(),
        'database_online' => $cc->db() !== null
    ];
}

// EXAMPLE 3: Cross-Server Item Management
function createLostItem($title, $description, $category, $image_file = null) {
    $cc = getCentralControl();
    
    // Upload image to ServerB if provided
    if ($image_file) {
        $api_b = $cc->api('ServerB');
        $image_response = $api_b->post('upload_file.php', [
            'file' => $image_file
        ], true);
        
        if (!$api_b->isSuccess()) {
            return ['success' => false, 'error' => 'Failed to upload image'];
        }
        
        $image_path = $image_response['path'];
    }
    
    // Store item in database
    $db = $cc->db();
    $item_id = $db->insert('items', [
        'title' => $title,
        'description' => $description,
        'category' => $category,
        'image_path' => $image_path ?? null,
        'created_at' => date('Y-m-d H:i:s')
    ]);
    
    if ($item_id) {
        return ['success' => true, 'item_id' => $item_id];
    } else {
        return ['success' => false, 'error' => 'Failed to create item'];
    }
}

// ============================================
// MIGRATION GUIDE: Replace Old Code
// ============================================

/**
 * OLD CODE (scattered):
 * 
 * $conn = mysqli_connect('localhost', 'root', '', 'lostfound_db');
 * $result = makeAPIRequest(SERVERA_URL . '/api/register_user.php', $data);
 * if (testServerConnection(SERVERA_URL, 5)) { ... }
 * 
 * NEW CODE (centralized):
 * 
 * $cc = getCentralControl();
 * $db = $cc->db();
 * $api = $cc->api('ServerA');
 * if ($cc->health()->test('ServerA')['online']) { ... }
 */

// ============================================
// FILE STRUCTURE AFTER MIGRATION
// ============================================

/**
 * ServerA/
 *   api/
 *     register_user.php    <- Use: $cc->api('ServerA')->post('register_user.php', [...])
 *     health.php           <- Use: $cc->health()->test('ServerA')
 *   config.php             <- Just has deployment_config.php
 * 
 * ServerB/
 *   api/
 *     add_item.php         <- Use: $cc->api('ServerB')->post('add_item.php', [...])
 *     get_items.php        <- Use: $cc->api('ServerB')->get('get_items.php', [...])
 *   config.php             <- Just has deployment_config.php
 * 
 * ServerC/
 *   (UI files)
 *   config.php             <- Just has deployment_config.php
 * 
 * CentralControl.php       <- THE MASTER CONTROL FILE (all connectivity)
 * central_control_test.php <- Testing interface (view at localhost/Lostnfound/central_control_test.php)
 */

?>
