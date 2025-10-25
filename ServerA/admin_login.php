<?php
/**
 * Server A - Admin Login Page
 * 
 * This page handles admin authentication for Server A
 */

require_once 'config.php';

// Redirect if already logged in as admin
if (isset($_SESSION['user_id']) && isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1) {
    header('Location: admin_dashboard.php');
    exit();
}

$error = '';

if ($_POST) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    if (!empty($username) && !empty($password)) {
        $conn = connectDB();
        
        $sql = "SELECT * FROM users WHERE username = '$username'";
        $result = mysqli_query($conn, $sql);
        
        if (mysqli_num_rows($result) > 0) {
            $user = mysqli_fetch_assoc($result);
            
            if (password_verify($password, $user['password'])) {
                if ($user['is_admin'] == 1) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['is_admin'] = $user['is_admin'];
                    
                    header('Location: admin_dashboard.php');
                    exit();
                } else {
                    $error = 'Access denied. You are not an admin.';
                }
            } else {
                $error = 'Wrong password.';
            }
        } else {
            $error = 'User not found.';
        }
        
        mysqli_close($conn);
    } else {
        $error = 'Please fill all fields.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Server A</title>
    <link rel="icon" href="../ServerC/assets/favicon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="../ServerC/style.css">
</head>
<body>
    <!-- Header -->
    <header>
        <div class="header-content">
            <div class="logo">
                <h1>🛡️ Server A - Admin Login</h1>
            </div>
            <button class="menu-toggle" aria-label="Toggle menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <nav>
                <ul>
                    <li><a href="../ServerC/index.php">Home</a></li>
                    <li><a href="../ServerC/report_lost.php">Report Lost</a></li>
                    <li><a href="../ServerC/report_found.php">Report Found</a></li>
                    <li><a href="../ServerC/items.php">View Items</a></li>
                    <li><a href="../ServerC/user_login.php">User Login</a></li>
                    <li><a href="admin_login.php" class="active">Admin Login</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <main>
        <div class="hero" style="background: linear-gradient(135deg, #dc3545 0%, #b02a37 100%);">
            <h2>Admin Authentication</h2>
            <p>Main Backend Server - Database Host</p>
        </div>

        <div class="form-container">
        <h2>🔑 Admin Access</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
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
        
        </div>
        
        <div class="form-container">
            <h2>ℹ️ Access Information</h2>
            <p>Only users with admin privileges can access this panel.</p>
            <p><strong>Server A</strong> hosts the main database and APIs.</p>
        </div>
    </main>

    <script src="../ServerC/script.js"></script>
    <script>
        document.getElementById('username').focus();
    </script>
</body>
</html>
