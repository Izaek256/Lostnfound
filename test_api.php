<?php
/**
 * API Endpoint Test Script
 * Tests if API endpoints are accessible
 */

// Test URLs
$test_urls = [
    'ServerA Health' => 'http://localhost/Lostnfound/ServerA/api/health.php',
    'ServerB Health' => 'http://localhost/Lostnfound/ServerB/api/health.php',
    'ServerA Add Item' => 'http://localhost/Lostnfound/ServerA/api/add_item.php',
    'ServerB Verify User' => 'http://localhost/Lostnfound/ServerB/api/verify_user.php',
    'ServerB Register User' => 'http://localhost/Lostnfound/ServerB/api/register_user.php',
];

echo "<h2>API Endpoint Test</h2>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Endpoint</th><th>URL</th><th>Status</th><th>Response</th></tr>";

foreach ($test_urls as $name => $url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $status = $http_code == 200 ? "✓ OK" : "✗ Error (HTTP $http_code)";
    $response_preview = strlen($response) > 100 ? substr($response, 0, 100) . "..." : $response;
    
    echo "<tr>";
    echo "<td><strong>$name</strong></td>";
    echo "<td><small>$url</small></td>";
    echo "<td>$status</td>";
    echo "<td><small>$response_preview</small></td>";
    echo "</tr>";
}

echo "</table>";
?>
