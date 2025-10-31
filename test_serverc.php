<?php
/**
 * Quick test for ServerC connectivity
 */

echo "Testing ServerC at 192.168.72.170...\n\n";

// Test basic HTTP connection
echo "1. Testing basic HTTP connection...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://192.168.72.170');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

$start_time = microtime(true);
$response = curl_exec($ch);
$response_time = round((microtime(true) - $start_time) * 1000, 2);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "   ✗ Connection failed: $error\n";
} else {
    echo "   ✓ HTTP connection successful (Status: $http_code, Time: {$response_time}ms)\n";
    echo "   Response length: " . strlen($response) . " bytes\n";
}

// Test health endpoint
echo "\n2. Testing health endpoint...\n";
$health_url = 'http://192.168.72.170/Lostnfound/ServerC/health.php';
echo "   URL: $health_url\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $health_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

$start_time = microtime(true);
$response = curl_exec($ch);
$response_time = round((microtime(true) - $start_time) * 1000, 2);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "   ✗ Health check failed: $error\n";
} elseif ($http_code == 200) {
    echo "   ✓ Health check successful (Time: {$response_time}ms)\n";
    echo "   Response: " . substr($response, 0, 200) . "\n";
} else {
    echo "   ✗ Health check returned HTTP $http_code\n";
    echo "   Response: " . substr($response, 0, 200) . "\n";
}

// Test API health endpoint
echo "\n3. Testing API health endpoint...\n";
$api_health_url = 'http://192.168.72.170/Lostnfound/ServerC/api/health.php';
echo "   URL: $api_health_url\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_health_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

$start_time = microtime(true);
$response = curl_exec($ch);
$response_time = round((microtime(true) - $start_time) * 1000, 2);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "   ✗ API health check failed: $error\n";
} elseif ($http_code == 200) {
    echo "   ✓ API health check successful (Time: {$response_time}ms)\n";
    echo "   Response: " . substr($response, 0, 200) . "\n";
} else {
    echo "   ✗ API health check returned HTTP $http_code\n";
    echo "   Response: " . substr($response, 0, 200) . "\n";
}

echo "\nTest complete.\n";
?>