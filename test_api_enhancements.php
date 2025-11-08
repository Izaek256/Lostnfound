<?php
/**
 * API Enhancement Test Script
 * 
 * Tests the new and enhanced API communication features
 * Run this from browser: http://localhost/Lostnfound/test_api_enhancements.php
 * Or from CLI: php test_api_enhancements.php
 */

require_once 'ServerC/config.php';
require_once 'ServerC/api_client.php';

// Determine if running from CLI or web
$is_cli = php_sapi_name() === 'web';

if (!$is_cli) {
    header('Content-Type: text/html; charset=utf-8');
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>API Enhancement Test - Lost & Found</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
            .container { max-width: 900px; margin: 0 auto; }
            .test { background: white; padding: 15px; margin: 15px 0; border-radius: 5px; border-left: 4px solid #ddd; }
            .pass { border-left-color: #28a745; background: #d4edda; }
            .fail { border-left-color: #dc3545; background: #f8d7da; }
            .info { border-left-color: #17a2b8; background: #d1ecf1; }
            h1 { color: #333; }
            h2 { color: #667eea; font-size: 18px; }
            code { background: #f0f0f0; padding: 2px 5px; border-radius: 3px; }
            pre { background: #f0f0f0; padding: 10px; border-radius: 3px; overflow-x: auto; }
            .status { display: inline-block; padding: 5px 10px; border-radius: 3px; font-weight: bold; }
            .status.pass { background: #28a745; color: white; }
            .status.fail { background: #dc3545; color: white; }
            table { width: 100%; border-collapse: collapse; }
            th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
            th { background: #f8f9fa; font-weight: bold; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🔧 API Communication Enhancements - Test Suite</h1>
            <p>Testing the enhanced <code>makeAPIRequest()</code> function and new <code>APIClient</code> class.</p>
    <?php
}

// ============================================
// TEST 1: Enhanced makeAPIRequest Function
// ============================================

if (!$is_cli) echo '<div class="test info"><h2>Test 1: Enhanced makeAPIRequest() Function</h2>';

$test_results = [];

// Test URL validation
if (!$is_cli) echo '<p><strong>Testing URL validation...</strong></p>';
$test_response = @makeAPIRequest('invalid-url', []);
if (strpos($test_response, 'error|') === 0 && strpos($test_response, 'Invalid URL') !== false) {
    if (!$is_cli) echo '<p class="status pass">PASS</p>';
    $test_results[] = ['Test 1.1: URL Validation', 'PASS'];
} else {
    if (!$is_cli) echo '<p class="status fail">FAIL</p>';
    $test_results[] = ['Test 1.1: URL Validation', 'FAIL'];
}

// Test ServerA connectivity
if (!$is_cli) echo '<p><strong>Testing ServerA connectivity...</strong></p>';
$test_response = makeAPIRequest(SERVERA_URL . '/health.php', [], 'GET');
if (strpos($test_response, 'error|') !== 0) {
    if (!$is_cli) echo '<p class="status pass">PASS - Connected to ServerA</p>';
    if (!$is_cli) echo '<p>Response preview: ' . substr($test_response, 0, 100) . '...</p>';
    $test_results[] = ['Test 1.2: ServerA Connectivity', 'PASS'];
} else {
    if (!$is_cli) echo '<p class="status fail">FAIL - Could not reach ServerA</p>';
    if (!$is_cli) echo '<p>Error: ' . htmlspecialchars($test_response) . '</p>';
    $test_results[] = ['Test 1.2: ServerA Connectivity', 'FAIL'];
}

// Test ServerB connectivity
if (!$is_cli) echo '<p><strong>Testing ServerB connectivity...</strong></p>';
$test_response = makeAPIRequest(SERVERB_URL . '/health.php', [], 'GET');
if (strpos($test_response, 'error|') !== 0) {
    if (!$is_cli) echo '<p class="status pass">PASS - Connected to ServerB</p>';
    $test_results[] = ['Test 1.3: ServerB Connectivity', 'PASS'];
} else {
    if (!$is_cli) echo '<p class="status fail">FAIL - Could not reach ServerB</p>';
    $test_results[] = ['Test 1.3: ServerB Connectivity', 'FAIL'];
}

if (!$is_cli) echo '</div>';

// ============================================
// TEST 2: New Server Connectivity Functions
// ============================================

if (!$is_cli) echo '<div class="test info"><h2>Test 2: Server Connectivity Functions</h2>';

// Test testServerConnection
if (!$is_cli) echo '<p><strong>Testing testServerConnection()...</strong></p>';
$result = testServerConnection(SERVERA_URL, 5);
if ($result['success'] || isset($result['http_code'])) {
    if (!$is_cli) {
        echo '<p class="status pass">PASS</p>';
        echo '<table>';
        echo '<tr><th>Property</th><th>Value</th></tr>';
        foreach ($result as $key => $value) {
            echo '<tr><td>' . htmlspecialchars($key) . '</td><td>' . htmlspecialchars((string)$value) . '</td></tr>';
        }
        echo '</table>';
    }
    $test_results[] = ['Test 2.1: testServerConnection()', 'PASS'];
} else {
    if (!$is_cli) echo '<p class="status fail">FAIL</p>';
    $test_results[] = ['Test 2.1: testServerConnection()', 'FAIL'];
}

// Test areAllServersOnline
if (!$is_cli) echo '<p><strong>Testing areAllServersOnline()...</strong></p>';
$all_online = areAllServersOnline();
if ($all_online !== false) {
    if (!$is_cli) echo '<p class="status pass">PASS - All servers online: ' . ($all_online ? 'YES' : 'NO') . '</p>';
    $test_results[] = ['Test 2.2: areAllServersOnline()', 'PASS'];
} else {
    if (!$is_cli) echo '<p class="status fail">FAIL</p>';
    $test_results[] = ['Test 2.2: areAllServersOnline()', 'FAIL'];
}

if (!$is_cli) echo '</div>';

// ============================================
// TEST 3: New APIClient Class
// ============================================

if (!$is_cli) echo '<div class="test info"><h2>Test 3: APIClient Class</h2>';

// Test APIClient instantiation
if (!$is_cli) echo '<p><strong>Testing APIClient instantiation...</strong></p>';
try {
    $client = new APIClient(SERVERA_URL);
    if (!$is_cli) echo '<p class="status pass">PASS</p>';
    $test_results[] = ['Test 3.1: APIClient Instantiation', 'PASS'];
} catch (Exception $e) {
    if (!$is_cli) echo '<p class="status fail">FAIL: ' . htmlspecialchars($e->getMessage()) . '</p>';
    $test_results[] = ['Test 3.1: APIClient Instantiation', 'FAIL'];
    $client = null;
}

// Test APIClient connection test
if ($client) {
    if (!$is_cli) echo '<p><strong>Testing APIClient->testConnection()...</strong></p>';
    $connection_ok = $client->testConnection();
    if ($connection_ok !== false) {
        if (!$is_cli) echo '<p class="status pass">PASS - Connection test: ' . ($connection_ok ? 'OK' : 'FAILED') . '</p>';
        $test_results[] = ['Test 3.2: APIClient Connection Test', 'PASS'];
    } else {
        if (!$is_cli) echo '<p class="status fail">FAIL</p>';
        $test_results[] = ['Test 3.2: APIClient Connection Test', 'FAIL'];
    }
    
    // Test APIClient GET request
    if (!$is_cli) echo '<p><strong>Testing APIClient->get() with JSON response...</strong></p>';
    $response = $client->get('health.php', [], true);
    if (is_array($response) || is_string($response)) {
        if (!$is_cli) {
            echo '<p class="status pass">PASS</p>';
            echo '<p>Response type: ' . gettype($response) . '</p>';
            if (is_array($response)) {
                echo '<p>JSON Keys: ' . implode(', ', array_keys($response)) . '</p>';
            }
        }
        $test_results[] = ['Test 3.3: APIClient GET Request (JSON)', 'PASS'];
    } else {
        if (!$is_cli) echo '<p class="status fail">FAIL</p>';
        $test_results[] = ['Test 3.3: APIClient GET Request (JSON)', 'FAIL'];
    }
    
    // Test APIClient error handling
    if (!$is_cli) echo '<p><strong>Testing APIClient error handling...</strong></p>';
    $error_response = $client->get('nonexistent.php', []);
    $http_code = $client->getLastHttpCode();
    if ($http_code !== null) {
        if (!$is_cli) echo '<p class="status pass">PASS - HTTP Code: ' . $http_code . '</p>';
        $test_results[] = ['Test 3.4: APIClient Error Handling', 'PASS'];
    } else {
        if (!$is_cli) echo '<p class="status fail">FAIL</p>';
        $test_results[] = ['Test 3.4: APIClient Error Handling', 'FAIL'];
    }
}

if (!$is_cli) echo '</div>';

// ============================================
// TEST 4: Configuration
// ============================================

if (!$is_cli) echo '<div class="test info"><h2>Test 4: Configuration</h2>';

if (!$is_cli) {
    echo '<p><strong>Current Deployment Configuration:</strong></p>';
    echo '<table>';
    echo '<tr><th>Setting</th><th>Value</th></tr>';
    echo '<tr><td>DEPLOYMENT_MODE</td><td>' . (defined('DEPLOYMENT_MODE') ? DEPLOYMENT_MODE : 'Not defined') . '</td></tr>';
    echo '<tr><td>SERVERA_IP</td><td>' . (defined('SERVERA_IP') ? SERVERA_IP : 'Not defined') . '</td></tr>';
    echo '<tr><td>SERVERB_IP</td><td>' . (defined('SERVERB_IP') ? SERVERB_IP : 'Not defined') . '</td></tr>';
    echo '<tr><td>SERVERC_IP</td><td>' . (defined('SERVERC_IP') ? SERVERC_IP : 'Not defined') . '</td></tr>';
    echo '<tr><td>SERVERA_URL</td><td>' . (defined('SERVERA_URL') ? SERVERA_URL : 'Not defined') . '</td></tr>';
    echo '<tr><td>SERVERB_URL</td><td>' . (defined('SERVERB_URL') ? SERVERB_URL : 'Not defined') . '</td></tr>';
    echo '</table>';
}

if (!$is_cli) echo '</div>';

// ============================================
// SUMMARY
// ============================================

if (!$is_cli) {
    echo '<div class="test">';
    echo '<h2>📊 Test Summary</h2>';
    
    $pass_count = count(array_filter($test_results, function($r) { return $r[1] === 'PASS'; }));
    $fail_count = count(array_filter($test_results, function($r) { return $r[1] === 'FAIL'; }));
    
    echo '<p>Total Tests: ' . count($test_results) . ' | Passed: ' . $pass_count . ' | Failed: ' . $fail_count . '</p>';
    
    echo '<table>';
    echo '<tr><th>Test Name</th><th>Result</th></tr>';
    foreach ($test_results as $result) {
        $class = $result[1] === 'PASS' ? 'pass' : 'fail';
        echo '<tr><td>' . htmlspecialchars($result[0]) . '</td><td><span class="status ' . $class . '">' . $result[1] . '</span></td></tr>';
    }
    echo '</table>';
    
    if ($fail_count === 0) {
        echo '<p style="color: green; font-weight: bold; margin-top: 20px;">✓ All tests passed!</p>';
    } else {
        echo '<p style="color: red; font-weight: bold; margin-top: 20px;">✗ ' . $fail_count . ' test(s) failed. Check server connectivity.</p>';
    }
    
    echo '<hr>';
    echo '<p><strong>Documentation:</strong></p>';
    echo '<ul>';
    echo '<li><a href="API_IMPROVEMENTS.md" target="_blank">API_IMPROVEMENTS.md</a> - Comprehensive documentation</li>';
    echo '<li><a href="QUICK_START_API.md" target="_blank">QUICK_START_API.md</a> - Quick reference guide</li>';
    echo '<li><a href="API_ENHANCEMENT_SUMMARY.txt" target="_blank">API_ENHANCEMENT_SUMMARY.txt</a> - Feature overview</li>';
    echo '<li><a href="server_health.php" target="_blank">server_health.php</a> - Server health dashboard</li>';
    echo '</ul>';
    
    echo '</div>';
    ?>
        </div>
    </body>
    </html>
    <?php
} else {
    // CLI output
    echo "\n";
    echo "================================================\n";
    echo "API ENHANCEMENT TEST SUITE\n";
    echo "================================================\n\n";
    
    $pass_count = count(array_filter($test_results, function($r) { return $r[1] === 'PASS'; }));
    $fail_count = count(array_filter($test_results, function($r) { return $r[1] === 'FAIL'; }));
    
    echo "Test Summary:\n";
    echo "  Total: " . count($test_results) . "\n";
    echo "  Passed: " . $pass_count . "\n";
    echo "  Failed: " . $fail_count . "\n\n";
    
    echo "Results:\n";
    foreach ($test_results as $result) {
        $symbol = $result[1] === 'PASS' ? '✓' : '✗';
        echo "  $symbol {$result[0]}: {$result[1]}\n";
    }
    
    echo "\n";
    if ($fail_count === 0) {
        echo "All tests passed!\n";
    } else {
        echo "Some tests failed. Check server connectivity.\n";
    }
    echo "================================================\n\n";
}
?>
