<?php

/**
 * Server C - User Dashboard Page
 */

require_once 'config.php';

// Require user to be logged in
requireUser();

// Initialize message variables
$message = '';
$messageType = '';

// Handle logout
if (isset($_GET['logout'])) {
    logoutUser();
}

// Handle item deletion
if (isset($_POST['delete_item'])) {
    $item_id = $_POST['item_id'];
    $current_user_id = getCurrentUserId();

    // First get item from ItemsServer API to verify ownership
    $api_response = makeAPIRequest(ITEMSSERVER_URL . '/get_item.php', [
        'item_id' => $item_id
    ], 'GET', ['return_json' => true]);

    $item = null;
    if (is_array($api_response) && isset($api_response['success']) && $api_response['success']) {
        $item = $api_response['item'];
        // Verify user owns this item
        if ($item['user_id'] != $current_user_id) {
            $item = null;
        }
    }

    // Call ItemsServer API to delete item
    $response = makeAPIRequest(ITEMSSERVER_URL . '/delete_item.php', [
        'id' => $item_id,
        'user_id' => $current_user_id
    ], 'POST', ['return_json' => true]);

    // Parse JSON response
    if (is_array($response) && isset($response['success']) && $response['success']) {
        // Delete image file locally if exists
        if ($item && $item['image']) {
            $image_path = '../ItemsServer/uploads/' . $item['image'];
            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }

        $message = 'Item deleted successfully';
        $messageType = 'success';
    } else {
        $message = isset($response['error']) ? $response['error'] : 'Failed to delete item';
        $messageType = 'error';
    }
}

$user_id = getCurrentUserId();
$username = getCurrentUsername();
$userEmail = getCurrentUserEmail();

// Get user's items from ItemsServer API instead of direct database connection
$api_response = makeAPIRequest(ITEMSSERVER_URL . '/get_user_items.php', [
    'user_id' => $user_id
], 'GET', ['return_json' => true]);

$userItems = [];
$stats = [
    'total' => 0,
    'lost_count' => 0,
    'found_count' => 0
];

if (is_array($api_response) && isset($api_response['success']) && $api_response['success']) {
    $userItems = $api_response['items'] ?? [];
    $stats = $api_response['stats'] ?? $stats;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - Lost & Found</title>
    <link rel="icon" href="assets/favicon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="assets/style.css">
</head>

<body>
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
                        <li><a href="admin_dashboard.php">Admin Panel</a></li>
                    <?php endif; ?>
                    <li><a href="?logout=1" onclick="return confirm('Are you sure you want to logout?')">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <?php if ($message != ''): ?>
            <div class="alert <?php echo $messageType === 'success' ? 'alert-success' : 'alert-error'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1): ?>
            <div class="alert" style="background: #10b981; color: white; border-color: #059669;">
                <strong>⭐ Admin Access:</strong> You have administrator privileges.
                <a href="admin_dashboard.php" style="color: white; text-decoration: underline; font-weight: bold;">Go to Admin Dashboard →</a>
            </div>
        <?php endif; ?>

        <div class="form-container">
            <h2>👤 Welcome, <?php echo htmlspecialchars($username); ?>!</h2>
            <p style="text-align: center; color: var(--text-secondary); margin-bottom: 2rem;">
                Email: <?php echo htmlspecialchars($userEmail); ?>
            </p>

            <!-- Statistics -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1.5rem; margin-top: 2rem;">
                <div style="text-align: center; padding: 1.5rem; background: var(--bg-secondary); border-radius: 8px;">
                    <h3 style="font-size: 2rem; color: var(--primary); margin-bottom: 0.5rem;"><?php echo $stats['total']; ?></h3>
                    <p style="color: var(--text-secondary); font-weight: 600;">Total Items</p>
                </div>
                <div style="text-align: center; padding: 1.5rem; background: var(--bg-secondary); border-radius: 8px;">
                    <h3 style="font-size: 2rem; color: var(--error); margin-bottom: 0.5rem;"><?php echo $stats['lost_count']; ?></h3>
                    <p style="color: var(--text-secondary); font-weight: 600;">Lost Items</p>
                </div>
                <div style="text-align: center; padding: 1.5rem; background: var(--bg-secondary); border-radius: 8px;">
                    <h3 style="font-size: 2rem; color: var(--success); margin-bottom: 0.5rem;"><?php echo $stats['found_count']; ?></h3>
                    <p style="color: var(--text-secondary); font-weight: 600;">Found Items</p>
                </div>
            </div>
        </div>

        <!-- User's Items -->
        <div class="form-container">
            <h2>📋 My Posted Items</h2>

            <?php if (count($userItems) > 0): ?>
                <div class="items-grid">
                    <?php foreach ($userItems as $item): ?>
                        <div class="item-card" style="position: relative; overflow: visible;">
                            <div class="item-card-header">
                                <span class="item-type <?php echo $item['type']; ?>">
                                    <?php echo $item['type'] === 'lost' ? '🔴 Lost' : '🟢 Found'; ?>
                                </span>

                                <?php if ($item['image']): ?>
                                    <img src="<?php echo getImageUrl($item['image']); ?>"
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

                                <div class="item-actions" style="margin-top: 1rem; display: flex; gap: 0.75rem; padding-top: 1rem; border-top: 1px solid var(--border); background: white; position: relative; z-index: 100;">
                                    <!-- Edit Button -->
                                    <a href="edit_item.php?id=<?php echo $item['id']; ?>"
                                        class="btn btn-secondary"
                                        style="flex: 1; text-align: center; padding: 0.7rem; font-size: 0.9rem; text-decoration: none; display: block; border-radius: 8px;">
                                        ✏️ Edit
                                    </a>

                                    <!-- Delete Form -->
                                    <form method="POST" style="flex: 1; margin: 0;" onsubmit="return confirm('Are you sure you want to delete this item? This cannot be undone.');">
                                        <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                        <button type="submit" name="delete_item"
                                            class="btn btn-danger"
                                            style="width: 100%; padding: 0.7rem; font-size: 0.9rem; cursor: pointer; border: none; border-radius: 8px;">
                                            🗑️ Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="text-align: center; color: var(--text-secondary); padding: 2rem;">
                    You haven't posted any items yet.
                </p>
                <div style="text-align: center;">
                    <a href="report_lost.php" class="btn">Report Lost Item</a>
                    <a href="report_found.php" class="btn btn-success" style="margin-left: 1rem;">Report Found Item</a>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <footer>
        <p>&copy; 2025University Lost and Found Portal. Built to help our campus community stay connected.</p>
    </footer>

    <script src="assets/script.js"></script>
</body>

</html>