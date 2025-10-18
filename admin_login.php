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
 */

// Include admin configuration and functions
require_once 'admin_config.php';

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
    
    // Try to authenticate with provided credentials
    // authenticateAdmin() function is defined in admin_config.php
    if (authenticateAdmin($username, $password)) {
        // If successful, redirect to admin dashboard
        header('Location: admin_dashboard.php');
        exit();
    } else {
        // If failed, show error message
        $error = 'Invalid username or password';
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
    <style>
        .login-container {
            max-width: 400px;
            margin: 10vh auto;
            padding: 0 20px;
        }
        
        .login-form {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            padding: 3rem 2rem;
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        
        .admin-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: white;
        }
        
        .login-title {
            color: white;
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 2rem;
        }
        
        .security-notice {
            background: rgba(255, 193, 7, 0.2);
            border: 1px solid rgba(255, 193, 7, 0.3);
            color: white;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            font-size: 0.9rem;
        }
        
        .back-link {
            margin-top: 2rem;
        }
        
        .back-link a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }
        
        .back-link a:hover {
            color: white;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-form">
            <div class="admin-icon">🔐</div>
            <h1 class="login-title">Admin Access</h1>
            
            <div class="security-notice">
                <strong>⚠️ Authorized Personnel Only</strong><br>
                This area is restricted to administrators only. All access attempts are logged.
            </div>
            
            <?php if ($error != ''): ?>
                <div class="alert alert-error">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
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
                
                <button type="submit" 
                        class="btn" 
                        style="width: 100%; margin-top: 1rem;">
                    🔓 Login to Admin Panel
                </button>
            </form>
            
            <div class="back-link">
                <a href="index.php">← Back to Portal</a>
            </div>
        </div>
        
        <!-- Default Credentials Info (Remove in production) -->
        <div style="background: rgba(0, 123, 255, 0.1); backdrop-filter: blur(20px); border: 1px solid rgba(0, 123, 255, 0.2); padding: 1.5rem; border-radius: 16px; margin-top: 2rem; text-align: center;">
            <h3 style="color: white; margin-bottom: 1rem;">🔑 Default Credentials</h3>
            <p style="color: rgba(255, 255, 255, 0.9); margin-bottom: 0.5rem;"><strong>Username:</strong> admin</p>
            <p style="color: rgba(255, 255, 255, 0.9); margin-bottom: 1rem;"><strong>Password:</strong> lostfound2024</p>
            <p style="color: rgba(255, 255, 255, 0.7); font-size: 0.8rem;">⚠️ Change these credentials in admin_config.php for production use</p>
        </div>
    </div>

    <script>
        // Focus on username field when page loads
        document.getElementById('username').focus();
    </script>
</body>
</html>
