<?php
// Test API calls for user registration and login
require_once 'ServerC/config.php';

echo "<h1>API Testing Dashboard</h1>";

// Show API endpoints configuration
echo "<h2>0. API Endpoints Configuration</h2>";
global $api_endpoints;
echo "<pre>";
print_r($api_endpoints);
echo "</pre>";

// Test 1: Session Status API
echo "<h2>1. Testing Session Status API</h2>";
echo "<p><strong>Calling:</strong> session_status via makeAPICall()</p>";
$sessionResult = makeAPICall('session_status', null, 'GET');
echo "<p><strong>Result:</strong></p>";
echo "<pre>";
print_r($sessionResult);
echo "</pre>";

// Test 2: Direct CURL test to ServerA session status  
echo "<h2>2. Direct CURL test to ServerA Session Status</h2>";
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

// Test 3: Test user registration API
echo "<h2>3. Testing User Registration API</h2>";
$testUser = [
    'username' => 'testuser_' . time(),
    'email' => 'test_' . time() . '@example.com',
    'password' => 'testpass123'
];

$registerResult = makeAPICall('register_user', $testUser, 'POST');
echo "<p><strong>Test User Data:</strong></p>";
echo "<pre>";
print_r($testUser);
echo "</pre>";
echo "<p><strong>Registration Result:</strong></p>";
echo "<pre>";
print_r($registerResult);
echo "</pre>";

// Test 4: Direct CURL test to ServerA registration
echo "<h2>4. Direct CURL test to ServerA Registration</h2>";
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

// Test 5: Test database connection
echo "<h2>5. Testing Database Connection</h2>";
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

// Test 6: Check if servers are running
echo "<h2>6. Server Connectivity Tests</h2>";

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
?>