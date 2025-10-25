<?php
/**
 * Debug API Test Page
 */

require_once 'config.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

echo "<h1>API Debug Test</h1>";

echo "<h2>Session Information</h2>";
echo "<pre>";
echo "Session ID: " . session_id() . "\n";
echo "Session Data: " . var_export($_SESSION, true) . "\n";
echo "User Logged In: " . (isUserLoggedIn() ? 'Yes' : 'No') . "\n";
echo "Current User ID: " . var_export(getCurrentUserId(), true) . "\n";
echo "Current Username: " . var_export(getCurrentUsername(), true) . "\n";
echo "Current User Email: " . var_export(getCurrentUserEmail(), true) . "\n";
echo "</pre>";

echo "<h2>API Test - Get Items</h2>";
echo "API URL being called: " . $GLOBALS['api_endpoints']['get_items'] . "<br><br>";

// Test direct file access first
$direct_path = "../ServerB/api/get_items.php";
echo "Direct file exists: " . (file_exists($direct_path) ? 'Yes' : 'No') . "<br>";

$result = makeAPICall('get_items');
echo "<pre>";
echo "API Result: " . var_export($result, true) . "\n";
echo "</pre>";

// Try alternative approach - direct include
echo "<h3>Alternative: Direct API Call</h3>";
if (file_exists($direct_path)) {
    echo "Trying direct include...<br>";
    ob_start();
    $_SERVER['REQUEST_METHOD'] = 'GET';
    include $direct_path;
    $direct_result = ob_get_clean();
    echo "Direct result: <pre>" . htmlspecialchars($direct_result) . "</pre>";
}

if (isset($result['items'])) {
    echo "<h3>Items Details</h3>";
    foreach ($result['items'] as $index => $item) {
        echo "<div style='border: 1px solid #ccc; margin: 10px; padding: 10px;'>";
        echo "<strong>Item #" . ($index + 1) . "</strong><br>";
        echo "ID: " . ($item['id'] ?? 'N/A') . "<br>";
        echo "User ID: " . var_export($item['user_id'] ?? 'NOT_SET', true) . "<br>";
        echo "Title: " . htmlspecialchars($item['title'] ?? 'N/A') . "<br>";
        echo "Type: " . ($item['type'] ?? 'N/A') . "<br>";
        echo "Created: " . ($item['created_at'] ?? 'N/A') . "<br>";
        echo "</div>";
    }
}

echo "<h2>Direct Database Test</h2>";
$conn = getDBConnection();
if ($conn) {
    echo "Database connection: <strong>SUCCESS</strong><br>";
    
    $result = $conn->query("SELECT COUNT(*) as total FROM items");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "Total items in database: <strong>" . $row['total'] . "</strong><br>";
    }
    
    $result = $conn->query("SELECT id, user_id, title, type, created_at FROM items ORDER BY created_at DESC LIMIT 5");
    if ($result) {
        echo "<h3>Recent Items from Database</h3>";
        while ($row = $result->fetch_assoc()) {
            echo "<div style='border: 1px solid #ccc; margin: 5px; padding: 5px;'>";
            echo "ID: " . $row['id'] . ", User ID: " . $row['user_id'] . ", Title: " . htmlspecialchars($row['title']) . ", Type: " . $row['type'];
            echo "</div>";
        }
    }
    
    $conn->close();
} else {
    echo "Database connection: <strong>FAILED</strong><br>";
}

echo "<br><a href='user_dashboard.php'>← Back to Dashboard</a>";
?>
