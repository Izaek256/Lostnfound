<?php
/**
 * Test Database Connections for All Servers
 * Verifies that each server can connect to the database properly
 */

echo "Testing Database Connections\n";
echo str_repeat("=", 50) . "\n\n";

$servers = ['ServerA', 'ServerB', 'ServerC'];

foreach ($servers as $server) {
    echo "Testing $server Database Connection:\n";
    echo str_repeat("-", 30) . "\n";
    
    $config_file = "$server/deployment_config.php";
    
    if (!file_exists($config_file)) {
        echo "❌ Configuration file not found: $config_file\n\n";
        continue;
    }
    
    // Include the config file in isolation
    $constants_before = get_defined_constants(true)['user'] ?? [];
    include $config_file;
    $constants_after = get_defined_constants(true)['user'] ?? [];
    
    // Get the database configuration
    $db_host = constant('DB_HOST');
    $db_name = constant('DB_NAME');
    $db_user = constant('DB_USER');
    $db_pass = constant('DB_PASS');
    
    echo "Database Host: $db_host\n";
    echo "Database Name: $db_name\n";
    echo "Database User: $db_user\n";
    
    // Test the connection
    echo "Testing connection... ";
    
    try {
        $start_time = microtime(true);
        $conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
        $connection_time = round((microtime(true) - $start_time) * 1000, 2);
        
        if ($conn) {
            echo "✅ SUCCESS ({$connection_time}ms)\n";
            
            // Test a simple query
            $result = mysqli_query($conn, "SELECT 1 as test");
            if ($result) {
                echo "✅ Query test passed\n";
                mysqli_free_result($result);
            } else {
                echo "⚠️  Query test failed: " . mysqli_error($conn) . "\n";
            }
            
            mysqli_close($conn);
        } else {
            echo "❌ FAILED: " . mysqli_connect_error() . "\n";
        }
    } catch (Exception $e) {
        echo "❌ EXCEPTION: " . $e->getMessage() . "\n";
    }
    
    echo "\n" . str_repeat("=", 50) . "\n\n";
}

echo "Database Connection Summary:\n";
echo "✅ All servers should connect to database via local IP: 192.168.72.225\n";
echo "✅ ServerB hosts the database locally (localhost)\n";
echo "✅ ServerA and ServerC connect to ServerB via local network\n";
echo "❌ Ngrok URLs should NOT be used for database connections\n\n";

echo "If you see connection failures:\n";
echo "1. Check that MySQL is running on ServerB (192.168.72.225)\n";
echo "2. Verify network connectivity between servers\n";
echo "3. Ensure MySQL allows connections from other IPs\n";
echo "4. Check firewall settings\n";
?>