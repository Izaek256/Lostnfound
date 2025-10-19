<?php
/**
 * User Dashboard
 * 
 * Personal dashboard for logged-in users
 * Users can:
 * - View their posted items
 * - Edit their items
 * - Delete their items directly
 */

require_once 'db.php';
require_once 'user_config.php';

// Require user to be logged in
requireUser();

// Handle logout
if (isset($_GET['logout'])) {
    logoutUser();
}

$message = '';
$messageType = '';

// Get current user info
$userId = getCurrentUserId();
$username = getCurrentUsername();
$userEmail = getCurrentUserEmail();

// Handle item deletion
if (isset($_POST['delete_item'])) {
    $itemId = mysqli_real_escape_string($conn, $_POST['item_id']);
    
    // Verify item belongs to user
    $sql = "SELECT id, image FROM items WHERE id = '$itemId' AND user_id = '$userId'";
    $result = mysqli_query($conn, $sql);
    
    if (mysqli_num_rows($result) > 0) {
        $item = mysqli_fetch_assoc($result);
        
        // Delete image file if exists
        if ($item['image'] && file_exists('uploads/' . $item['image'])) {
            unlink('uploads/' . $item['image']);
        }
        
        // Delete item from database
        $sql = "DELETE FROM items WHERE id = '$itemId' AND user_id = '$userId'";
        if (mysqli_query($conn, $sql)) {
            $message = 'Item deleted successfully!';
            $messageType = 'success';
        } else {
            $message = 'Error deleting item: ' . mysqli_error($conn);
            $messageType = 'error';
        }
    }
}

// Get user's items
$sql = "SELECT * FROM items WHERE user_id = '$userId' ORDER BY created_at DESC";
$result = mysqli_query($conn, $sql);
$userItems = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Get statistics
$sql = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN type = 'lost' THEN 1 ELSE 0 END) as lost_count,
        SUM(CASE WHEN type = 'found' THEN 1 ELSE 0 END) as found_count
        FROM items WHERE user_id = '$userId'";
$result = mysqli_query($conn, $sql);
$stats = mysqli_fetch_assoc($result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard - University Lost and Found</title>
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
                    <li><a href="user_dashboard.php" class="active">My Dashboard</a></li>
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
                    <div class="item-card">
                        <span class="item-type <?php echo $item['type']; ?>">
                            <?php echo $item['type'] === 'lost' ? '❌ Lost' : '✅ Found'; ?>
                        </span>
                        
                        <?php if ($item['image'] && file_exists('uploads/' . $item['image'])): ?>
                            <img src="uploads/<?php echo htmlspecialchars($item['image']); ?>" 
                                 alt="<?php echo htmlspecialchars($item['title']); ?>" 
                                 class="item-image">
                        <?php endif; ?>
                        
                        <h3><?php echo htmlspecialchars($item['title']); ?></h3>
                        <p><strong>Description:</strong> <?php echo htmlspecialchars($item['description']); ?></p>
                        <p><strong>📍 Location:</strong> <?php echo htmlspecialchars($item['location']); ?></p>
                        <p><strong>📧 Contact:</strong> <?php echo htmlspecialchars($item['contact']); ?></p>
                        
                        <div class="item-meta">
                            <p><strong>Posted:</strong> <?php echo date('M j, Y g:i A', strtotime($item['created_at'])); ?></p>
                        </div>
                        
                        <div style="margin-top: 1rem; display: flex; gap: 0.5rem;">
                            <a href="edit_item.php?id=<?php echo $item['id']; ?>" class="btn btn-secondary" style="flex: 1; text-align: center; padding: 0.6rem;">
                                ✏️ Edit
                            </a>
                            
                            <form method="POST" style="flex: 1;" onsubmit="return confirm('Are you sure you want to delete this item? This cannot be undone.')">
                                <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                <button type="submit" name="delete_item" class="btn btn-danger" style="width: 100%; padding: 0.6rem;">
                                    🗑️ Delete
                                </button>
                            </form>
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
        <p>&copy; 2024 University Lost and Found Portal. Built to help our campus community stay connected.</p>
    </footer>
</body>
</html>
