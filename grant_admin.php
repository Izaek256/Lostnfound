<?php
/**
 * Grant Admin Rights Script
 * 
 * This is a utility script to grant admin rights to a user.
 * Run this file in your browser to make a user an administrator.
 * 
 * SECURITY WARNING: Delete or restrict access to this file after using it!
 */

require_once 'db.php';

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
</head>
<body>
    <header>
        <div class="header-content">
            <div class="logo">
                <img src="./assets/logo.webp" alt="Lost & Found Logo">
                <h1>University Lost & Found</h1>
            </div>
        </div>
    </header>

    <main>
        <div class="form-container" style="max-width: 600px; margin: 2rem auto;">
            <div style="background: #ff6b6b; color: white; padding: 1rem; border-radius: 8px; margin-bottom: 2rem; text-align: center;">
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
                            <div style="padding: 1rem; background: var(--bg-secondary); border-radius: 8px; margin-bottom: 1rem;">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                        <br>
                                        <small style="color: var(--text-secondary);"><?php echo htmlspecialchars($user['email']); ?></small>
                                    </div>
                                    <div>
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
