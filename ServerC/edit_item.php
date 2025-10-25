<?php
/**
 * Server C - Edit Item Page
 */

require_once 'config.php';

// Require user to be logged in
requireUser();

$message = '';
$messageType = '';
$item = null;

// Get item ID from URL
$item_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($item_id <= 0) {
    header('Location: user_dashboard.php');
    exit();
}

// Get current user ID
$current_user_id = getCurrentUserId();

// Get all items and find the specific one
$result = makeAPICall('get_items');
$all_items = $result['items'] ?? [];

foreach ($all_items as $i) {
    if ($i['id'] == $item_id && $i['user_id'] == $current_user_id) {
        $item = $i;
        break;
    }
}

if (!$item) {
    header('Location: user_dashboard.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // For now, redirect back to dashboard with a message
    // TODO: Implement update functionality when update_item API is available
    $message = 'Edit functionality will be implemented soon. Please delete and recreate the item for now.';
    $messageType = 'info';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Item - Lost & Found</title>
    <link rel="icon" href="assets/favicon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="style.css">
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
                    <li><a href="user_dashboard.php">My Dashboard</a></li>
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
            <div class="alert <?php echo $messageType === 'success' ? 'alert-success' : ($messageType === 'error' ? 'alert-error' : 'alert-info'); ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="form-container">
            <h2>✏️ Edit Item</h2>
            
            <div style="text-align: center; margin-bottom: 2rem;">
                <a href="user_dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
            </div>

            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="title">Item Title</label>
                    <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($item['title']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="4" required><?php echo htmlspecialchars($item['description']); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="type">Type</label>
                    <select id="type" name="type" required>
                        <option value="lost" <?php echo $item['type'] === 'lost' ? 'selected' : ''; ?>>Lost Item</option>
                        <option value="found" <?php echo $item['type'] === 'found' ? 'selected' : ''; ?>>Found Item</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="location">Location</label>
                    <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($item['location']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="contact">Contact Information</label>
                    <input type="text" id="contact" name="contact" value="<?php echo htmlspecialchars($item['contact']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="image">Current Image</label>
                    <?php if ($item['image'] && file_exists('../ServerB/uploads/' . $item['image'])): ?>
                        <div style="margin-bottom: 1rem;">
                            <img src="../ServerB/uploads/<?php echo htmlspecialchars($item['image']); ?>" 
                                 alt="Current item image" 
                                 style="max-width: 200px; max-height: 200px; border-radius: 8px;">
                        </div>
                    <?php endif; ?>
                    <label for="new_image">Upload New Image (optional)</label>
                    <input type="file" id="new_image" name="new_image" accept="image/*">
                    <small>Leave empty to keep current image</small>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn">Update Item</button>
                    <a href="user_dashboard.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </main>

    <footer>
        <p>&copy; 2024 University Lost and Found Portal. Built to help our campus community stay connected.</p>
    </footer>
    
    <script src="script.js"></script>
</body>
</html>
