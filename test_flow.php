<?php
/**
 * Test Script to Verify Complete User Flow
 * 
 * This script tests the complete user journey:
 * 1. Registration
 * 2. Login
 * 3. Session handling
 * 4. Reporting items
 */

require_once 'ServerC/config.php';

echo "<h1>Lost & Found System Test</h1>";

// Test 1: Database Connection
echo "<h2>1. Database Connection Test</h2>";
$conn = getDBConnection();
if ($conn) {
    echo "✅ Database connection successful<br>";
    $conn->close();
} else {
    echo "❌ Database connection failed<br>";
}

// Test 2: API Endpoints
echo "<h2>2. API Endpoints Test</h2>";
foreach ($api_endpoints as $name => $url) {
    echo "📡 $name: $url<br>";
}

// Test 3: Session Functions
echo "<h2>3. Session Functions Test</h2>";
echo "User logged in: " . (isUserLoggedIn() ? "✅ Yes" : "❌ No") . "<br>";
echo "Current User ID: " . (getCurrentUserId() ?? "None") . "<br>";
echo "Current Username: " . (getCurrentUsername() ?? "None") . "<br>";
echo "Current User Email: " . (getCurrentUserEmail() ?? "None") . "<br>";
echo "Is Admin: " . (isCurrentUserAdmin() ? "✅ Yes" : "❌ No") . "<br>";

// Test 4: File Permissions
echo "<h2>4. File Permissions Test</h2>";
$upload_dir = 'ServerB/uploads/';
if (is_dir($upload_dir)) {
    echo "✅ Upload directory exists<br>";
    if (is_writable($upload_dir)) {
        echo "✅ Upload directory is writable<br>";
    } else {
        echo "❌ Upload directory is not writable<br>";
    }
} else {
    echo "❌ Upload directory does not exist<br>";
}

// Test 5: Navigation Links
echo "<h2>5. Navigation Links Test</h2>";
$pages = [
    'Home' => 'ServerC/index.php',
    'Register' => 'ServerC/user_register.php',
    'Login' => 'ServerC/user_login.php',
    'Report Lost' => 'ServerC/report_lost.php',
    'Report Found' => 'ServerC/report_found.php',
    'View Items' => 'ServerC/items.php',
    'User Dashboard' => 'ServerC/user_dashboard.php'
];

foreach ($pages as $name => $path) {
    if (file_exists($path)) {
        echo "✅ $name ($path)<br>";
    } else {
        echo "❌ $name ($path) - File not found<br>";
    }
}

echo "<h2>6. Test Instructions</h2>";
echo "<ol>";
echo "<li>Go to <a href='ServerC/user_register.php'>Registration Page</a> and create a new account</li>";
echo "<li>You should be automatically redirected to login page with success message</li>";
echo "<li>Login with your credentials - you should be redirected to dashboard</li>";
echo "<li>Try reporting a <a href='ServerC/report_lost.php'>lost item</a> or <a href='ServerC/report_found.php'>found item</a></li>";
echo "<li>Check <a href='ServerC/items.php'>View Items</a> to see your reported items</li>";
echo "<li>Test logout from dashboard</li>";
echo "</ol>";

?>
