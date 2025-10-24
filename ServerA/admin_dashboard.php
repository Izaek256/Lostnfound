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
    <link rel="stylesheet" href="../ServerB/style.css">
    <style>
        .admin-header {
            background: #dc3545;
            color: white;
            padding: 1rem;
            text-align: center;
            margin-bottom: 2rem;
        }
        .server-info {
            background: #e3f2fd;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <h1>🛡️ Server A - Admin Dashboard</h1>
        <p>Main Backend Server | Database Host</p>
        <a href="?logout=1" style="color: white; text-decoration: underline;">Logout</a>
    </div>

    <main>
        <div class="server-info">
            <h3>🖥️ Server A Information</h3>
            <p><strong>Role:</strong> Main Backend Server & Database Host</p>
            <p><strong>Database:</strong> MySQL (lostfound_db)</p>
            <p><strong>APIs:</strong> /api/ directory</p>
            <p><strong>Admin Panel:</strong> Full system management</p>
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
                <div style="padding: 1.5rem; background: #f8f9fa; border-radius: 10px;">
                    <h3 style="font-size: 2rem; color: #667eea;"><?php echo $stats['total']; ?></h3>
                    <p>Total Items</p>
                </div>
                <div style="padding: 1.5rem; background: #f8f9fa; border-radius: 10px;">
                    <h3 style="font-size: 2rem; color: #dc3545;"><?php echo $stats['lost_count']; ?></h3>
                    <p>Lost Items</p>
                </div>
                <div style="padding: 1.5rem; background: #f8f9fa; border-radius: 10px;">
                    <h3 style="font-size: 2rem; color: #28a745;"><?php echo $stats['found_count']; ?></h3>
                    <p>Found Items</p>
                </div>
                <div style="padding: 1.5rem; background: #f8f9fa; border-radius: 10px;">
                    <h3 style="font-size: 2rem; color: #6f42c1;"><?php echo count($allUsers); ?></h3>
                    <p>Total Users</p>
                </div>
            </div>
        </section>

        <!-- Recent Items -->
        <section class="form-container">
            <h2>🕒 Recent Items</h2>
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
                                <?php endif; ?>
                            </div>
                            
                            <div class="item-card-body">
                                <h3><?php echo htmlspecialchars($item['title']); ?></h3>
                                <p><?php echo htmlspecialchars($item['description']); ?></p>
                                <p><strong>Location:</strong> <?php echo htmlspecialchars($item['location']); ?></p>
                                <p><strong>Contact:</strong> <?php echo htmlspecialchars($item['contact']); ?></p>
                                <p><strong>Posted:</strong> <?php echo date('M j, Y g:i A', strtotime($item['created_at'])); ?></p>
                                
                                <form method="POST" style="margin-top: 1rem;" onsubmit="return confirm('Delete this item?')">
                                    <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                    <button type="submit" name="delete_item" class="btn" style="background: #dc3545;">🗑️ Delete Item</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>No items found.</p>
            <?php endif; ?>
        </section>

        <!-- User Management -->
        <section class="form-container">
            <h2>👥 User Management</h2>
            <?php if (count($allUsers) > 0): ?>
                <div style="display: grid; gap: 1rem;">
                    <?php foreach ($allUsers as $user): ?>
                        <div style="padding: 1rem; border: 1px solid #ddd; border-radius: 8px; display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <h4>
                                    <?php echo htmlspecialchars($user['username']); ?>
                                    <?php if ($user['is_admin'] == 1): ?>
                                        <span style="background: #28a745; color: white; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.8rem;">⭐ ADMIN</span>
                                    <?php endif; ?>
                                </h4>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                                <p><strong>Items Posted:</strong> <?php echo $user['item_count']; ?></p>
                                <p><strong>Joined:</strong> <?php echo date('M j, Y', strtotime($user['created_at'])); ?></p>
                            </div>
                            
                            <div style="display: flex; gap: 0.5rem;">
                                <form method="POST">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <input type="hidden" name="current_status" value="<?php echo $user['is_admin']; ?>">
                                    <button type="submit" name="toggle_admin" class="btn" style="background: #6f42c1;">
                                        <?php echo $user['is_admin'] == 1 ? '❌ Remove Admin' : '⭐ Make Admin'; ?>
                                    </button>
                                </form>
                                
                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                    <form method="POST" onsubmit="return confirm('Delete this user?')">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" name="delete_user" class="btn" style="background: #dc3545;">🗑️ Delete User</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>No users found.</p>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
