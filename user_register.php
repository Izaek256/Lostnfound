<?php
/**
 * User Registration Page
 * 
 * Allows new users to create an account
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

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    
    // Check if passwords match
    if ($password !== $confirmPassword) {
        $message = 'Passwords do not match';
        $messageType = 'error';
    } else {
        // Attempt to register user
        $error = registerUser($conn, $username, $email, $password);
        
        if (empty($error)) {
            // Registration successful, redirect to login page
            header('Location: user_login.php?registered=1');
            exit();
        } else {
            $message = $error;
            $messageType = 'error';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - University Lost and Found</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <div class="header-content">
            <div class="logo">
                <h1>🎓 University Lost & Found</h1>
            </div>
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
        <?php if ($message != ''): ?>
            <div class="alert <?php echo $messageType === 'success' ? 'alert-success' : 'alert-error'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="form-container" style="max-width: 500px; margin: 0 auto;">
            <h2>👤 Create Account</h2>
            <p style="text-align: center; color: var(--text-secondary); margin-bottom: 2rem;">
                Create an account to manage your lost and found items
            </p>
            
            <form method="POST">
                <div class="form-group">
                    <label for="username">Username *</label>
                    <input type="text" 
                           id="username" 
                           name="username" 
                           placeholder="Choose a username"
                           value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>"
                           required>
                </div>

                <div class="form-group">
                    <label for="email">Email Address *</label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           placeholder="your.email@university.edu"
                           value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>"
                           required>
                </div>

                <div class="form-group">
                    <label for="password">Password *</label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           placeholder="At least 6 characters"
                           required>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password *</label>
                    <input type="password" 
                           id="confirm_password" 
                           name="confirm_password" 
                           placeholder="Re-enter your password"
                           required>
                </div>

                <div style="text-align: center; margin-top: 2rem;">
                    <button type="submit" class="btn">Create Account</button>
                </div>
            </form>

            <div style="text-align: center; margin-top: 2rem; padding-top: 2rem; border-top: 1px solid var(--border);">
                <p style="color: var(--text-secondary);">
                    Already have an account? 
                    <a href="user_login.php" style="color: var(--primary); font-weight: 600;">Login here</a>
                </p>
            </div>
        </div>
    </main>

    <footer>
        <p>&copy; 2024 University Lost and Found Portal. Built to help our campus community stay connected.</p>
    </footer>
</body>
</html>
