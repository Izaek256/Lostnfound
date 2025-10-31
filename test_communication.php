<?php
/**
 * Multi-Server Communication Test Script
 * Tests all API endpoints and database connections
 */

// Disable error display for production-like testing
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Lost & Found - Multi-Server Communication Test</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 2rem;
            min-height: 100vh;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        h1 {
            color: #333;
            margin-bottom: 2rem;
            text-align: center;
            font-size: 2rem;
        }
        .test-section {
            margin-bottom: 2rem;
            padding: 1.5rem;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
        }
        .test-section h2 {
            color: #667eea;
            margin-bottom: 1rem;
            font-size: 1.5rem;
            border-bottom: 2px solid #667eea;
            padding-bottom: 0.5rem;
        }
        .test-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem;
            margin: 0.5rem 0;
            background: #f5f5f5;
            border-radius: 6px;
        }
        .status {
            padding: 0.5rem 1rem;
            border-radius: 4px;
            font-weight: bold;
        }
        .success {
            background: #4caf50;
            color: white;
        }
        .error {
            background: #f44336;
            color: white;
        }
        .info {
            background: #2196f3;
            color: white;
        }
        .warning {
            background: #ff9800;
            color: white;
        }
        .details {
            margin-top: 0.5rem;
            padding: 0.75rem;
            background: #fff;
            border-left: 4px solid #667eea;
            font-size: 0.9rem;
            font-family: monospace;
        }
        .summary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 8px;
            margin-top: 2rem;
            text-align: center;
        }
        .summary h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        .summary .stats {
            display: flex;
            justify-content: space-around;
            margin-top: 1rem;
        }
        .summary .stat {
            text-align: center;
        }
        .summary .stat-value {
            font-size: 2rem;
            font-weight: bold;
            display: block;
        }
        .summary .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔧 Lost & Found System - Communication Test</h1>";

$total_tests = 0;
$passed_tests = 0;
$failed_tests = 0;

// Test function
function runTest($name, $callback) {
    global $total_tests, $passed_tests, $failed_tests;
    $total_tests++;
    
    echo "<div class='test-item'>";
    echo "<span>$name</span>";
    
    try {
        $result = $callback();
        if ($result['success']) {
            $passed_tests++;
            echo "<span class='status success'>✓ PASS</span>";
        } else {
            $failed_tests++;
            echo "<span class='status error'>✗ FAIL</span>";
        }
        echo "</div>";
        
        if (!empty($result['details'])) {
            echo "<div class='details'>" . htmlspecialchars($result['details']) . "</div>";
        }
    } catch (Exception $e) {
        $failed_tests++;
        echo "<span class='status error'>✗ ERROR</span>";
        echo "</div>";
        echo "<div class='details'>Exception: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

// ============================================
// TEST 1: Database Connections
// ============================================
echo "<div class='test-section'>";
echo "<h2>📊 Database Connectivity</h2>";

// Load ServerC config once (it has all connection functions)
require_once 'ServerC/config.php';

// Test ServerA connection
runTest("ServerA Database Connection", function() {
    $conn = connectServerA();
    if ($conn) {
        mysqli_close($conn);
        return ['success' => true, 'details' => 'Successfully connected to ServerA database'];
    }
    return ['success' => false, 'details' => 'Failed to connect to ServerA database'];
});

// Test ServerB connection
runTest("ServerB Database Connection", function() {
    $conn = connectServerB();
    if ($conn) {
        mysqli_close($conn);
        return ['success' => true, 'details' => 'Successfully connected to ServerB database'];
    }
    return ['success' => false, 'details' => 'Failed to connect to ServerB database'];
});

// Test ServerC connection
runTest("ServerC Database Connection", function() {
    $conn = connectServerC();
    if ($conn) {
        mysqli_close($conn);
        return ['success' => true, 'details' => 'Successfully connected to ServerC database'];
    }
    return ['success' => false, 'details' => 'Failed to connect to ServerC database'];
});

echo "</div>";

// ============================================
// TEST 2: API Endpoints
// ============================================
echo "<div class='test-section'>";
echo "<h2>🌐 API Endpoints</h2>";

// Test ServerA API endpoints using configured URLs
runTest("ServerA - verify_user.php endpoint", function() {
    $url = SERVERA_URL . '/verify_user.php';
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
    curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return ['success' => false, 'details' => "Connection Error: $error"];
    }
    if ($http_code == 200 || $http_code == 0) {
        return ['success' => true, 'details' => "Endpoint accessible (HTTP $http_code)"];
    }
    return ['success' => false, 'details' => "HTTP Error: $http_code"];
});

runTest("ServerA - register_user.php endpoint", function() {
    $url = SERVERA_URL . '/register_user.php';
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
    curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return ['success' => false, 'details' => "Connection Error: $error"];
    }
    if ($http_code == 200 || $http_code == 0) {
        return ['success' => true, 'details' => "Endpoint accessible (HTTP $http_code)"];
    }
    return ['success' => false, 'details' => "HTTP Error: $http_code"];
});

// Test ServerB API endpoints using configured URLs
runTest("ServerB - get_items.php endpoint", function() {
    $url = SERVERB_URL . '/get_items.php';
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return ['success' => false, 'details' => "Connection Error: $error"];
    }
    if ($http_code == 200) {
        $data = json_decode($response, true);
        if (isset($data['success'])) {
            return ['success' => true, 'details' => "API responding correctly with JSON data"];
        }
    }
    return ['success' => false, 'details' => "HTTP $http_code - Response invalid"];
});

runTest("ServerB - add_item.php endpoint", function() {
    $url = SERVERB_URL . '/add_item.php';
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
    curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return ['success' => false, 'details' => "Connection Error: $error"];
    }
    if ($http_code == 200 || $http_code == 0) {
        return ['success' => true, 'details' => "Endpoint accessible (HTTP $http_code)"];
    }
    return ['success' => false, 'details' => "HTTP Error: $http_code"];
});

runTest("ServerB - delete_item.php endpoint", function() {
    $url = SERVERB_URL . '/delete_item.php';
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
    curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return ['success' => false, 'details' => "Connection Error: $error"];
    }
    if ($http_code == 200 || $http_code == 0) {
        return ['success' => true, 'details' => "Endpoint accessible (HTTP $http_code)"];
    }
    return ['success' => false, 'details' => "HTTP Error: $http_code"];
});

runTest("ServerB - update_item.php endpoint", function() {
    $url = SERVERB_URL . '/update_item.php';
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
    curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return ['success' => false, 'details' => "Connection Error: $error"];
    }
    if ($http_code == 200 || $http_code == 0) {
        return ['success' => true, 'details' => "Endpoint accessible (HTTP $http_code)"];
    }
    return ['success' => false, 'details' => "HTTP Error: $http_code"];
});

echo "</div>";

// ============================================
// TEST 3: Database Tables
// ============================================
echo "<div class='test-section'>";
echo "<h2>🗄️ Database Tables</h2>";

runTest("Users table exists", function() {
    $conn = connectServerA();
    $result = mysqli_query($conn, "SHOW TABLES LIKE 'users'");
    $exists = mysqli_num_rows($result) > 0;
    mysqli_close($conn);
    
    if ($exists) {
        return ['success' => true, 'details' => 'Users table found in database'];
    }
    return ['success' => false, 'details' => 'Users table not found'];
});

runTest("Items table exists", function() {
    $conn = connectServerB();
    $result = mysqli_query($conn, "SHOW TABLES LIKE 'items'");
    $exists = mysqli_num_rows($result) > 0;
    mysqli_close($conn);
    
    if ($exists) {
        return ['success' => true, 'details' => 'Items table found in database'];
    }
    return ['success' => false, 'details' => 'Items table not found'];
});

echo "</div>";

// ============================================
// TEST 4: File System
// ============================================
echo "<div class='test-section'>";
echo "<h2>📁 File System & Permissions</h2>";

runTest("ServerB uploads directory exists", function() {
    $upload_dir = 'ServerB/uploads/';
    if (is_dir($upload_dir)) {
        return ['success' => true, 'details' => 'Upload directory exists at ' . realpath($upload_dir)];
    }
    return ['success' => false, 'details' => 'Upload directory not found'];
});

runTest("ServerB uploads directory is writable", function() {
    $upload_dir = 'ServerB/uploads/';
    if (is_writable($upload_dir)) {
        return ['success' => true, 'details' => 'Upload directory has write permissions'];
    }
    return ['success' => false, 'details' => 'Upload directory is not writable'];
});

echo "</div>";

// ============================================
// TEST 5: Cross-Server Communication
// ============================================
echo "<div class='test-section'>";
echo "<h2>🔄 Cross-Server Communication</h2>";

runTest("ServerC can call ServerA API", function() {
    $response = makeAPIRequest(SERVERA_URL . '/verify_user.php', [
        'username' => 'test_nonexistent',
        'password' => 'test123'
    ]);
    
    if (strpos($response, '|') !== false) {
        return ['success' => true, 'details' => 'ServerC successfully communicated with ServerA API'];
    }
    return ['success' => false, 'details' => 'Failed to communicate: ' . substr($response, 0, 100)];
});

runTest("ServerC can call ServerB API", function() {
    $ch = curl_init(SERVERB_URL . '/get_items.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    
    if (!$error && !empty($response)) {
        $data = json_decode($response, true);
        if (isset($data['success'])) {
            return ['success' => true, 'details' => 'ServerC successfully communicated with ServerB API'];
        }
    }
    return ['success' => false, 'details' => 'Failed to communicate: ' . ($error ?: 'Invalid response')];
});

echo "</div>";

// ============================================
// SUMMARY
// ============================================
echo "<div class='summary'>";
echo "<h3>📋 Test Summary</h3>";
echo "<div class='stats'>";
echo "<div class='stat'>";
echo "<span class='stat-value'>$total_tests</span>";
echo "<span class='stat-label'>Total Tests</span>";
echo "</div>";
echo "<div class='stat'>";
echo "<span class='stat-value'>$passed_tests</span>";
echo "<span class='stat-label'>Passed</span>";
echo "</div>";
echo "<div class='stat'>";
echo "<span class='stat-value'>$failed_tests</span>";
echo "<span class='stat-label'>Failed</span>";
echo "</div>";
echo "<div class='stat'>";
$success_rate = $total_tests > 0 ? round(($passed_tests / $total_tests) * 100, 1) : 0;
echo "<span class='stat-value'>$success_rate%</span>";
echo "<span class='stat-label'>Success Rate</span>";
echo "</div>";
echo "</div>";

if ($failed_tests == 0) {
    echo "<p style='margin-top: 2rem; font-size: 1.2rem;'>✅ All systems operational! Multi-server communication is working perfectly.</p>";
} else {
    echo "<p style='margin-top: 2rem; font-size: 1.2rem;'>⚠️ Some tests failed. Please check the details above.</p>";
}

echo "</div>";

echo "<div style='text-align: center; margin-top: 2rem;'>";
echo "<a href='ServerC/index.php' style='display: inline-block; background: #667eea; color: white; padding: 1rem 2rem; border-radius: 6px; text-decoration: none; font-weight: bold;'>← Back to Application</a>";
echo "</div>";

echo "</div>";
echo "</body>
</html>";
?>
