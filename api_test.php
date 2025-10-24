<?php
// Test API calls for user registration and login
require_once 'ServerC/config.php';

echo "<h1>API Testing Dashboard</h1>";

// Test 0: Simple endpoint test
echo "<h2>0. Simple Endpoint Test</h2>";
echo "<p><strong>Testing basic PHP execution with test endpoint</strong></p>";
$testUrl = "http://localhost/Lostnfound/test_endpoint.php";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $testUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$startTime = microtime(true);
$response = curl_exec($ch);
$endTime = microtime(true);
$duration = round(($endTime - $startTime) * 1000, 2);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "<p><strong>URL:</strong> $testUrl</p>";
echo "<p><strong>Duration:</strong> {$duration}ms</p>";
echo "<p><strong>HTTP Code:</strong> $httpCode</p>";
echo "<p><strong>CURL Error:</strong> " . ($error ? htmlspecialchars($error) : 'None') . "</p>";
echo "<p><strong>Response:</strong></p>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";

if ($httpCode === 200 && !$error) {
    echo "<p><strong>✅ Status:</strong> Basic PHP execution working</p>";
} else {
    echo "<p><strong>❌ Status:</strong> Basic PHP execution failed</p>";
}

// Show API endpoints configuration
echo "<h2>1. API Endpoints Configuration</h2>";
global $api_endpoints;
echo "<pre>";
print_r($api_endpoints);
echo "</pre>";

// Test 2: Session Status API (with session cookies)
echo "<h2>2. Testing Session Status API (with session cookies)</h2>";
echo "<p><strong>Calling:</strong> session_status via makeAPICall()</p>";
$startTime = microtime(true);
$sessionResult = makeAPICall('session_status', null, 'GET');
$endTime = microtime(true);
$duration = round(($endTime - $startTime) * 1000, 2);
echo "<p><strong>Duration:</strong> {$duration}ms</p>";
echo "<p><strong>Result:</strong></p>";
echo "<pre>";
print_r($sessionResult);
echo "</pre>";

// Test 2b: Session Status API (without session cookies)
echo "<h2>2b. Testing Session Status API (without session cookies)</h2>";
echo "<p><strong>Testing makeAPICall logic without session cookies</strong></p>";
$url = "http://localhost/Lostnfound/ServerA/api/session_status.php";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_VERBOSE, false);
// NOTE: No session cookie included here
$startTime = microtime(true);
$response = curl_exec($ch);
$endTime = microtime(true);
$duration = round(($endTime - $startTime) * 1000, 2);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "<p><strong>Duration:</strong> {$duration}ms</p>";
echo "<p><strong>HTTP Code:</strong> $httpCode</p>";
echo "<p><strong>CURL Error:</strong> " . ($error ? htmlspecialchars($error) : 'None') . "</p>";
echo "<p><strong>Response:</strong> " . htmlspecialchars($response) . "</p>";

if ($httpCode === 200 && !$error) {
    echo "<p><strong>✅ Status:</strong> makeAPICall logic works without session cookies</p>";
} else {
    echo "<p><strong>❌ Status:</strong> makeAPICall logic still fails without session cookies</p>";
}

// Test 2c: Exact makeAPICall replication test
echo "<h2>2c. Exact makeAPICall Replication Test</h2>";
echo "<p><strong>Testing exact makeAPICall logic step by step</strong></p>";

// Step 1: Test minimal CURL (like direct test)
echo "<h3>Step 1: Minimal CURL (working)</h3>";
$url = "http://localhost/Lostnfound/ServerA/api/session_status.php";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$startTime = microtime(true);
$response = curl_exec($ch);
$endTime = microtime(true);
$duration = round(($endTime - $startTime) * 1000, 2);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);
echo "<p><strong>Duration:</strong> {$duration}ms | <strong>HTTP Code:</strong> $httpCode | <strong>Response:</strong> " . htmlspecialchars(substr($response, 0, 50)) . "</p>";

// Step 2: Add JSON headers (like makeAPICall for POST)
echo "<h3>Step 2: Add Content-Type header</h3>";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$startTime = microtime(true);
$response = curl_exec($ch);
$endTime = microtime(true);
$duration = round(($endTime - $startTime) * 1000, 2);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);
echo "<p><strong>Duration:</strong> {$duration}ms | <strong>HTTP Code:</strong> $httpCode | <strong>Response:</strong> " . htmlspecialchars(substr($response, 0, 50)) . "</p>";

// Test 3: Direct CURL test to ServerA session status  
echo "<h2>3. Direct CURL test to ServerA Session Status</h2>";
$url = "http://localhost/Lostnfound/ServerA/api/session_status.php";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "<p><strong>HTTP Code:</strong> " . $httpCode . "</p>";
echo "<p><strong>CURL Error:</strong> " . ($error ? htmlspecialchars($error) : 'None') . "</p>";
echo "<p><strong>Response:</strong> " . htmlspecialchars($response) . "</p>";

// Test 4: Test user registration API
echo "<h2>4. Testing User Registration API</h2>";
$testUser = [
    'username' => 'testuser_' . time(),
    'email' => 'test_' . time() . '@example.com',
    'password' => 'testpass123'
];

$startTime = microtime(true);
$registerResult = makeAPICall('register_user', $testUser, 'POST');
$endTime = microtime(true);
$duration = round(($endTime - $startTime) * 1000, 2);
echo "<p><strong>Test User Data:</strong></p>";
echo "<pre>";
print_r($testUser);
echo "</pre>";
echo "<p><strong>Duration:</strong> {$duration}ms</p>";
echo "<p><strong>Registration Result:</strong></p>";
echo "<pre>";
print_r($registerResult);
echo "</pre>";

// Test 5: Direct CURL test to ServerA registration
echo "<h2>5. Direct CURL test to ServerA Registration</h2>";
$url = "http://localhost/Lostnfound/ServerA/api/register_user.php";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testUser));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "<p><strong>HTTP Code:</strong> " . $httpCode . "</p>";
echo "<p><strong>CURL Error:</strong> " . ($error ? htmlspecialchars($error) : 'None') . "</p>";
echo "<p><strong>Response:</strong> " . htmlspecialchars($response) . "</p>";

// Test 6: Test database connection
echo "<h2>6. Testing Database Connection</h2>";
try {
    $conn = getDBConnection();
    if ($conn) {
        echo "<p><strong>✅ Database connection successful</strong></p>";
        
        // Test if users table exists
        $result = $conn->query("SHOW TABLES LIKE 'users'");
        if ($result->num_rows > 0) {
            echo "<p><strong>✅ Users table exists</strong></p>";
            
            // Check table structure
            $result = $conn->query("DESCRIBE users");
            echo "<p><strong>Users table structure:</strong></p>";
            echo "<pre>";
            while ($row = $result->fetch_assoc()) {
                print_r($row);
            }
            echo "</pre>";
        } else {
            echo "<p><strong>❌ Users table does not exist</strong></p>";
        }
        
        $conn->close();
    } else {
        echo "<p><strong>❌ Database connection failed</strong></p>";
    }
} catch (Exception $e) {
    echo "<p><strong>❌ Database error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test 7: Check if servers are running
echo "<h2>7. Server Connectivity Tests</h2>";

$servers = [
    'ServerA (User Management)' => 'http://localhost/Lostnfound/ServerA',
    'ServerB (Item Management)' => 'http://localhost/Lostnfound/ServerB', 
    'ServerC (Frontend)' => 'http://localhost/Lostnfound/ServerC'
];

foreach ($servers as $name => $url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_NOBODY, true); // HEAD request
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($httpCode > 0) {
        echo "<p><strong>✅ $name:</strong> Responding (HTTP $httpCode)</p>";
    } else {
        echo "<p><strong>❌ $name:</strong> Not responding - " . htmlspecialchars($error) . "</p>";
    }
}

// Test 8: Comprehensive API Endpoint Testing
echo "<h2>8. Comprehensive API Endpoint Testing</h2>";
foreach ($api_endpoints as $endpoint => $url) {
    echo "<h3>Testing: $endpoint</h3>";
    echo "<p><strong>URL:</strong> $url</p>";
    
    $startTime = microtime(true);
    
    // Choose appropriate method and data based on endpoint
    $method = 'GET';
    $data = null;
    
    if (in_array($endpoint, ['register_user', 'add_item', 'update_item'])) {
        $method = 'POST';
        if ($endpoint === 'register_user') {
            $data = [
                'username' => 'test_' . time(),
                'email' => 'test_' . time() . '@example.com',
                'password' => 'testpass123'
            ];
        } elseif ($endpoint === 'add_item') {
            $data = [
                'title' => 'Test Item',
                'description' => 'Test Description',
                'category' => 'lost',
                'location' => 'Test Location'
            ];
        }
    }
    
    $result = makeAPICall($endpoint, $data, $method);
    $endTime = microtime(true);
    $duration = round(($endTime - $startTime) * 1000, 2);
    
    echo "<p><strong>Method:</strong> $method</p>";
    if ($data) {
        echo "<p><strong>Data:</strong></p>";
        echo "<pre>" . htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT)) . "</pre>";
    }
    echo "<p><strong>Duration:</strong> {$duration}ms</p>";
    echo "<p><strong>Result:</strong></p>";
    echo "<pre>";
    print_r($result);
    echo "</pre>";
    
    // Status indicator
    if (isset($result['error'])) {
        echo "<p><strong>❌ Status:</strong> Failed - " . htmlspecialchars($result['error']) . "</p>";
    } elseif (isset($result['success'])) {
        echo "<p><strong>✅ Status:</strong> " . ($result['success'] ? 'Success' : 'Failed') . "</p>";
    } else {
        echo "<p><strong>⚠️ Status:</strong> Unknown response format</p>";
    }
    
    echo "<hr>";
}
?>