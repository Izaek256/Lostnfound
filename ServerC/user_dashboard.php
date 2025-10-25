<?php
/**
 * Server C - User Dashboard Page
 */

require_once 'config.php';

// Require user to be logged in
requireUser();

// Handle logout
if (isset($_GET['logout'])) {
    logoutUser();
}

$user_id = getCurrentUserId();
$username = getCurrentUsername();
$user_email = getCurrentUserEmail();

// Get user's items
$result = makeAPICall('get_items');
$all_items = $result['items'] ?? [];
$user_items = array_filter($all_items, function($item) use ($user_id) {
    return $item['user_id'] == $user_id;
});
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - Lost & Found</title>
    <link rel="icon" href="assets/favicon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Header -->
    <header>
        <div class="header-content">
            <div class="logo">
                <img src="assets/logo.webp" alt="Lost & Found Logo">
                <h1>Lost & Found Portal</h1>
            </div>
            <button class="menu-toggle" aria-label="Toggle menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <nav>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="report_lost.php">Report Lost</a></li>
                    <li><a href="report_found.php">Report Found</a></li>
                    <li><a href="items.php">View Items</a></li>
                    <?php if (isUserLoggedIn()): ?>
                        <li><a href="user_dashboard.php" class="active">My Dashboard</a></li>
                        <?php if (isCurrentUserAdmin()): ?>
                            <li><a href="admin_dashboard.php">Admin Panel</a></li>
                        <?php endif; ?>
                        <li><a href="user_dashboard.php?logout=1">Logout</a></li>
                    <?php else: ?>
                        <li><a href="user_login.php">Login</a></li>
                        <li><a href="user_register.php">Register</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <main>
        <div class="dashboard">
            <!-- Welcome Section -->
            <div class="user-welcome">
                <h2>👤 Welcome, <?php echo htmlspecialchars($username); ?>!</h2>
                <?php if ($user_email): ?>
                <p class="user-email">Email: <?php echo htmlspecialchars($user_email); ?></p>
                <?php endif; ?>
            </div>
            
            <!-- Statistics Cards -->
            <div class="dashboard-stats">
                <div class="stat-card">
                    <h3>Total Items</h3>
                    <p class="stat-number"><?php echo count($user_items); ?></p>
                </div>
                
                <div class="stat-card">
                    <h3>Lost Items</h3>
                    <p class="stat-number"><?php echo count(array_filter($user_items, function($item) { return $item['type'] === 'lost'; })); ?></p>
                </div>
                
                <div class="stat-card">
                    <h3>Found Items</h3>
                    <p class="stat-number"><?php echo count(array_filter($user_items, function($item) { return $item['type'] === 'found'; })); ?></p>
                </div>
            </div>
                
            <!-- My Posted Items Section -->
            <?php if (!empty($user_items)): ?>
            <div class="user-items">
                <h3>📋 My Posted Items</h3>
                <div class="items-grid">
                    <?php foreach ($user_items as $item): ?>
                    <div class="item-card">
                        <div class="item-card-header">
                            <div class="item-image">
                                <img src="../ServerB/uploads/<?php echo htmlspecialchars($item['image']); ?>" 
                                     alt="<?php echo htmlspecialchars($item['title']); ?>"
                                     onerror="this.src='assets/default-item.jpg'">
                            </div>
                            <span class="item-type <?php echo $item['type']; ?>">
                                <?php echo ucfirst($item['type']); ?>
                            </span>
                        </div>
                        <div class="item-card-body">
                            <h4>📧 <?php echo htmlspecialchars($item['title']); ?></h4>
                            <div class="item-card-section">
                                <p class="item-description"><?php echo htmlspecialchars($item['description']); ?></p>
                            </div>
                            <div class="item-card-section">
                                <div class="item-detail">
                                    <span class="item-detail-icon">📍</span>
                                    <div class="item-detail-content">
                                        <strong>Location:</strong> <?php echo htmlspecialchars($item['location']); ?>
                                    </div>
                                </div>
                                <div class="item-detail">
                                    <span class="item-detail-icon">📞</span>
                                    <div class="item-detail-content">
                                        <strong>Contact:</strong> <?php echo htmlspecialchars($item['contact']); ?>
                                    </div>
                                </div>
                            </div>
                            <div class="item-meta">
                                <p>📅 <?php echo date('M j, Y g:i A', strtotime($item['created_at'])); ?></p>
                            </div>
                            <div class="item-actions">
                                <button class="btn btn-secondary btn-sm">✏️ Edit</button>
                                <button class="btn btn-danger btn-sm">🗑️ Delete</button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php else: ?>
            <div class="no-items">
                <h3>📋 My Posted Items</h3>
                <p>You haven't reported any lost or found items yet.</p>
                <a href="report_lost.php" class="btn btn-primary">Report Your First Item</a>
            </div>
            <?php endif; ?>
        </div>
    </main>
    
    <script src="script.js"></script>
</body>
</html>
