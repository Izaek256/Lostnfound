<?php
/**
 * Server B - User Dashboard
 * 
 * This page displays user's personal dashboard and fetches data from Server A
 */

require_once 'config.php';

// Check if user is logged in
requireUser();

// Handle logout
if (isset($_GET['logout'])) {
    logoutUser();
}

$userId = getCurrentUserId();
$username = getCurrentUsername();
$userEmail = getCurrentUserEmail();

// Try to get user's items from Server A via API
$userItemsData = makeAPICall('get_user_items', [], 'GET');
$userItems = [];
$stats = ['total' => 0, 'lost_count' => 0, 'found_count' => 0];

if (isset($userItemsData['success']) && $userItemsData['success']) {
    $userItems = $userItemsData['items'];
    $stats = $userItemsData['stats'];
} else {
    // Fallback: try direct database connection
    $conn = getDBConnection();
    if ($conn) {
        // Get user's items
        $sql = "SELECT * FROM items WHERE user_id = '$userId' ORDER BY created_at DESC";
        $result = $conn->query($sql);
        if ($result) {
            $userItems = $result->fetch_all(MYSQLI_ASSOC);
        }
        
        // Get user statistics
        $sql = "SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN type = 'lost' THEN 1 ELSE 0 END) as lost_count,
            SUM(CASE WHEN type = 'found' THEN 1 ELSE 0 END) as found_count
            FROM items WHERE user_id = '$userId'";
        $result = $conn->query($sql);
        if ($result) {
            $stats = $result->fetch_assoc();
        }
        
        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard - Server B</title>
    <link rel="icon" type="image/svg+xml" href="./assets/favicon.svg">
    <link rel="stylesheet" href="style.css">
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
                    <li><a href="index.php">Home</a></li>
                    <li><a href="report_lost.php">Report Lost</a></li>
                    <li><a href="report_found.php">Report Found</a></li>
                    <li><a href="items.php">View Items</a></li>
                    <li><a href="user_dashboard.php" class="active">My Dashboard</a></li>
                    <?php if (isCurrentUserAdmin()): ?>
                        <li><a href="../ServerA/admin_dashboard.php">Admin Panel</a></li>
                    <?php endif; ?>
                    <li><a href="user_dashboard.php?logout=1">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <main>
        <div class="server-info">
            <h3>🖥️ Server B - User Dashboard</h3>
            <p>Data synchronized from Server A (Main Backend)</p>
        </div>

        <!-- Welcome Section -->
        <section class="form-container">
            <h2>👤 Welcome, <?php echo htmlspecialchars($username); ?>!</h2>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($userEmail); ?></p>
            
            <div style="margin-top: 2rem; display: flex; gap: 1rem; flex-wrap: wrap;">
                <a href="report_lost.php" class="btn">📢 Report Lost Item</a>
                <a href="report_found.php" class="btn btn-success">🔍 Report Found Item</a>
                <a href="items.php" class="btn btn-secondary">👀 Browse All Items</a>
            </div>
        </section>

        <!-- Personal Statistics -->
        <section class="form-container">
            <h2>📊 My Statistics</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 2rem; text-align: center;">
                <div style="padding: 1.5rem; background: #f8f9fa; border-radius: 10px;">
                    <h3 style="font-size: 2rem; color: #667eea;"><?php echo $stats['total']; ?></h3>
                    <p>My Total Items</p>
                </div>
                <div style="padding: 1.5rem; background: #f8f9fa; border-radius: 10px;">
                    <h3 style="font-size: 2rem; color: #dc3545;"><?php echo $stats['lost_count']; ?></h3>
                    <p>Lost Items</p>
                </div>
                <div style="padding: 1.5rem; background: #f8f9fa; border-radius: 10px;">
                    <h3 style="font-size: 2rem; color: #28a745;"><?php echo $stats['found_count']; ?></h3>
                    <p>Found Items</p>
                </div>
            </div>
        </section>

        <!-- My Items -->
        <section class="items-container">
            <div class="items-header">
                <h2>📋 My Items</h2>
            </div>
            
            <?php if (count($userItems) > 0): ?>
                <div class="items-grid">
                    <?php foreach ($userItems as $item): ?>
                        <div class="item-card">
                            <div class="item-card-header">
                                <span class="item-type <?php echo $item['type']; ?>">
                                    <?php echo $item['type'] === 'lost' ? '🔴 Lost' : '🟢 Found'; ?>
                                </span>
                                
                                <?php if ($item['image'] && file_exists('../ServerA/uploads/' . $item['image'])): ?>
                                    <img src="../ServerA/uploads/<?php echo htmlspecialchars($item['image']); ?>" 
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
                                            <strong>Contact:</strong> <?php echo htmlspecialchars($item['contact']); ?>
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
                                
                                <!-- Item Actions -->
                                <div style="margin-top: 1rem; display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                    <a href="edit_item.php?id=<?php echo $item['id']; ?>" class="btn" style="background: #6f42c1; font-size: 0.9rem;">✏️ Edit</a>
                                    <button onclick="deleteItem(<?php echo $item['id']; ?>)" class="btn" style="background: #dc3545; font-size: 0.9rem;">🗑️ Delete</button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 3rem;">
                    <h3>No items posted yet</h3>
                    <p>Start by reporting a lost or found item!</p>
                    <div style="margin-top: 2rem;">
                        <a href="report_lost.php" class="btn">📢 Report Lost Item</a>
                        <a href="report_found.php" class="btn btn-success">🔍 Report Found Item</a>
                    </div>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <!-- Footer -->
    <footer>
        <p>&copy; 2024 University Lost and Found Portal. Built to help our campus community stay connected.</p>
    </footer>

    <script src="script.js"></script>
    <script>
        function deleteItem(itemId) {
            if (confirm('Are you sure you want to delete this item?')) {
                // Make API call to Server A to delete item
                fetch('../ServerA/api/delete_item.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'item_id=' + itemId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Item deleted successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + (data.error || 'Failed to delete item'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error deleting item. Please try again.');
                });
            }
        }
    </script>
</body>
</html>
