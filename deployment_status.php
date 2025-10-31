<?php
/**
 * Deployment Status & Testing Utility
 * 
 * Check deployment status, test connectivity, and validate configuration
 */

require_once 'deploy.php';

// ============================================
// STATUS CHECKING FUNCTIONS
// ============================================

function checkServerStatus($server_name) {
    $config_file = "$server_name/deployment_config.php";
    
    $status = [
        'name' => $server_name,
        'config_exists' => file_exists($config_file),
        'config_readable' => false,
        'config_valid' => false,
        'deployment_mode' => 'unknown',
        'last_updated' => 'unknown',
        'errors' => []
    ];
    
    if ($status['config_exists']) {
        $status['config_readable'] = is_readable($config_file);
        
        if ($status['config_readable']) {
            // Try to extract info from config file
            $content = file_get_contents($config_file);
            
            // Extract deployment mode
            if (preg_match("/define\('DEPLOYMENT_MODE', '([^']+)'\)/", $content, $matches)) {
                $status['deployment_mode'] = $matches[1];
            }
            
            // Extract generation date
            if (preg_match("/Generated on: ([^\n]+)/", $content, $matches)) {
                $status['last_updated'] = trim($matches[1]);
            }
            
            // Check if config is valid by parsing content instead of including
            try {
                // Check for required constants in the file content
                $required_constants = ['SERVERA_IP', 'SERVERB_IP', 'SERVERC_IP', 'DB_HOST', 'DB_NAME'];
                $found_constants = 0;
                
                foreach ($required_constants as $constant) {
                    if (preg_match("/define\('$constant',\s*'[^']+'\)/", $content)) {
                        $found_constants++;
                    }
                }
                
                if ($found_constants >= count($required_constants)) {
                    $status['config_valid'] = true;
                } else {
                    $status['errors'][] = "Missing required constants (found $found_constants/" . count($required_constants) . ")";
                }
                
                // Check for syntax errors by validating PHP syntax
                $temp_file = tempnam(sys_get_temp_dir(), 'config_check');
                file_put_contents($temp_file, $content);
                
                $output = [];
                $return_code = 0;
                exec("php -l \"$temp_file\" 2>&1", $output, $return_code);
                
                if ($return_code !== 0) {
                    $status['errors'][] = 'PHP syntax errors detected';
                }
                
                unlink($temp_file);
                
            } catch (Exception $e) {
                $status['errors'][] = 'Config validation error: ' . $e->getMessage();
            }
        } else {
            $status['errors'][] = 'Config file is not readable';
        }
    } else {
        $status['errors'][] = 'Config file does not exist';
    }
    
    return $status;
}

function testServerConnectivity($server_ip, $server_name, $base_path = '/Lostnfound') {
    $test_urls = [
        'health' => "http://$server_ip$base_path/$server_name/health.php",
        'api_health' => "http://$server_ip$base_path/$server_name/api/health.php"
    ];
    
    $results = [
        'server' => $server_name,
        'ip' => $server_ip,
        'tests' => []
    ];
    
    foreach ($test_urls as $test_name => $url) {
        $start_time = microtime(true);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $response_time = round((microtime(true) - $start_time) * 1000, 2);
        curl_close($ch);
        
        $results['tests'][$test_name] = [
            'url' => $url,
            'success' => ($error === '' && $http_code == 200),
            'http_code' => $http_code,
            'response_time' => $response_time,
            'error' => $error,
            'response' => substr($response, 0, 200) // First 200 chars
        ];
    }
    
    return $results;
}

function testDatabaseConnection($config) {
    $result = [
        'success' => false,
        'error' => '',
        'host' => $config['db_host'],
        'database' => $config['db_name']
    ];
    
    try {
        $conn = mysqli_connect(
            $config['db_host'], 
            $config['db_user'], 
            $config['db_pass'], 
            $config['db_name']
        );
        
        if ($conn) {
            $result['success'] = true;
            
            // Test basic queries
            $tables_result = mysqli_query($conn, "SHOW TABLES");
            $result['tables_count'] = mysqli_num_rows($tables_result);
            
            mysqli_close($conn);
        } else {
            $result['error'] = mysqli_connect_error();
        }
    } catch (Exception $e) {
        $result['error'] = $e->getMessage();
    }
    
    return $result;
}

// ============================================
// DISPLAY FUNCTIONS
// ============================================

function displayStatusReport() {
    $config = getCurrentConfig();
    
    echo "<h2>🚀 Deployment Status Report</h2>\n";
    echo "<div style='background: #f5f5f5; padding: 15px; border-radius: 5px; margin: 10px 0;'>\n";
    echo "<strong>Current Mode:</strong> " . DEPLOYMENT_MODE . " - {$config['name']}<br>\n";
    echo "<strong>Generated:</strong> " . date('Y-m-d H:i:s') . "<br>\n";
    echo "</div>\n";
    
    // Check each server configuration
    echo "<h3>📋 Server Configuration Status</h3>\n";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
    echo "<tr style='background: #e0e0e0;'><th>Server</th><th>Config</th><th>Mode</th><th>Last Updated</th><th>Status</th></tr>\n";
    
    $servers = ['ServerA', 'ServerB', 'ServerC'];
    foreach ($servers as $server) {
        $status = checkServerStatus($server);
        
        $status_color = $status['config_valid'] ? 'green' : 'red';
        $status_text = $status['config_valid'] ? '✓ Valid' : '✗ Invalid';
        
        if (!empty($status['errors'])) {
            $status_text .= ' (' . implode(', ', $status['errors']) . ')';
        }
        
        echo "<tr>\n";
        echo "<td><strong>$server</strong></td>\n";
        echo "<td>" . ($status['config_exists'] ? '✓ Exists' : '✗ Missing') . "</td>\n";
        echo "<td>{$status['deployment_mode']}</td>\n";
        echo "<td>{$status['last_updated']}</td>\n";
        echo "<td style='color: $status_color;'>$status_text</td>\n";
        echo "</tr>\n";
    }
    echo "</table>\n";
    
    // Test database connectivity
    echo "<h3>🗄️ Database Connectivity</h3>\n";
    $db_test = testDatabaseConnection($config);
    
    if ($db_test['success']) {
        echo "<div style='color: green; background: #e8f5e8; padding: 10px; border-radius: 5px;'>\n";
        echo "✓ Database connection successful<br>\n";
        echo "Host: {$db_test['host']}<br>\n";
        echo "Database: {$db_test['database']}<br>\n";
        echo "Tables found: {$db_test['tables_count']}<br>\n";
        echo "</div>\n";
    } else {
        echo "<div style='color: red; background: #f5e8e8; padding: 10px; border-radius: 5px;'>\n";
        echo "✗ Database connection failed<br>\n";
        echo "Host: {$db_test['host']}<br>\n";
        echo "Error: {$db_test['error']}<br>\n";
        echo "</div>\n";
    }
    
    // Test server connectivity (only if not localhost)
    if (DEPLOYMENT_MODE !== 'local') {
        echo "<h3>🌐 Server Connectivity Tests</h3>\n";
        
        $connectivity_tests = [
            'ServerA' => testServerConnectivity($config['servera_ip'], 'ServerA'),
            'ServerB' => testServerConnectivity($config['serverb_ip'], 'ServerB'),
            'ServerC' => testServerConnectivity($config['serverc_ip'], 'ServerC')
        ];
        
        foreach ($connectivity_tests as $server => $test) {
            echo "<h4>$server ({$test['ip']})</h4>\n";
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
            echo "<tr style='background: #e0e0e0;'><th>Test</th><th>URL</th><th>Status</th><th>Response Time</th></tr>\n";
            
            foreach ($test['tests'] as $test_name => $result) {
                $status_color = $result['success'] ? 'green' : 'red';
                $status_text = $result['success'] ? "✓ OK ({$result['http_code']})" : "✗ Failed";
                
                if (!$result['success'] && $result['error']) {
                    $status_text .= " - {$result['error']}";
                }
                
                echo "<tr>\n";
                echo "<td>$test_name</td>\n";
                echo "<td style='font-size: 12px;'>{$result['url']}</td>\n";
                echo "<td style='color: $status_color;'>$status_text</td>\n";
                echo "<td>{$result['response_time']}ms</td>\n";
                echo "</tr>\n";
            }
            echo "</table>\n";
        }
    } else {
        echo "<div style='background: #e8f4fd; padding: 10px; border-radius: 5px; margin: 10px 0;'>\n";
        echo "ℹ️ <strong>Local Mode:</strong> Server connectivity tests skipped (all servers on localhost)\n";
        echo "</div>\n";
    }
    
    // Quick deployment guide
    echo "<h3>📖 Quick Deployment Guide</h3>\n";
    echo "<div style='background: #f0f8ff; padding: 15px; border-radius: 5px;'>\n";
    echo "<strong>To switch deployment modes:</strong><br>\n";
    echo "1. Edit <code>deploy.php</code> and change <code>DEPLOYMENT_MODE</code><br>\n";
    echo "2. Run: <code>php deploy.php</code><br>\n";
    echo "3. Check this page to verify deployment<br><br>\n";
    
    echo "<strong>Available modes:</strong><br>\n";
    echo "• <code>local</code> - All servers on localhost (development)<br>\n";
    echo "• <code>split</code> - Split across multiple computers<br>\n";
    echo "• <code>production</code> - Production deployment<br>\n";
    echo "</div>\n";
}

// ============================================
// WEB INTERFACE
// ============================================

if (php_sapi_name() !== 'cli') {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Lost & Found - Deployment Status</title>
        <meta charset="utf-8">
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            table { border-collapse: collapse; width: 100%; margin: 10px 0; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f2f2f2; }
            code { background: #f5f5f5; padding: 2px 4px; border-radius: 3px; }
            .refresh-btn { 
                background: #007cba; color: white; padding: 10px 20px; 
                text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px 0;
            }
        </style>
    </head>
    <body>
        <h1>Lost & Found System - Deployment Status</h1>
        
        <a href="?refresh=1" class="refresh-btn">🔄 Refresh Status</a>
        <a href="deploy.php" class="refresh-btn">⚙️ Run Deployment</a>
        
        <?php displayStatusReport(); ?>
        
        <hr>
        <p><small>Last checked: <?php echo date('Y-m-d H:i:s'); ?></small></p>
    </body>
    </html>
    <?php
} else {
    // Command line interface
    echo "Lost & Found - Deployment Status\n";
    echo str_repeat("=", 50) . "\n";
    displayStatusReport();
}
?>