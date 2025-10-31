<?php
/**
 * Command Line Connectivity Test
 * Run this script to test server connectivity before deployment
 * 
 * Usage: php test_connectivity.php
 */

require_once __DIR__ . '/ServerC/config.php';

echo "=== Lost & Found Multi-Server Connectivity Test ===\n\n";

// Test database connection
echo "1. Testing Database Connection...\n";
try {
    $conn = connectDB();
    if ($conn) {
        echo "   ✓ Database connection successful\n";
        
        // Test if tables exist
        $tables = ['users', 'items'];
        foreach ($tables as $table) {
            $result = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
            if (mysqli_num_rows($result) > 0) {
                echo "   ✓ Table '$table' exists\n";
            } else {
                echo "   ✗ Table '$table' missing\n";
            }
        }
        mysqli_close($conn);
    } else {
        echo "   ✗ Database connection failed\n";
    }
} catch (Exception $e) {
    echo "   ✗ Database error: " . $e->getMessage() . "\n";
}

echo "\n2. Testing ServerA API...\n";
$servera_health = getServerStatus(SERVERA_URL, 'ServerA');
if ($servera_health['online']) {
    echo "   ✓ ServerA is reachable ({$servera_health['response_time']}ms)\n";
} else {
    echo "   ✗ ServerA is unreachable: {$servera_health['error']}\n";
}

echo "\n3. Testing ServerB API...\n";
$serverb_health = getServerStatus(SERVERB_URL, 'ServerB');
if ($serverb_health['online']) {
    echo "   ✓ ServerB is reachable ({$serverb_health['response_time']}ms)\n";
} else {
    echo "   ✗ ServerB is unreachable: {$serverb_health['error']}\n";
}

echo "\n4. Testing File System Access...\n";
if (is_dir(UPLOADS_PATH)) {
    echo "   ✓ Uploads directory exists: " . realpath(UPLOADS_PATH) . "\n";
    if (is_writable(UPLOADS_PATH)) {
        echo "   ✓ Uploads directory is writable\n";
    } else {
        echo "   ✗ Uploads directory is not writable\n";
    }
} else {
    echo "   ✗ Uploads directory not found: " . UPLOADS_PATH . "\n";
}

echo "\n5. Testing Cross-Server API Calls...\n";

// Test ServerA API call
echo "   Testing ServerA verify_user endpoint...\n";
$response = makeAPIRequest(SERVERA_URL . '/verify_user.php', [
    'username' => 'test_nonexistent',
    'password' => 'test123'
]);

if (strpos($response, 'error|') === 0) {
    echo "   ✓ ServerA API call successful (expected error response)\n";
} else {
    echo "   ✗ ServerA API call failed: " . substr($response, 0, 50) . "...\n";
}

// Test ServerB API call
echo "   Testing ServerB get_items endpoint...\n";
$ch = curl_init(SERVERB_URL . '/get_items.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
$error = curl_error($ch);
curl_close($ch);

if (!$error && !empty($response)) {
    $data = json_decode($response, true);
    if (isset($data['success'])) {
        echo "   ✓ ServerB API call successful\n";
    } else {
        echo "   ✗ ServerB API returned invalid JSON\n";
    }
} else {
    echo "   ✗ ServerB API call failed: " . ($error ?: 'No response') . "\n";
}

echo "\n=== Configuration Summary ===\n";
echo "ServerA URL: " . SERVERA_URL . "\n";
echo "ServerB URL: " . SERVERB_URL . "\n";
echo "Database Host: " . (defined('DB_HOST') ? DB_HOST : $GLOBALS['db_host']) . "\n";
echo "Uploads Path: " . UPLOADS_PATH . "\n";
echo "Uploads HTTP URL: " . UPLOADS_HTTP_URL . "\n";

echo "\n=== Test Complete ===\n";
echo "If all tests pass, your multi-server setup should work correctly.\n";
echo "If any tests fail, check the deployment guide for troubleshooting steps.\n";
?>