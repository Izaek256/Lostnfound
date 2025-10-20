<?php
/**
 * User Login Page
 * 
 * Allows users to login to their account
 */

require_once 'db.php';
require_once 'user_config.php';

// Redirect if already logged in
if (isUserLoggedIn()) {
    header('Location: user_dashboard.php');
    exit();
}

$message = '';
$messageType = '';

// Check if user was just registered
if (isset($_GET['registered']) && $_GET['registered'] == '1') {
    $message = 'Account created successfully! Please login with your credentials.';
    $messageType = 'success';
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    // Attempt login
    $error = loginUser($conn, $username, $password);
    
    if (empty($error)) {
        // Login successful, check if user is admin
        if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1) {
            // Redirect admin users to admin dashboard
            header('Location: admin_dashboard.php');
            exit();
        } else {
            // Redirect regular users to user dashboard
            header('Location: user_dashboard.php');
            exit();
        }
    } else {
        $message = $error;
        $messageType = 'error';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Login - University Lost and Found</title>
    <link rel="icon" type="image/svg+xml" href="./assets/favicon.svg">
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
                    <li><a href="user_login.php" class="active">Login</a></li>
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

        <div class="form-container" style="max-width: 500px; margin: 0 auto;">
            <h2>🔐 User Login</h2>
            <p style="text-align: center; color: var(--text-secondary); margin-bottom: 2rem;">
                Login to manage your lost and found items
            </p>
            
            <form method="POST">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" 
                           id="username" 
                           name="username" 
                           placeholder="Enter your username"
                           required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           placeholder="Enter your password"
                           required>
                </div>

                <div style="text-align: center; margin-top: 2rem;">
                    <button type="submit" class="btn">Login</button>
                </div>
            </form>

            <div style="text-align: center; margin-top: 2rem; padding-top: 2rem; border-top: 1px solid var(--border);">
                <p style="color: var(--text-secondary);">
                    Don't have an account? 
                    <a href="user_register.php" style="color: var(--primary); font-weight: 600;">Create one here</a>
                </p>
            </div>
        </div>
    </main>

    <footer>
        <p>&copy; 2024 University Lost and Found Portal. Built to help our campus community stay connected.</p>
    </footer>
    
    <script src="script.js"></script>
</body>
</html>
