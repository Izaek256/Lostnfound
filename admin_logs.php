<?php
/**
 * Lost and Found Portal - University
 * Admin Logs Viewer
 */

require_once 'admin_config.php';

// Require admin authentication
requireAdmin();

// Handle logout
if (isset($_GET['logout'])) {
    logAdminAction('Logout from logs page');
    logoutAdmin();
}

// Read log file
$logFile = 'logs/admin.log';
$logs = [];

if (file_exists($logFile)) {
    $logContent = file_get_contents($logFile);
    $logLines = array_filter(explode("\n", $logContent));
    $logs = array_reverse($logLines); // Show newest first
}

// Pagination
$logsPerPage = 20;
$currentPage = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$totalLogs = count($logs);
$totalPages = ceil($totalLogs / $logsPerPage);
$offset = ($currentPage - 1) * $logsPerPage;
$currentLogs = array_slice($logs, $offset, $logsPerPage);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Logs - University Lost and Found</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .log-entry {
            background: rgba(255, 255, 255, 0.05);
            padding: 1rem;
            margin-bottom: 0.5rem;
            border-radius: 12px;
            border-left: 4px solid rgba(255, 255, 255, 0.3);
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.9);
            transition: all 0.3s ease;
        }
        
        .log-entry:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateX(5px);
        }
        
        .log-entry.login {
            border-left-color: rgba(40, 167, 69, 0.6);
        }
        
        .log-entry.logout {
            border-left-color: rgba(220, 53, 69, 0.6);
        }
        
        .log-entry.delete {
            border-left-color: rgba(255, 193, 7, 0.6);
        }
        
        .log-timestamp {
            color: rgba(255, 255, 255, 0.6);
            font-weight: bold;
        }
        
        .log-action {
            color: white;
            font-weight: bold;
        }
        
        .log-details {
            color: rgba(255, 255, 255, 0.7);
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 2rem;
            flex-wrap: wrap;
        }
        
        .pagination a,
        .pagination span {
            padding: 0.5rem 1rem;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .pagination a:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }
        
        .pagination .current {
            background: rgba(255, 255, 255, 0.3);
            font-weight: bold;
        }
        
        .log-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .log-stat {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            padding: 1.5rem;
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            text-align: center;
        }
        
        .log-stat-number {
            font-size: 1.8rem;
            font-weight: 700;
            color: white;
            margin-bottom: 0.5rem;
        }
        
        .log-stat-label {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.8rem;
            text-transform: uppercase;
        }
    </style>
</head>
<body>
    <!-- Admin Header -->
    <div class="admin-header">
        <div class="admin-nav">
            <div class="admin-title">
                📋 Admin Logs
            </div>
            <div class="admin-actions">
                <a href="admin_dashboard.php" class="btn btn-secondary">Dashboard</a>
                <a href="index.php" class="btn btn-secondary">Portal</a>
                <a href="?logout=1" class="btn btn-danger" onclick="return confirm('Are you sure you want to logout?')">Logout</a>
            </div>
        </div>
    </div>

    <main>
        <!-- Log Statistics -->
        <div class="log-stats">
            <div class="log-stat">
                <div class="log-stat-number"><?php echo $totalLogs; ?></div>
                <div class="log-stat-label">Total Entries</div>
            </div>
            <div class="log-stat">
                <div class="log-stat-number"><?php echo $totalPages; ?></div>
                <div class="log-stat-label">Pages</div>
            </div>
            <div class="log-stat">
                <div class="log-stat-number"><?php echo $currentPage; ?></div>
                <div class="log-stat-label">Current Page</div>
            </div>
        </div>

        <!-- Logs Container -->
        <div class="form-container">
            <h2>🔍 System Activity Logs</h2>
            
            <?php if (empty($logs)): ?>
                <div style="text-align: center; padding: 3rem; color: rgba(255, 255, 255, 0.6);">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">📝</div>
                    <p>No log entries found.</p>
                </div>
            <?php else: ?>
                <div style="margin-top: 2rem;">
                    <?php foreach ($currentLogs as $log): ?>
                        <?php
                        // Parse log entry
                        $logClass = '';
                        if (strpos($log, 'Login') !== false) {
                            $logClass = 'login';
                        } elseif (strpos($log, 'Logout') !== false) {
                            $logClass = 'logout';
                        } elseif (strpos($log, 'deleted') !== false) {
                            $logClass = 'delete';
                        }
                        
                        // Extract timestamp, action, and details
                        $parts = explode(' - ', $log, 3);
                        $timestamp = $parts[0] ?? '';
                        $action = $parts[1] ?? '';
                        $details = $parts[2] ?? '';
                        ?>
                        <div class="log-entry <?php echo $logClass; ?>">
                            <span class="log-timestamp"><?php echo htmlspecialchars($timestamp); ?></span>
                            <span class="log-action"> - <?php echo htmlspecialchars($action); ?></span>
                            <?php if ($details): ?>
                                <br><span class="log-details"><?php echo htmlspecialchars($details); ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php if ($currentPage > 1): ?>
                            <a href="?page=1">First</a>
                            <a href="?page=<?php echo $currentPage - 1; ?>">Previous</a>
                        <?php endif; ?>
                        
                        <?php
                        $startPage = max(1, $currentPage - 2);
                        $endPage = min($totalPages, $currentPage + 2);
                        
                        for ($i = $startPage; $i <= $endPage; $i++):
                        ?>
                            <?php if ($i == $currentPage): ?>
                                <span class="current"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($currentPage < $totalPages): ?>
                            <a href="?page=<?php echo $currentPage + 1; ?>">Next</a>
                            <a href="?page=<?php echo $totalPages; ?>">Last</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- Log Information -->
        <div class="form-container">
            <h2>ℹ️ Log Information</h2>
            <div style="background: rgba(255, 255, 255, 0.05); padding: 1.5rem; border-radius: 16px; margin-top: 1.5rem;">
                <h4 style="color: white; margin-bottom: 1rem;">Log Entry Types:</h4>
                <ul style="color: rgba(255, 255, 255, 0.8); padding-left: 2rem;">
                    <li><strong style="color: rgba(40, 167, 69, 0.8);">Login Events:</strong> Successful and failed login attempts</li>
                    <li><strong style="color: rgba(220, 53, 69, 0.8);">Logout Events:</strong> Admin logout activities</li>
                    <li><strong style="color: rgba(255, 193, 7, 0.8);">Item Management:</strong> Item deletions and modifications</li>
                    <li><strong style="color: rgba(255, 255, 255, 0.8);">System Events:</strong> Other administrative actions</li>
                </ul>
                
                <h4 style="color: white; margin: 2rem 0 1rem 0;">Log File Location:</h4>
                <p style="color: rgba(255, 255, 255, 0.7); font-family: monospace; background: rgba(0, 0, 0, 0.2); padding: 0.5rem; border-radius: 8px;">
                    <?php echo realpath($logFile) ?: 'logs/admin.log (not created yet)'; ?>
                </p>
                
                <h4 style="color: white; margin: 2rem 0 1rem 0;">Security Notes:</h4>
                <ul style="color: rgba(255, 255, 255, 0.8); padding-left: 2rem;">
                    <li>All admin actions are automatically logged</li>
                    <li>IP addresses are recorded for security tracking</li>
                    <li>Failed login attempts are monitored</li>
                    <li>Logs are stored securely on the server</li>
                </ul>
            </div>
        </div>
    </main>

    <footer>
        <p>&copy; 2024 University Lost and Found Portal - Admin Logs</p>
    </footer>

    <script>
        // Auto-refresh logs every 60 seconds
        setInterval(function() {
            location.reload();
        }, 60000);
        
        // Highlight search terms if any
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const searchTerm = urlParams.get('search');
            
            if (searchTerm) {
                const logEntries = document.querySelectorAll('.log-entry');
                logEntries.forEach(entry => {
                    const text = entry.innerHTML;
                    const highlightedText = text.replace(
                        new RegExp(searchTerm, 'gi'),
                        '<mark style="background: rgba(255, 255, 0, 0.3); color: white;">$&</mark>'
                    );
                    entry.innerHTML = highlightedText;
                });
            }
        });
    </script>
</body>
</html>
