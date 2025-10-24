<?php
/**
 * Server B - User Login Page
 * 
 * This page handles user login and communicates with Server A
 */

require_once 'config.php';

// Redirect if already logged in
if (isUserLoggedIn()) {
    header('Location: user_dashboard.php');
    exit();
}

$message = '';
$messageType = '';

// Handle registration success message
if (isset($_GET['registered']) && $_GET['registered'] == '1') {
    $message = 'Account created successfully! Please login with your credentials.';
    $messageType = 'success';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (!empty($username) && !empty($password)) {
        // Make API call to Server A for authentication
        $response = makeAPICall('verify_user', [
            'username' => $username,
            'password' => $password
        ], 'POST');
        
        if (isset($response['success']) && $response['success']) {
            // Set session variables
            $_SESSION['user_id'] = $response['user_id'];
            $_SESSION['username'] = $response['username'];
            $_SESSION['user_email'] = $response['email'];
            $_SESSION['is_admin'] = $response['is_admin'];
            
            header('Location: user_dashboard.php');
            exit();
        } else {
            $message = $response['error'] ?? 'Login failed. Please try again.';
            $messageType = 'error';
        }
    } else {
        $message = 'Please enter both username and password.';
        $messageType = 'error';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Login - Server B</title>
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
        <h3>🖥️ Server B - User Login</h3>
        <p>Authenticating with Server A (Main Backend)</p>
    </div>

    <div class="form-container">
        <h2>User Login</h2>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" placeholder="Enter your username" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
            </div>
            
            <button type="submit" class="btn">Login</button>
        </form>
        
        <p style="text-align: center; margin-top: 2rem;">
            Don't have an account? 
            <a href="user_register.php">Create one here</a>
        </p>
    </div>

    <script>
        document.getElementById('username').focus();
    </script>
</body>
</html>
