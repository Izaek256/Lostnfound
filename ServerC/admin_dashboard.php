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
$result = makeAPICall('get_items');
$all_items = $result['items'] ?? [];

// Get user statistics
$conn = getDBConnection();
$stats = [
    'total_users' => 0,
    'admin_users' => 0,
    'regular_users' => 0,
    'total_items' => count($all_items),
    'lost_items' => count(array_filter($all_items, function($item) { return $item['type'] === 'lost'; })),
    'found_items' => count(array_filter($all_items, function($item) { return $item['type'] === 'found'; }))
];

if ($conn) {
    // Get user statistics
    $user_stats = $conn->query("SELECT 
        COUNT(*) as total_users,
        SUM(CASE WHEN is_admin = 1 THEN 1 ELSE 0 END) as admin_users,
        SUM(CASE WHEN is_admin = 0 THEN 1 ELSE 0 END) as regular_users
        FROM users");
    
    if ($user_stats && $user_stats->num_rows > 0) {
        $user_data = $user_stats->fetch_assoc();
        $stats['total_users'] = $user_data['total_users'];
        $stats['admin_users'] = $user_data['admin_users'];
        $stats['regular_users'] = $user_data['regular_users'];
    }
    
    $conn->close();
}

// Handle admin actions
$action_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'delete_item':
                if (isset($_POST['item_id'])) {
                    $delete_result = makeAPICall('delete_item', ['id' => $_POST['item_id']], 'POST');
                    if (isset($delete_result['success']) && $delete_result['success']) {
                        $action_message = '<div class="alert alert-success">Item deleted successfully.</div>';
                    } else {
                        $action_message = '<div class="alert alert-error">Failed to delete item: ' . ($delete_result['error'] ?? 'Unknown error') . '</div>';
                    }
                }
                break;
            
            case 'toggle_user_status':
                if (isset($_POST['user_id']) && $conn = getDBConnection()) {
                    $user_id_to_toggle = intval($_POST['user_id']);
                    $new_status = intval($_POST['new_status']);
                    
                    $update_query = "UPDATE users SET is_admin = ? WHERE id = ?";
                    $stmt = $conn->prepare($update_query);
                    $stmt->bind_param("ii", $new_status, $user_id_to_toggle);
                    
                    if ($stmt->execute()) {
                        $action_message = '<div class="alert alert-success">User status updated successfully.</div>';
                    } else {
                        $action_message = '<div class="alert alert-error">Failed to update user status.</div>';
                    }
                    
                    $stmt->close();
                    $conn->close();
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
if ($conn = getDBConnection()) {
    $users_result = $conn->query("SELECT id, username, email, is_admin, created_at FROM users ORDER BY created_at DESC");
    if ($users_result && $users_result->num_rows > 0) {
        while ($user = $users_result->fetch_assoc()) {
            $users[] = $user;
        }
    }
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Lost & Found</title>
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

            <!-- Recent Items Management -->
            <?php if (!empty($all_items)): ?>
            <div class="admin-section">
                <h3>📦 Recent Items Management</h3>
                <div class="items-grid">
                    <?php 
                    // Show only the 6 most recent items
                    $recent_items = array_slice($all_items, 0, 6);
                    foreach ($recent_items as $item): 
                    ?>
                    <div class="item-card admin-item">
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
                            <p class="item-description"><?php echo htmlspecialchars(substr($item['description'], 0, 80)); ?>...</p>
                            <p class="item-location">📍 <?php echo htmlspecialchars($item['location']); ?></p>
                            <p class="item-date">📅 <?php echo date('M j, Y', strtotime($item['created_at'])); ?></p>
                            <p class="item-user">👤 User ID: <?php echo $item['user_id']; ?></p>
                            
                            <div class="admin-actions">
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this item?');">
                                    <input type="hidden" name="action" value="delete_item">
                                    <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">🗑️ Delete</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="section-footer">
                    <a href="items.php" class="btn btn-outline">View All Items →</a>
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

    <script src="script.js"></script>
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
