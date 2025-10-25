<?php
/**
 * Server B - Homepage
 */

require_once 'config.php';

// Get data from database
$conn = connectDB();

// Get recent items
$sql = "SELECT * FROM items ORDER BY created_at DESC LIMIT 6";
$result = mysqli_query($conn, $sql);
$recentItems = [];

while ($row = mysqli_fetch_assoc($result)) {
    $recentItems[] = $row;
}

// Get simple statistics
$stats_sql = "SELECT COUNT(*) as total FROM items";
$stats_result = mysqli_query($conn, $stats_sql);
$stats_data = mysqli_fetch_assoc($stats_result);

$lost_sql = "SELECT COUNT(*) as lost_count FROM items WHERE type = 'lost'";
$lost_result = mysqli_query($conn, $lost_sql);
$lost_data = mysqli_fetch_assoc($lost_result);

$found_sql = "SELECT COUNT(*) as found_count FROM items WHERE type = 'found'";
$found_result = mysqli_query($conn, $found_sql);
$found_data = mysqli_fetch_assoc($found_result);

$stats = [
    'total' => $stats_data['total'],
    'lost_count' => $lost_data['lost_count'],
    'found_count' => $found_data['found_count']
];

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>University Lost and Found Portal - Server B</title>
    <link rel="icon" type="image/svg+xml" href="./assets/favicon.svg">
    <link rel="stylesheet" href="../ServerC/style.css">
    <style>
        .server-info {
            background: #e8f5e8;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            text-align: center;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="header-content">
            <div class="logo">
                <img src="./assets/logo.webp" alt="Lost & Found Logo">
                <h1>University Lost & Found</h1>
            </div>
            <button class="menu-toggle" aria-label="Toggle menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <nav>
                <ul>
                    <li><a href="home.php" class="active">Home</a></li>
                    <li><a href="report_lost.php">Report Lost</a></li>
                    <li><a href="report_found.php">Report Found</a></li>
                    <li><a href="items.php">View Items</a></li>
                    <?php if (isUserLoggedIn()): ?>
                        <li><a href="../ServerC/user_dashboard.php">My Dashboard</a></li>
                        <?php if (isCurrentUserAdmin()): ?>
                            <li><a href="../ServerA/admin_dashboard.php">Admin Panel</a></li>
                        <?php endif; ?>
                        <li><a href="../ServerC/user_dashboard.php?logout=1">Logout</a></li>
                    <?php else: ?>
                        <li><a href="../ServerC/user_login.php">Login</a></li>
                        <li><a href="../ServerC/user_register.php">Register</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <main>
        <div class="server-info">
            <h3>🖥️ Server B - Item Management</h3>
            <p>Browse and manage lost & found items | Connected to shared database</p>
        </div>

        <!-- Hero Section -->
        <section class="hero">
            <h2>University Lost and Found Portal</h2>
            <p>Help reunite lost items with their owners. Report lost items, browse found items, and help build a stronger campus community.</p>
            
            <!-- Quick Action Buttons -->
            <div style="margin-top: 2rem; display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                <a href="report_lost.php" class="btn">📢 Report Lost Item</a>
                <a href="report_found.php" class="btn btn-success">🔍 Report Found Item</a>
                <a href="items.php" class="btn btn-secondary">👀 Browse All Items</a>
            </div>
        </section>

        <!-- Statistics Section -->
        <section class="form-container">
            <h2>📊 Portal Statistics</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 2rem; text-align: center;">
                <div style="padding: 1.5rem; background: #f8f9fa; border-radius: 10px;">
                    <h3 style="font-size: 2rem; color: #667eea; margin-bottom: 0.5rem;"><?php echo $stats['total']; ?></h3>
                    <p style="color: #666;">Total Items</p>
                </div>
                <div style="padding: 1.5rem; background: #f8f9fa; border-radius: 10px;">
                    <h3 style="font-size: 2rem; color: #dc3545; margin-bottom: 0.5rem;"><?php echo $stats['lost_count']; ?></h3>
                    <p style="color: #666;">Lost Items</p>
                </div>
                <div style="padding: 1.5rem; background: #f8f9fa; border-radius: 10px;">
                    <h3 style="font-size: 2rem; color: #28a745; margin-bottom: 0.5rem;"><?php echo $stats['found_count']; ?></h3>
                    <p style="color: #666;">Found Items</p>
                </div>
            </div>
        </section>

        <!-- Recent Items Section -->
        <?php if (count($recentItems) > 0): ?>
        <section class="items-container">
            <div class="items-header">
                <h2>🕒 Recent Items</h2>
                <a href="items.php" class="btn btn-secondary">View All Items</a>
            </div>
            
            <div class="items-grid">
                <?php foreach ($recentItems as $item): ?>
                <div class="item-card">
                    <div class="item-card-header">
                        <span class="item-type <?php echo $item['type']; ?>">
                            <?php echo $item['type'] === 'lost' ? '🔴 Lost' : '🟢 Found'; ?>
                        </span>
                        
                        <?php if ($item['image'] && file_exists('uploads/' . $item['image'])): ?>
                            <img src="uploads/<?php echo htmlspecialchars($item['image']); ?>" 
                                 alt="<?php echo htmlspecialchars($item['title']); ?>" 
                                 class="item-image">
                        <?php else: ?>
                            <div class="no-image-placeholder">
                                <span>📷</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="item-card-body">
                        <h3>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                <line x1="9" y1="3" x2="9" y2="21"></line>
                            </svg>
                            <?php echo htmlspecialchars($item['title']); ?>
                        </h3>
                        
                        <div class="item-card-section">
                            <div class="item-description"><?php echo htmlspecialchars($item['description']); ?></div>
                        </div>
                        
                        <div class="item-card-section">
                            <div class="item-detail">
                                <svg class="item-detail-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                    <circle cx="12" cy="10" r="3"></circle>
                                </svg>
                                <div class="item-detail-content">
                                    <strong>Location:</strong> <?php echo htmlspecialchars($item['location']); ?>
                                </div>
                            </div>
                            
                            <div class="item-detail">
                                <svg class="item-detail-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                    <polyline points="22,6 12,13 2,6"></polyline>
                                </svg>
                                <div class="item-detail-content">
                                    <strong>Contact:</strong> <a href="mailto:<?php echo htmlspecialchars($item['contact']); ?>" style="color: var(--primary);"><?php echo htmlspecialchars($item['contact']); ?></a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="item-meta">
                            <p>
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 4px;">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                                <?php echo date('M j, Y g:i A', strtotime($item['created_at'])); ?>
                            </p>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- How It Works Section -->
        <section class="form-container">
            <h2>❓ How It Works</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem; margin-top: 2rem;">
                <div style="text-align: center; padding: 1rem;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">📢</div>
                    <h3 style="color: #667eea; margin-bottom: 1rem;">Report Lost Items</h3>
                    <p>Lost something on campus? Create a detailed report with description, location, and contact information to help others identify and return your item.</p>
                </div>
                
                <div style="text-align: center; padding: 1rem;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">🔍</div>
                    <h3 style="color: #667eea; margin-bottom: 1rem;">Report Found Items</h3>
                    <p>Found an item? Help reunite it with its owner by posting details about what you found and where you found it.</p>
                </div>
                
                <div style="text-align: center; padding: 1rem;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">🤝</div>
                    <h3 style="color: #667eea; margin-bottom: 1rem;">Connect & Reunite</h3>
                    <p>Browse through lost and found items, use the search feature, and contact item owners or finders to arrange returns.</p>
                </div>
            </div>
        </section>

        <!-- Tips Section -->
        <section class="form-container">
            <h2>💡 Tips for Success</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; margin-top: 2rem;">
                <div>
                    <h4 style="color: #667eea; margin-bottom: 1rem;">📝 Be Descriptive</h4>
                    <ul style="padding-left: 1.5rem; color: #666;">
                        <li>Include specific details about the item</li>
                        <li>Mention unique characteristics or markings</li>
                        <li>Add the exact location where lost/found</li>
                        <li>Upload a clear photo if possible</li>
                    </ul>
                </div>
                
                <div>
                    <h4 style="color: #667eea; margin-bottom: 1rem;">⚡ Act Quickly</h4>
                    <ul style="padding-left: 1.5rem; color: #666;">
                        <li>Report items as soon as possible</li>
                        <li>Check the portal regularly for updates</li>
                        <li>Respond promptly to contact attempts</li>
                        <li>Update your post if item is recovered</li>
                    </ul>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer>
        <p>&copy; 2024 University Lost and Found Portal. Built to help our campus community stay connected.</p>
    </footer>

    <script src="../ServerC/script.js"></script>
</body>
</html>
