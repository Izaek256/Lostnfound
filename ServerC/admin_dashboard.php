<?php
/**
 * Server C - Admin Dashboard Page
 */

require_once 'config.php';

// Require user to be logged in as admin
if (!isUserLoggedIn() || !isCurrentUserAdmin()) {
    header('Location: user_login.php');
    exit();
}

// Handle logout
if (isset($_GET['logout'])) {
    logoutUser();
}

$user_id = getCurrentUserId();
$username = getCurrentUsername();

// Get all items for admin overview
$conn = connectDB();

$sql = "SELECT i.*, u.username FROM items i LEFT JOIN users u ON i.user_id = u.id ORDER BY i.created_at DESC";
$result = mysqli_query($conn, $sql);
$all_items = [];

while ($row = mysqli_fetch_assoc($result)) {
    $all_items[] = $row;
}

// Get user statistics
$stats = [
    'total_users' => 0,
    'admin_users' => 0,
    'regular_users' => 0,
    'total_items' => count($all_items),
    'lost_items' => count(array_filter($all_items, function($item) { return $item['type'] === 'lost'; })),
    'found_items' => count(array_filter($all_items, function($item) { return $item['type'] === 'found'; }))
];

// Get user statistics
$user_sql = "SELECT COUNT(*) as total_users FROM users";
$user_result = mysqli_query($conn, $user_sql);
$user_data = mysqli_fetch_assoc($user_result);

$admin_sql = "SELECT COUNT(*) as admin_users FROM users WHERE is_admin = 1";
$admin_result = mysqli_query($conn, $admin_sql);
$admin_data = mysqli_fetch_assoc($admin_result);

$regular_sql = "SELECT COUNT(*) as regular_users FROM users WHERE is_admin = 0";
$regular_result = mysqli_query($conn, $regular_sql);
$regular_data = mysqli_fetch_assoc($regular_result);

$stats['total_users'] = $user_data['total_users'];
$stats['admin_users'] = $admin_data['admin_users'];
$stats['regular_users'] = $regular_data['regular_users'];

mysqli_close($conn);

// Handle admin actions
$action_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'delete_item':
                if (isset($_POST['item_id'])) {
                    $item_id = $_POST['item_id'];
                    $conn = connectDB();
                    
                    $delete_sql = "DELETE FROM items WHERE id = '$item_id'";
                    
                    if (mysqli_query($conn, $delete_sql)) {
                        $action_message = '<div class="alert alert-success">Item deleted successfully.</div>';
                    } else {
                        $action_message = '<div class="alert alert-error">Failed to delete item.</div>';
                    }
                    
                    mysqli_close($conn);
                }
                break;
            
            case 'toggle_user_status':
                if (isset($_POST['user_id'])) {
                    $user_id_to_toggle = $_POST['user_id'];
                    $new_status = $_POST['new_status'];
                    
                    $conn = connectDB();
                    $update_query = "UPDATE users SET is_admin = '$new_status' WHERE id = '$user_id_to_toggle'";
                    
                    if (mysqli_query($conn, $update_query)) {
                        $action_message = '<div class="alert alert-success">User status updated successfully.</div>';
                    } else {
                        $action_message = '<div class="alert alert-error">Failed to update user status.</div>';
                    }
                    
                    mysqli_close($conn);
                }
                break;
        }
        
        // Refresh page to show updated data
        header('Location: admin_dashboard.php');
        exit();
    }
}

// Get all users for management
$users = [];
$conn = connectDB();
$users_result = mysqli_query($conn, "SELECT id, username, email, is_admin, created_at FROM users ORDER BY created_at DESC");
if ($users_result && mysqli_num_rows($users_result) > 0) {
    while ($user = mysqli_fetch_assoc($users_result)) {
        $users[] = $user;
    }
}
mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Lost & Found</title>
    <link rel="icon" href="assets/favicon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="assets/style.css">
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
                        <li><a href="user_dashboard.php">My Dashboard</a></li>
                        <?php if (isCurrentUserAdmin()): ?>
                            <li><a href="admin_dashboard.php" class="active">Admin Panel</a></li>
                        <?php endif; ?>
                        <li><a href="admin_dashboard.php?logout=1">Logout</a></li>
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
        <div class="hero" style="background: linear-gradient(135deg, #dc3545 0%, #b02a37 100%);">
            <h2>🛡️ Admin Dashboard</h2>
            <p>Welcome, <?php echo htmlspecialchars($username); ?>! Manage the Lost & Found system.</p>
        </div>

        <div class="dashboard">
            <?php echo $action_message; ?>
            
            <!-- Statistics Overview -->
            <div class="dashboard-stats">
                <div class="stat-card">
                    <h3>👥 Total Users</h3>
                    <p class="stat-number"><?php echo $stats['total_users']; ?></p>
                    <p class="stat-detail">
                        <?php echo $stats['admin_users']; ?> admins, 
                        <?php echo $stats['regular_users']; ?> users
                    </p>
                </div>
                
                <div class="stat-card">
                    <h3>📦 Total Items</h3>
                    <p class="stat-number"><?php echo $stats['total_items']; ?></p>
                    <p class="stat-detail">
                        <?php echo $stats['lost_items']; ?> lost, 
                        <?php echo $stats['found_items']; ?> found
                    </p>
                </div>
                
                <div class="stat-card">
                    <h3>🔍 Lost Items</h3>
                    <p class="stat-number"><?php echo $stats['lost_items']; ?></p>
                    <p class="stat-detail">Items waiting to be found</p>
                </div>
                
                <div class="stat-card">
                    <h3>✅ Found Items</h3>
                    <p class="stat-number"><?php echo $stats['found_items']; ?></p>
                    <p class="stat-detail">Items waiting to be claimed</p>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="dashboard-actions">
                <a href="items.php" class="btn btn-primary">📦 Manage All Items</a>
                <a href="#users-section" class="btn btn-secondary">👥 Manage Users</a>
                <a href="report_lost.php" class="btn btn-outline">➕ Add Lost Item</a>
                <a href="report_found.php" class="btn btn-outline">➕ Add Found Item</a>
            </div>

            <!-- All Items Management -->
            <?php if (!empty($all_items)): ?>
            <div class="admin-section">
                <h3>📦 All Items (<?php echo count($all_items); ?>)</h3>
                <div class="admin-table">
                    <div class="table-header">
                        <h3>Complete Items List</h3>
                    </div>
                    <div class="table-content">
                        <?php foreach ($all_items as $item): ?>
                        <div class="item-row">
                            <div class="item-image-container">
                                <img src="<?php echo getImageUrl($item['image']); ?>" 
                                     alt="<?php echo htmlspecialchars($item['title']); ?>"
                                     onerror="this.src='assets/default-item.jpg'">
                            </div>
                            <div class="item-info">
                                <h4><?php echo htmlspecialchars($item['title']); ?></h4>
                                <span class="item-type-badge <?php echo $item['type']; ?>">
                                    <?php echo strtoupper($item['type']); ?>
                                </span>
                                <p class="item-user">👤 <?php echo htmlspecialchars($item['username'] ? $item['username'] : 'User ID: ' . $item['user_id']); ?></p>
                                <p class="item-date">📅 <?php echo date('M j, Y', strtotime($item['created_at'])); ?></p>
                            </div>
                            <div class="item-actions">
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this item?');">
                                    <input type="hidden" name="action" value="delete_item">
                                    <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                    <button type="submit" class="delete-btn">🗑️ Delete</button>
                                </form>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- User Management -->
            <?php if (!empty($users)): ?>
            <div class="admin-section" id="users-section">
                <h3>👥 User Management</h3>
                <div class="users-table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $user['is_admin'] ? 'admin' : 'user'; ?>">
                                        <?php echo $user['is_admin'] ? '👑 Admin' : '👤 User'; ?>
                                    </span>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <?php if ($user['id'] != getCurrentUserId()): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="toggle_user_status">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <input type="hidden" name="new_status" value="<?php echo $user['is_admin'] ? 0 : 1; ?>">
                                        <button type="submit" class="btn btn-sm <?php echo $user['is_admin'] ? 'btn-warning' : 'btn-success'; ?>" 
                                                onclick="return confirm('Are you sure you want to change this user\'s status?');">
                                            <?php echo $user['is_admin'] ? '⬇️ Remove Admin' : '⬆️ Make Admin'; ?>
                                        </button>
                                    </form>
                                    <?php else: ?>
                                    <span class="text-muted">Current User</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- System Information -->
            <div class="admin-section">
                <h3>ℹ️ System Information</h3>
                <div class="info-grid">
                    <div class="info-card">
                        <h4>🖥️ Server Configuration</h4>
                        <p><strong>Server:</strong> ServerC (Frontend & Admin)</p>
                        <p><strong>Database:</strong> ServerA (User Management)</p>
                        <p><strong>Storage:</strong> ServerB (Item Management)</p>
                        <p><strong>Session:</strong> <?php echo session_id() ? 'Active' : 'Inactive'; ?></p>
                    </div>
                    
                    <div class="info-card">
                        <h4>📊 Quick Stats</h4>
                        <p><strong>Your Role:</strong> Administrator</p>
                        <p><strong>Login Time:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
                        <p><strong>User ID:</strong> <?php echo getCurrentUserId(); ?></p>
                        <p><strong>PHP Version:</strong> <?php echo PHP_VERSION; ?></p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="assets/script.js"></script>
    <script>
        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            });
        }, 5000);
    </script>
</body>
</html>
