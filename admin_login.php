<?php
/**
 * Admin Login Page
 * 
 * This page provides a login form for administrators.
 * It handles:
 * - Displaying the login form
 * - Processing login attempts
 * - Redirecting logged-in admins to dashboard
 * - Showing error messages for failed logins
 * 
 * Note: Users with admin rights can login here with their regular credentials
 */

// Include admin configuration and database
require_once 'admin_config.php';
require_once 'db.php';
require_once 'user_config.php';

// If admin is already logged in, redirect to dashboard
// No need to show login form if already authenticated
if (isAdminLoggedIn()) {
    header('Location: admin_dashboard.php');
    exit();
}

// Variable to store error messages
$error = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get username and password from form
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    // Try to authenticate using the user login function
    $loginError = loginUser($conn, $username, $password);
    
    if (empty($loginError)) {
        // Check if the logged-in user has admin rights
        if (isAdminLoggedIn()) {
            // Redirect to admin dashboard
            header('Location: admin_dashboard.php');
            exit();
        } else {
            // User logged in but doesn't have admin rights
            session_destroy();
            $error = 'Access denied. You do not have administrator privileges.';
        }
    } else {
        // Login failed
        $error = $loginError;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - University Lost and Found</title>
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
                    <li><a href="user_login.php">Login</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <?php if ($error != ''): ?>
            <div class="alert alert-error">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="form-container" style="max-width: 500px; margin: 2rem auto;">
            <div style="text-align: center; margin-bottom: 2rem;">
                <div style="font-size: 3.5rem; margin-bottom: 1rem;">🔐</div>
                <h2>Admin Access</h2>
                <p style="color: var(--text-secondary); margin-top: 0.5rem;">
                    Restricted area for authorized administrators only
                </p>
            </div>
            
            <div style="background: #fff3cd; border: 1px solid #ffc107; color: #856404; padding: 1rem; border-radius: 8px; margin-bottom: 2rem; text-align: center;">
                <strong>⚠️ Authorized Personnel Only</strong><br>
                All access attempts are logged and monitored.
            </div>
            
            <form method="POST">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" 
                           id="username" 
                           name="username" 
                           placeholder="Enter admin username"
                           value="<?php echo isset($username) ? $username : ''; ?>"
                           required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           placeholder="Enter admin password"
                           required>
                </div>
                
                <div style="text-align: center; margin-top: 2rem;">
                    <button type="submit" class="btn" style="width: 100%;">
                        🔓 Login to Admin Panel
                    </button>
                </div>
            </form>
            
            <div style="text-align: center; margin-top: 2rem; padding-top: 2rem; border-top: 1px solid var(--border);">
                <p style="color: var(--text-secondary);">
                    <a href="index.php" style="color: var(--primary); font-weight: 600; text-decoration: none;">← Back to Portal</a>
                </p>
            </div>
        </div>
        
        <!-- Default Credentials Info (Remove in production) -->
        <div class="form-container" style="max-width: 500px; margin: 0 auto; background: #e7f3ff; border: 1px solid #2563eb;">
            <div style="text-align: center;">
                <h3 style="color: var(--text-primary); margin-bottom: 1rem;">🔑 Admin Access Information</h3>
                <p style="color: var(--text-primary); margin-bottom: 0.5rem;">Only users with admin privileges can access this panel.</p>
                <p style="color: var(--text-secondary); font-size: 0.9rem;">⚠️ Login with your regular user account if you have admin rights assigned.</p>
            </div>
        </div>
    </main>

    <footer>
        <p>&copy; 2024 University Lost and Found Portal. Built to help our campus community stay connected.</p>
    </footer>

    <script src="script.js"></script>
    <script>
        // Focus on username field when page loads
        document.getElementById('username').focus();
    </script>
</body>
</html>
