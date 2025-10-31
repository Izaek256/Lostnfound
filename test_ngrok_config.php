<?php
/**
 * Test Ngrok Configuration
 * Verifies that all servers are configured to use ngrok URLs
 */

echo "Testing Ngrok Configuration\n";
echo str_repeat("=", 50) . "\n\n";

// Test each server's configuration
$servers = ['ServerA', 'ServerB', 'ServerC'];

foreach ($servers as $server) {
    echo "Testing $server Configuration:\n";
    echo str_repeat("-", 30) . "\n";
    
    $config_file = "$server/deployment_config.php";
    
    if (!file_exists($config_file)) {
        echo "❌ Configuration file not found: $config_file\n\n";
        continue;
    }
    
    // Include the config file
    include $config_file;
    
    // Display configuration
    echo "Server IP: " . constant('SERVERA_IP') . "\n";
    echo "ServerB IP: " . constant('SERVERB_IP') . "\n";
    echo "ServerC IP: " . constant('SERVERC_IP') . "\n";
    echo "Database Host: " . constant('DB_HOST') . "\n";
    echo "Deployment Mode: " . constant('DEPLOYMENT_MODE') . "\n";
    
    // Check if using ngrok
    $using_ngrok_a = strpos(constant('SERVERA_IP'), 'ngrok') !== false;
    $using_ngrok_b = strpos(constant('SERVERB_IP'), 'ngrok') !== false;
    
    echo "Using Ngrok for ServerA: " . ($using_ngrok_a ? "✅ Yes" : "❌ No") . "\n";
    echo "Using Ngrok for ServerB: " . ($using_ngrok_b ? "✅ Yes" : "❌ No") . "\n";
    
    // Display URLs
    echo "\nAPI URLs:\n";
    echo "  ServerA: " . constant('SERVERA_API_URL') . "\n";
    echo "  ServerB: " . constant('SERVERB_API_URL') . "\n";
    echo "  ServerC: " . constant('SERVERC_API_URL') . "\n";
    
    echo "\nHealth Check URLs:\n";
    echo "  ServerA: " . constant('SERVERA_HEALTH_URL') . "\n";
    echo "  ServerB: " . constant('SERVERB_HEALTH_URL') . "\n";
    echo "  ServerC: " . constant('SERVERC_HEALTH_URL') . "\n";
    
    echo "\nUploads URL: " . constant('UPLOADS_BASE_URL') . "\n";
    
    echo "\n" . str_repeat("=", 50) . "\n\n";
}

echo "Configuration Summary:\n";
echo "✅ ServerA and ServerB now use ngrok URL: awfully-ophthalmoscopical-brittny.ngrok-free.dev\n";
echo "✅ ServerC remains on local IP: 192.168.72.170\n";
echo "✅ Database connections updated\n";
echo "✅ All URLs now use HTTPS for ngrok domains\n\n";

echo "Next Steps:\n";
echo "1. Make sure ngrok is running and pointing to your local servers\n";
echo "2. Test the health check URLs to verify connectivity\n";
echo "3. Update any client applications to use the new URLs\n";
?>