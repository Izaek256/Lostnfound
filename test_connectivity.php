<?php
/**
 * Test script to verify API connectivity and basic operations
 */

// Set up error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Load ServerC config
require_once 'ServerC/config.php';

echo "<h1>Lost & Found API Connectivity Test</h1>\n";
echo "<p>Testing cross-server API communication...</p>\n";

// Test 1: Test ServerA API connectivity
echo "<h2>Test 1: ServerA API (Item Operations)</h2>\n";
echo "<p>Testing: " . SERVERA_URL . "/get_all_items.php</p>\n";

$response = makeAPIRequest(SERVERA_URL . '/get_all_items.php', [], 'GET', ['return_json' => true]);
if (is_array($response) && isset($response['success'])) {
    if ($response['success']) {
        echo "<p style='color: green;'>✅ SUCCESS: ServerA API is working</p>\n";
        $items = $response['items'] ?? [];
        echo "<p>Found " . count($items) . " items in database</p>\n";
    } else {
        echo "<p style='color: red;'>❌ FAILED: " . ($response['error'] ?? 'Unknown error') . "</p>\n";
    }
} else {
    echo "<p style='color: red;'>❌ FAILED: Invalid response format</p>\n";
    echo "<pre>" . json_encode($response) . "</pre>\n";
}

// Test 2: Test ServerB API connectivity
echo "<h2>Test 2: ServerB API (User Operations)</h2>\n";
echo "<p>Testing: " . SERVERB_URL . "/health.php</p>\n";

$health_response = makeAPIRequest(SERVERB_URL . '/health.php', [], 'GET', ['return_json' => true]);
if (is_array($health_response)) {
    if (isset($health_response['server']) && $health_response['server'] === 'ServerB') {
        echo "<p style='color: green;'>✅ SUCCESS: ServerB API is responding</p>\n";
    } else {
        echo "<p style='color: orange;'>⚠️ WARNING: ServerB responded but response format unexpected</p>\n";
    }
} else {
    echo "<p style='color: red;'>❌ FAILED: Invalid response</p>\n";
    echo "<pre>" . json_encode($health_response) . "</pre>\n";
}

// Test 3: Test login (without credentials - should fail gracefully)
echo "<h2>Test 3: Login Verification Endpoint</h2>\n";
echo "<p>Testing: " . SERVERB_URL . "/verify_user.php</p>\n";
echo "<p>This test attempts a login with dummy credentials (will fail as expected)</p>\n";

$login_response = makeAPIRequest(SERVERB_URL . '/verify_user.php', [
    'username' => 'test_user_that_doesnt_exist',
    'password' => 'wrong_password'
], 'POST', ['return_json' => true]);

if (is_array($login_response)) {
    echo "<p style='color: green;'>✅ SUCCESS: Endpoint is responding with JSON</p>\n";
    if (isset($login_response['error'])) {
        echo "<p>Expected error received: " . htmlspecialchars($login_response['error']) . "</p>\n";
    }
} else {
    echo "<p style='color: red;'>❌ FAILED: Not returning JSON</p>\n";
    echo "<pre>" . htmlspecialchars(substr($login_response, 0, 500)) . "</pre>\n";
}

// Test 4: Test item registration
echo "<h2>Test 4: Item Registration Endpoint</h2>\n";
echo "<p>Testing: " . SERVERA_URL . "/add_item.php</p>\n";
echo "<p>This test attempts to add an item (will fail without valid user_id)</p>\n";

$item_response = makeAPIRequest(SERVERA_URL . '/add_item.php', [
    'user_id' => '999',
    'title' => 'Test Item',
    'description' => 'Test Description',
    'type' => 'lost',
    'location' => 'Test Location',
    'contact' => 'test@example.com',
    'image_filename' => null
], 'POST', ['return_json' => true]);

if (is_array($item_response)) {
    echo "<p style='color: green;'>✅ SUCCESS: Endpoint is responding</p>\n";
    if (isset($item_response['success'])) {
        echo "<p>Response: " . ($item_response['success'] ? 'Success' : 'Failed') . "</p>\n";
    }
    if (isset($item_response['error'])) {
        echo "<p>Error: " . htmlspecialchars($item_response['error']) . "</p>\n";
    }
} else {
    echo "<p style='color: red;'>❌ FAILED: Invalid response format</p>\n";
}

echo "\n<h2>Summary</h2>\n";
echo "<p>API connectivity test completed. Check results above.</p>\n";
echo "<p><a href='ServerC/index.php'>Back to main site</a></p>\n";
?>
