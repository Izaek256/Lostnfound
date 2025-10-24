<?php
// Test API calls from ServerC to ServerB via web browser
require_once 'ServerC/config.php';

// Test the get_items API call
echo "<h2>Testing API call to ServerB...</h2>";

// Test with no parameters
echo "<h3>API Response (no parameters):</h3>";
$itemsData = makeAPICall('get_items', null, 'GET');
echo "<pre>";
print_r($itemsData);
echo "</pre>";

// Test with type parameter
echo "<h3>API Response with type=lost:</h3>";
$itemsData = makeAPICall('get_items?type=lost', null, 'GET');
echo "<pre>";
print_r($itemsData);
echo "</pre>";

echo "<h3>Direct CURL test:</h3>";
$url = "http://localhost:8081/api/get_items.php";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p>HTTP Code: " . $httpCode . "</p>";
echo "<p>Response: " . htmlspecialchars($response) . "</p>";
?>