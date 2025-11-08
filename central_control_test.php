<?php
/**
 * CENTRAL CONTROL TESTING INTERFACE
 * 
 * Test all server connectivity, database access, and API communication
 * from a single unified interface
 */

require_once 'CentralControl.php';

// Initialize central control (debug mode enabled)
$cc = getCentralControl(true);

// Determine if running from web or CLI
$is_web = php_sapi_name() !== 'cli';

if ($is_web) {
    header('Content-Type: text/html; charset=utf-8');
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Central Control Test - Lost & Found</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { 
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                padding: 20px;
            }
            .container { max-width: 1200px; margin: 0 auto; }
            .header {
                background: white;
                border-radius: 12px;
                padding: 30px;
                margin-bottom: 20px;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                text-align: center;
            }
            .header h1 { font-size: 28px; margin-bottom: 10px; color: #1f2937; }
            .section {
                background: white;
                border-radius: 12px;
                padding: 25px;
                margin-bottom: 20px;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            }
            .section h2 {
                color: #667eea;
                font-size: 20px;
                margin-bottom: 15px;
                border-bottom: 2px solid #f0f0f0;
                padding-bottom: 10px;
            }
            .test-item {
                padding: 12px;
                margin: 10px 0;
                border-radius: 6px;
                border-left: 4px solid #ddd;
            }
            .test-item.success {
                background: #d4edda;
                border-left-color: #28a745;
            }
            .test-item.error {
                background: #f8d7da;
                border-left-color: #dc3545;
            }
            .test-item.info {
                background: #d1ecf1;
                border-left-color: #17a2b8;
            }
            .test-item.warning {
                background: #fff3cd;
                border-left-color: #ffc107;
            }
            .label { font-weight: 600; color: #333; }
            .value { color: #666; margin-top: 5px; }
            .code { 
                background: #f5f5f5;
                padding: 2px 6px;
                border-radius: 3px;
                font-family: 'Courier New', monospace;
                font-size: 12px;
            }
            .status-badge {
                display: inline-block;
                padding: 6px 12px;
                border-radius: 20px;
                font-weight: 600;
                font-size: 12px;
                margin-left: 10px;
            }
            .badge-online { background: #d4edda; color: #155724; }
            .badge-offline { background: #f8d7da; color: #721c24; }
            .badge-healthy { background: #d4edda; color: #155724; }
            .badge-degraded { background: #fff3cd; color: #856404; }
            .badge-critical { background: #f8d7da; color: #721c24; }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 15px;
            }
            th, td {
                border: 1px solid #ddd;
                padding: 12px;
                text-align: left;
            }
            th {
                background: #f8f9fa;
                font-weight: 600;
            }
            .button-group {
                display: flex;
                gap: 10px;
                margin-top: 15px;
            }
            button {
                background: #667eea;
                color: white;
                border: none;
                padding: 10px 20px;
                border-radius: 6px;
                cursor: pointer;
                font-size: 14px;
                font-weight: 600;
            }
            button:hover { background: #5568d3; }
            .footer { color: white; text-align: center; margin-top: 20px; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>🔧 Central Control System - Test Interface</h1>
                <p>Unified server connectivity, database access, and API testing</p>
            </div>

            <?php
            
            // DEPLOYMENT CONFIGURATION
            ?>
            <div class="section">
                <h2>📋 Deployment Configuration</h2>
                <?php
                $deployment = $cc->getDeploymentInfo();
                ?>
                <div class="test-item info">
                    <div class="label">Deployment Mode: <span class="code"><?php echo htmlspecialchars($deployment['mode']); ?></span></div>
                    <div class="value">Is Local: <?php echo $deployment['is_local'] ? '✓ Yes' : '✗ No'; ?></div>
                    <div class="value">Is Production: <?php echo $deployment['is_production'] ? '✓ Yes' : '✗ No'; ?></div>
                </div>
                
                <table>
                    <tr>
                        <th>Component</th>
                        <th>Configuration</th>
                    </tr>
                    <tr>
                        <td>ServerA (Auth)</td>
                        <td><span class="code"><?php echo htmlspecialchars($deployment['servera_ip']); ?></span></td>
                    </tr>
                    <tr>
                        <td>ServerB (DB/Files)</td>
                        <td><span class="code"><?php echo htmlspecialchars($deployment['serverb_ip']); ?></span></td>
                    </tr>
                    <tr>
                        <td>ServerC (UI)</td>
                        <td><span class="code"><?php echo htmlspecialchars($deployment['serverc_ip']); ?></span></td>
                    </tr>
                    <tr>
                        <td>Database Host</td>
                        <td><span class="code"><?php echo htmlspecialchars($deployment['db_host']); ?></span></td>
                    </tr>
                    <tr>
                        <td>Database Name</td>
                        <td><span class="code"><?php echo htmlspecialchars($deployment['db_name']); ?></span></td>
                    </tr>
                </table>
            </div>

            <?php
            
            // HEALTH CHECK
            ?>
            <div class="section">
                <h2>❤️ Server Health Check</h2>
                <?php
                $health_results = $cc->health()->testAll();
                $summary = $cc->health()->getSummary();
                ?>
                
                <div class="test-item <?php echo $summary['all_online'] ? 'success' : 'warning'; ?>">
                    <div class="label">Overall Status
                        <span class="status-badge <?php echo $summary['all_online'] ? 'badge-online' : 'badge-offline'; ?>">
                            <?php echo $summary['online_count']; ?>/<?php echo $summary['total_servers']; ?> Online
                        </span>
                    </div>
                </div>
                
                <table>
                    <tr>
                        <th>Server</th>
                        <th>Status</th>
                        <th>Response Time</th>
                        <th>HTTP Code</th>
                        <th>Details</th>
                    </tr>
                    <?php foreach ($health_results as $server => $result): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($server); ?></strong></td>
                        <td>
                            <span class="status-badge <?php echo $result['online'] ? 'badge-online' : 'badge-offline'; ?>">
                                <?php echo $result['online'] ? '✓ Online' : '✗ Offline'; ?>
                            </span>
                        </td>
                        <td><?php echo $result['response_time']; ?> ms</td>
                        <td><?php echo htmlspecialchars((string)$result['http_code']); ?></td>
                        <td>
                            <?php if ($result['error']): ?>
                                Error: <?php echo htmlspecialchars($result['error']); ?>
                            <?php elseif ($result['response']): ?>
                                Database: <?php echo htmlspecialchars($result['response']['database'] ?? 'Unknown'); ?>
                            <?php else: ?>
                                No response data
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>

            <?php
            
            // DATABASE CONNECTIVITY
            ?>
            <div class="section">
                <h2>🗄️ Database Connectivity Test</h2>
                <?php
                $db = $cc->db();
                if ($db) {
                    // Test basic query
                    $rows = $db->getRows("SELECT 1 as test_value");
                    if ($rows !== null && count($rows) > 0) {
                        echo '<div class="test-item success">';
                        echo '<div class="label">✓ Database Connection Successful</div>';
                        echo '<div class="value">Connected to: ' . htmlspecialchars(defined('DB_HOST') ? DB_HOST : 'unknown') . '</div>';
                        echo '<div class="value">Database: ' . htmlspecialchars(defined('DB_NAME') ? DB_NAME : 'unknown') . '</div>';
                        echo '</div>';
                        
                        // List tables
                        $tables = $db->getRows("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE()");
                        if ($tables) {
                            echo '<div class="test-item info">';
                            echo '<div class="label">Available Tables</div>';
                            echo '<div class="value">';
                            foreach ($tables as $table) {
                                echo '<span class="code">' . htmlspecialchars($table['TABLE_NAME']) . '</span> ';
                            }
                            echo '</div></div>';
                        }
                    } else {
                        echo '<div class="test-item error">';
                        echo '<div class="label">✗ Database Query Failed</div>';
                        echo '</div>';
                    }
                } else {
                    echo '<div class="test-item error">';
                    echo '<div class="label">✗ Database Connection Failed</div>';
                    echo '</div>';
                }
                ?>
            </div>

            <?php
            
            // API CONNECTIVITY
            ?>
            <div class="section">
                <h2>🌐 API Request Testing</h2>
                <?php
                $api = $cc->api('ServerA');
                $api_test = $api->get('health.php', [], true);
                
                if ($api->isSuccess()) {
                    echo '<div class="test-item success">';
                    echo '<div class="label">✓ API Request Successful</div>';
                    echo '<div class="value">HTTP Code: ' . $api->getLastHttpCode() . '</div>';
                    if (is_array($api_test)) {
                        echo '<div class="value">Response Keys: ' . implode(', ', array_keys($api_test)) . '</div>';
                    }
                    echo '</div>';
                } else {
                    echo '<div class="test-item error">';
                    echo '<div class="label">✗ API Request Failed</div>';
                    echo '<div class="value">Error: ' . htmlspecialchars($api->getLastError() ?? 'Unknown error') . '</div>';
                    echo '<div class="value">HTTP Code: ' . $api->getLastHttpCode() . '</div>';
                    echo '</div>';
                }
                ?>
            </div>

            <?php
            
            // OVERALL STATUS
            ?>
            <div class="section">
                <h2>📊 System Overview</h2>
                <?php
                $status = $cc->status()->getAll();
                $health = $status['overall_health'];
                $badge_class = 'badge-' . $health;
                ?>
                
                <table>
                    <tr>
                        <th>Metric</th>
                        <th>Value</th>
                    </tr>
                    <tr>
                        <td>Overall Health</td>
                        <td>
                            <span class="status-badge <?php echo $badge_class; ?>">
                                <?php echo ucfirst($health); ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td>Deployment Mode</td>
                        <td><span class="code"><?php echo htmlspecialchars($status['deployment']['mode']); ?></span></td>
                    </tr>
                    <tr>
                        <td>Timestamp</td>
                        <td><?php echo htmlspecialchars($status['timestamp']); ?></td>
                    </tr>
                    <tr>
                        <td>Log File</td>
                        <td><span class="code"><?php echo htmlspecialchars($cc->getLogFile()); ?></span></td>
                    </tr>
                </table>
                
                <div class="button-group">
                    <button onclick="location.reload()">🔄 Refresh All Tests</button>
                    <button onclick="window.open('<?php echo $cc->getLogFile(); ?>', '_blank')">📋 View Logs</button>
                </div>
            </div>

            <div class="footer">
                <p>Central Control System v1.0 - One File, All Connectivity</p>
                <p>For usage examples, see: <span class="code">$cc->db()</span>, <span class="code">$cc->api()</span>, <span class="code">$cc->health()</span></p>
            </div>
        </div>
    </body>
    </html>
    <?php
    
} else {
    
    // CLI OUTPUT
    echo "\n";
    echo "================================================\n";
    echo "CENTRAL CONTROL SYSTEM - TEST SUITE\n";
    echo "================================================\n\n";
    
    // Deployment Info
    $deployment = $cc->getDeploymentInfo();
    echo "DEPLOYMENT CONFIGURATION\n";
    echo "Mode: {$deployment['mode']}\n";
    echo "ServerA: {$deployment['servera_ip']}\n";
    echo "ServerB: {$deployment['serverb_ip']}\n";
    echo "ServerC: {$deployment['serverc_ip']}\n";
    echo "Database: {$deployment['db_host']}:{$deployment['db_name']}\n";
    echo "\n";
    
    // Health Check
    echo "SERVER HEALTH CHECK\n";
    $health_results = $cc->health()->testAll();
    $summary = $cc->health()->getSummary();
    foreach ($health_results as $server => $result) {
        $status = $result['online'] ? '✓ ONLINE' : '✗ OFFLINE';
        echo "  $server: $status ({$result['response_time']}ms, HTTP {$result['http_code']})\n";
    }
    echo "  Overall: {$summary['online_count']}/{$summary['total_servers']} servers online\n";
    echo "\n";
    
    // Database Test
    echo "DATABASE CONNECTIVITY\n";
    $db = $cc->db();
    if ($db) {
        $rows = $db->getRows("SELECT 1 as test");
        if ($rows !== null) {
            echo "  ✓ Connection successful\n";
        } else {
            echo "  ✗ Query failed\n";
        }
    } else {
        echo "  ✗ Connection failed\n";
    }
    echo "\n";
    
    // API Test
    echo "API CONNECTIVITY\n";
    $api = $cc->api('ServerA');
    $api_test = $api->get('health.php', [], true);
    if ($api->isSuccess()) {
        echo "  ✓ API request successful (HTTP {$api->getLastHttpCode()})\n";
    } else {
        echo "  ✗ API request failed: {$api->getLastError()}\n";
    }
    echo "\n";
    
    // Overall Status
    $status = $cc->status()->getAll();
    echo "OVERALL SYSTEM STATUS: " . strtoupper($status['overall_health']) . "\n";
    echo "================================================\n\n";
}

// Close connections
$cc->closeAllConnections();

?>
