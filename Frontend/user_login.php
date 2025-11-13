<?php
/**
 * Server C - User Login Page
 */

require_once 'config.php';

// Redirect if already logged in
if (isUserLoggedIn()) {
    header('Location: index.php');
    exit();
}

$error = '';
$success = '';

// Check if user was redirected from registration
if (isset($_GET['registered']) && $_GET['registered'] == '1') {
    $success = 'Registration successful! Please login with your credentials.';
}

if ($_POST) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    if (!empty($username) && !empty($password)) {
        // Call UserServer API to verify user
        $response = makeAPIRequest(USERSERVER_URL . '/verify_user.php', [
            'username' => $username,
            'password' => $password
        ], 'POST', ['return_json' => true, 'force_json' => true]);
        
        // Parse JSON response
        if (is_array($response) && isset($response['success']) && $response['success']) {
            $_SESSION['user_id'] = $response['user_id'];
            $_SESSION['username'] = $response['username'];
            $_SESSION['user_email'] = $response['email'];
            $_SESSION['is_admin'] = $response['is_admin'] ?? 0;
            
            header('Location: user_dashboard.php');
            exit();
        } else {
            $error = isset($response['error']) ? $response['error'] : 'Login failed';
        }
    } else {
        $error = 'Please fill all fields';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Login - Lost & Found</title>
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
                            <li><a href="admin_dashboard.php">Admin Panel</a></li>
                        <?php endif; ?>
                        <li><a href="user_dashboard.php?logout=1">Logout</a></li>
                    <?php else: ?>
                        <li><a href="user_login.php" class="active">Login</a></li>
                        <li><a href="user_register.php">Register</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <main>
        <div class="hero">
            <h2>User Login</h2>
            <p>Access your Lost & Found account</p>
        </div>

        <div class="form-container">
            <h2>🔑 Sign In</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn">Login</button>
            </form>
            
            <div style="text-align: center; margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid var(--border);">
                <p>Don't have an account? <a href="user_register.php" style="color: var(--primary); text-decoration: none; font-weight: 600;">Register here</a></p>
                <p><a href="index.php" style="color: var(--text-secondary); text-decoration: none;">Back to Home</a></p>
            </div>
        </div>
    </main>

    <script src="assets/script.js"></script>
    <script>
        document.getElementById('username').focus();
    </script>
</body>
</html>
