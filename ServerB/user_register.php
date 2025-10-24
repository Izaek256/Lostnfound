<?php
/**
 * Server B - User Registration Page
 * 
 * This page handles user registration and communicates with Server A
 */

require_once 'config.php';

// Redirect if already logged in
if (isUserLoggedIn()) {
    header('Location: user_dashboard.php');
    exit();
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if ($password !== $confirmPassword) {
        $message = 'Passwords do not match';
        $messageType = 'error';
    } else {
        // Make API call to Server A for registration
        $response = makeAPICall('register_user', [
            'username' => $username,
            'email' => $email,
            'password' => $password
        ], 'POST');
        
        if (isset($response['success']) && $response['success']) {
            header('Location: user_login.php?registered=1');
            exit();
        } else {
            $message = $response['error'] ?? 'Registration failed. Please try again.';
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
    <title>User Registration - Server B</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .server-info {
            background: #e8f5e8;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="server-info">
        <h3>🖥️ Server B - User Registration</h3>
        <p>Creating account on Server A (Main Backend)</p>
    </div>

    <div class="form-container">
        <h2>Create Account</h2>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="username">Username *</label>
                <input type="text" 
                       id="username" 
                       name="username" 
                       value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>"
                       placeholder="Choose a username" 
                       required>
            </div>
            
            <div class="form-group">
                <label for="email">Email Address *</label>
                <input type="email" 
                       id="email" 
                       name="email" 
                       value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>"
                       placeholder="Enter your email" 
                       required>
            </div>
            
            <div class="form-group">
                <label for="password">Password *</label>
                <input type="password" 
                       id="password" 
                       name="password" 
                       placeholder="Enter your password (min 6 characters)" 
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
            
            <button type="submit" class="btn">Create Account</button>
        </form>
        
        <p style="text-align: center; margin-top: 2rem;">
            Already have an account? 
            <a href="user_login.php">Login here</a>
        </p>
    </div>

    <script>
        document.getElementById('username').focus();
    </script>
</body>
</html>
