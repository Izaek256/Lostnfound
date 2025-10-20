<?php
/**
 * Grant Admin Rights Script
 * 
 * This is a utility script to grant admin rights to a user.
 * Run this file in your browser to make a user an administrator.
 * 
 * SECURITY WARNING: Only admins can access this file!
 */

require_once 'db.php';
require_once 'user_config.php';
require_once 'admin_config.php';

// Require admin authentication
requireAdmin();

$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    
    // Check if user exists
    $sql = "SELECT id, username, is_admin FROM users WHERE username = '$username'";
    $result = mysqli_query($conn, $sql);
    
    if (mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        
        if ($user['is_admin'] == 1) {
            $message = "User '{$username}' already has admin rights!";
            $messageType = 'info';
        } else {
            // Grant admin rights
            $sql = "UPDATE users SET is_admin = 1 WHERE username = '$username'";
            if (mysqli_query($conn, $sql)) {
                $message = "Admin rights successfully granted to user '{$username}'!";
                $messageType = 'success';
            } else {
                $message = "Error granting admin rights: " . mysqli_error($conn);
                $messageType = 'error';
            }
        }
    } else {
        $message = "User '{$username}' not found!";
        $messageType = 'error';
    }
}

// Get list of all users
$sql = "SELECT id, username, email, is_admin FROM users ORDER BY username";
$result = mysqli_query($conn, $sql);
$users = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grant Admin Rights - University Lost and Found</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .user-item {
            padding: 1rem;
            background: var(--bg-secondary);
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        .user-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
        }
        
        .user-info {
            flex: 1;
            min-width: 0;
        }
        
        .user-badge {
            flex-shrink: 0;
        }
        
        @media (max-width: 768px) {
            .form-container {
                margin: 1rem;
                padding: 1.5rem;
            }
            
            .user-content {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.75rem;
            }
            
            .user-badge {
                width: 100%;
                text-align: center;
            }
            
            .user-badge span {
                display: inline-block;
                width: 100%;
            }
            
            .warning-box {
                padding: 0.85rem;
                font-size: 0.9rem;
            }
        }
        
        @media (max-width: 480px) {
            .form-container {
                margin: 0.5rem;
                padding: 1rem;
            }
            
            h2 {
                font-size: 1.5rem;
            }
            
            h3 {
                font-size: 1.2rem;
            }
            
            .btn {
                font-size: 0.9rem;
                padding: 0.6rem 1rem;
            }
            
            .warning-box {
                padding: 0.75rem;
                font-size: 0.85rem;
            }
        }
    </style>
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
                    <li><a href="admin_dashboard.php">Admin Dashboard</a></li>
                    <li><a href="grant_admin.php" class="active">Grant Admin</a></li>
                    <li><a href="user_dashboard.php">My Dashboard</a></li>
                    <li><a href="user_dashboard.php?logout=1">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <div class="form-container" style="max-width: 600px; margin: 2rem auto;">
            <div class="warning-box" style="background: #ff6b6b; color: white; padding: 1rem; border-radius: 8px; margin-bottom: 2rem; text-align: center;">
                <strong>⚠️ SECURITY WARNING</strong><br>
                Delete this file after granting admin rights to prevent unauthorized access!
            </div>

            <h2>🔐 Grant Admin Rights</h2>
            <p style="text-align: center; color: var(--text-secondary); margin-bottom: 2rem;">
                Enter a username below to grant administrator privileges
            </p>

            <?php if ($message != ''): ?>
                <div class="alert <?php 
                    if ($messageType === 'success') echo 'alert-success';
                    elseif ($messageType === 'error') echo 'alert-error';
                    else echo 'alert-info';
                ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" 
                           id="username" 
                           name="username" 
                           placeholder="Enter username to grant admin rights"
                           required>
                </div>

                <div style="text-align: center; margin-top: 2rem;">
                    <button type="submit" class="btn">Grant Admin Rights</button>
                </div>
            </form>

            <div style="margin-top: 3rem; padding-top: 2rem; border-top: 2px solid var(--border);">
                <h3>📋 Registered Users</h3>
                
                <?php if (count($users) > 0): ?>
                    <div style="max-height: 300px; overflow-y: auto; margin-top: 1rem;">
                        <?php foreach ($users as $user): ?>
                            <div class="user-item">
                                <div class="user-content">
                                    <div class="user-info">
                                        <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                        <br>
                                        <small style="color: var(--text-secondary);"><?php echo htmlspecialchars($user['email']); ?></small>
                                    </div>
                                    <div class="user-badge">
                                        <?php if ($user['is_admin'] == 1): ?>
                                            <span style="background: #10b981; color: white; padding: 0.4rem 0.8rem; border-radius: 6px; font-size: 0.85rem; font-weight: 600;">
                                                ⭐ ADMIN
                                            </span>
                                        <?php else: ?>
                                            <span style="background: #6b7280; color: white; padding: 0.4rem 0.8rem; border-radius: 6px; font-size: 0.85rem;">
                                                USER
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p style="text-align: center; color: var(--text-secondary); padding: 2rem;">
                        No users registered yet.
                    </p>
                <?php endif; ?>
            </div>

            <div style="text-align: center; margin-top: 2rem; padding-top: 2rem; border-top: 1px solid var(--border);">
                <a href="index.php" class="btn btn-secondary">← Back to Portal</a>
            </div>
        </div>
    </main>

    <footer>
        <p>&copy; 2024 University Lost and Found Portal. Built to help our campus community stay connected.</p>
    </footer>
</body>
</html>
