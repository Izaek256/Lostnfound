<?php
/**
 * Server A - Admin Login API Endpoint
 * 
 * This endpoint handles admin authentication for Server A
 */

require_once 'config.php';

// Log that the endpoint was accessed
error_log("Server A: admin_login.php accessed via " . $_SERVER['REQUEST_METHOD'] . " method");

// Return simple JSON response indicating the endpoint is working
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // For POST requests, indicate that authentication logic would be processed
    echo json_encode([
        'server' => 'Server A',
        'endpoint' => 'admin_login',
        'status' => 'Processing authentication request',
        'method' => 'POST',
        'message' => 'Authentication request received. Processing would happen here.',
        'received_data' => array_keys($_POST),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
} else {
    // For GET requests or others, just indicate the endpoint is accessible
    echo json_encode([
        'server' => 'Server A',
        'endpoint' => 'admin_login',
        'status' => 'Ready to receive authentication requests',
        'method' => $_SERVER['REQUEST_METHOD'],
        'message' => 'This is an API endpoint. Send POST requests with username and password.',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}

exit();
?>
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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (!empty($username) && !empty($password)) {
        $conn = getDBConnection();
        
        // Escape username for security
        $username = $conn->real_escape_string($username);
        
        // Get user from database
        $sql = "SELECT id, username, email, password, is_admin FROM users WHERE username = '$username'";
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Check if user has admin rights
                if ($user['is_admin'] == 1) {
                    // Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['is_admin'] = $user['is_admin'];
                    
                    header('Location: admin_dashboard.php');
                    exit();
                } else {
                    $error = 'Access denied. You do not have administrator privileges.';
                }
            } else {
                $error = 'Invalid username or password.';
            }
        } else {
            $error = 'Invalid username or password.';
        }
        
        $conn->close();
    } else {
        $error = 'Please enter both username and password.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Server A</title>
    <link rel="stylesheet" href="../ServerC/style.css">
</head>
<body>
    <!-- Header -->
    <header>
        <div class="header-content">
            <div class="logo">
                <h1>🛡️ Server A - Admin Login</h1>
            </div>
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

    <script>
        document.getElementById('username').focus();
    </script>
</body>
</html>
