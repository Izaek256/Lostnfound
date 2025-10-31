<?php
/**
 * Test ServerC Network Connectivity
 * Diagnose why ServerC cannot connect to the database
 */

echo "ServerC Network Connectivity Test\n";
echo str_repeat("=", 50) . "\n\n";

// Load ServerC configuration
require_once 'ServerC/deployment_config.php';

echo "ServerC Configuration:\n";
echo "- ServerC IP: " . SERVERC_IP . "\n";
echo "- Database Host: " . DB_HOST . "\n";
echo "- Database Name: " . DB_NAME . "\n";
echo "- Database User: " . DB_USER . "\n\n";

echo "Network Connectivity Tests:\n";
echo str_repeat("-", 30) . "\n";

// Test 1: Check if we can reach the database host
echo "1. Testing ping to database host (" . DB_HOST . "):\n";
$ping_result = exec("ping -n 1 " . DB_HOST . " 2>&1", $ping_output, $ping_return);
if ($ping_return === 0) {
    echo "   ✅ Ping successful\n";
} else {
    echo "   ❌ Ping failed\n";
    echo "   Output: " . implode("\n   ", $ping_output) . "\n";
}
echo "\n";

// Test 2: Test MySQL port connectivity
echo "2. Testing MySQL port (3306) connectivity:\n";
$socket = @fsockopen(DB_HOST, 3306, $errno, $errstr, 5);
if ($socket) {
    echo "   ✅ MySQL port 3306 is reachable\n";
    fclose($socket);
} else {
    echo "   ❌ MySQL port 3306 is not reachable\n";
    echo "   Error: $errstr ($errno)\n";
}
echo "\n";

// Test 3: Try database connection with detailed error reporting
echo "3. Testing database connection:\n";
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $start_time = microtime(true);
    $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $connection_time = round((microtime(true) - $start_time) * 1000, 2);
    
    if ($conn) {
        echo "   ✅ Database connection successful ({$connection_time}ms)\n";
        
        // Test query
        $result = mysqli_query($conn, "SELECT VERSION() as version, NOW() as current_time");
        if ($result) {
            $row = mysqli_fetch_assoc($result);
            echo "   ✅ MySQL Version: " . $row['version'] . "\n";
            echo "   ✅ Server Time: " . $row['current_time'] . "\n";
            mysqli_free_result($result);
        }
        
        mysqli_close($conn);
    }
} catch (mysqli_sql_exception $e) {
    echo "   ❌ Database connection failed\n";
    echo "   Error: " . $e->getMessage() . "\n";
    echo "   Code: " . $e->getCode() . "\n";
}
echo "\n";

echo "Troubleshooting Guide:\n";
echo str_repeat("-", 30) . "\n";
echo "If ServerC cannot connect to the database:\n\n";

echo "1. Network Issues:\n";
echo "   - Check if ServerC (192.168.72.170) can reach ServerB (192.168.72.225)\n";
echo "   - Verify both computers are on the same network\n";
echo "   - Check firewall settings on both computers\n\n";

echo "2. MySQL Configuration Issues:\n";
echo "   - MySQL might only allow localhost connections\n";
echo "   - Check MySQL bind-address in my.cnf/my.ini\n";
echo "   - Ensure MySQL user has permissions from remote hosts\n\n";

echo "3. Possible Solutions:\n";
echo "   a) Allow remote MySQL connections:\n";
echo "      - Edit MySQL config: bind-address = 0.0.0.0\n";
echo "      - Grant permissions: GRANT ALL ON lostfound_db.* TO 'root'@'192.168.72.170';\n";
echo "      - Or use: GRANT ALL ON lostfound_db.* TO 'root'@'%';\n\n";
echo "   b) Alternative: Use SSH tunnel or VPN\n\n";
echo "   c) Alternative: Run ServerC database locally and sync data\n\n";

echo "Current Setup Analysis:\n";
echo "- ServerA & ServerB: Same computer (192.168.72.225) ✅\n";
echo "- ServerC: Different computer (192.168.72.170) ⚠️\n";
echo "- Database: On ServerB computer (192.168.72.225) 📍\n";
echo "- Issue: Cross-computer database access 🔍\n";
?>