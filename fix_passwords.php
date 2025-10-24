<?php
/**
 * Password Hash Generator and Database Fixer
 * Run this script once to fix the sample user passwords
 */

require_once 'ServerC/config.php';

echo "<h1>Password Hash Generator & Database Fixer</h1>";

// Generate correct password hashes
$admin_password = 'admin123';
$user_password = 'user123';

$admin_hash = password_hash($admin_password, PASSWORD_DEFAULT);
$user_hash = password_hash($user_password, PASSWORD_DEFAULT);

echo "<h2>Generated Password Hashes:</h2>";
echo "<p><strong>Admin password (admin123):</strong> $admin_hash</p>";
echo "<p><strong>User password (user123):</strong> $user_hash</p>";

// Update database with correct hashes
$conn = getDBConnection();
if ($conn) {
    echo "<h2>Updating Database...</h2>";
    
    // Update admin user
    $admin_hash_escaped = $conn->real_escape_string($admin_hash);
    $sql1 = "UPDATE users SET password = '$admin_hash_escaped' WHERE username = 'admin'";
    
    // Update test user
    $user_hash_escaped = $conn->real_escape_string($user_hash);
    $sql2 = "UPDATE users SET password = '$user_hash_escaped' WHERE username = 'testuser'";
    
    if ($conn->query($sql1) && $conn->query($sql2)) {
        echo "<p>✅ Successfully updated user passwords!</p>";
        echo "<p><strong>You can now login with:</strong></p>";
        echo "<ul>";
        echo "<li>Username: <strong>admin</strong>, Password: <strong>admin123</strong> (Admin user)</li>";
        echo "<li>Username: <strong>testuser</strong>, Password: <strong>user123</strong> (Regular user)</li>";
        echo "</ul>";
    } else {
        echo "<p>❌ Error updating passwords: " . $conn->error . "</p>";
    }
    
    $conn->close();
} else {
    echo "<p>❌ Could not connect to database</p>";
}

echo "<hr>";
echo "<p><strong>After running this script, you can delete it for security.</strong></p>";
echo "<p><a href='ServerC/user_login.php'>Go to Login Page</a> | <a href='ServerC/user_register.php'>Go to Register Page</a></p>";
?>
