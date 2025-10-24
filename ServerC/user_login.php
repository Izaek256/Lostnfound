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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (!empty($username) && !empty($password)) {
        $result = makeAPICall('verify_user', [
            'username' => $username,
            'password' => $password
        ], 'POST');
        
        if (isset($result['success']) && $result['success']) {
            $_SESSION['user_id'] = $result['user_id'];
            $_SESSION['username'] = $result['username'];
            $_SESSION['user_email'] = $result['email'];
            $_SESSION['is_admin'] = $result['is_admin'];
            
            header('Location: index.php');
            exit();
        } else {
            $error = $result['error'] ?? 'Login failed';
        }
    } else {
        $error = 'Please fill in all fields';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Login - Lost & Found</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="login-form">
            <div class="logo">
                <img src="assets/logo.webp" alt="Lost & Found Logo">
                <h1>Lost & Found Portal</h1>
            </div>
            
            <h2>User Login</h2>
            
            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn btn-primary">Login</button>
            </form>
            
            <div class="form-links">
                <p>Don't have an account? <a href="user_register.php">Register here</a></p>
                <p><a href="index.php">Back to Home</a></p>
            </div>
        </div>
    </div>
</body>
</html>
