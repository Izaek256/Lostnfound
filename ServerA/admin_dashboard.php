<?php
/**
 * Server A - Admin Dashboard
 * 
 * This page provides admin control panel for Server A
 */

require_once 'config.php';

// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header('Location: admin_login.php');
    exit();
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin_login.php');
    exit();
}

$conn = getDBConnection();

// Handle item deletion
if (isset($_POST['delete_item'])) {
    $itemId = (int)($_POST['item_id'] ?? 0);
    
    if ($itemId > 0) {
        // Get image filename
        $sql = "SELECT image FROM items WHERE id = '$itemId'";
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            $item = $result->fetch_assoc();
            
            // Delete image file if exists
            if ($item['image'] && file_exists('uploads/' . $item['image'])) {
                unlink('uploads/' . $item['image']);
            }
            
            // Delete from database
            $sql = "DELETE FROM items WHERE id = '$itemId'";
            if ($conn->query($sql)) {
                $success = "Item deleted successfully";
            } else {
                $error = "Error deleting item: " . $conn->error;
            }
        }
    }
}

// Handle user deletion
if (isset($_POST['delete_user'])) {
    $userId = (int)($_POST['user_id'] ?? 0);
    
    if ($userId > 0) {
        $sql = "DELETE FROM users WHERE id = '$userId'";
        if ($conn->query($sql)) {
            $success = "User deleted successfully";
        } else {
            $error = "Error deleting user: " . $conn->error;
        }
    }
}

// Handle admin rights toggle
if (isset($_POST['toggle_admin'])) {
    $userId = (int)($_POST['user_id'] ?? 0);
    $currentStatus = (int)($_POST['current_status'] ?? 0);
    $newStatus = $currentStatus == 1 ? 0 : 1;
    
    if ($userId > 0) {
        $sql = "UPDATE users SET is_admin = '$newStatus' WHERE id = '$userId'";
        if ($conn->query($sql)) {
            $success = $newStatus == 1 ? "Admin rights granted successfully" : "Admin rights removed successfully";
        } else {
            $error = "Error updating admin status: " . $conn->error;
        }
    }
}

// Get statistics
$sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN type = 'lost' THEN 1 ELSE 0 END) as lost_count,
    SUM(CASE WHEN type = 'found' THEN 1 ELSE 0 END) as found_count
    FROM items";
$result = $conn->query($sql);
$stats = $result->fetch_assoc();

// Get recent items
$sql = "SELECT * FROM items ORDER BY created_at DESC LIMIT 10";
$result = $conn->query($sql);
$recentItems = [];
while ($row = $result->fetch_assoc()) {
    $recentItems[] = $row;
}

// Get all users
$sql = "SELECT users.*, COUNT(items.id) as item_count 
        FROM users 
        LEFT JOIN items ON users.id = items.user_id 
        GROUP BY users.id 
        ORDER BY users.created_at DESC";
$result = $conn->query($sql);
$allUsers = [];
while ($row = $result->fetch_assoc()) {
    $allUsers[] = $row;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Server A</title>
    <link rel="stylesheet" href="../ServerC/style.css">
</head>
<body>
    <!-- Header -->
    <header>
        <div class="header-content">
            <div class="logo">
                <h1>🛡️ Server A - Admin Dashboard</h1>
            </div>
            <nav>
                <ul>
                    <li><a href="?logout=1" class="btn btn-secondary">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <main>
        <div class="hero" style="background: linear-gradient(135deg, #dc3545 0%, #b02a37 100%);">
            <h2>Admin Dashboard</h2>
            <p>Main Backend Server | Database Host | Full System Management</p>
        </div>
        
        <div class="form-container">
            <h2>🖥️ Server Information</h2>
            <div class="item-detail">
                <div class="item-detail-content">
                    <strong>Role:</strong> Main Backend Server & Database Host<br>
                    <strong>Database:</strong> MySQL (lostfound_db)<br>
                    <strong>APIs:</strong> /api/ directory<br>
                    <strong>Admin Panel:</strong> Full system management
                </div>
            </div>
        </div>

        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Statistics -->
        <section class="form-container">
            <h2>📊 System Statistics</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 2rem; text-align: center;">
                <div style="padding: 1.5rem; background: var(--bg-secondary); border-radius: 10px; border: 1px solid var(--border);">
                    <h3 style="font-size: 2rem; color: var(--primary); margin-bottom: 0.5rem;"><?php echo $stats['total']; ?></h3>
                    <p style="color: var(--text-secondary);">Total Items</p>
                </div>
                <div style="padding: 1.5rem; background: var(--bg-secondary); border-radius: 10px; border: 1px solid var(--border);">
                    <h3 style="font-size: 2rem; color: var(--error); margin-bottom: 0.5rem;"><?php echo $stats['lost_count']; ?></h3>
                    <p style="color: var(--text-secondary);">Lost Items</p>
                </div>
                <div style="padding: 1.5rem; background: var(--bg-secondary); border-radius: 10px; border: 1px solid var(--border);">
                    <h3 style="font-size: 2rem; color: var(--success); margin-bottom: 0.5rem;"><?php echo $stats['found_count']; ?></h3>
                    <p style="color: var(--text-secondary);">Found Items</p>
                </div>
                <div style="padding: 1.5rem; background: var(--bg-secondary); border-radius: 10px; border: 1px solid var(--border);">
                    <h3 style="font-size: 2rem; color: var(--primary-dark); margin-bottom: 0.5rem;"><?php echo count($allUsers); ?></h3>
                    <p style="color: var(--text-secondary);">Total Users</p>
                </div>
            </div>
        </section>

        <!-- Recent Items -->
        <section class="items-container">
            <div class="items-header">
                <h2>🕒 Recent Items</h2>
            </div>
            <?php if (count($recentItems) > 0): ?>
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
                            <h3><?php echo htmlspecialchars($item['title']); ?></h3>
                            
                            <div class="item-card-section">
                                <div class="item-description"><?php echo htmlspecialchars($item['description']); ?></div>
                            </div>
                            
                            <div class="item-card-section">
                                <div class="item-detail">
                                    <div class="item-detail-content">
                                        <strong>Location:</strong> <?php echo htmlspecialchars($item['location']); ?>
                                    </div>
                                </div>
                                <div class="item-detail">
                                    <div class="item-detail-content">
                                        <strong>Contact:</strong> <?php echo htmlspecialchars($item['contact']); ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="item-meta">
                                <p><?php echo date('M j, Y g:i A', strtotime($item['created_at'])); ?></p>
                            </div>
                            
                            <form method="POST" style="margin-top: 1rem;" onsubmit="return confirm('Delete this item?')">
                                <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                <button type="submit" name="delete_item" class="btn btn-danger">🗑️ Delete Item</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="text-align: center; color: var(--text-secondary); padding: 2rem;">No items found.</p>
            <?php endif; ?>
        </section>

        <!-- User Management -->
        <section class="form-container">
            <h2>👥 User Management</h2>
            <?php if (count($allUsers) > 0): ?>
                <div style="display: grid; gap: 1rem;">
                    <?php foreach ($allUsers as $user): ?>
                        <div class="item-card" style="padding: 1.5rem;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem;">
                                <div style="flex: 1; min-width: 250px;">
                                    <h3 style="margin-bottom: 1rem;">
                                        <?php echo htmlspecialchars($user['username']); ?>
                                        <?php if ($user['is_admin'] == 1): ?>
                                            <span class="item-type found" style="position: static; margin-left: 0.5rem;">⭐ ADMIN</span>
                                        <?php endif; ?>
                                    </h3>
                                    <div class="item-detail">
                                        <div class="item-detail-content">
                                            <strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?><br>
                                            <strong>Items Posted:</strong> <?php echo $user['item_count']; ?><br>
                                            <strong>Joined:</strong> <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                    <form method="POST">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <input type="hidden" name="current_status" value="<?php echo $user['is_admin']; ?>">
                                        <button type="submit" name="toggle_admin" class="btn btn-secondary">
                                            <?php echo $user['is_admin'] == 1 ? '❌ Remove Admin' : '⭐ Make Admin'; ?>
                                        </button>
                                    </form>
                                    
                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                        <form method="POST" onsubmit="return confirm('Delete this user?')">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" name="delete_user" class="btn btn-danger">🗑️ Delete User</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="text-align: center; color: var(--text-secondary); padding: 2rem;">No users found.</p>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
