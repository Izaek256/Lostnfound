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
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header>
            <div class="logo">
                <img src="assets/logo.webp" alt="Lost & Found Logo">
                <h1>Lost & Found Portal</h1>
            </div>
            <nav>
                <a href="index.php">Home</a>
                <a href="items.php">Browse Items</a>
                <a href="report_lost.php">Report Lost</a>
                <a href="report_found.php">Report Found</a>
                <a href="?logout=1">Logout</a>
            </nav>
        </header>
        
        <main>
            <div class="dashboard">
                <h2>Welcome, <?php echo htmlspecialchars($username); ?>!</h2>
                
                <div class="dashboard-stats">
                    <div class="stat-card">
                        <h3>My Lost Items</h3>
                        <p class="stat-number"><?php echo count(array_filter($user_items, function($item) { return $item['type'] === 'lost'; })); ?></p>
                    </div>
                    
                    <div class="stat-card">
                        <h3>My Found Items</h3>
                        <p class="stat-number"><?php echo count(array_filter($user_items, function($item) { return $item['type'] === 'found'; })); ?></p>
                    </div>
                    
                    <div class="stat-card">
                        <h3>Total Items</h3>
                        <p class="stat-number"><?php echo count($user_items); ?></p>
                    </div>
                </div>
                
                <div class="dashboard-actions">
                    <a href="report_lost.php" class="btn btn-primary">Report Lost Item</a>
                    <a href="report_found.php" class="btn btn-secondary">Report Found Item</a>
                    <a href="items.php" class="btn btn-outline">Browse All Items</a>
                </div>
                
                <?php if (!empty($user_items)): ?>
                <div class="user-items">
                    <h3>My Items</h3>
                    <div class="items-grid">
                        <?php foreach ($user_items as $item): ?>
                        <div class="item-card">
                            <div class="item-image">
                                <img src="../ServerB/uploads/<?php echo htmlspecialchars($item['image']); ?>" 
                                     alt="<?php echo htmlspecialchars($item['title']); ?>"
                                     onerror="this.src='assets/default-item.jpg'">
                            </div>
                            <div class="item-details">
                                <h4><?php echo htmlspecialchars($item['title']); ?></h4>
                                <p class="item-type <?php echo $item['type']; ?>">
                                    <?php echo ucfirst($item['type']); ?>
                                </p>
                                <p class="item-description"><?php echo htmlspecialchars(substr($item['description'], 0, 100)); ?>...</p>
                                <p class="item-location">📍 <?php echo htmlspecialchars($item['location']); ?></p>
                                <p class="item-date">📅 <?php echo date('M j, Y', strtotime($item['created_at'])); ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php else: ?>
                <div class="no-items">
                    <h3>No Items Yet</h3>
                    <p>You haven't reported any lost or found items yet.</p>
                    <a href="report_lost.php" class="btn btn-primary">Report Your First Item</a>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
