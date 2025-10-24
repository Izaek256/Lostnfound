<?php
/**
 * Server C - User Registration Page
 */

require_once 'config.php';

// Redirect if already logged in
if (isUserLoggedIn()) {
    header('Location: index.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'Please fill in all fields';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long';
    } else {
        $result = makeAPICall('register_user', [
            'username' => $username,
            'email' => $email,
            'password' => $password
        ], 'POST');
        
        if (isset($result['success']) && $result['success']) {
            $success = 'Registration successful! You can now login.';
        } else {
            $error = $result['error'] ?? 'Registration failed';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Registration - Lost & Found</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="login-form">
            <div class="logo">
                <img src="assets/logo.webp" alt="Lost & Found Logo">
                <h1>Lost & Found Portal</h1>
            </div>
            
            <h2>User Registration</h2>
            
            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required minlength="6">
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password:</label>
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                </div>
                
                <button type="submit" class="btn btn-primary">Register</button>
            </form>
            
            <div class="form-links">
                <p>Already have an account? <a href="user_login.php">Login here</a></p>
                <p><a href="index.php">Back to Home</a></p>
            </div>
        </div>
    </div>
</body>
</html>
