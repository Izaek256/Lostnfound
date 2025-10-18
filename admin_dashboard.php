<?php
/**
 * Admin Dashboard
 * 
 * This is the main admin control panel.
 * It provides:
 * - Overview statistics (total items, lost, found)
 * - List of recent items
 * - Ability to delete items
 * - Links to other admin functions
 * 
 * Only accessible to logged-in administrators.
 */

// Include admin authentication functions
require_once 'admin_config.php';

// Include database connection
require_once 'db.php';

// Check if user is logged in as admin
// If not, this will redirect to login page
requireAdmin();

// Handle logout request
// If user clicked logout button, log them out
if (isset($_GET['logout'])) {
    logoutAdmin();
}

// Handle item deletion
// If admin clicked delete button on an item
if (isset($_POST['delete_item'])) {
    // Get the ID of the item to delete
    $itemId = $_POST['item_id'];
    
    // Escape the ID to prevent SQL injection
    $itemId = mysqli_real_escape_string($conn, $itemId);
    
    // First, get the image filename so we can delete the file too
    $sql = "SELECT image FROM items WHERE id = '$itemId'";
    $result = mysqli_query($conn, $sql);
    $item = mysqli_fetch_assoc($result);
    
    // Delete the image file if it exists
    if ($item['image'] && file_exists('uploads/' . $item['image'])) {
        unlink('uploads/' . $item['image']);
    }
    
    // Delete the item from the database
    $sql = "DELETE FROM items WHERE id = '$itemId'";
    
    if (mysqli_query($conn, $sql)) {
        $success = "Item deleted successfully";
    } else {
        $error = "Error deleting item: " . mysqli_error($conn);
    }
}

// Handle deletion request approval
if (isset($_POST['approve_deletion'])) {
    $requestId = mysqli_real_escape_string($conn, $_POST['request_id']);
    $itemId = mysqli_real_escape_string($conn, $_POST['item_id']);
    
    // Get item image
    $sql = "SELECT image FROM items WHERE id = '$itemId'";
    $result = mysqli_query($conn, $sql);
    $item = mysqli_fetch_assoc($result);
    
    // Delete image
    if ($item && $item['image'] && file_exists('uploads/' . $item['image'])) {
        unlink('uploads/' . $item['image']);
    }
    
    // Delete item
    $sql = "DELETE FROM items WHERE id = '$itemId'";
    if (mysqli_query($conn, $sql)) {
        // Mark request as approved (will be auto-deleted due to CASCADE)
        $success = "Deletion request approved and item deleted";
    } else {
        $error = "Error deleting item: " . mysqli_error($conn);
    }
}

// Handle deletion request rejection
if (isset($_POST['reject_deletion'])) {
    $requestId = mysqli_real_escape_string($conn, $_POST['request_id']);
    
    $sql = "UPDATE deletion_requests SET status = 'rejected' WHERE id = '$requestId'";
    if (mysqli_query($conn, $sql)) {
        $success = "Deletion request rejected";
    } else {
        $error = "Error rejecting request: " . mysqli_error($conn);
    }
}

// Handle user deletion
if (isset($_POST['delete_user'])) {
    $userId = mysqli_real_escape_string($conn, $_POST['user_id']);
    
    // This will cascade delete user's items and requests
    $sql = "DELETE FROM users WHERE id = '$userId'";
    if (mysqli_query($conn, $sql)) {
        $success = "User deleted successfully";
    } else {
        $error = "Error deleting user: " . mysqli_error($conn);
    }
}

// Get pending deletion requests
$sql = "SELECT dr.*, items.title, items.type, users.username 
        FROM deletion_requests dr
        JOIN items ON dr.item_id = items.id
        JOIN users ON dr.user_id = users.id
        WHERE dr.status = 'pending'
        ORDER BY dr.created_at DESC";
$result = mysqli_query($conn, $sql);
$deletionRequests = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Get all users
$sql = "SELECT users.*, COUNT(items.id) as item_count 
        FROM users 
        LEFT JOIN items ON users.id = items.user_id 
        GROUP BY users.id 
        ORDER BY users.created_at DESC";
$result = mysqli_query($conn, $sql);
$allUsers = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Get statistics for dashboard display
$sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN type = 'lost' THEN 1 ELSE 0 END) as lost_count,
    SUM(CASE WHEN type = 'found' THEN 1 ELSE 0 END) as found_count
    FROM items";
    
$result = mysqli_query($conn, $sql);
$stats = mysqli_fetch_assoc($result);

// Get the 10 most recent items
// Admin can see recent activity at a glance
$sql = "SELECT * FROM items ORDER BY created_at DESC LIMIT 10";
$result = mysqli_query($conn, $sql);
$recentItems = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - University Lost and Found</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .admin-header {
            background: #dc3545;
            border-bottom: 3px solid #c82333;
            padding: 1.5rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .admin-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .admin-title {
            color: white;
            font-size: 1.75rem;
            font-weight: 600;
            margin: 0;
        }
        
        .admin-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        
        .admin-actions span {
            color: white;
            font-size: 1rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            border: 2px solid var(--border);
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .stat-card:hover {
            border-color: var(--primary);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 0.5rem;
            line-height: 1;
        }
        
        .stat-label {
            color: var(--text-secondary);
            font-size: 1rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .admin-table {
            background: white;
            border-radius: 8px;
            border: 2px solid var(--border);
            overflow: hidden;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .table-header {
            background: var(--bg-secondary);
            padding: 1.5rem;
            border-bottom: 2px solid var(--border);
        }
        
        .table-header h3 {
            color: var(--text-primary);
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0;
        }
        
        .table-content {
            max-height: 600px;
            overflow-y: auto;
        }
        
        .item-row {
            display: grid;
            grid-template-columns: auto 1fr auto auto auto;
            gap: 1rem;
            padding: 1.5rem;
            border-bottom: 1px solid var(--border);
            align-items: center;
        }
        
        .item-row:hover {
            background: var(--bg-secondary);
        }
        
        .item-row:last-child {
            border-bottom: none;
        }
        
        .item-type-badge {
            padding: 0.4rem 1rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .item-info h4 {
            color: var(--text-primary);
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }
        
        .item-info p {
            color: var(--text-secondary);
            font-size: 0.95rem;
            margin: 0.25rem 0;
            line-height: 1.5;
        }
        
        .item-date {
            color: var(--text-secondary);
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .delete-btn {
            background: var(--error);
            border: none;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .delete-btn:hover {
            background: #dc2626;
        }
        
        .no-items {
            text-align: center;
            padding: 3rem;
            color: var(--text-secondary);
        }
        
        @media (max-width: 768px) {
            .admin-nav {
                flex-direction: column;
                gap: 1rem;
            }
            
            .admin-actions {
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .item-row {
                grid-template-columns: 1fr;
                gap: 0.5rem;
                text-align: center;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <!-- Admin Header -->
    <div class="admin-header">
        <div class="admin-nav">
            <div class="admin-title">
                🛡️ Admin Dashboard
            </div>
            <div class="admin-actions">
                <span>Welcome, Admin</span>
                <a href="index.php" class="btn btn-secondary">View Portal</a>
                <a href="?logout=1" class="btn btn-danger" onclick="return confirm('Are you sure you want to logout?')">Logout</a>
            </div>
        </div>
    </div>

    <main>
        <!-- Display messages -->
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total']; ?></div>
                <div class="stat-label">Total Items</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['lost_count']; ?></div>
                <div class="stat-label">Lost Items</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['found_count']; ?></div>
                <div class="stat-label">Found Items</div>
            </div>
        </div>

        <!-- Recent Items Table -->
        <div class="admin-table">
            <div class="table-header">
                <h3>📋 Recent Items</h3>
            </div>
            
            <div class="table-content">
                <?php if (count($recentItems) > 0): ?>
                    <?php foreach ($recentItems as $item): ?>
                    <div class="item-row">
                        <span class="item-type-badge <?php echo $item['type']; ?>">
                            <?php echo $item['type'] === 'lost' ? '❌ Lost' : '✅ Found'; ?>
                        </span>
                        
                        <div class="item-info">
                            <h4><?php echo $item['title']; ?></h4>
                            <p><strong>Location:</strong> <?php echo $item['location']; ?></p>
                            <p><strong>Contact:</strong> <?php echo $item['contact']; ?></p>
                        </div>
          
                        
                        <div class="item-date">
                            <?php echo date('M j, Y', strtotime($item['created_at'])); ?>
                        </div>
                        
                        <div>
                            <a href="items.php" class="btn btn-secondary" style="padding: 0.4rem 0.8rem; font-size: 0.8rem;">View</a>
                        </div>
                        
                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this item? This action cannot be undone.')">
                            <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                            <button type="submit" name="delete_item" class="delete-btn">🗑️ Delete</button>
                        </form>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-items">
                        <div style="font-size: 3rem; margin-bottom: 1rem;">📭</div>
                        <p>No items found in the database.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Pending Deletion Requests -->
        <?php if (count($deletionRequests) > 0): ?>
        <div class="admin-table" style="margin-top: 2rem;">
            <div class="table-header">
                <h3>⌛ Pending Deletion Requests (<?php echo count($deletionRequests); ?>)</h3>
            </div>
            
            <div class="table-content">
                <?php foreach ($deletionRequests as $request): ?>
                <div class="item-row">
                    <span class="item-type-badge <?php echo $request['type']; ?>">
                        <?php echo $request['type'] === 'lost' ? '❌ Lost' : '✅ Found'; ?>
                    </span>
                    
                    <div class="item-info">
                        <h4><?php echo htmlspecialchars($request['title']); ?></h4>
                        <p><strong>Requested by:</strong> <?php echo htmlspecialchars($request['username']); ?></p>
                        <p><strong>Requested on:</strong> <?php echo date('M j, Y', strtotime($request['created_at'])); ?></p>
                    </div>
                    
                    <form method="POST" style="display: inline;" onsubmit="return confirm('Approve deletion and delete this item?')">
                        <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                        <input type="hidden" name="item_id" value="<?php echo $request['item_id']; ?>">
                        <button type="submit" name="approve_deletion" class="btn btn-success" style="padding: 0.5rem 1rem; font-size: 0.8rem;">
                            ✅ Approve
                        </button>
                    </form>
                    
                    <form method="POST" style="display: inline;" onsubmit="return confirm('Reject this deletion request?')">
                        <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                        <button type="submit" name="reject_deletion" class="delete-btn">
                            ❌ Reject
                        </button>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- User Management -->
        <div class="admin-table" style="margin-top: 2rem;">
            <div class="table-header">
                <h3>👥 User Management (<?php echo count($allUsers); ?> users)</h3>
            </div>
            
            <div class="table-content" style="max-height: 400px;">
                <?php if (count($allUsers) > 0): ?>
                    <?php foreach ($allUsers as $user): ?>
                    <div class="item-row" style="grid-template-columns: 1fr auto auto auto;">
                        <div class="item-info">
                            <h4><?php echo htmlspecialchars($user['username']); ?></h4>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                            <p><strong>Items Posted:</strong> <?php echo $user['item_count']; ?> | <strong>Joined:</strong> <?php echo date('M j, Y', strtotime($user['created_at'])); ?></p>
                        </div>
                        
                        <div>
                            <a href="items.php" class="btn btn-secondary" style="padding: 0.4rem 0.8rem; font-size: 0.8rem;">View Items</a>
                        </div>
                        
                        <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this user? This will also delete all their items.')">
                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                            <button type="submit" name="delete_user" class="delete-btn">🗑️ Delete User</button>
                        </form>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-items">
                        <p>No registered users yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="form-container" style="margin-top: 2rem;">
            <h2>⚡ Quick Actions</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-top: 2rem;">
                <div style="text-align: center;">
                    <div style="font-size: 2.5rem; margin-bottom: 1rem;">📊</div>
                    <h4 style="color: white; margin-bottom: 1rem;">View All Items</h4>
                    <p style="color: rgba(255, 255, 255, 0.8); margin-bottom: 1.5rem;">Browse and manage all lost and found items</p>
                    <a href="items.php" class="btn">View Items</a>
                </div>
                
                <div style="text-align: center;">
                    <div style="font-size: 2.5rem; margin-bottom: 1rem;">🏠</div>
                    <h4 style="color: white; margin-bottom: 1rem;">Portal Home</h4>
                    <p style="color: rgba(255, 255, 255, 0.8); margin-bottom: 1.5rem;">Return to the main portal interface</p>
                    <a href="index.php" class="btn btn-success">Go to Portal</a>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <p>&copy; 2024 University Lost and Found Portal - Admin Dashboard</p>
    </footer>

    <script>
        // Confirm before deleting
        function confirmDelete() {
            return confirm('Are you sure you want to delete this item?');
        }
    </script>
</body>
</html>
