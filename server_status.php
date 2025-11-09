<?php
/**
 * Lost & Found Server Status Monitor
 * 
 * Real-time monitoring dashboard for all three servers
 * Shows health status, database connectivity, and live event logs
 */

require_once 'ServerC/config.php';
require_once 'ServerC/deployment_config.php';

// Disable output buffering for real-time streaming
// This allows sending data to browser progressively
@ob_end_clean();
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');
header('Content-Type: text/html; charset=UTF-8');
header('Connection: Keep-Alive');
header('X-Accel-Buffering: no');

// Initialize status arrays
$servers = [
    'ServerA' => [
        'name' => 'ServerA (Item Logic Server)',
        'url' => SERVERA_URL,
        'health_url' => SERVERA_HEALTH_URL,
        'role' => 'Item operations, item database access',
        'status' => 'checking...',
        'database' => 'checking...',
        'response_time' => 0,
        'http_code' => 0,
        'error' => '',
        'services' => []
    ],
    'ServerB' => [
        'name' => 'ServerB (User & Database Server)',
        'url' => SERVERB_URL,
        'health_url' => SERVERB_HEALTH_URL,
        'role' => 'User management, database host, file storage',
        'status' => 'checking...',
        'database' => 'checking...',
        'response_time' => 0,
        'http_code' => 0,
        'error' => '',
        'services' => []
    ],
    'ServerC' => [
        'name' => 'ServerC (User Interface Server)',
        'url' => SERVERC_API_URL,
        'health_url' => SERVERC_HEALTH_URL,
        'role' => 'Web interface, no database access',
        'status' => 'checking...',
        'database' => 'N/A',
        'response_time' => 0,
        'http_code' => 0,
        'error' => '',
        'services' => []
    ]
];

// Function to check server health
function checkServerHealth($health_url, $timeout = 5) {
    $start_time = microtime(true);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $health_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, min($timeout, 3));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    $elapsed_time = round((microtime(true) - $start_time) * 1000, 2);
    
    return [
        'success' => ($http_code === 200 && !$error),
        'http_code' => $http_code,
        'response_time' => $elapsed_time,
        'error' => $error,
        'data' => $response ? json_decode($response, true) : null
    ];
}

// Function to get database stats
function getDatabaseStats() {
    try {
        $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if (!$conn || $conn->connect_error) {
            return [
                'connected' => false,
                'users' => 0,
                'items' => 0,
                'error' => $conn->connect_error ?? 'Unknown error'
            ];
        }
        
        // Get user count
        $users_result = mysqli_query($conn, "SELECT COUNT(*) as count FROM users");
        $users = mysqli_fetch_assoc($users_result)['count'] ?? 0;
        
        // Get item counts
        $items_result = mysqli_query($conn, "SELECT COUNT(*) as count FROM items");
        $total_items = mysqli_fetch_assoc($items_result)['count'] ?? 0;
        
        $lost_result = mysqli_query($conn, "SELECT COUNT(*) as count FROM items WHERE type = 'lost'");
        $lost_items = mysqli_fetch_assoc($lost_result)['count'] ?? 0;
        
        $found_result = mysqli_query($conn, "SELECT COUNT(*) as count FROM items WHERE type = 'found'");
        $found_items = mysqli_fetch_assoc($found_result)['count'] ?? 0;
        
        mysqli_close($conn);
        
        return [
            'connected' => true,
            'users' => $users,
            'items' => $total_items,
            'lost' => $lost_items,
            'found' => $found_items,
            'error' => ''
        ];
    } catch (Exception $e) {
        return [
            'connected' => false,
            'users' => 0,
            'items' => 0,
            'error' => $e->getMessage()
        ];
    }
}

// Get current PHP/system info
function getServerInfo() {
    return [
        'php_version' => phpversion(),
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
        'memory_usage' => round(memory_get_usage() / 1024 / 1024, 2) . ' MB',
        'peak_memory' => round(memory_get_peak_usage() / 1024 / 1024, 2) . ' MB',
        'system_load' => function_exists('sys_getloadavg') ? implode(', ', array_map(function($x) { return round($x, 2); }, sys_getloadavg())) : 'N/A',
        'current_time' => date('Y-m-d H:i:s'),
        'deployment_mode' => DEPLOYMENT_MODE,
        'deployment_name' => DEPLOYMENT_NAME ?? 'Unknown'
    ];
}

// Check all servers
foreach ($servers as $key => $server) {
    $health = checkServerHealth($server['health_url']);
    
    $servers[$key]['status'] = $health['success'] ? 'ONLINE' : 'OFFLINE';
    $servers[$key]['http_code'] = $health['http_code'];
    $servers[$key]['response_time'] = $health['response_time'];
    $servers[$key]['error'] = $health['error'];
    
    if ($health['success'] && $health['data']) {
        $data = $health['data'];
        $servers[$key]['database'] = $data['database'] ?? 'unknown';
        $servers[$key]['services'] = $data['services'] ?? [];
    }
}

$db_stats = getDatabaseStats();
$server_info = getServerInfo();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Server Status Monitor - Lost & Found Portal</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Courier New', monospace;
            background: #0f172a;
            color: #e2e8f0;
            padding: 20px;
            min-height: 100vh;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        header {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            border-left: 5px solid #3b82f6;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
        }

        header h1 {
            font-size: 2rem;
            margin-bottom: 10px;
            color: #60a5fa;
        }

        header p {
            color: #94a3b8;
            margin: 5px 0;
        }

        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .server-card {
            background: #1e293b;
            border-radius: 8px;
            padding: 25px;
            border-left: 4px solid;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .server-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.3);
        }

        .server-card.online {
            border-left-color: #10b981;
            background: rgba(16, 185, 129, 0.05);
        }

        .server-card.offline {
            border-left-color: #ef4444;
            background: rgba(239, 68, 68, 0.05);
        }

        .server-card h3 {
            color: #60a5fa;
            margin-bottom: 15px;
            font-size: 1.2rem;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: bold;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-badge.online {
            background: rgba(16, 185, 129, 0.2);
            color: #10b981;
            border: 1px solid #10b981;
        }

        .status-badge.offline {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
            border: 1px solid #ef4444;
        }

        .status-badge.checking {
            background: rgba(245, 158, 11, 0.2);
            color: #f59e0b;
            border: 1px solid #f59e0b;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #334155;
            font-size: 0.9rem;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            color: #94a3b8;
            font-weight: 600;
        }

        .info-value {
            color: #e2e8f0;
            text-align: right;
            word-break: break-word;
        }

        .database-status {
            background: #0f172a;
            padding: 12px;
            border-radius: 6px;
            margin-top: 10px;
            border-left: 3px solid #3b82f6;
        }

        .services-list {
            background: #0f172a;
            padding: 12px;
            border-radius: 6px;
            margin-top: 10px;
            max-height: 200px;
            overflow-y: auto;
        }

        .service-item {
            padding: 6px 0;
            font-size: 0.85rem;
            border-bottom: 1px solid #334155;
            display: flex;
            justify-content: space-between;
        }

        .service-item:last-child {
            border-bottom: none;
        }

        .service-status {
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            background: rgba(16, 185, 129, 0.2);
            color: #10b981;
        }

        .response-time {
            color: #60a5fa;
            font-weight: bold;
        }

        .error-message {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid #ef4444;
            color: #fca5a5;
            padding: 12px;
            border-radius: 6px;
            margin-top: 10px;
            font-size: 0.85rem;
            word-break: break-word;
        }

        .database-section {
            background: #1e293b;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 30px;
            border-left: 4px solid #10b981;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
        }

        .database-section h3 {
            color: #60a5fa;
            margin-bottom: 20px;
            font-size: 1.2rem;
        }

        .database-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat-box {
            background: #0f172a;
            padding: 20px;
            border-radius: 6px;
            text-align: center;
            border: 1px solid #334155;
        }

        .stat-number {
            font-size: 2rem;
            color: #60a5fa;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 0.85rem;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .system-info {
            background: #1e293b;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 30px;
            border-left: 4px solid #8b5cf6;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
        }

        .system-info h3 {
            color: #60a5fa;
            margin-bottom: 20px;
            font-size: 1.2rem;
        }

        .system-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .event-log {
            background: #0f172a;
            border: 1px solid #334155;
            border-radius: 8px;
            padding: 20px;
            margin-top: 30px;
            max-height: 400px;
            overflow-y: auto;
        }

        .event-log h3 {
            color: #60a5fa;
            margin-bottom: 15px;
            font-size: 1.1rem;
        }

        .event-line {
            padding: 8px 0;
            border-bottom: 1px solid #334155;
            font-size: 0.85rem;
            color: #94a3b8;
            display: flex;
            gap: 10px;
        }

        .event-line:last-child {
            border-bottom: none;
        }

        .event-time {
            color: #60a5fa;
            white-space: nowrap;
            min-width: 120px;
        }

        .event-type {
            color: #a78bfa;
            text-transform: uppercase;
            font-weight: bold;
            min-width: 80px;
        }

        .event-message {
            color: #e2e8f0;
            flex: 1;
            word-break: break-word;
        }

        .event-status {
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            background: rgba(16, 185, 129, 0.2);
            color: #10b981;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .event-status.error {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
        }

        .refresh-info {
            text-align: center;
            color: #64748b;
            font-size: 0.85rem;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #334155;
        }

        .spinner {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #f59e0b;
            animation: pulse 2s infinite;
            margin-right: 5px;
        }

        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.5;
            }
        }

        .health-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 5px;
        }

        .health-indicator.online {
            background: #10b981;
            box-shadow: 0 0 8px #10b981;
        }

        .health-indicator.offline {
            background: #ef4444;
            box-shadow: 0 0 8px #ef4444;
        }

        @media (max-width: 768px) {
            header h1 {
                font-size: 1.5rem;
            }

            .status-grid {
                grid-template-columns: 1fr;
            }

            .database-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .system-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Scrollbar styling */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #0f172a;
        }

        ::-webkit-scrollbar-thumb {
            background: #334155;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #475569;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <header>
            <h1>🖥️ Server Status Monitor</h1>
            <p>Lost & Found Portal - Real-time Server Health Dashboard</p>
            <p>Deployment Mode: <strong><?php echo DEPLOYMENT_MODE; ?></strong> | Last Updated: <strong><?php echo date('Y-m-d H:i:s'); ?></strong></p>
        </header>

        <!-- Server Status Cards -->
        <div class="status-grid">
            <?php foreach ($servers as $key => $server): ?>
            <div class="server-card <?php echo strtolower($server['status']); ?>">
                <h3>
                    <span class="health-indicator <?php echo strtolower($server['status']); ?>"></span>
                    <?php echo $server['name']; ?>
                </h3>

                <span class="status-badge <?php echo strtolower($server['status']); ?>">
                    <?php echo $server['status']; ?>
                </span>

                <div class="info-row">
                    <span class="info-label">Role:</span>
                    <span class="info-value"><?php echo $server['role']; ?></span>
                </div>

                <div class="info-row">
                    <span class="info-label">Response Time:</span>
                    <span class="info-value"><span class="response-time"><?php echo $server['response_time']; ?>ms</span></span>
                </div>

                <div class="info-row">
                    <span class="info-label">HTTP Code:</span>
                    <span class="info-value"><?php echo $server['http_code'] > 0 ? $server['http_code'] : 'N/A'; ?></span>
                </div>

                <div class="info-row">
                    <span class="info-label">Database:</span>
                    <span class="info-value"><?php echo ucfirst($server['database']); ?></span>
                </div>

                <?php if (!empty($server['services'])): ?>
                <div class="services-list">
                    <?php foreach ($server['services'] as $service => $status): ?>
                    <div class="service-item">
                        <span><?php echo str_replace('_', ' ', ucfirst($service)); ?>:</span>
                        <span class="service-status"><?php echo $status; ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <?php if ($server['error']): ?>
                <div class="error-message">
                    ⚠️ <strong>Error:</strong> <?php echo htmlspecialchars($server['error']); ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Database Statistics -->
        <div class="database-section">
            <h3>📊 Database Statistics</h3>
            
            <?php if ($db_stats['connected']): ?>
            <div class="database-grid">
                <div class="stat-box">
                    <div class="stat-number"><?php echo $db_stats['users']; ?></div>
                    <div class="stat-label">Registered Users</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number"><?php echo $db_stats['items']; ?></div>
                    <div class="stat-label">Total Items</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number"><?php echo $db_stats['lost']; ?></div>
                    <div class="stat-label">Lost Items</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number"><?php echo $db_stats['found']; ?></div>
                    <div class="stat-label">Found Items</div>
                </div>
            </div>
            <div class="info-row">
                <span class="info-label">Status:</span>
                <span class="info-value" style="color: #10b981;">✓ Connected to MySQL</span>
            </div>
            <?php else: ?>
            <div class="error-message">
                ⚠️ <strong>Database Error:</strong> <?php echo htmlspecialchars($db_stats['error']); ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- System Information -->
        <div class="system-info">
            <h3>⚙️ System Information</h3>
            <div class="system-grid">
                <div class="info-row">
                    <span class="info-label">PHP Version:</span>
                    <span class="info-value"><?php echo $server_info['php_version']; ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Server Software:</span>
                    <span class="info-value"><?php echo $server_info['server_software']; ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Memory Usage:</span>
                    <span class="info-value"><?php echo $server_info['memory_usage']; ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Peak Memory:</span>
                    <span class="info-value"><?php echo $server_info['peak_memory']; ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">System Load:</span>
                    <span class="info-value"><?php echo $server_info['system_load']; ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Current Time:</span>
                    <span class="info-value"><?php echo $server_info['current_time']; ?></span>
                </div>
            </div>
        </div>

        <!-- Event Log -->
        <div class="event-log">
            <h3>📋 Status Events Log</h3>
            <?php
            // Generate event log entries
            $events = [];
            
            // Server status events
            foreach ($servers as $key => $server) {
                $status = strtolower($server['status']) === 'online' ? 'success' : 'error';
                $events[] = [
                    'time' => date('H:i:s'),
                    'type' => 'SERVER',
                    'message' => $server['name'] . ' status check',
                    'status' => $server['status']
                ];
            }
            
            // Database event
            $db_status = $db_stats['connected'] ? 'Connected' : 'Disconnected';
            $db_class = $db_stats['connected'] ? 'success' : 'error';
            $events[] = [
                'time' => date('H:i:s'),
                'type' => 'DATABASE',
                'message' => 'Database connection: ' . $db_status,
                'status' => $db_status,
                'class' => $db_class
            ];
            
            // System info event
            $events[] = [
                'time' => date('H:i:s'),
                'type' => 'SYSTEM',
                'message' => 'Memory Usage: ' . $server_info['memory_usage'],
                'status' => 'info'
            ];
            
            // Render events
            foreach ($events as $event):
            ?>
            <div class="event-line">
                <span class="event-time"><?php echo $event['time']; ?></span>
                <span class="event-type"><?php echo $event['type']; ?></span>
                <span class="event-message"><?php echo $event['message']; ?></span>
                <span class="event-status <?php echo $event['class'] ?? ''; ?>">
                    <?php echo isset($event['status']) ? $event['status'] : 'OK'; ?>
                </span>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Refresh Information -->
        <div class="refresh-info">
            <p>🔄 Auto-refresh this page to get latest server status updates</p>
            <p style="font-size: 0.75rem; margin-top: 10px; color: #475569;">
                Tip: Use browser F5 or Ctrl+R to refresh. For continuous monitoring, set up auto-refresh in your browser or use a monitoring tool.
            </p>
        </div>
    </div>

    <script>
        // Optional: Auto-refresh every 30 seconds
        // Uncomment the line below to enable auto-refresh
        // setTimeout(function() { location.reload(); }, 30000);

        // Add click to copy functionality for server URLs
        document.querySelectorAll('.server-card').forEach(card => {
            card.addEventListener('click', function() {
                // Visual feedback
                this.style.opacity = '0.8';
                setTimeout(() => { this.style.opacity = '1'; }, 100);
            });
        });

        // Highlight critical alerts
        document.querySelectorAll('.error-message').forEach(error => {
            error.style.animation = 'pulse 2s infinite';
        });
    </script>
</body>
</html>
