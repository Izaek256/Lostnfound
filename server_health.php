<?php
/**
 * Multi-Server Health Check Dashboard
 * Single page to check if all servers are active and communicating
 * 
 * Access via: http://localhost/Lostnfound/server_health.php
 */

// Server endpoints to check
$servers = [
    'ServerA' => [
        'url' => 'http://localhost/Lostnfound/ServerA/api/health.php',
        'name' => 'Server A - User Authentication',
        'color' => '#3b82f6'
    ],
    'ServerB' => [
        'url' => 'http://localhost/Lostnfound/ServerB/api/health.php',
        'name' => 'Server B - Item Management',
        'color' => '#10b981'
    ],
    'ServerC' => [
        'url' => 'http://localhost/Lostnfound/ServerC/health.php',
        'name' => 'Server C - User Interface',
        'color' => '#8b5cf6'
    ]
];

// Function to check server health
function checkServer($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    
    $start_time = microtime(true);
    $response = curl_exec($ch);
    $response_time = round((microtime(true) - $start_time) * 1000, 2);
    
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    $result = [
        'online' => false,
        'response_time' => $response_time,
        'http_code' => $http_code,
        'error' => $error,
        'data' => null
    ];
    
    if (!$error && $http_code == 200) {
        $data = json_decode($response, true);
        if ($data) {
            $result['online'] = true;
            $result['data'] = $data;
        }
    }
    
    return $result;
}

// Check all servers
$results = [];
$all_online = true;

foreach ($servers as $key => $server) {
    $results[$key] = checkServer($server['url']);
    if (!$results[$key]['online']) {
        $all_online = false;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Server Health Check - Lost & Found</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            color: #333;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        
        .header h1 {
            font-size: 32px;
            margin-bottom: 10px;
            color: #1f2937;
        }
        
        .header p {
            color: #6b7280;
            font-size: 16px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            margin-top: 15px;
            font-size: 14px;
        }
        
        .status-badge.success {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-badge.error {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .servers-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .server-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s;
        }
        
        .server-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.15);
        }
        
        .server-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f3f4f6;
        }
        
        .server-name {
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
        }
        
        .status-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 8px;
            animation: pulse 2s infinite;
        }
        
        .status-indicator.online {
            background: #10b981;
            box-shadow: 0 0 8px rgba(16, 185, 129, 0.5);
        }
        
        .status-indicator.offline {
            background: #ef4444;
            box-shadow: 0 0 8px rgba(239, 68, 68, 0.5);
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #f3f4f6;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            color: #6b7280;
            font-size: 14px;
        }
        
        .info-value {
            font-weight: 600;
            color: #1f2937;
            font-size: 14px;
        }
        
        .info-value.success {
            color: #10b981;
        }
        
        .info-value.error {
            color: #ef4444;
        }
        
        .services-list {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px solid #f3f4f6;
        }
        
        .services-title {
            font-size: 14px;
            font-weight: 600;
            color: #6b7280;
            margin-bottom: 10px;
        }
        
        .service-item {
            padding: 8px 12px;
            background: #f9fafb;
            border-radius: 6px;
            margin-bottom: 6px;
            font-size: 13px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .refresh-btn {
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
            padding: 12px 30px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            display: block;
            margin: 0 auto;
            transition: all 0.3s;
        }
        
        .refresh-btn:hover {
            background: #667eea;
            color: white;
            transform: scale(1.05);
        }
        
        .timestamp {
            text-align: center;
            color: white;
            margin-top: 20px;
            font-size: 14px;
        }
        
        .error-message {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
            padding: 10px;
            border-radius: 6px;
            margin-top: 10px;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔍 Server Health Monitor</h1>
            <p>Lost & Found Multi-Server System</p>
            <?php if ($all_online): ?>
                <div class="status-badge success">
                    ✓ All Servers Online & Communicating
                </div>
            <?php else: ?>
                <div class="status-badge error">
                    ✗ Some Servers Are Down
                </div>
            <?php endif; ?>
        </div>
        
        <div class="servers-grid">
            <?php foreach ($servers as $key => $server): ?>
                <?php $result = $results[$key]; ?>
                <div class="server-card">
                    <div class="server-header">
                        <div class="server-name">
                            <span class="status-indicator <?php echo $result['online'] ? 'online' : 'offline'; ?>"></span>
                            <?php echo $server['name']; ?>
                        </div>
                    </div>
                    
                    <?php if ($result['online']): ?>
                        <div class="info-row">
                            <span class="info-label">Status</span>
                            <span class="info-value success">ONLINE</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Response Time</span>
                            <span class="info-value"><?php echo $result['response_time']; ?> ms</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Database</span>
                            <span class="info-value success"><?php echo strtoupper($result['data']['database']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Last Check</span>
                            <span class="info-value"><?php echo $result['data']['timestamp']; ?></span>
                        </div>
                        
                        <?php if (isset($result['data']['services'])): ?>
                            <div class="services-list">
                                <div class="services-title">Active Services</div>
                                <?php foreach ($result['data']['services'] as $service_name => $service_status): ?>
                                    <div class="service-item">
                                        <span><?php echo ucwords(str_replace('_', ' ', $service_name)); ?></span>
                                        <span style="color: <?php echo (strpos($service_status, 'active') !== false || strpos($service_status, 'reachable') !== false || strpos($service_status, 'writable') !== false) ? '#10b981' : '#f59e0b'; ?>">
                                            <?php echo $service_status; ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="info-row">
                            <span class="info-label">Status</span>
                            <span class="info-value error">OFFLINE</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">HTTP Code</span>
                            <span class="info-value error"><?php echo $result['http_code'] ?: 'N/A'; ?></span>
                        </div>
                        <?php if ($result['error']): ?>
                            <div class="error-message">
                                <strong>Error:</strong> <?php echo htmlspecialchars($result['error']); ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        
        <button class="refresh-btn" onclick="location.reload()">🔄 Refresh Status</button>
        
        <div class="timestamp">
            Last updated: <?php echo date('Y-m-d H:i:s'); ?>
        </div>
    </div>
</body>
</html>
